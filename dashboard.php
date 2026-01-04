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

// Check if user is club admin
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    // Not a club admin - redirect or show error
    set_flash('dashboard', 'Access denied. You must be an admin of a verified club.', 'error');
    redirect('index.php');
}

$club_id = $club_info['Club_ID'];
$club_name = $club_info['Name'];
$club_short_name = $club_info['Short_name'];

// Fetch club events with media
try {
    $stmt = $pdo->prepare("
        SELECT e.*, em.Media_url as Poster_url
        FROM Event e
        LEFT JOIN EventMedia em ON e.Event_ID = em.Event_ID
        WHERE e.Club_ID = ?
        ORDER BY e.Start_time DESC
    ");
    $stmt->execute([$club_id]);
    $club_events = $stmt->fetchAll();
} catch (PDOException $e) {
    $club_events = [];
}



// Fetch club's notices from Notice table
try {
    $stmt = $pdo->prepare("
        SELECT Notice_ID, Title, Content, Created_at
        FROM Notice
        WHERE Club_ID = ?
        ORDER BY Created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$club_id]);
    $notices = $stmt->fetchAll();
} catch (PDOException $e) {
    $notices = [];
}

// Fetch notification count
$unread_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Gets_notification WHERE Student_ID = ? AND Is_read = 0");
    $stmt->execute([$user_id]);
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
    <title><?php echo htmlspecialchars($club_short_name); ?> - Club Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    UniBoard
                </a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link active">
                    <span class="sidebar-icon"></span>
                    Dashboard
                </a>
                <a href="analytics_dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon">📊</span>
                    Analytics
                </a>
                <a href="notifications.php" class="sidebar-link">
                    <span class="sidebar-icon">🔔</span>
                    Notifications
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">
                            <?= $unread_count ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="browse_event.php" class="sidebar-link">
                    <span class="sidebar-icon">📅</span>
                    Browse Events
                </a>
                <a href="create_event.php" class="sidebar-link">
                    <span class="sidebar-icon">➕</span>
                    Create Event
                </a>
                <a href="create_notice.php" class="sidebar-link">
                    <span class="sidebar-icon">📢</span>
                    Create Notice
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
                <h1 class="dashboard-title"><?php echo htmlspecialchars($club_short_name); ?> Management</h1>
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

            <!-- Flash Messages -->
            <?php display_flash('dashboard'); ?>

            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2>Welcome to <?php echo htmlspecialchars($club_name); ?> Dashboard! 🎭</h2>
                    <p>Manage your club's events and announcements from here.</p>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="create_event.php" class="action-btn action-btn-primary">
                        <span class="action-icon">📅</span>
                        Create Event
                    </a>
                    <a href="create_notice.php" class="action-btn action-btn-primary">
                        <span class="action-icon">📢</span>
                        Create Notice
                    </a>
                </div>
            </section>

            <!-- Content Grid -->
            <div class="content-grid">


                <!-- My Events Section -->
                <section class="dashboard-card">
                    <div class="card-header">
                        <h3>My Created Events</h3>
                        <a href="create_event.php" class="card-link">Create New →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($club_events)): ?>
                            <p class="empty-state">No events yet. Create your first event!</p>
                        <?php else: ?>
                            <div style="display: grid; gap: 1.5rem;">
                                <?php foreach ($club_events as $event): ?>
                                    <div style="border: 1px solid var(--glass-border); border-radius: 12px; padding: 1.5rem; background: var(--glass-bg);">
                                        <div style="display: flex; gap: 1.5rem;">
                                            <?php if (!empty($event['Poster_url']) && file_exists($event['Poster_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($event['Poster_url']); ?>" 
                                                     alt="Event Poster" 
                                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
                                            <?php else: ?>
                                                <div style="width: 150px; height: 150px; background: var(--glass-border); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    <span style="font-size: 3rem;">📅</span>
                                                </div>
                                            <?php endif; ?>
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($event['Title']); ?></h4>
                                                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0 0 0.75rem 0;">
                                                    📅 <?php echo date('M d, Y g:i A', strtotime($event['Start_time'])); ?>
                                                </p>
                                                <p style="color: var(--text-muted); margin: 0 0 1rem 0; line-height: 1.5;">
                                                    <?php echo htmlspecialchars(substr($event['Description'], 0, 150)) . (strlen($event['Description']) > 150 ? '...' : ''); ?>
                                                </p>
                                                <div style="display: flex; gap: 0.75rem;">
                                                    <a href="edit_event.php?id=<?php echo $event['Event_ID']; ?>" 
                                                       class="btn btn-outline" 
                                                       style="padding: 0.5rem 1rem; text-decoration: none; font-size: 0.875rem;">
                                                        ✏️ Edit
                                                    </a>
                                                    <form action="handlers/delete_event_handler.php" method="POST" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['Event_ID']; ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline" 
                                                                style="padding: 0.5rem 1rem; font-size: 0.875rem; color: #ef4444; border-color: #ef4444;">
                                                            🗑️ Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Notices List -->
                <section class="dashboard-card">
                    <div class="card-header">
                        <h3>Published Notices</h3>
                        <a href="create_notice.php" class="card-link">Create New →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notices)): ?>
                            <p class="empty-state">No notices yet. Create your first notice!</p>
                        <?php else: ?>
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($notices as $notice): ?>
                                    <div style="padding: 1.5rem; border: 1px solid var(--glass-border); border-radius: 12px; background: var(--glass-bg);">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                                            <h4 style="margin: 0;"><?php echo htmlspecialchars($notice['Title']); ?></h4>
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
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>