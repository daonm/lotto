<?php
/**
 * Admin API: User Details
 * Returns detailed information about a specific user including combinations and stats
 */
require_once '../config.php';

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

try {
    // Get user info
    $stmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Get user's combinations
    $stmt = $db->prepare("SELECT id, draw_number, numbers, created_at FROM saved_combinations WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $combinations = [];
    foreach ($rows as $row) {
        $combinations[] = [
            'id' => $row['id'],
            'draw_number' => $row['draw_number'],
            'numbers' => json_decode($row['numbers']),
            'created_at' => $row['created_at']
        ];
    }
    
    // Calculate stats
    $total_combinations = count($combinations);
    
    // Load history for prize calculation
    $history_file = __DIR__ . '/../../data/lotto_history.json';
    $lottery_history = [];
    if (file_exists($history_file)) {
        $lottery_history = json_decode(file_get_contents($history_file), true);
    }
    
    // Calculate wins
    $wins = ['1등' => 0, '2등' => 0, '3등' => 0, '4등' => 0, '5등' => 0];
    
    foreach ($combinations as $combo) {
        $draw_number = $combo['draw_number'];
        $my_numbers = $combo['numbers'];
        
        // Find winning numbers for this draw
        $winning_draw = null;
        foreach ($lottery_history as $draw) {
            if ($draw['draw_no'] == $draw_number) {
                $winning_draw = $draw;
                break;
            }
        }
        
        if ($winning_draw) {
            $winning_numbers = array_slice($winning_draw['numbers'], 0, 6);
            $bonus_number = $winning_draw['numbers'][6];
            
            $matched = count(array_intersect($my_numbers, $winning_numbers));
            $bonus_matched = in_array($bonus_number, $my_numbers);
            
            if ($matched == 6) $wins['1등']++;
            elseif ($matched == 5 && $bonus_matched) $wins['2등']++;
            elseif ($matched == 5) $wins['3등']++;
            elseif ($matched == 4) $wins['4등']++;
            elseif ($matched == 3) $wins['5등']++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'combinations' => $combinations,
        'stats' => [
            'total_combinations' => $total_combinations,
            'wins' => $wins
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
