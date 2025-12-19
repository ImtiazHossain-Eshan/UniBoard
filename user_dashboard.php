<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user profile picture and info
try {
    $stmt = $pdo->prepare("SELECT * FROM User WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $profile_pic = $user['Profile_Pic'] ?? null;
} catch (PDOException $e) {
    $profile_pic = null;
}

// Check if user has any club admin roles
$club_admin = is_club_admin($pdo, $user_id);
$is_admin = is_project_admin($pdo, $user_id);

// Fetch user's pending/approved applications
$applications = [];
try {
    // Check if Role_Request table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'Role_Request'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT rr.*, c.Name as Club_Name, c.Short_name
            FROM Role_Request rr
            JOIN Club c ON rr.Club_ID = c.Club_ID
            WHERE rr.Student_ID = ?
            ORDER BY rr.Created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $applications = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $applications = [];
}

// Fetch upcoming events from all clubs
$events = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'Event'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("
            SELECT e.*, c.Name as Club_Name, c.Short_name, em.Media_url as Poster_url
            FROM Event e
            JOIN Club c ON e.Club_ID = c.Club_ID
            LEFT JOIN EventMedia em ON e.Event_ID = em.Event_ID
            WHERE e.Start_time >= NOW()
            ORDER BY e.Start_time ASC
            LIMIT 5
        ");
        $events = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $events = [];
}

// Fetch recent notices from all clubs
$all_notices = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'Notice'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("
            SELECT n.*, c.Name as Club_Name, c.Short_name
            FROM Notice n
            JOIN Club c ON n.Club_ID = c.Club_ID
            ORDER BY n.Created_at DESC
            LIMIT 10
        ");
        $all_notices = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $all_notices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UniBoard</title>
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
                <a href="user_dashboard.php" class="sidebar-link active">
                    <span class="sidebar-icon"></span>
                    Dashboard
                </a>
                <?php if ($club_admin): ?>
                    <a href="dashboard.php" class="sidebar-link">
                        <span class="sidebar-icon">🎭</span>
                        My Club Dashboard
                    </a>
                <?php endif; ?>
                <a href="explore_clubs.php" class="sidebar-link">
                    <span class="sidebar-icon">🔍</span>
                    Explore Clubs
                </a>
                <a href="apply_for_club.php" class="sidebar-link">
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
                <h1 class="dashboard-title">My Dashboard</h1>
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

            <?php display_flash('dashboard'); ?>

            <!-- Welcome Section -->
            <section class="welcome-section" style="margin-top: 2rem;">
                <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                <p>Explore clubs, events, and opportunities at BRAC University.</p>
            </section>

            <!-- Quick Actions -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <?php if (!$club_admin): ?>
                    <a href="apply_for_club.php" style="text-decoration: none;">
                        <div class="stat-card stat-card-primary" style="cursor: pointer;">
                            <div class="stat-icon">📝</div>
                            <div class="stat-info">
                                <div class="stat-label">Apply for Club Admin</div>
                            </div>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" style="text-decoration: none;">
                        <div class="stat-card stat-card-success" style="cursor: pointer;">
                            <div class="stat-icon">🎭</div>
                            <div class="stat-info">
                                <div class="stat-label">My Club Dashboard</div>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
                <a href="explore_clubs.php" style="text-decoration: none;">
                    <div class="stat-card stat-card-secondary" style="cursor: pointer;">
                        <div class="stat-icon">🔍</div>
                        <div class="stat-info">
                            <div class="stat-label">Explore Clubs</div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- My Applications -->
            <?php if (!empty($applications)): ?>
                <section class="dashboard-card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3>My Applications</h3>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--glass-border); text-align: left;">
                                        <th style="padding: 12px;">Club</th>
                                        <th style="padding: 12px;">Role</th>
                                        <th style="padding: 12px;">Status</th>
                                        <th style="padding: 12px;">Date</th>
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
                    </div>
                </section>
            <?php endif; ?>

            <!-- Upcoming Events -->
            <?php if (!empty($events)): ?>
                <section class="dashboard-card" style="margin-top: 2.5rem;">
                    <div class="card-header">
                        <h3>Upcoming Events</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($events as $event): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid var(--glass-border);">
                                <?php if (!empty($event['Poster_url']) && file_exists($event['Poster_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['Poster_url']); ?>" 
                                         alt="Event Poster" 
                                         style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 0.75rem;">
                                <?php endif; ?>
                                <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($event['Title']); ?></h4>
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                                    <strong><?php echo htmlspecialchars($event['Club_Name']); ?></strong> • 
                                    <?php echo date('M d, Y g:i A', strtotime($event['Start_time'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Recent Notices -->
            <?php if (!empty($all_notices)): ?>
                <section class="dashboard-card" style="margin-top: 2.5rem; margin-bottom: 3rem;">
                    <div class="card-header">
                        <h3>Recent Notices</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach ($all_notices as $notice): ?>
                                <div style="padding: 1.25rem; border: 1px solid var(--glass-border); border-radius: 12px; background: var(--glass-bg);">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                                        <div>
                                            <h4 style="margin: 0 0 0.25rem 0;"><?php echo htmlspecialchars($notice['Title']); ?></h4>
                                            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                                                <strong><?php echo htmlspecialchars($notice['Club_Name']); ?></strong>
                                            </p>
                                        </div>
                                        <span style="color: var(--text-muted); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($notice['Created_at'])); ?>
                                        </span>
                                    </div>
                                    <p style="color: var(--text-muted); margin: 0; line-height: 1.6;">
                                        <?php echo nl2br(htmlspecialchars($notice['Content'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>