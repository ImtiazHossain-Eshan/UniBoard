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
        INSERT INTO Event (Name, Description, Start_time, Location_ID, Event_type_ID, Club_ID, Created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$event_name, $description, $start_time, $location_id, $event_type_id, $club_id]);

    set_flash('dashboard', 'Event created successfully!', 'success');
    redirect('../dashboard.php');
} catch (PDOException $e) {
    set_flash('event', 'Failed to create event. Please try again.', 'error');
    redirect('../create_event.php');
}
?>