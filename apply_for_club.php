<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user profile picture
try {
    $stmt = $pdo->prepare("SELECT Profile_Pic FROM User WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $profile_pic = $user['Profile_Pic'] ?? null;
} catch (PDOException $e) {
    $profile_pic = null;
}

// Fetch all verified clubs
try {
    $stmt = $pdo->query("SELECT Club_ID, Name, Short_name FROM Club WHERE Verified = TRUE ORDER BY Name");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    $clubs = [];
}

// Fetch user's existing roles
try {
    $stmt = $pdo->prepare("SELECT Club_ID FROM Role WHERE St_ID = ?");
    $stmt->execute([$user_id]);
    $existing_roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_roles = [];
}

// Fetch user's application history
try {
    $stmt = $pdo->prepare("
        SELECT rr.*, c.Name as Club_Name, c.Short_name
        FROM Role_Request rr
        JOIN Club c ON rr.Club_ID = c.Club_ID
        WHERE rr.Student_ID = ?
        ORDER BY rr.Created_at DESC
    ");
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Club Admin - UniBoard</title>
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
                <a href="user_dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon"></span>
                    Dashboard
                </a>
                <a href="apply_for_club.php" class="sidebar-link active">
                    <span class="sidebar-icon">📝</span>
                    Apply for Club Admin
                </a>
                <a href="settings.php" class="sidebar-link">
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
                <h1 class="dashboard-title">Apply for Club Admin</h1>
                <div class="topbar-user">
                    <span class="user-greeting"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar">
                        <?php if ($profile_pic && file_exists($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php display_flash('application'); ?>

            <!-- Application Form -->
            <section class="dashboard-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Submit New Application</h3>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                        Apply to become an admin for a club. Your application will be reviewed by the Project Administrator.
                    </p>
                    
                    <form action="handlers/club_application_handler.php" method="POST" style="max-width: 600px;">
                        <div class="form-group">
                            <label for="club_id">Select Club *</label>
                            <select id="club_id" name="club_id" required>
                                <option value="">Choose a club...</option>
                                <?php foreach ($clubs as $club): ?>
                                    <?php 
                                    $has_role = in_array($club['Club_ID'], $existing_roles);
                                    $has_pending = false;
                                    foreach ($applications as $app) {
                                        if ($app['Club_ID'] == $club['Club_ID'] && $app['Status'] == 'Pending') {
                                            $has_pending = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?php echo $club['Club_ID']; ?>" 
                                            <?php echo ($has_role || $has_pending) ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($club['Name']); ?>
                                        <?php if ($has_role): ?> (Already Admin)<?php endif; ?>
                                        <?php if ($has_pending): ?> (Application Pending)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="role_name">Select Role *</label>
                            <select id="role_name" name="role_name" required>
                                <option value="">Choose a role...</option>
                                <option value="Club_President">Club President</option>
                                <option value="Club_Admin">Club Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message">Why do you want this role? *</label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="5"
                                placeholder="Explain why you're interested in this role and what you can contribute to the club..."
                                required
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                            Submit Application
                        </button>
                    </form>
                </div>
            </section>

            <!-- Application History -->
            <section class="dashboard-card" style="margin-top: 2.5rem; margin-bottom: 3rem;">
                <div class="card-header">
                    <h3>Your Application History</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($applications)): ?>
                        <p class="empty-state">You haven't submitted any applications yet.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--glass-border); text-align: left;">
                                        <th style="padding: 12px;">Club</th>
                                        <th style="padding: 12px;">Role</th>
                                        <th style="padding: 12px;">Status</th>
                                        <th style="padding: 12px;">Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr style="border-bottom: 1px solid var(--glass-border);">
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($app['Club_Name']); ?></td>
                                            <td style="padding: 12px;"><?php echo str_replace('_', ' ', $app['Requested_Role']); ?></td>
                                            <td style="padding: 12px;">
                                                <?php if ($app['Status'] == 'Pending'): ?>
                                                    <span style="color: #f59e0b;">🟡 Pending</span>
                                                <?php elseif ($app['Status'] == 'Approved'): ?>
                                                    <span style="color: #10b981;">✅ Approved</span>
                                                <?php else: ?>
                                                    <span style="color: #ef4444;">❌ Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px; color: var(--text-muted); font-size: 0.875rem;">
                                                <?php echo date('M d, Y', strtotime($app['Created_at'])); ?>
                                            </td>
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