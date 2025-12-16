<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../settings.php');
}

$user_id = $_SESSION['user_id'];
$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$address = sanitize_input($_POST['address'] ?? '');

// Validation
$errors = [];

if (empty($name)) $errors[] = 'Name is required';
if (empty($phone)) $errors[] = 'Phone number is required';
if (empty($address)) $errors[] = 'Address is required';

// Handle profile picture upload
$new_profile_pic = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_pic'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = 'Profile picture must be JPG, PNG, or GIF';
    }

    if ($file['size'] > $max_size) {
        $errors[] = 'Profile picture must be less than 2MB';
    }

    if (empty($errors)) {
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Get old profile picture to delete later
        try {
            $stmt = $pdo->prepare("SELECT Profile_Pic FROM User WHERE Student_ID = ?");
            $stmt->execute([$user_id]);
            $old_user = $stmt->fetch();
            $old_profile_pic = $old_user['Profile_Pic'] ?? null;
        } catch (PDOException $e) {
            $old_profile_pic = null;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $upload_path = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $new_profile_pic = 'uploads/profiles/' . $filename;
            
            // Delete old profile picture if it exists
            if ($old_profile_pic && file_exists('../' . $old_profile_pic)) {
                unlink('../' . $old_profile_pic);
            }
        } else {
            $errors[] = 'Failed to upload profile picture';
        }
    }
}

if (!empty($errors)) {
    set_flash('settings', implode('<br>', $errors), 'error');
    redirect('../settings.php');
}

try {
    // Build update query
    if ($new_profile_pic) {
        $stmt = $pdo->prepare("
            UPDATE User 
            SET Name = ?, Phone_No = ?, Address = ?, Profile_Pic = ?
            WHERE Student_ID = ?
        ");
        $stmt->execute([$name, $phone, $address, $new_profile_pic, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE User 
            SET Name = ?, Phone_No = ?, Address = ?
            WHERE Student_ID = ?
        ");
        $stmt->execute([$name, $phone, $address, $user_id]);
    }

    // Update session name
    $_SESSION['user_name'] = $name;

    set_flash('settings', 'Profile updated successfully!', 'success');
    redirect('../settings.php');
} catch (PDOException $e) {
    set_flash('settings', 'Failed to update profile. Please try again.', 'error');
    redirect('../settings.php');
}
?>