<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    set_flash('dashboard', 'Access denied.', 'error');
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Notice - <?php echo htmlspecialchars($club_info['Short_name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">🎓 UniBoard</a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon">📊</span> Dashboard
                </a>
                <a href="create_event.php" class="sidebar-link">
                    <span class="sidebar-icon">➕</span> Create Event
                </a>
                <a href="create_notice.php" class="sidebar-link active">
                    <span class="sidebar-icon">📢</span> Create Notice
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
                <h1 class="dashboard-title">Create Notice</h1>
            </div>

            <?php display_flash('notice'); ?>

            <div class="form-page" style="display: block; padding: 0;">
                <div class="form-container" style="max-width: 700px;">
                    <h2>Create New Notice</h2>
                    <p>Send an announcement to all <?php echo htmlspecialchars($club_info['Name']); ?> members</p>

                    <form action="handlers/create_notice_handler.php" method="POST">
                        <div class="form-group">
                            <label for="title">Notice Title *</label>
                            <input type="text" id="title" name="title" required 
                                   placeholder="e.g., Upcoming Meeting Announcement">
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" required 
                                      style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color); font-family: inherit;"
                                      placeholder="Write your announcement message..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority"
                                    style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                                <option value="normal">Normal</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Publish Notice</button>
                            <a href="dashboard.php" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
