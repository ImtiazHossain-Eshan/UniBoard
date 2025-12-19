<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../dashboard.php');
}

$user_id = $_SESSION['user_id'];
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    set_flash('dashboard', 'Access denied', 'error');
    redirect('../dashboard.php');
}

$event_id = (int)($_POST['event_id'] ?? 0);

if ($event_id <= 0) {
    set_flash('dashboard', 'Invalid event', 'error');
    redirect('../dashboard.php');
}

try {
    // Verify event belongs to this club
    $stmt = $pdo->prepare("SELECT Club_ID FROM Event WHERE Event_ID = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event || $event['Club_ID'] != $club_info['Club_ID']) {
        set_flash('dashboard', 'Event not found or access denied', 'error');
        redirect('../dashboard.php');
    }
    
    // Get media files to delete
    $stmt = $pdo->prepare("SELECT Media_url FROM EventMedia WHERE Event_ID = ?");
    $stmt->execute([$event_id]);
    $media_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete media records
    $stmt = $pdo->prepare("DELETE FROM EventMedia WHERE Event_ID = ?");
    $stmt->execute([$event_id]);
    
   // Delete media files from filesystem
    foreach ($media_files as $file_path) {
        $full_path = '../' . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    // Delete event
    $stmt = $pdo->prepare("DELETE FROM Event WHERE Event_ID = ?");
    $stmt->execute([$event_id]);
    
    set_flash('dashboard', 'Event deleted successfully!', 'success');
    redirect('../dashboard.php');
} catch (PDOException $e) {
    set_flash('dashboard', 'Failed to delete event: ' . $e->getMessage(), 'error');
    redirect('../dashboard.php');
}
?>