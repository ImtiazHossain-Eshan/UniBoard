<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_project_admin($pdo, $_SESSION['user_id'])) {
    set_flash('clubs', 'Access denied', 'error');
    redirect('../manage_clubs.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../manage_clubs.php');
}

$user_id = (int)($_POST['user_id'] ?? 0);
$club_id = (int)($_POST['club_id'] ?? 0);
$role_name = sanitize_input($_POST['role_name'] ?? '');

// Validation
if ($user_id <= 0 || $club_id <= 0 || empty($role_name)) {
    set_flash('clubs', 'All fields are required', 'error');
    redirect('../manage_clubs.php');
}

try {
    // Check if user already has this role for this club
    $stmt = $pdo->prepare("SELECT St_ID FROM Role WHERE St_ID = ? AND Club_ID = ?");
    $stmt->execute([$user_id, $club_id]);
    
    if ($stmt->fetch()) {
        // Update existing role
        $stmt = $pdo->prepare("UPDATE Role SET Role_name = ? WHERE St_ID = ? AND Club_ID = ?");
        $stmt->execute([$role_name, $user_id, $club_id]);
    } else {
        // Insert new role with Club_ID
        $stmt = $pdo->prepare("INSERT INTO Role (St_ID, Role_name, Club_ID) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $role_name, $club_id]);
    }

    // Get names for success message
    $stmt = $pdo->prepare("SELECT Name FROM User WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT Name FROM Club WHERE Club_ID = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();
    
    $message = 'Assigned ' . $user['Name'] . ' as ' . $role_name . ' for ' . $club['Name'];
    set_flash('clubs', $message, 'success');
    redirect('../manage_clubs.php');
} catch (PDOException $e) {
    set_flash('clubs', 'Database error: ' . $e->getMessage(), 'error');
    redirect('../manage_clubs.php');
}
?>