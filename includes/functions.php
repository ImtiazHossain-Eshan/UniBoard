<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Set flash message
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

// Display flash message
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

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password strength
function validate_password($password) {
    return strlen($password) >= 6;
}

// Check if user is a club admin in a verified club
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

// Check if user is project admin
function is_project_admin($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT Role_name FROM Role WHERE St_ID = ? AND Role_name = 'Project_Admin' AND Club_ID IS NULL");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// Get user role
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
?>