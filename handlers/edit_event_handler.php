<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../dashboard.php');
}

$user_id = $_SESSION['user_id'];
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    set_flash('event', 'Access denied', 'error');
    redirect('../dashboard.php');
}

$event_id = (int)($_POST['event_id'] ?? 0);
$event_name = trim($_POST['event_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$start_time = $_POST['start_time'] ?? '';
$location_id = (int)($_POST['location_id'] ?? 0);
$event_type_id = (int)($_POST['event_type_id'] ?? 0);

if ($event_id <= 0 || empty($event_name) || empty($description) || empty($start_time) || $location_id <= 0 || $event_type_id <= 0) {
    set_flash('event', 'All fields are required', 'error');
    redirect('../edit_event.php?id=' . $event_id);
}

try {
    // Verify event belongs to this club
    $stmt = $pdo->prepare("SELECT Club_ID FROM Event WHERE Event_ID = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event || $event['Club_ID'] != $club_info['Club_ID']) {
        set_flash('event', 'Event not found or access denied', 'error');
        redirect('../dashboard.php');
    }
    
    // Update event
    $stmt = $pdo->prepare("
        UPDATE Event 
        SET Title = ?, Description = ?, Start_time = ?, Location_ID = ?, Event_Type_ID = ?
        WHERE Event_ID = ?
    ");
    $stmt->execute([$event_name, $description, $start_time, $location_id, $event_type_id, $event_id]);
    
    // Handle poster upload if provided
    if (isset($_FILES['event_poster']) && $_FILES['event_poster']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/event_posters/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_tmp = $_FILES['event_poster']['tmp_name'];
        $file_name = $_FILES['event_poster']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = 'event_' . $event_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old poster
                $stmt = $pdo->prepare("SELECT Media_url FROM EventMedia WHERE Event_ID = ?");
                $stmt->execute([$event_id]);
                $old_media = $stmt->fetch();
                
                if ($old_media && file_exists('../' . $old_media['Media_url'])) {
                    unlink('../' . $old_media['Media_url']);
                }
                
                // Update or insert new poster
                $media_url = 'uploads/event_posters/' . $new_filename;
                $stmt = $pdo->prepare("SELECT Media_ID FROM EventMedia WHERE Event_ID = ?");
                $stmt->execute([$event_id]);
                
                if ($stmt->fetch()) {
                    // Update existing
                    $stmt = $pdo->prepare("
                        UPDATE EventMedia 
                        SET File_name = ?, Media_url = ?, Uploaded_at = CURRENT_TIMESTAMP
                        WHERE Event_ID = ?
                    ");
                    $stmt->execute([$new_filename, $media_url, $event_id]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare("
                        INSERT INTO EventMedia (File_name, Media_url, Media_type, Event_ID)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$new_filename, $media_url, 'image', $event_id]);
                }
            }
        }
    }
    
    // Send Notification
    send_notification($pdo, $club_info['Club_ID'], 'Event Updated', "$event_name has been updated.", "browse_event.php");

    set_flash('dashboard', 'Event updated successfully!', 'success');
    redirect('../dashboard.php');
} catch (PDOException $e) {
    set_flash('event', 'Failed to update event: ' . $e->getMessage(), 'error');
    redirect('../edit_event.php?id=' . $event_id);
}
?>