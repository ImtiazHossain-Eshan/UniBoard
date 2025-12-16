<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../settings.php');
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';

// Validation
$errors = [];

if (empty($current_password)) $errors[] = 'Current password is required';
if (empty($new_password)) $errors[] = 'New password is required';
if (!validate_password($new_password)) $errors[] = 'New password must be at least 6 characters';
if ($new_password !== $confirm_new_password) $errors[] = 'New passwords do not match';

if (!empty($errors)) {
    set_flash('settings', implode('<br>', $errors), 'error');
    redirect('../settings.php');
}

try {
    // Get current password from database
    $stmt = $pdo->prepare("SELECT Password FROM User WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('settings', 'User not found', 'error');
        redirect('../settings.php');
    }

    // Verify current password
    if (!password_verify($current_password, $user['Password'])) {
        set_flash('settings', 'Current password is incorrect', 'error');
        redirect('../settings.php');
    }

    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

    // Update password
    $stmt = $pdo->prepare("UPDATE User SET Password = ? WHERE Student_ID = ?");
    $stmt->execute([$new_password_hash, $user_id]);

    set_flash('settings', 'Password changed successfully!', 'success');
    redirect('../settings.php');
} catch (PDOException $e) {
    set_flash('settings', 'Failed to change password. Please try again.', 'error');
    redirect('../settings.php');
}
?>