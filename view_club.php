<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$club_id = (int)($_GET['id'] ?? 0);

if ($club_id <= 0) {
    set_flash('clubs', 'Invalid club', 'error');
    redirect('explore_clubs.php');
}

// Fetch user profile picture
try {
    $stmt = $pdo->prepare("SELECT Profile_Pic FROM User WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $profile_pic = $user['Profile_Pic'] ?? null;
} catch (PDOException $e) {
    $profile_pic = null;
}

// Fetch club information
try {
    $stmt = $pdo->prepare("SELECT * FROM Club WHERE Club_ID = ? AND Verified = TRUE");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();
    
    if (!$club) {
        set_flash('clubs', 'Club not found', 'error');
        redirect('explore_clubs.php');
    }
} catch (PDOException $e) {
    set_flash('clubs', 'Error loading club', 'error');
    redirect('explore_clubs.php');
}

// Check if user is following this club
try {
    $stmt = $pdo->prepare("SELECT * FROM Follows_club WHERE Student_ID = ? AND Club_ID = ?");
    $stmt->execute([$user_id, $club_id]);
    $is_following = $stmt->fetch() !== false;
} catch (PDOException $e) {
    $is_following = false;
}

// Fetch club events
$events = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'Event'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT e.*, em.Media_url as Poster_url
            FROM Event e
            LEFT JOIN EventMedia em ON e.Event_ID = em.Event_ID
            WHERE e.Club_ID = ? AND e.Start_time >= NOW()
            ORDER BY e.Start_time ASC
        ");
        $stmt->execute([$club_id]);
        $events = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $events = [];
}

// Fetch club notices
$notices = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'Notice'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT * FROM Notice 
            WHERE Club_ID = ?
            ORDER BY Created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$club_id]);
        $notices = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $notices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($club['Name']); ?> - UniBoard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">🎓 UniBoard</a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon"></span> Dashboard
                </a>
                <a href="explore_clubs.php" class="sidebar-link">
                    <span class="sidebar-icon">🔍</span> Explore Clubs
                </a>
                <a href="apply_for_club.php" class="sidebar-link">
                    <span class="sidebar-icon">📝</span> Apply for Club Admin
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

        <main class="dashboard-main">
            <div class="dashboard-topbar">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <h1 class="dashboard-title"><?php echo htmlspecialchars($club['Short_name']); ?></h1>
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

            <?php display_flash('club'); ?>

            <!-- Club Header -->
            <section class="dashboard-card" style="margin-top: 2rem;">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                        <div>
                            <h2 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($club['Name']); ?></h2>
                            <p style="color: var(--text-muted); margin: 0;">
                                Est. <?php echo date('Y', strtotime($club['Created_at'])); ?>
                            </p>
                        </div>
                        <form action="handlers/follow_club_handler.php" method="POST">
                            <input type="hidden" name="club_id" value="<?php echo $club_id; ?>">
                            <input type="hidden" name="action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                            <?php if ($is_following): ?>
                                <button type="submit" class="btn btn-outline">✓ Following</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary">+ Follow Club</button>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <?php if ($club['Description']): ?>
                        <p style="color: var(--text-muted); line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($club['Description'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Upcoming Events -->
            <section class="dashboard-card" style="margin-top: 2.5rem;">
                <div class="card-header">
                    <h3>Upcoming Events</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($events)): ?>
                        <p class="empty-state">No upcoming events scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <div style="padding: 1.5rem; border: 1px solid var(--glass-border); border-radius: 12px; margin-bottom: 1rem; background: var(--glass-bg);">
                                <?php if (!empty($event['Poster_url']) && file_exists($event['Poster_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['Poster_url']); ?>" 
                                         alt="Event Poster" 
                                         style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;">
                                <?php endif; ?>
                                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.125rem;">
                                    <?php echo htmlspecialchars($event['Title']); ?>
                                </h4>
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0 0 1rem 0;">
                                    📅 <?php echo date('l, F j, Y', strtotime($event['Start_time'])); ?> at 
                                    <?php echo date('g:i A', strtotime($event['Start_time'])); ?>
                                </p>
                                <?php if ($event['Description']): ?>
                                    <p style="margin: 0; line-height: 1.6;">
                                        <?php echo nl2br(htmlspecialchars($event['Description'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Notices -->
            <?php if (!empty($notices)): ?>
                <section class="dashboard-card" style="margin-top: 2.5rem; margin-bottom: 3rem;">
                    <div class="card-header">
                        <h3>Recent Notices</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($notices as $notice): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid var(--glass-border);">
                                <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($notice['Title']); ?></h4>
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0 0 0.5rem 0;">
                                    <?php echo date('M d, Y', strtotime($notice['Created_at'])); ?>
                                </p>
                                <p style="margin: 0; color: var(--text-muted);">
                                    <?php echo nl2br(htmlspecialchars($notice['Content'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>