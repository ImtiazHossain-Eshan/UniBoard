<?php
require_once 'config/database.php';

try {
    // 1. Drop existing foreign key
    $pdo->exec("ALTER TABLE EventAnalytics DROP FOREIGN KEY eventanalytics_ibfk_1");
    echo "Dropped old foreign key.\n";

    // 2. Add new foreign key with CASCADE
    $pdo->exec("ALTER TABLE EventAnalytics ADD CONSTRAINT eventanalytics_ibfk_1 FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID) ON DELETE CASCADE");
    echo "Added new foreign key with ON DELETE CASCADE.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
