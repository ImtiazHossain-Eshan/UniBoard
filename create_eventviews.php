<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS EventViews (
        View_ID INT PRIMARY KEY AUTO_INCREMENT,
        Event_ID INT NOT NULL,
        Student_ID INT NOT NULL,
        Viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_view (Event_ID, Student_ID),
        FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID) ON DELETE CASCADE,
        FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "EventViews table created successfully.\n";
    
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
