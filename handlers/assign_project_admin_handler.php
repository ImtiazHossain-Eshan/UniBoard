<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_project_admin($pdo, $_SESSION['user_id'])) {
    set_flash('admin', 'Access denied', 'error');
    redirect('../admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../admin_dashboard.php');
}

$user_id = (int)($_POST['user_id'] ?? 0);

// Validation
if ($user_id <= 0) {
    set_flash('admin', 'User selection is required', 'error');
    redirect('../admin_dashboard.php');
}

try {
    // Check if user already has a role
    $stmt = $pdo->prepare("SELECT St_ID FROM Role WHERE St_ID = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->fetch()) {
        // Update to Project_Admin
        $stmt = $pdo->prepare("UPDATE Role SET Role_name = 'Project_Admin' WHERE St_ID = ?");
        $stmt->execute([$user_id]);
        $message = 'User promoted to Project Admin successfully!';
    } else {
        // Insert new Project_Admin role
        $stmt = $pdo->prepare("INSERT INTO Role (St_ID, Role_name) VALUES (?, 'Project_Admin')");
        $stmt->execute([$user_id]);
        $message = 'Project Admin assigned successfully!';
    }

    set_flash('admin', $message, 'success');
    redirect('../admin_dashboard.php');
} catch (PDOException $e) {
    set_flash('admin', 'Failed to assign Project Admin: ' . $e->getMessage(), 'error');
    redirect('../admin_dashboard.php');
}
?>