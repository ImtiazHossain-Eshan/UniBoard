<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBoard - University Community Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                🎓 UniBoard
            </a>
            <button class="mobile-menu-toggle">☰</button>
            <ul class="nav-menu">
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <?php if (is_logged_in()): ?>
                    <li><span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span></li>
                    <li><a href="handlers/logout.php" class="nav-btn">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-btn">Login</a></li>
                    <li><a href="register.php" class="nav-btn primary">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <?php display_flash('home'); ?>
            <h1>Connect, Collaborate, Grow</h1>
            <p>
                UniBoard is your gateway to a vibrant university community. Join clubs, 
                discover events, and connect with fellow students in one unified platform.
            </p>
            <div class="hero-buttons">
                <?php if (!is_logged_in()): ?>
                    <a href="register.php" class="btn btn-primary">Get Started Free</a>
                    <a href="#features" class="btn btn-outline">Learn More</a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="#features" class="btn btn-outline">Explore Features</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2>Why Choose UniBoard?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3>🎭 Discover Clubs</h3>
                <p>
                    Explore a wide range of student organizations and find your perfect community. 
                    From academic clubs to hobby groups, there's something for everyone.
                </p>
            </div>
            <div class="feature-card">
                <h3>📅 Event Management</h3>
                <p>
                    Never miss out on campus events. Get real-time updates, RSVP to events, 
                    and stay connected with your favorite activities.
                </p>
            </div>
            <div class="feature-card">
                <h3>🔔 Smart Notifications</h3>
                <p>
                    Stay informed with personalized notifications about events, club updates, 
                    and community announcements tailored to your interests.
                </p>
            </div>
            <div class="feature-card">
                <h3>👥 Connect & Network</h3>
                <p>
                    Build meaningful connections with students who share your passions. 
                    Collaborate on projects and grow your university network.
                </p>
            </div>
            <div class="feature-card">
                <h3>📊 Analytics Dashboard</h3>
                <p>
                    Track event attendance, club engagement, and community growth with 
                    comprehensive analytics and insights.
                </p>
            </div>
            <div class="feature-card">
                <h3>🔐 Secure & Private</h3>
                <p>
                    Your data is protected with industry-standard security measures. 
                    Control your privacy settings and manage your digital footprint.
                </p>
            </div>
        </div>
    </section>

    <script src="assets/js/main.js"></script>
</body>
</html>