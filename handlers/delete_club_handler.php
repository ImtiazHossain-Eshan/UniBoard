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

$club_id = (int)($_POST['club_id'] ?? 0);

if ($club_id <= 0) {
    set_flash('clubs', 'Invalid club ID', 'error');
    redirect('../manage_clubs.php');
}

try {
    // Delete related records first (foreign key constraints)
    $pdo->prepare("DELETE FROM Gets_notification WHERE Notification_ID IN (SELECT Notification_ID FROM Notifications WHERE Club_ID = ?)")->execute([$club_id]);
    $pdo->prepare("DELETE FROM Notifications WHERE Club_ID = ?")->execute([$club_id]);
    $pdo->prepare("DELETE FROM Participate_in_events WHERE Event_ID IN (SELECT Event_ID FROM Event WHERE Club_ID = ?)")->execute([$club_id]);
    $pdo->prepare("DELETE FROM RSVP WHERE Event_ID IN (SELECT Event_ID FROM Event WHERE Club_ID = ?)")->execute([$club_id]);
    $pdo->prepare("DELETE FROM EventMedia WHERE Event_ID IN (SELECT Event_ID FROM Event WHERE Club_ID = ?)")->execute([$club_id]);
    $pdo->prepare("DELETE FROM Event WHERE Club_ID = ?")->execute([$club_id]);
    $pdo->prepare("DELETE FROM Joins_club WHERE Club_ID = ?")->execute([$club_id]);
    $pdo->prepare("DELETE FROM Follows_club WHERE Club_ID = ?")->execute([$club_id]);
    
    // Finally delete the club
    $stmt = $pdo->prepare("DELETE FROM Club WHERE Club_ID = ?");
    $stmt->execute([$club_id]);

    set_flash('clubs', 'Club deleted successfully!', 'success');
    redirect('../manage_clubs.php');
} catch (PDOException $e) {
    set_flash('clubs', 'Failed to delete club: ' . $e->getMessage(), 'error');
    redirect('../manage_clubs.php');
}
?>
