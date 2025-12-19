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

// Get filter status (default: Pending)
$status_filter = $_GET['status'] ?? 'Pending';
if (!in_array($status_filter, ['Pending', 'Approved', 'Rejected'])) {
    $status_filter = 'Pending';
}

// Fetch role requests based on status
try {
    $stmt = $pdo->prepare("
        SELECT 
            rr.*,
            u.Name as Applicant_Name,
            u.GSuite_Email as Applicant_Email,
            c.Name as Club_Name,
            c.Short_name,
            reviewer.Name as Reviewer_Name
        FROM Role_Request rr
        JOIN User u ON rr.Student_ID = u.Student_ID
        JOIN Club c ON rr.Club_ID = c.Club_ID
        LEFT JOIN User reviewer ON rr.Reviewed_by = reviewer.Student_ID
        WHERE rr.Status = ?
        ORDER BY rr.Created_at DESC
    ");
    $stmt->execute([$status_filter]);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
}

// Count pending requests for badge
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM Role_Request WHERE Status = 'Pending'");
    $pending_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Applications - UniBoard</title>
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
                <a href="admin_dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon"></span> Dashboard
                </a>
                <a href="manage_clubs.php" class="sidebar-link">
                    <span class="sidebar-icon">🎭</span> Manage Clubs
                </a>
                <a href="review_applications.php" class="sidebar-link active">
                    <span class="sidebar-icon">📋</span> Review Applications
                    <?php if ($pending_count > 0): ?>
                        <span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">
                            <?php echo $pending_count; ?>
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
                <h1 class="dashboard-title">Review Applications</h1>
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

            <?php display_flash('applications'); ?>

            <!-- Status Tabs -->
            <div style="display: flex; gap: 1rem; margin: 2rem 0 1.5rem 0; border-bottom: 2px solid var(--glass-border);">
                <a href="?status=Pending" 
                   style="padding: 0.75rem 1.5rem; text-decoration: none; color: <?php echo $status_filter == 'Pending' ? 'var(--primary-color)' : 'var(--text-muted)'; ?>; border-bottom: 3px solid <?php echo $status_filter == 'Pending' ? 'var(--primary-color)' : 'transparent'; ?>; font-weight: 600;">
                    Pending <?php if ($pending_count > 0): ?>(<?php echo $pending_count; ?>)<?php endif; ?>
                </a>
                <a href="?status=Approved" 
                   style="padding: 0.75rem 1.5rem; text-decoration: none; color: <?php echo $status_filter == 'Approved' ? 'var(--primary-color)' : 'var(--text-muted)'; ?>; border-bottom: 3px solid <?php echo $status_filter == 'Approved' ? 'var(--primary-color)' : 'transparent'; ?>; font-weight: 600;">
                    Approved
                </a>
                <a href="?status=Rejected" 
                   style="padding: 0.75rem 1.5rem; text-decoration: none; color: <?php echo $status_filter == 'Rejected' ? 'var(--primary-color)' : 'var(--text-muted)'; ?>; border-bottom: 3px solid <?php echo $status_filter == 'Rejected' ? 'var(--primary-color)' : 'transparent'; ?>; font-weight: 600;">
                    Rejected
                </a>
            </div>

            <!-- Applications List -->
            <section class="dashboard-card">
                <div class="card-body">
                    <?php if (empty($requests)): ?>
                        <p class="empty-state">No <?php echo strtolower($status_filter); ?> applications.</p>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <div style="border: 1px solid var(--glass-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: var(--glass-bg);">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.125rem;">
                                            <?php echo htmlspecialchars($request['Applicant_Name']); ?>
                                        </h4>
                                        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                                            <?php echo htmlspecialchars($request['Applicant_Email']); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php if ($request['Status'] == 'Pending'): ?>
                                            <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.875rem;">
                                                Pending
                                            </span>
                                        <?php elseif ($request['Status'] == 'Approved'): ?>
                                            <span style="background: #10b981; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.875rem;">
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #ef4444; color: white; padding: 4px 12px; border:radius: 12px; font-size: 0.875rem;">
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 8px;">
                                    <div>
                                        <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0 0 0.25rem 0;">Club</p>
                                        <p style="font-weight: 600; margin: 0;"><?php echo htmlspecialchars($request['Club_Name']); ?></p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0 0 0.25rem 0;">Requested Role</p>
                                        <p style="font-weight: 600; margin: 0;"><?php echo str_replace('_', ' ', $request['Requested_Role']); ?></p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0 0 0.25rem 0;">Applied On</p>
                                        <p style="font-weight: 600; margin: 0;"><?php echo date('M d, Y', strtotime($request['Created_at'])); ?></p>
                                    </div>
                                </div>

                                <div style="margin-bottom: 1rem;">
                                    <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0 0 0.5rem 0;">Application Message:</p>
                                    <p style="padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 8px; margin: 0;">
                                        <?php echo nl2br(htmlspecialchars($request['Request_Message'])); ?>
                                    </p>
                                </div>

                                <?php if ($request['Status'] == 'Pending'): ?>
                                    <div style="display: flex; gap: 1rem;">
                                        <form action="handlers/approve_application_handler.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['Request_ID']; ?>">
                                            <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981;">
                                                ✅ Approve
                                            </button>
                                        </form>
                                        <form action="handlers/reject_application_handler.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['Request_ID']; ?>">
                                            <button type="submit" class="btn" style="background: #ef4444; border-color: #ef4444; color: white;">
                                                ❌ Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                                        <?php if ($request['Reviewed_by']): ?>
                                            Reviewed by <?php echo htmlspecialchars($request['Reviewer_Name']); ?> on 
                                            <?php echo date('M d, Y', strtotime($request['Reviewed_at'])); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>