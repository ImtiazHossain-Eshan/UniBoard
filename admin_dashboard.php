<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Check if user is project admin
if (!is_logged_in() || !is_project_admin($pdo, $_SESSION['user_id'])) {
    set_flash('login', 'Access denied. Project Admin privileges required.', 'error');
    redirect('login.php');
}

$admin_name = $_SESSION['user_name'];
$admin_id = $_SESSION['user_id'];

// Fetch admin profile picture
try {
    $stmt = $pdo->prepare("SELECT Profile_Pic FROM User WHERE Student_ID = ?");
    $stmt->execute([$admin_id]);
    $user = $stmt->fetch();
    $profile_pic = $user['Profile_Pic'] ?? null;
} catch (PDOException $e) {
    $profile_pic = null;
}

// Fetch all clubs
try {
    $stmt = $pdo->query("SELECT Club_ID, Name, Short_name, Verified, Created_at FROM Club ORDER BY Name");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    $clubs = [];
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT Student_ID, Name, GSuite_Email, Department FROM User ORDER BY Name LIMIT 200");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// Fetch all current role assignments
try {
    $stmt = $pdo->query("
        SELECT r.St_ID, r.Role_name, u.Name as User_Name, u.GSuite_Email
        FROM Role r
        JOIN User u ON r.St_ID = u.Student_ID
        ORDER BY u.Name
    ");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
}

// Count pending role requests
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM Role_Request WHERE Status = 'Pending'");
    $pending_requests = $stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_requests = 0;
}

// Fetch notification count
$unread_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Gets_notification WHERE Student_ID = ? AND Is_read = 0");
    $stmt->execute([$admin_id]);
    $unread_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="sidebar-logo">Admin Panel</a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="sidebar-link active">
                    <span class="sidebar-icon"></span> Dashboard
                </a>
                <a href="notifications.php" class="sidebar-link">
                    <span class="sidebar-icon">🔔</span> Notifications
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">
                            <?= $unread_count ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="browse_event.php" class="sidebar-link">
                    <span class="sidebar-icon">📅</span> Browse Events
                </a>
                <a href="manage_clubs.php" class="sidebar-link">
                    <span class="sidebar-icon">🎭</span> Manage Clubs
                </a>
                <a href="review_applications.php" class="sidebar-link">
                    <span class="sidebar-icon">📋</span> Review Applications
                    <?php if ($pending_requests > 0): ?>
                        <span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">
                            <?php echo $pending_requests; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="settings.php" class="sidebar-link">
                    <span class="sidebar-icon">⚙️</span> Settings
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="handlers/logout.php" class="sidebar-link logout-link">
                    <span class="sidebar-icon">🚪</span> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-topbar">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <h1 class="dashboard-title">Project Admin Dashboard</h1>
                <div class="topbar-user">
                    <span class="user-greeting"><?php echo htmlspecialchars($admin_name); ?></span>
                    <div class="user-avatar">
                        <?php if ($profile_pic && file_exists($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            P
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php display_flash('admin'); ?>

            <!-- Welcome -->
            <section class="welcome-section">
                <h2>Welcome, Project Administrator!</h2>
                <p>Manage clubs, assign roles, and verify club status from this dashboard.</p>
            </section>

            <!-- Stats -->
            <section class="stats-grid">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">🎭</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($clubs); ?></div>
                        <div class="stat-label">Total Clubs</div>
                    </div>
                </div>
                <div class="stat-card stat-card-secondary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($users); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count(array_filter($clubs, fn($c) => $c['Verified'])); ?></div>
                        <div class="stat-label">Verified Clubs</div>
                    </div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $pending_requests; ?></div>
                        <div class="stat-label">Pending Applications</div>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-icon">🔑</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($roles); ?></div>
                        <div class="stat-label">Assigned Roles</div>
                    </div>
                </div>
            </section>

            <!-- Assign Project Admin -->
            <section class="dashboard-card" style="margin-top: 3rem;">
                <div class="card-header">
                    <h3>Assign Project Admin Role</h3>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                        Grant Project Admin privileges to a user. Project Admins can manage all clubs and assign club admins.
                    </p>
                    <form action="handlers/assign_project_admin_handler.php" method="POST" style="max-width: 600px;">
                        <div class="form-group">
                            <label for="user_id">Select User *</label>
                            <select id="user_id" name="user_id" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                                <option value="">Choose a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['Student_ID']; ?>">
                                        <?php echo htmlspecialchars($user['Name']) . ' (' . htmlspecialchars($user['GSuite_Email']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Assign as Project Admin</button>
                    </form>
                </div>
            </section>

            <!-- Current Project Admins -->
            <section class="dashboard-card" style="margin-top: 2.5rem;">
                <div class="card-header">
                    <h3>Current Project Admins</h3>
                </div>
                <div class="card-body">
                    <?php 
                    $project_admins = array_filter($roles, fn($r) => $r['Role_name'] === 'Project_Admin');
                    if (empty($project_admins)): ?>
                        <p class="empty-state">No project admins yet.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--glass-border); text-align: left;">
                                        <th style="padding: 12px;">User</th>
                                        <th style="padding: 12px;">Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($project_admins as $admin): ?>
                                        <tr style="border-bottom: 1px solid var(--glass-border);">
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($admin['User_Name']); ?></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($admin['GSuite_Email']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>