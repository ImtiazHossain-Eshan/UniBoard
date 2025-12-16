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

// Fetch club's events
try {
    $stmt = $pdo->prepare("
        SELECT Event_ID, Name, Description, Start_time, Location_ID, Event_type_ID
        FROM Event
        WHERE Club_ID = ?
        ORDER BY Start_time DESC
        LIMIT 10
    ");
    $stmt->execute([$club_id]);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
}

// Fetch club's notices
try {
    $stmt = $pdo->prepare("
        SELECT Notification_ID, Title, Message, Created_at, Is_read
        FROM Notifications
        WHERE Club_ID = ?
        ORDER BY Created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$club_id]);
    $notices = $stmt->fetchAll();
} catch (PDOException $e) {
    $notices = [];
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
                    <a href="create_notice.php" class="action-btn action-btn-secondary">
                        <span class="action-icon">📢</span>
                        Create Notice
                    </a>
                </div>
            </section>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Events List -->
                <section class="dashboard-card">
                    <div class="card-header">
                        <h3>Your Events</h3>
                        <a href="create_event.php" class="card-link">Create New →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($events)): ?>
                            <p class="empty-state">No events yet. Create your first event!</p>
                        <?php else: ?>
                            <div class="event-list">
                                <?php foreach ($events as $event): ?>
                                    <div class="event-item">
                                        <div class="event-date">
                                            <div class="event-day"><?php echo date('d', strtotime($event['Start_time'])); ?></div>
                                            <div class="event-month"><?php echo strtoupper(date('M', strtotime($event['Start_time']))); ?></div>
                                        </div>
                                        <div class="event-details">
                                            <h4><?php echo htmlspecialchars($event['Name']); ?></h4>
                                            <p><?php echo htmlspecialchars(substr($event['Description'], 0, 60)) . '...'; ?></p>
                                        </div>
                                        <a href="edit_event.php?id=<?php echo $event['Event_ID']; ?>" class="event-action">Edit</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Notices List -->
                <section class="dashboard-card">
                    <div class="card-header">
                        <h3>Your Notices</h3>
                        <a href="create_notice.php" class="card-link">Create New →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notices)): ?>
                            <p class="empty-state">No notices yet. Create your first notice!</p>
                        <?php else: ?>
                            <div class="notification-list">
                                <?php foreach ($notices as $notice): ?>
                                    <div class="notification-item">
                                        <div class="notification-icon">📢</div>
                                        <div class="notification-content">
                                            <h4><?php echo htmlspecialchars($notice['Title']); ?></h4>
                                            <p><?php echo htmlspecialchars(substr($notice['Message'], 0, 80)) . '...'; ?></p>
                                            <span class="notification-time"><?php echo date('M d, Y', strtotime($notice['Created_at'])); ?></span>
                                        </div>
                                        <a href="edit_notice.php?id=<?php echo $notice['Notification_ID']; ?>" class="event-action">Edit</a>
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