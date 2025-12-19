<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../dashboard.php');
}

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    set_flash('event', 'Access denied', 'error');
    redirect('../create_event.php');
}

$club_id = $club_info['Club_ID'];

// Get form data
$event_name = sanitize_input($_POST['event_name'] ?? '');
$description = sanitize_input($_POST['description'] ?? '');
$start_time = $_POST['start_time'] ?? '';
$location_id = (int)($_POST['location_id'] ?? 0);
$event_type_id = (int)($_POST['event_type_id'] ?? 0);

// Validation
$errors = [];
if (empty($event_name)) $errors[] = 'Event name is required';
if (empty($description)) $errors[] = 'Description is required';
if (empty($start_time)) $errors[] = 'Start time is required';
if ($location_id <= 0) $errors[] = 'Location is required';
if ($event_type_id <= 0) $errors[] = 'Event type is required';

if (!empty($errors)) {
    set_flash('event', implode('<br>', $errors), 'error');
    redirect('../create_event.php');
}

try {
    // Insert event
    $stmt = $pdo->prepare("
        INSERT INTO Event (Title, Description, Start_time, Location_ID, Event_Type_ID, Club_ID)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$event_name, $description, $start_time, $location_id, $event_type_id, $club_id]);
    
    $event_id = $pdo->lastInsertId();
    
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
                // Insert into EventMedia table
                $media_url = 'uploads/event_posters/' . $new_filename;
                $stmt = $pdo->prepare("
                    INSERT INTO EventMedia (File_name, Media_url, Media_type, Event_ID)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$new_filename, $media_url, 'image', $event_id]);
            }
        }
    }

    set_flash('dashboard', 'Event created successfully!', 'success');
    redirect('../dashboard.php');
} catch (PDOException $e) {
    set_flash('event', 'Failed to create event: ' . $e->getMessage(), 'error');
    redirect('../create_event.php');
}
?>