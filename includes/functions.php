<?php
// Starting session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sanitizing input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Checking if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirecting function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Setting flash message
function set_flash($key, $message, $type = 'info') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

// Get and clear flash message
function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

// Displaying flash message
function display_flash($key) {
    $flash = get_flash($key);
    if ($flash) {
        $type_class = [
            'success' => 'flash-success',
            'error' => 'flash-error',
            'info' => 'flash-info',
            'warning' => 'flash-warning'
        ];
        $class = $type_class[$flash['type']] ?? 'flash-info';
        echo "<div class='flash-message {$class}'>{$flash['message']}</div>";
    }
}

// Validating email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validating password strength
function validate_password($password) {
    return strlen($password) >= 6;
}

// Checking if user is a club admin in a verified club
function is_club_admin($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.Club_ID, c.Name, c.Short_name, r.Role_name
            FROM Role r
            JOIN Club c ON r.Club_ID = c.Club_ID
            WHERE r.St_ID = ? AND c.Verified = 1
            AND (r.Role_name = 'Club_President' OR r.Role_name = 'Club_Admin')
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// Checking if user is project admin
function is_project_admin($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT Role_name FROM Role WHERE St_ID = ? AND Role_name = 'Project_Admin' AND Club_ID IS NULL");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// Getting user role
function get_user_role($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT Role_name FROM Role WHERE St_ID = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['Role_name'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

// Send Notification to Club Followers
function send_notification($pdo, $club_id, $title, $message, $link = '') {
    try {
        // 1. Get Club Details
        $stmt = $pdo->prepare("SELECT Name FROM Club WHERE Club_ID = ?");
        $stmt->execute([$club_id]);
        $club = $stmt->fetch();
        if (!$club) return false;
        
        $club_name = $club['Name'];

        // 2. Get Followers
        $stmt = $pdo->prepare("
            SELECT u.Student_ID, u.GSuite_Email, u.Name
            FROM Follows_club f
            JOIN User u ON f.Student_ID = u.Student_ID
            WHERE f.Club_ID = ?
        ");
        $stmt->execute([$club_id]);
        $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($followers)) return true;

        // 3. Create Notification in DB
        $notification_content = "[$club_name] $title";
        $stmt = $pdo->prepare("INSERT INTO Notifications (Content, Link, Type) VALUES (?, ?, 'event')");
        $stmt->execute([$notification_content, $link]);
        $notification_id = $pdo->lastInsertId();

        // 4. Link to Followers and Send Email
        $insert_stmt = $pdo->prepare("INSERT INTO Gets_notification (Student_ID, Notification_ID) VALUES (?, ?)");
        
        foreach ($followers as $follower) {
            // Add to Dashboard Notifications
            $insert_stmt->execute([$follower['Student_ID'], $notification_id]);
            
            // Send Email
            $to = $follower['GSuite_Email'];
            $subject = "Update from $club_name: $title";
            $email_body = "Hi " . htmlspecialchars($follower['Name']) . ",\n\n" .
                         "$club_name has posted an update:\n\n" .
                         "$title\n$message\n\n" .
                         "View details on UniBoard.\n\n" .
                         "Regards,\nUniBoard Team";
            $headers = "From: notifications@uniboard.local";
            
            // Using @ to suppress local mail setup warnings
            @mail($to, $subject, $email_body, $headers);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
// Update Event Analytics
function update_analytics($pdo, $event_id, $type, $operation = 'add') {
    try {
        // Ensure analytics row exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO EventAnalytics (Event_ID) VALUES (?)");
        $stmt->execute([$event_id]);
        
        $op_sign = ($operation === 'remove') ? '-' : '+';
        
        // Update specific metric
        switch ($type) {
            case 'interested':
                // Prevent negative counts
                $sql = "UPDATE EventAnalytics SET Interested_count = GREATEST(0, Interested_count $op_sign 1), Last_updated = NOW() WHERE Event_ID = ?";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([$event_id]);
                break;
            case 'going':
                $sql = "UPDATE EventAnalytics SET Going_count = GREATEST(0, Going_count $op_sign 1), Last_updated = NOW() WHERE Event_ID = ?";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([$event_id]);
                break;
            default:
                return false;
        }
    } catch (PDOException $e) {
        return false;
    }
}
?>