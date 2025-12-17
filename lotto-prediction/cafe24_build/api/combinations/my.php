<?php
// combinations/my.php
require_once '../config.php';

$user = verify_jwt();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $user['user_id'];

try {
    $stmt = $db->prepare("SELECT * FROM saved_combinations WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    $combinations = [];
    foreach ($rows as $row) {
        $combinations[] = [
            'id' => $row['id'],
            'draw_number' => $row['draw_number'],
            'numbers' => json_decode($row['numbers']),
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode(['success' => true, 'combinations' => $combinations]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
