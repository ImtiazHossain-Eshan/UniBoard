<?php
require_once 'config/database.php';

function fix_fk($pdo, $table, $constraint, $column, $ref_table, $ref_column) {
    try {
        $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $constraint");
        echo "Dropped $constraint from $table.\n";
    } catch (PDOException $e) {
        // Ignore if constraint doesn't exist (e.g., might be auto-generated name)
        echo "Could not drop $constraint (might differ or not exist): " . $e->getMessage() . "\n";
        
        // Try to identify constraint name if standard name fails? 
        // For now, assume standard naming or previously defined naming.
        // If it fails, we might need a more robust way to find FK name, but let's try standard alter first.
    }

    try {
        $pdo->exec("ALTER TABLE $table ADD CONSTRAINT $constraint FOREIGN KEY ($column) REFERENCES $ref_table($ref_column) ON DELETE CASCADE");
        echo "Added $constraint to $table with CASCADE.\n";
    } catch (PDOException $e) {
        echo "Error adding constraint to $table: " . $e->getMessage() . "\n";
    }
}

// Fix Participate_in_events
fix_fk($pdo, 'Participate_in_events', 'participate_in_events_ibfk_3', 'Event_ID', 'Event', 'Event_ID');

// Check EventMedia as well just in case
fix_fk($pdo, 'EventMedia', 'eventmedia_ibfk_1', 'Event_ID', 'Event', 'Event_ID');

// Fix Follows_club (User deletion)
fix_fk($pdo, 'Follows_club', 'follows_club_ibfk_1', 'Student_ID', 'User', 'Student_ID');
fix_fk($pdo, 'Follows_club', 'follows_club_ibfk_2', 'Club_ID', 'Club', 'Club_ID');

// Fix Role_Request (User deletion)
fix_fk($pdo, 'Role_Request', 'role_request_ibfk_1', 'Student_ID', 'User', 'Student_ID');

// Fix Gets_notification (User deletion)
fix_fk($pdo, 'Gets_notification', 'gets_notification_ibfk_1', 'Student_ID', 'User', 'Student_ID');
// Fix Participate_in_events (Student_ID) - The primary key here is composite, but the FKs are separate.
fix_fk($pdo, 'Participate_in_events', 'participate_in_events_ibfk_1', 'Student_ID', 'User', 'Student_ID');

// Fix Joins_club (User deletion)
fix_fk($pdo, 'Joins_club', 'joins_club_ibfk_1', 'Student_ID', 'User', 'Student_ID');

// Fix UserInterests (User deletion) - Assuming this table exists from previous context
try {
    $pdo->query("SELECT 1 FROM UserInterests LIMIT 1"); // Check if table exists
    fix_fk($pdo, 'UserInterests', 'userinterests_ibfk_1', 'Student_ID', 'User', 'Student_ID');
} catch (PDOException $e) {
    // Table might not exist yet
}
?>
