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
$content = sanitize_input($_POST['content'] ?? '');

// Validation
$errors = [];
if (empty($title)) $errors[] = 'Title is required';
if (empty($content)) $errors[] = 'Content is required';

if (!empty($errors)) {
    set_flash('notice', implode('<br>', $errors), 'error');
    redirect('../create_notice.php');
}

try {
    // Insert notice
    $stmt = $pdo->prepare("
        INSERT INTO Notice (Title, Content, Club_ID, Created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$title, $content, $club_id]);

    set_flash('dashboard', 'Notice posted successfully!', 'success');
    redirect('../dashboard.php');
} catch (PDOException $e) {
    set_flash('notice', 'Failed to create notice: ' . $e->getMessage(), 'error');
    redirect('../create_notice.php');
}
?>