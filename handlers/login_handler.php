<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validation
    if (empty($email) || empty($password)) {
        set_flash('login', 'Please fill in all fields', 'error');
        redirect('../login.php');
    }

    try {
        // Check user exists
        $stmt = $pdo->prepare("SELECT Student_ID, Password, Name FROM User WHERE GSuite_Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['Student_ID'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_email'] = $email;

            // Remember me functionality
            if ($remember) {
                setcookie('remember_token', bin2hex(random_bytes(32)), time() + (86400 * 30), "/");
            }

            set_flash('home', 'Login successful! Welcome back, ' . $user['Name'], 'success');
            redirect('../index.php');
        } else {
            set_flash('login', 'Invalid email or password', 'error');
            redirect('../login.php');
        }
    } catch (PDOException $e) {
        set_flash('login', 'An error occurred. Please try again', 'error');
        redirect('../login.php');
    }
} else {
    redirect('../login.php');
}
?>