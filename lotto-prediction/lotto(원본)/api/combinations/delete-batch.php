<?php
// combinations/delete-batch.php
require_once '../config.php';

$user = verify_jwt();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $user['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

try {
    if (isset($data['all']) && $data['all'] === true) {
        // Delete All
        if (isset($data['draw_number'])) {
            $stmt = $db->prepare("DELETE FROM saved_combinations WHERE user_id = ? AND draw_number = ?");
            $stmt->execute([$user_id, $data['draw_number']]);
        } else {
            $stmt = $db->prepare("DELETE FROM saved_combinations WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        $count = $stmt->rowCount();
    } elseif (isset($data['ids']) && is_array($data['ids'])) {
        // Delete Selected
        if (empty($data['ids'])) {
            echo json_encode(['success' => true, 'deleted_count' => 0]);
            exit;
        }
        
        $placeholders = str_repeat('?,', count($data['ids']) - 1) . '?';
        $sql = "DELETE FROM saved_combinations WHERE user_id = ? AND id IN ($placeholders)";
        
        $params = array_merge([$user_id], $data['ids']);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }

    echo json_encode(['success' => true, 'deleted_count' => $count]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
