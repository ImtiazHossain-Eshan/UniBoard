<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    display_flash('settings', 'Unauthorized access', 'error');
    redirect('../settings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $interests = $_POST['interests'] ?? []; // Array of Event_Type_IDs

    try {
        $pdo->beginTransaction();

        // 1. Clear existing interests
        $stmt = $pdo->prepare("DELETE FROM UserInterests WHERE Student_ID = ?");
        $stmt->execute([$user_id]);

        // 2. Add new interests
        if (!empty($interests)) {
            $sql = "INSERT INTO UserInterests (Student_ID, Event_Type_ID) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($interests as $type_id) {
                $stmt->execute([$user_id, $type_id]);
            }
        }

        $pdo->commit();
        set_flash('settings', 'Interests updated successfully!', 'success');

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash('settings', 'Failed to update interests', 'error');
    }
}

redirect('../settings.php');
?>
