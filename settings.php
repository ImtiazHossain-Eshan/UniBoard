<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM User WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        set_flash('settings', 'User not found', 'error');
        redirect('index.php');
    }

    // Check if user is Project Admin
    $is_admin = is_project_admin($pdo, $user_id);
    $dashboard_url = $is_admin ? 'admin_dashboard.php' : 'dashboard.php';
} catch (PDOException $e) {
    set_flash('settings', 'Failed to load user data', 'error');
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - UniBoard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    🎓 UniBoard
                </a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>

            <nav class="sidebar-nav">
                <a href="<?php echo $dashboard_url; ?>" class="sidebar-link">
                    <span class="sidebar-icon"></span>
                    Dashboard
                </a>
                <a href="settings.php" class="sidebar-link active">
                    <span class="sidebar-icon">⚙️</span>
                    Settings
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="handlers/logout.php" class="sidebar-link logout-link">
                    <span class="sidebar-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Top Bar -->
            <div class="dashboard-topbar">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <h1 class="dashboard-title">Settings</h1>
                <div class="topbar-user">
                    <span class="user-greeting"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar">
                        <?php if ($user['Profile_Pic'] && file_exists($user['Profile_Pic'])): ?>
                            <img src="<?php echo htmlspecialchars($user['Profile_Pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php display_flash('settings'); ?>

            <!-- Account Information (Read-only) -->
            <section class="dashboard-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Account Information</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div>
                            <label style="color: var(--text-muted); font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">Student ID</label>
                            <p style="font-weight: 600;"><?php echo htmlspecialchars($user['Student_ID']); ?></p>
                        </div>
                        <div>
                            <label style="color: var(--text-muted); font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">Email Address</label>
                            <p style="font-weight: 600;"><?php echo htmlspecialchars($user['GSuite_Email']); ?></p>
                        </div>
                        <div>
                            <label style="color: var(--text-muted); font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">Department</label>
                            <p style="font-weight: 600;"><?php echo htmlspecialchars($user['Department'] ?? 'Not set'); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Settings -->
            <section class="dashboard-card" style="margin-top: 2.5rem;">
                <div class="card-header">
                    <h3>Profile Settings</h3>
                </div>
                <div class="card-body">
                    <form action="handlers/update_profile_handler.php" method="POST" enctype="multipart/form-data">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="<?php echo htmlspecialchars($user['Name']); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone" 
                                    value="<?php echo htmlspecialchars($user['Phone_No'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="address">Address</label>
                                <textarea 
                                    id="address" 
                                    name="address" 
                                    rows="3"
                                    required
                                ><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="profile_pic">Profile Picture</label>
                                <?php if ($user['Profile_Pic'] && file_exists($user['Profile_Pic'])): ?>
                                    <div style="margin-bottom: 1rem;">
                                        <img src="<?php echo htmlspecialchars($user['Profile_Pic']); ?>" alt="Current Profile" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary-color);">
                                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem;">Current profile picture</p>
                                    </div>
                                <?php endif; ?>
                                <input 
                                    type="file" 
                                    id="profile_pic" 
                                    name="profile_pic" 
                                    accept="image/jpeg,image/jpg,image/png,image/gif"
                                >
                                <small style="color: #888; font-size: 0.85rem;">Upload a new picture to replace current (Max: 2MB, JPG/PNG/GIF)</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                            Update Profile
                        </button>
                    </form>
                </div>
            </section>

            <!-- Security Settings -->
            <section class="dashboard-card" style="margin-top: 2.5rem; margin-bottom: 3rem;">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <div class="card-body">
                    <form action="handlers/change_password_handler.php" method="POST" style="max-width: 500px;">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                placeholder="At least 6 characters"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input 
                                type="password" 
                                id="confirm_new_password" 
                                name="confirm_new_password" 
                                placeholder="Re-enter new password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                            Change Password
                        </button>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>