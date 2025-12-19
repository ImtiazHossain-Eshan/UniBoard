<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../explore_clubs.php');
}

$user_id = $_SESSION['user_id'];
$club_id = (int)($_POST['club_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($club_id <= 0 || !in_array($action, ['follow', 'unfollow'])) {
    set_flash('clubs', 'Invalid request', 'error');
    redirect('../explore_clubs.php');
}

try {
    // Get club name
    $stmt = $pdo->prepare("SELECT Name FROM Club WHERE Club_ID = ? AND Verified = TRUE");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();
    
    if (!$club) {
        set_flash('clubs', 'Club not found', 'error');
        redirect('../explore_clubs.php');
    }
    
    if ($action === 'follow') {
        // Follow club
        $stmt = $pdo->prepare("INSERT IGNORE INTO Follows_club (Student_ID, Club_ID) VALUES (?, ?)");
        $stmt->execute([$user_id, $club_id]);
        set_flash('clubs', 'You are now following ' . htmlspecialchars($club['Name']), 'success');
    } else {
        // Unfollow club
        $stmt = $pdo->prepare("DELETE FROM Follows_club WHERE Student_ID = ? AND Club_ID = ?");
        $stmt->execute([$user_id, $club_id]);
        set_flash('clubs', 'You unfollowed ' . htmlspecialchars($club['Name']), 'success');
    }
    
    redirect('../explore_clubs.php');
} catch (PDOException $e) {
    set_flash('clubs', 'An error occurred. Please try again.', 'error');
    redirect('../explore_clubs.php');
}
?>