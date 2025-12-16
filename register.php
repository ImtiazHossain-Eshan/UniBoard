<?php
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UniBoard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-page">
        <div class="form-container">
            <?php display_flash('register'); ?>
            
            <h2>Create Account</h2>
            <p>Join UniBoard and connect with your community</p>

            <form action="handlers/register_handler.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="John Doe" 
                        required
                        autocomplete="name"
                    >
                </div>

                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input 
                        type="text" 
                        id="student_id" 
                        name="student_id" 
                        placeholder="23101137" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your.name@g.bracu.ac.bd" 
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <option value="CSE">Computer Science & Engineering (CSE)</option>
                        <option value="EEE">Electrical & Electronic Engineering (EEE)</option>
                        <option value="BBA">Business Administration (BBA)</option>
                        <option value="Economics">Economics</option>
                        <option value="Law">Law</option>
                        <option value="Pharmacy">Pharmacy</option>
                        <option value="Architecture">Architecture</option>
                        <option value="English">English</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+880 1234-567890" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea 
                        id="address" 
                        name="address" 
                        placeholder="Enter your full address" 
                        rows="3"
                        required
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="rfid">RFID Card Number (Optional)</label>
                    <input 
                        type="text" 
                        id="rfid" 
                        name="rfid" 
                        placeholder="Scan or enter RFID number"
                    >
                </div>

                <div class="form-group">
                    <label for="profile_pic">Profile Picture (Optional)</label>
                    <input 
                        type="file" 
                        id="profile_pic" 
                        name="profile_pic" 
                        accept="image/jpeg,image/jpg,image/png,image/gif"
                    >
                    <small style="color: #888; font-size: 0.85rem;">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="At least 6 characters" 
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Re-enter your password" 
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    Create Account
                </button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="login.php">Sign in instead</a>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>