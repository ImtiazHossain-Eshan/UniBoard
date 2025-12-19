<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

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
    $stmt = $pdo->query("
        SELECT Club_ID, Name, Short_name, Description, Created_at 
        FROM Club 
        WHERE Verified = TRUE 
        ORDER BY Name
    ");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    $clubs = [];
}

// Fetch clubs user is following
try {
    $stmt = $pdo->prepare("SELECT Club_ID FROM Follows_club WHERE Student_ID = ?");
    $stmt->execute([$user_id]);
    $following = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $following = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Clubs - UniBoard</title>
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
                <a href="explore_clubs.php" class="sidebar-link active">
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
                <h1 class="dashboard-title">Explore Clubs</h1>
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

            <?php display_flash('clubs'); ?>

            <section style="margin-top: 2rem;">
                <p style="color: var(--text-muted); margin-bottom: 2rem;">
                    Discover and follow clubs at BRAC University. Stay updated with their events and notices.
                </p>

                <?php if (empty($clubs)): ?>
                    <p class="empty-state">No clubs available yet.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($clubs as $club): ?>
                            <?php $is_following = in_array($club['Club_ID'], $following); ?>
                            <div class="dashboard-card">
                                <div class="card-body">
                                    <a href="view_club.php?id=<?php echo $club['Club_ID']; ?>" style="text-decoration: none; color: inherit;">
                                        <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($club['Name']); ?></h3>
                                    </a>
                                    <p style="color: var(--primary-color); font-size: 0.875rem; margin: 0 0 1rem 0;">
                                        <?php echo htmlspecialchars($club['Short_name']); ?>
                                    </p>
                                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">
                                        <?php echo htmlspecialchars($club['Description'] ?? 'No description available.'); ?>
                                    </p>
                                    
                                    <form action="handlers/follow_club_handler.php" method="POST">
                                        <input type="hidden" name="club_id" value="<?php echo $club['Club_ID']; ?>">
                                        <input type="hidden" name="action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                                        <?php if ($is_following): ?>
                                            <button type="submit" class="btn btn-outline" style="width: 100%;">
                                                ✓ Following
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                                + Follow Club
                                            </button>
                                        <?php endif; ?>
                                    </form>
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