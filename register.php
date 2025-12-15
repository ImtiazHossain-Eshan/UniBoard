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

            <form action="handlers/register_handler.php" method="POST">
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