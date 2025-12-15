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
    <title>Login - UniBoard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-page">
        <div class="form-container">
            <?php display_flash('login'); ?>
            
            <h2>Welcome Back</h2>
            <p>Sign in to access your UniBoard account</p>

            <form action="handlers/login_handler.php" method="POST">
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
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle">👁️</button>
                    </div>
                </div>

                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="form-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Sign In
                </button>
            </form>

            <div class="form-footer">
                Don't have an account? <a href="register.php">Create one now</a>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>