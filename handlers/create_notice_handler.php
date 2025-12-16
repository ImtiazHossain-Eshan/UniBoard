<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../dashboard.php');
}

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    set_flash('notice', 'Access denied', 'error');
    redirect('../create_notice.php');
}

$club_id = $club_info['Club_ID'];

// Get form data
$title = sanitize_input($_POST['title'] ?? '');
$message = sanitize_input($_POST['message'] ?? '');
$priority = sanitize_input($_POST['priority'] ?? 'normal');

// Validation
$errors = [];
if (empty($title)) $errors[] = 'Title is required';
if (empty($message)) $errors[] = 'Message is required';

if (!empty($errors)) {
    set_flash('notice', implode('<br>', $errors), 'error');
    redirect('../create_notice.php');
}

try {
    // Insert notification
    $stmt = $pdo->prepare("
        INSERT INTO Notifications (Title, Message, Created_at, Is_read, Club_ID)
        VALUES (?, ?, NOW(), 0, ?)
    ");
    $stmt->execute([$title, $message, $club_id]);

    $notification_id = $pdo->lastInsertId();

    // Send notification to all club members
    $stmt = $pdo->prepare("
        SELECT Student_ID FROM Joins_club WHERE Club_ID = ?
    ");
    $stmt->execute([$club_id]);
    $members = $stmt->fetchAll();

    $insert_stmt = $pdo->prepare("
        INSERT INTO Gets_notification (Student_ID, Notification_ID) VALUES (?, ?)
    ");

    foreach ($members as $member) {
        $insert_stmt->execute([$member['Student_ID'], $notification_id]);
    }

    set_flash('dashboard', 'Notice published successfully to all members!', 'success');
    redirect('../dashboard.php');
} catch (PDOException $e) {
    set_flash('notice', 'Failed to create notice. Please try again.', 'error');
    redirect('../create_notice.php');
}
?>