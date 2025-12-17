<?php
// combinations/save.php
require_once '../config.php';

$user = verify_jwt();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['numbers']) || !isset($data['draw_number'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$numbers = $data['numbers'];
$draw_number = $data['draw_number'];
$user_id = $user['user_id'];

// Sort numbers for consistent storage
sort($numbers);
$numbers_json = json_encode($numbers);

try {
    // Check for duplicate
    $stmt = $db->prepare("SELECT id FROM saved_combinations WHERE user_id = ? AND numbers = ?");
    $stmt->execute([$user_id, $numbers_json]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already saved']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO saved_combinations (user_id, draw_number, numbers) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $draw_number, $numbers_json]);

    echo json_encode(['success' => true, 'message' => 'Saved successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
