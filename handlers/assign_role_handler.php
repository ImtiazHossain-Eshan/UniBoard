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
$role_name = sanitize_input($_POST['role_name'] ?? '');

// Validation
if ($user_id <= 0 || empty($role_name)) {
    set_flash('admin', 'All fields are required', 'error');
    redirect('../admin_dashboard.php');
}

try {
    // Check if role already exists for this user
    $stmt = $pdo->prepare("SELECT St_ID FROM Role WHERE St_ID = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->fetch()) {
        // Update existing role
        $stmt = $pdo->prepare("UPDATE Role SET Role_name = ? WHERE St_ID = ?");
        $stmt->execute([$role_name, $user_id]);
        $message = 'Role updated successfully!';
    } else {
        // Insert new role
        $stmt = $pdo->prepare("INSERT INTO Role (St_ID, Role_name) VALUES (?, ?)");
        $stmt->execute([$user_id, $role_name]);
        $message = 'Role assigned successfully!';
    }

    set_flash('admin', $message, 'success');
    redirect('../admin_dashboard.php');
} catch (PDOException $e) {
    set_flash('admin', 'Failed to assign role: ' . $e->getMessage(), 'error');
    redirect('../admin_dashboard.php');
}
?>