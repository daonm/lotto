<?php
/**
 * Admin API: All Combinations
 * Returns all combinations (member + guest) with optional filtering and prize checking
 */
require_once '../config.php';

// Get optional draw_number filter
$draw_number = isset($_GET['draw_number']) ? intval($_GET['draw_number']) : null;

try {
    // Build query
    if ($draw_number) {
        $stmt = $db->prepare("SELECT c.*, u.username, u.email 
                              FROM saved_combinations c 
                              LEFT JOIN users u ON c.user_id = u.id 
                              WHERE c.draw_number = ? 
                              ORDER BY c.created_at DESC");
        $stmt->execute([$draw_number]);
    } else {
        $stmt = $db->query("SELECT c.*, u.username, u.email 
                            FROM saved_combinations c 
                            LEFT JOIN users u ON c.user_id = u.id 
                            ORDER BY c.created_at DESC");
    }
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process combinations
    $combinations = [];
    $member_count = 0;
    $guest_count = 0;
    
    foreach ($rows as $row) {
        $is_guest = $row['user_id'] === null;
        if ($is_guest) {
            $guest_count++;
        } else {
            $member_count++;
        }
        
        $combinations[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'username' => $is_guest ? '게스트' : $row['username'],
            'email' => $is_guest ? '' : $row['email'],
            'draw_number' => $row['draw_number'],
            'numbers' => json_decode($row['numbers']),
            'created_at' => $row['created_at'],
            'is_guest' => $is_guest
        ];
    }
    
    // If specific draw number is provided, calculate prizes
    $results = null;
    if ($draw_number) {
        $history_file = __DIR__ . '/../../data/lotto_history.json';
        if (file_exists($history_file)) {
            $lottery_history = json_decode(file_get_contents($history_file), true);
            
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
                
                $results = [
                    'winning_numbers' => $winning_numbers,
                    'bonus_number' => $bonus_number,
                    'prizes' => []
                ];
                
                // Calculate prizes for each combination
                foreach ($combinations as &$combo) {
                    $my_numbers = $combo['numbers'];
                    $matched = count(array_intersect($my_numbers, $winning_numbers));
                    $bonus_matched = in_array($bonus_number, $my_numbers);
                    
                    $prize = null;
                    if ($matched == 6) $prize = '1등';
                    elseif ($matched == 5 && $bonus_matched) $prize = '2등';
                    elseif ($matched == 5) $prize = '3등';
                    elseif ($matched == 4) $prize = '4등';
                    elseif ($matched == 3) $prize = '5등';
                    
                    $combo['matched_count'] = $matched;
                    $combo['prize'] = $prize;
                    
                    if ($prize) {
                        if (!isset($results['prizes'][$prize])) {
                            $results['prizes'][$prize] = 0;
                        }
                        $results['prizes'][$prize]++;
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'total_combinations' => count($combinations),
        'member_combinations' => $member_count,
        'guest_combinations' => $guest_count,
        'combinations' => $combinations,
        'results' => $results
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
