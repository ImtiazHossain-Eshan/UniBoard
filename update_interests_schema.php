<?php
require_once 'config/database.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS UserInterests (
        Interest_ID INT PRIMARY KEY AUTO_INCREMENT,
        Student_ID INT,
        Event_Type_ID INT,
        UNIQUE KEY unique_user_interest (Student_ID, Event_Type_ID),
        FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
        FOREIGN KEY (Event_Type_ID) REFERENCES EventType(Event_Type_ID) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "UserInterests table created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
