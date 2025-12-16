<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Check if user is project admin
if (!is_logged_in() || !is_project_admin($pdo, $_SESSION['user_id'])) {
    set_flash('login', 'Access denied. Project Admin privileges required.', 'error');
    redirect('login.php');
}

$admin_name = $_SESSION['user_name'];

// Fetch all clubs
try {
    $stmt = $pdo->query("SELECT Club_ID, Name, Short_name, Verified FROM Club ORDER BY Name");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    $clubs = [];
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT Student_ID, Name, GSuite_Email FROM User ORDER BY Name");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clubs - Admin</title>
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
                <a href="manage_clubs.php" class="sidebar-link active">
                    <span class="sidebar-icon">🎭</span> Manage Clubs
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
                <h1 class="dashboard-title">Manage Clubs</h1>
                <div class="topbar-user">
                    <span class="user-greeting"><?php echo htmlspecialchars($admin_name); ?></span>
                    <div class="user-avatar">P</div>
                </div>
            </div>

            <?php display_flash('clubs'); ?>

            <!-- Header Stats -->
            <section class="welcome-section">
                <h2>Club Management 🎭</h2>
                <p>Total Clubs: <strong><?php echo count($clubs); ?></strong> | 
                   Verified: <strong><?php echo count(array_filter($clubs, fn($c) => $c['Verified'])); ?></strong></p>
            </section>

            <!-- Clubs Table -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h3>All Clubs</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($clubs)): ?>
                        <div class="empty-state">
                            <p>No clubs in database. Run <code>insert_bracu_clubs.sql</code> to add clubs.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <thead style="background: rgba(139, 92, 246, 0.1); border-bottom: 2px solid var(--glass-border);">
                                        <tr>
                                            <th style="padding: 12px; text-align: left;">#</th>
                                            <th style="padding: 12px; text-align: left;">Club Name</th>
                                            <th style="padding: 12px; text-align: left;">Short Name</th>
                                            <th style="padding: 12px; text-align: left;">Assigned Admin</th>
                                            <th style="padding: 12px; text-align: left;">Verification</th>
                                            <th style="padding: 12px; text-align: left;">Status</th>
                                            <th style="padding: 12px; text-align: left;">Actions</th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    <?php foreach ($clubs as $club): 
                                        // Get club admin for THIS specific club
                                        try {
                                            $stmt = $pdo->prepare("
                                                SELECT u.Name, u.GSuite_Email, r.Role_name 
                                                FROM Role r
                                                JOIN User u ON r.St_ID = u.Student_ID
                                                WHERE r.Club_ID = ?
                                                AND (r.Role_name = 'Club_President' OR r.Role_name = 'Club_Admin')
                                                LIMIT 1
                                            ");
                                            $stmt->execute([$club['Club_ID']]);
                                            $admin = $stmt->fetch();
                                        } catch (PDOException $e) {
                                            $admin = null;
                                        }
                                    ?>
                                        <tr style="border-bottom: 1px solid var(--glass-border);">
                                            <td style="padding: 12px;"><?php echo $club['Club_ID']; ?></td>
                                            <td style="padding: 12px;"><strong><?php echo htmlspecialchars($club['Name']); ?></strong></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($club['Short_name']); ?></td>
                                            <td style="padding: 12px;">
                                                <?php if ($admin): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($admin['Name']); ?></strong>
                                                        <br><span style="color: #10b981; font-size: 0.85rem;"><?php echo htmlspecialchars($admin['Role_name']); ?></span>
                                                        <br>
                                                        <button onclick="removeAdmin(<?php echo $club['Club_ID']; ?>, '<?php echo htmlspecialchars($admin['Name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($club['Name'], ENT_QUOTES); ?>')" 
                                                                style="margin-top: 6px; padding: 4px 8px; font-size: 0.75rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                                            Remove
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #f59e0b;">No admin assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px;">
                                                <?php if ($club['Verified']): ?>
                                                    <button onclick="toggleVerification(<?php echo $club['Club_ID']; ?>, 0, '<?php echo htmlspecialchars($club['Name'], ENT_QUOTES); ?>')" 
                                                            style="padding: 6px 12px; font-size: 0.8rem; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                                        Unverify
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="toggleVerification(<?php echo $club['Club_ID']; ?>, 1, '<?php echo htmlspecialchars($club['Name'], ENT_QUOTES); ?>')" 
                                                            style="padding: 6px 12px; font-size: 0.8rem; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                                        Verify
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px;">
                                                <?php if ($club['Verified']): ?>
                                                    <span style="color: #10b981; font-weight: 600;">✓ Verified</span>
                                                <?php else: ?>
                                                    <span style="color: #f59e0b; font-weight: 600;">⚠ Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px;">
                                                <button onclick="assignToClub(<?php echo $club['Club_ID']; ?>, '<?php echo htmlspecialchars($club['Name'], ENT_QUOTES); ?>')" 
                                                        class="btn btn-primary" 
                                                        style="padding: 6px 12px; font-size: 0.85rem;">
                                                    Assign Role
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Assign Role Modal -->
            <div id="assignModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
                <div style="background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; padding: 2rem; max-width: 500px; width: 90%; border: 1px solid var(--glass-border);">
                    <h3 style="margin-top: 0;">Assign Club Role</h3>
                    <p id="clubNameDisplay" style="color: var(--text-muted); margin-bottom: 1.5rem;"></p>
                    
                    <form action="handlers/assign_club_admin_handler.php" method="POST">
                        <input type="hidden" name="club_id" id="hidden_club_id">
                        
                        <div class="form-group" id="club_select_container" style="display: none;">
                            <label for="club_select">Select Club *</label>
                            <select id="club_select" name="club_id_visible" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                                <option value="">Choose a club...</option>
                                <?php foreach ($clubs as $club): ?>
                                    <option value="<?php echo $club['Club_ID']; ?>">
                                        <?php echo htmlspecialchars($club['Name']) . ' (' . htmlspecialchars($club['Short_name']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

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

                        <div class="form-group">
                            <label for="role_name">Club Role *</label>
                            <select id="role_name" name="role_name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                                <option value="Club_President">President</option>
                                <option value="Club_Admin">Admin</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Assign Role</button>
                            <button type="button" onclick="closeModal()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
        function assignToClub(clubId, clubName) {
            // Set the club ID in hidden field
            document.getElementById('hidden_club_id').value = clubId;
            // Update display text
            document.getElementById('clubNameDisplay').textContent = 'Assigning role for: ' + clubName;
            // Hide club selector since club is already chosen
            document.getElementById('club_select_container').style.display = 'none';
            // Show modal
            document.getElementById('assignModal').style.display = 'flex';
        }

        function removeAdmin(clubId, adminName, clubName) {
            if (confirm('Are you sure you want to remove ' + adminName + ' as admin for ' + clubName + '?')) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'handlers/remove_club_admin_handler.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'club_id';
                input.value = clubId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleVerification(clubId, verifyStatus, clubName) {
            const action = verifyStatus === 1 ? 'verify' : 'unverify';
            if (confirm('Are you sure you want to ' + action + ' ' + clubName + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'handlers/verify_club_handler.php';
                
                const clubInput = document.createElement('input');
                clubInput.type = 'hidden';
                clubInput.name = 'club_id';
                clubInput.value = clubId;
                
                const verifyInput = document.createElement('input');
                verifyInput.type = 'hidden';
                verifyInput.name = 'verified';
                verifyInput.value = verifyStatus;
                
                form.appendChild(clubInput);
                form.appendChild(verifyInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('assignModal').style.display = 'none';
            document.getElementById('hidden_club_id').value = '';
            document.getElementById('user_id').value = '';
        }

        // Close on outside click
        document.getElementById('assignModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>