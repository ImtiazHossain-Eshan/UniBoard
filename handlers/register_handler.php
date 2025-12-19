<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $student_id = sanitize_input($_POST['student_id'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $rfid = sanitize_input($_POST['rfid'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];

    if (empty($name)) $errors[] = 'Name is required';
    if (empty($student_id)) $errors[] = 'Student ID is required';
    if (empty($email) || !validate_email($email)) $errors[] = 'Valid email is required';
    if (empty($department)) $errors[] = 'Department is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($password) || !validate_password($password)) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';

    // Handle profile picture upload
    $profile_pic_path = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Profile picture must be JPG, PNG, or GIF';
        }

        // Validate file size
        if ($file['size'] > $max_size) {
            $errors[] = 'Profile picture must be less than 2MB';
        }

        if (empty($errors)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $student_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $profile_pic_path = 'uploads/profiles/' . $filename;
            } else {
                $errors[] = 'Failed to upload profile picture';
            }
        }
    }

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

        // Check if RFID already exists (if provided)
        if (!empty($rfid)) {
            $stmt = $pdo->prepare("SELECT Student_ID FROM User WHERE RFID = ?");
            $stmt->execute([$rfid]);
            if ($stmt->fetch()) {
                set_flash('register', 'RFID card number already registered', 'error');
                redirect('../register.php');
            }
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert user with all fields
        $stmt = $pdo->prepare("
            INSERT INTO User (Student_ID, Name, GSuite_Email, Department, Phone_No, Gender, Address, RFID, Profile_Pic, Password, Created) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $student_id, 
            $name, 
            $email, 
            $department, 
            $phone, 
            $gender, 
            $address, 
            $rfid ?: null, 
            $profile_pic_path, 
            $hashed_password
        ]);

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