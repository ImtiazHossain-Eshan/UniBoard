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
$verified = (int)($_POST['verified'] ?? 0);

// Validation
if ($club_id <= 0) {
    set_flash('clubs', 'Invalid club ID', 'error');
    redirect('../manage_clubs.php');
}

try {
    // Get club name for message
    $stmt = $pdo->prepare("SELECT Name FROM Club WHERE Club_ID = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();
    
    if (!$club) {
        set_flash('clubs', 'Club not found', 'error');
        redirect('../manage_clubs.php');
    }
    
    // Update verification status
    $stmt = $pdo->prepare("UPDATE Club SET Verified = ?, Verification_requested_at = NOW() WHERE Club_ID = ?");
    $stmt->execute([$verified, $club_id]);
    
    $action = $verified ? 'verified' : 'unverified';
    $message = $club['Name'] . ' has been ' . $action;
    
    set_flash('clubs', $message, 'success');
    redirect('../manage_clubs.php');
} catch (PDOException $e) {
    set_flash('clubs', 'Failed to update verification: ' . $e->getMessage(), 'error');
    redirect('../manage_clubs.php');
}
?>