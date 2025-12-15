<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $student_id = sanitize_input($_POST['student_id'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];

    if (empty($name)) $errors[] = 'Name is required';
    if (empty($student_id)) $errors[] = 'Student ID is required';
    if (empty($email) || !validate_email($email)) $errors[] = 'Valid email is required';
    if (empty($password) || !validate_password($password)) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';

    if (!empty($errors)) {
        set_flash('register', implode('<br>', $errors), 'error');
        redirect('../register.php');
    }

    try {
        // Check if email or student ID already exists
        $stmt = $pdo->prepare("SELECT Student_ID FROM User WHERE GSuite_Email = ? OR Student_ID = ?");
        $stmt->execute([$email, $student_id]);
        
        if ($stmt->fetch()) {
            set_flash('register', 'Email or Student ID already registered', 'error');
            redirect('../register.php');
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO User (Student_ID, Name, GSuite_Email, Password, Created) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$student_id, $name, $email, $hashed_password]);

        set_flash('login', 'Registration successful! Please login', 'success');
        redirect('../login.php');
    } catch (PDOException $e) {
        set_flash('register', 'Registration failed. Please try again', 'error');
        redirect('../register.php');
    }
} else {
    redirect('../register.php');
}
?>