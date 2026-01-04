<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'] ?? 0;
$status = $data['status'] ?? ''; // 'Interested' or 'Going'
$user_id = $_SESSION['user_id'];

if ($event_id <= 0 || !in_array($status, ['Interested', 'Going'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid input']));
}

try {
    // Check if RSVP exists
    $stmt = $pdo->prepare("
        SELECT r.Rsvp_ID, r.Status 
        FROM Participate_in_events pie
        JOIN RSVP r ON pie.Rsvp_ID = r.Rsvp_ID
        WHERE pie.Student_ID = ? AND pie.Event_ID = ?
    ");
    $stmt->execute([$user_id, $event_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing RSVP
        $rsvp_id = $existing['Rsvp_ID'];
        if ($existing['Status'] === $status) {
            // Toggle off (Remove RSVP)
            // 1. Delete from Participate
            $pdo->prepare("DELETE FROM Participate_in_events WHERE Student_ID = ? AND Event_ID = ?")->execute([$user_id, $event_id]);
            // 2. Delete from RSVP
            $pdo->prepare("DELETE FROM RSVP WHERE Rsvp_ID = ?")->execute([$rsvp_id]);
            
            // Update Analytics: Decrease count for the old status
            update_analytics($pdo, $event_id, strtolower($existing['Status']), 'remove');

            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            // Change status
            $pdo->prepare("UPDATE RSVP SET Status = ? WHERE Rsvp_ID = ?")->execute([$status, $rsvp_id]);
            
            // Analytics: Decrease old status, Increase new status
            update_analytics($pdo, $event_id, strtolower($existing['Status']), 'remove');
            update_analytics($pdo, $event_id, strtolower($status), 'add');
            
            echo json_encode(['success' => true, 'action' => 'updated']);
        }
    } else {
        // Create new RSVP
        // 1. Insert RSVP
        $pdo->prepare("INSERT INTO RSVP (Status) VALUES (?)")->execute([$status]);
        $rsvp_id = $pdo->lastInsertId();
        
        // 2. Link User, RSVP, Event
        $pdo->prepare("INSERT INTO Participate_in_events (Student_ID, Rsvp_ID, Event_ID) VALUES (?, ?, ?)")
            ->execute([$user_id, $rsvp_id, $event_id]);
            
        // Update Analytics
        if ($status === 'Interested') update_analytics($pdo, $event_id, 'interested');
        if ($status === 'Going') update_analytics($pdo, $event_id, 'going');
        
        echo json_encode(['success' => true, 'action' => 'created']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
