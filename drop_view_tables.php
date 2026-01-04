<?php
require_once 'config/database.php';

try {
    // Drop EventViews table
    $pdo->exec("DROP TABLE IF EXISTS EventViews");
    echo "EventViews table dropped successfully.\n";
    
    // Remove Views column from EventAnalytics (optional, can keep column but not use it)
    // Uncomment if you want to fully remove:
    // $pdo->exec("ALTER TABLE EventAnalytics DROP COLUMN Views");
    // echo "Views column dropped from EventAnalytics.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
