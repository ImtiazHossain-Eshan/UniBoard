<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Helper to get notifications
try {
    // Mark all as read if 'read_all' is set
    if (isset($_POST['read_all'])) {
        $stmt = $pdo->prepare("UPDATE Gets_notification SET Is_read = 1 WHERE Student_ID = ?");
        $stmt->execute([$user_id]);
        set_flash('notifications', 'All notifications marked as read', 'success');
        redirect('notifications.php');
    }

    $stmt = $pdo->prepare("
        SELECT n.*, gn.Is_read, gn.Student_ID
        FROM Notifications n
        JOIN Gets_notification gn ON n.Notification_ID = gn.Notification_ID
        WHERE gn.Student_ID = ?
        ORDER BY n.Created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - UniBoard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .notification-item {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }

        .notification-item.unread {
            border-left: 4px solid var(--primary-color);
            background: rgba(var(--primary-rgb), 0.05);
        }

        .notification-content {
            flex: 1;
        }

        .notification-time {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .notification-actions {
            margin-left: 1rem;
        }

        .btn-link {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar (Simplified for brevity, usually included) -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">🎓 UniBoard</a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon">🏠</span> Dashboard
                </a>
                <a href="browse_event.php" class="sidebar-link">
                    <span class="sidebar-icon">📅</span> Browse Events
                </a>
                <a href="explore_clubs.php" class="sidebar-link">
                    <span class="sidebar-icon">🔍</span> Explore Clubs
                </a>
                <a href="notifications.php" class="sidebar-link active">
                    <span class="sidebar-icon">🔔</span> Notifications
                </a>
                <a href="settings.php" class="sidebar-link">
                    <span class="sidebar-icon">⚙️</span> Settings
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="handlers/logout.php" class="sidebar-link logout-link"><span class="sidebar-icon">🚪</span> Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-topbar">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <h1 class="dashboard-title">Notifications</h1>
                <div class="topbar-user">
                    <span class="user-greeting"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>

            <?php display_flash('notifications'); ?>

            <section style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Your Updates</h2>
                    <?php if (!empty($notifications)): ?>
                        <form method="POST">
                            <button type="submit" name="read_all" class="btn btn-outline" style="font-size: 0.875rem;">Mark all as read</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                    <p class="empty-state">No notifications yet.</p>
                <?php else: ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $n): ?>
                            <div class="notification-item <?= !$n['Is_read'] ? 'unread' : '' ?>">
                                <div class="notification-content">
                                    <p style="margin: 0; font-size: 1.1rem;"><?= htmlspecialchars($n['Content']) ?></p>
                                    <div class="notification-time">
                                        <?= date('M d, Y g:i A', strtotime($n['Created_at'])) ?>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!empty($n['Link'])): ?>
                                        <a href="<?= htmlspecialchars($n['Link']) ?>" class="btn-link">View →</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
