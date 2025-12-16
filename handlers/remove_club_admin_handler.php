<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is project admin
if (!is_logged_in() || !is_project_admin($pdo, $_SESSION['user_id'])) {
    set_flash('clubs', 'Access denied. Project Admin privileges required.', 'error');
    redirect('../manage_clubs.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../manage_clubs.php');
}

$club_id = (int)($_POST['club_id'] ?? 0);

// Validation
if ($club_id <= 0) {
    set_flash('clubs', 'Invalid club ID', 'error');
    redirect('../manage_clubs.php');
}

try {
    // Get club name for confirmation message
    $stmt = $pdo->prepare("SELECT Name FROM Club WHERE Club_ID = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();
    
    if (!$club) {
        set_flash('clubs', 'Club not found', 'error');
        redirect('../manage_clubs.php');
    }
    
    // Get admin name before deleting
    $stmt = $pdo->prepare("
        SELECT u.Name 
        FROM Role r
        JOIN User u ON r.St_ID = u.Student_ID
        WHERE r.Club_ID = ? 
        AND (r.Role_name = 'Club_President' OR r.Role_name = 'Club_Admin')
        LIMIT 1
    ");
    $stmt->execute([$club_id]);
    $admin = $stmt->fetch();
    
    // Delete the role assignment
    $stmt = $pdo->prepare("
        DELETE FROM Role 
        WHERE Club_ID = ? 
        AND (Role_name = 'Club_President' OR Role_name = 'Club_Admin')
    ");
    $stmt->execute([$club_id]);
    
    if ($admin) {
        $message = 'Removed ' . $admin['Name'] . ' as admin from ' . $club['Name'];
    } else {
        $message = 'Admin role removed from ' . $club['Name'];
    }
    
    set_flash('clubs', $message, 'success');
    redirect('../manage_clubs.php');
} catch (PDOException $e) {
    set_flash('clubs', 'Failed to remove admin: ' . $e->getMessage(), 'error');
    redirect('../manage_clubs.php');
}
?>