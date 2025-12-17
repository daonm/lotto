<?php
// combinations/check-results.php
require_once '../config.php';

$user = verify_jwt();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $user['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$draw_number = $data['draw_number'] ?? null;

if (!$draw_number) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Draw number required']);
    exit;
}

// Load history to find winning numbers
$history_file = __DIR__ . '/../../data/lotto_history.json';
$history_data = json_decode(file_get_contents($history_file), true);

$winning_draw = null;
foreach ($history_data as $draw) {
    if ($draw['draw_no'] == $draw_number) {
        $winning_draw = $draw;
        break;
    }
}

if (!$winning_draw) {
    // If not found in history, maybe it's a future draw or not updated
    // For demo, let's just return empty results or mock it
    // But correctly we should say "Draw not found"
    echo json_encode(['success' => false, 'error' => 'Draw data not found']);
    exit;
}

$winning_numbers = array_slice($winning_draw['numbers'], 0, 6);
$bonus_number = $winning_draw['numbers'][6];

try {
    $stmt = $db->prepare("SELECT * FROM saved_combinations WHERE user_id = ? AND draw_number = ?");
    $stmt->execute([$user_id, $draw_number]);
    $saved_combos = $stmt->fetchAll();

    $results = [];
    foreach ($saved_combos as $combo) {
        $my_numbers = json_decode($combo['numbers']);
        $matched = count(array_intersect($my_numbers, $winning_numbers));
        $bonus_matched = in_array($bonus_number, $my_numbers);

        $prize = null;
        if ($matched == 6) $prize = '1등';
        elseif ($matched == 5 && $bonus_matched) $prize = '2등';
        elseif ($matched == 5) $prize = '3등';
        elseif ($matched == 4) $prize = '4등';
        elseif ($matched == 3) $prize = '5등';

        $results[] = [
            'id' => $combo['id'],
            'numbers' => $my_numbers,
            'matched_count' => $matched,
            'prize' => $prize
        ];
    }

    echo json_encode([
        'success' => true,
        'winning_numbers' => $winning_numbers,
        'bonus_number' => $bonus_number,
        'results' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
