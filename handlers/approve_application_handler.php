<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_project_admin($pdo, $_SESSION['user_id'])) {
    set_flash('applications', 'Access denied', 'error');
    redirect('../review_applications.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../review_applications.php');
}

$request_id = (int)($_POST['request_id'] ?? 0);
$admin_id = $_SESSION['user_id'];

if ($request_id <= 0) {
    set_flash('applications', 'Invalid request', 'error');
    redirect('../review_applications.php');
}

try {
    // Getting request details
    $stmt = $pdo->prepare("
        SELECT rr.*, u.Name as Applicant_Name, c.Name as Club_Name
        FROM Role_Request rr
        JOIN User u ON rr.Student_ID = u.Student_ID
        JOIN Club c ON rr.Club_ID = c.Club_ID
        WHERE rr.Request_ID = ? AND rr.Status = 'Pending'
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        set_flash('applications', 'Request not found or already processed', 'error');
        redirect('../review_applications.php');
    }

    // Updating request status to Approved
    $stmt = $pdo->prepare("
        UPDATE Role_Request 
        SET Status = 'Approved', Reviewed_at = NOW(), Reviewed_by = ?
        WHERE Request_ID = ?
    ");
    $stmt->execute([$admin_id, $request_id]);

    // Assigning the role
    $stmt = $pdo->prepare("
        INSERT INTO Role (St_ID, Role_name, Club_ID, Created_at) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE Role_name = VALUES(Role_name)
    ");
    $stmt->execute([
        $request['Student_ID'],
        $request['Requested_Role'],
        $request['Club_ID']
    ]);

    $message = 'Approved: ' . htmlspecialchars($request['Applicant_Name']) . ' as ' . 
               str_replace('_', ' ', $request['Requested_Role']) . ' for ' . 
               htmlspecialchars($request['Club_Name']);
    
    set_flash('applications', $message, 'success');
    redirect('../review_applications.php');
} catch (PDOException $e) {
    set_flash('applications', 'Failed to approve application: ' . $e->getMessage(), 'error');
    redirect('../review_applications.php');
}
?>