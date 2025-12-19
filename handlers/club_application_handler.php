<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../apply_for_club.php');
}

$user_id = $_SESSION['user_id'];
$club_id = (int)($_POST['club_id'] ?? 0);
$role_name = sanitize_input($_POST['role_name'] ?? '');
$message = sanitize_input($_POST['message'] ?? '');

// Validation
$errors = [];

if ($club_id <= 0) $errors[] = 'Please select a club';
if (empty($role_name) || !in_array($role_name, ['Club_President', 'Club_Admin'])) {
    $errors[] = 'Please select a valid role';
}
if (empty($message)) $errors[] = 'Please explain why you want this role';

if (!empty($errors)) {
    set_flash('application', implode('<br>', $errors), 'error');
    redirect('../apply_for_club.php');
}

try {
    // Check if user already has a role for this club
    $stmt = $pdo->prepare("SELECT St_ID FROM Role WHERE St_ID = ? AND Club_ID = ?");
    $stmt->execute([$user_id, $club_id]);
    if ($stmt->fetch()) {
        set_flash('application', 'You already have an admin role for this club', 'error');
        redirect('../apply_for_club.php');
    }

    // Check if user has a pending application for this club
    $stmt = $pdo->prepare("SELECT Request_ID FROM Role_Request WHERE Student_ID = ? AND Club_ID = ? AND Status = 'Pending'");
    $stmt->execute([$user_id, $club_id]);
    if ($stmt->fetch()) {
        set_flash('application', 'You already have a pending application for this club', 'error');
        redirect('../apply_for_club.php');
    }

    // Get club name for success message
    $stmt = $pdo->prepare("SELECT Name FROM Club WHERE Club_ID = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();

    // Insert application
    $stmt = $pdo->prepare("
        INSERT INTO Role_Request (Student_ID, Club_ID, Requested_Role, Request_Message, Status, Created_at) 
        VALUES (?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([$user_id, $club_id, $role_name, $message]);

    set_flash('application', 'Application submitted successfully for ' . htmlspecialchars($club['Name']) . '! Awaiting Project Admin approval.', 'success');
    redirect('../apply_for_club.php');
} catch (PDOException $e) {
    set_flash('application', 'Failed to submit application. Please try again.', 'error');
    redirect('../apply_for_club.php');
}
?>