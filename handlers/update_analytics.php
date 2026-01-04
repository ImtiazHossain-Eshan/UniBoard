<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'] ?? 0;
$type = $data['type'] ?? '';

if ($event_id > 0 && in_array($type, ['view'])) {
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    if (update_analytics($pdo, $event_id, $type, 'add', $user_id)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>
