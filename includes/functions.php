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
?>