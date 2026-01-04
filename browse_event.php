<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

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

// Fetch user RSVPs to highlight buttons
$user_rsvps = [];
try {
    $stmt = $pdo->prepare("
        SELECT pie.Event_ID, r.Status 
        FROM Participate_in_events pie
        JOIN RSVP r ON pie.Rsvp_ID = r.Rsvp_ID
        WHERE pie.Student_ID = ?
    ");
    $stmt->execute([$user_id]);
    $user_rsvps = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $user_rsvps = [];
}

// Check roles for sidebar
$club_admin = is_club_admin($pdo, $user_id);
$is_admin = is_project_admin($pdo, $user_id);


/* Fetch filter values */
$club_id   = $_GET['club'] ?? '';
$type_id   = $_GET['type'] ?? '';
$location  = $_GET['location'] ?? '';
$date      = $_GET['date'] ?? '';
$view      = $_GET['view'] ?? 'all'; // 'all' or 'my_feed'

/* Base query */
$sql = "
SELECT 
    e.Event_ID,
    e.Title,
    e.Description,
    e.Start_time,
    c.Name AS club_name,
    et.Name AS event_type,
    CONCAT(l.Building, ' ', l.Room) AS location,
    em.Media_url as Poster_url
FROM Event e
JOIN Club c ON e.Club_ID = c.Club_ID
JOIN EventType et ON e.Event_Type_ID = et.Event_Type_ID
JOIN Location l ON e.Location_ID = l.Location_ID
LEFT JOIN EventMedia em ON e.Event_ID = em.Event_ID
WHERE 1=1
";

$params = [];

/* Apply View Filter (Personalization) */
if ($view === 'my_feed') {
    $sql .= " AND (
        e.Club_ID IN (SELECT Club_ID FROM Follows_club WHERE Student_ID = ?) 
        OR 
        e.Event_Type_ID IN (SELECT Event_Type_ID FROM UserInterests WHERE Student_ID = ?)
    )";
    $params[] = $user_id;
    $params[] = $user_id;
}

/* Apply filters */
if ($club_id) {
    $sql .= " AND c.Club_ID = ?";
    $params[] = $club_id;
}

if ($type_id) {
    $sql .= " AND et.Event_Type_ID = ?";
    $params[] = $type_id;
}

if ($location) {
    $sql .= " AND l.Location_ID = ?";
    $params[] = $location;
}

if ($date) {
    $sql .= " AND DATE(e.Start_time) = ?";
    $params[] = $date;
} else {
    // Default to upcoming events if no date filter is set
    $sql .= " AND e.Start_time >= NOW()";
}

$sql .= " GROUP BY e.Event_ID ORDER BY e.Start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

/* Dropdown data */
$clubs = $pdo->query("SELECT Club_ID, Name FROM Club WHERE Verified = 1")->fetchAll();
$types = $pdo->query("SELECT Event_Type_ID, Name FROM EventType")->fetchAll();
$locations = $pdo->query("SELECT Location_ID, Building, Room FROM Location")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events - UniBoard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-section {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .form-control {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        select.form-control option {
            background: #1f2937; /* Dark bg for options to match theme if dark mode, or just solid color */
            color: white; /* Ensure text is visible */
            padding: 10px;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-filter {
            background: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            min-width: 100px;
        }

        .btn-reset {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--text-color);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            min-width: 100px;
        }

        .events-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .event-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .event-card:hover {
            transform: translateY(-4px);
        }

        .event-poster {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--glass-border);
        }

        .event-content {
            padding: 1.5rem;
        }

        .event-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-rsvp {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            background: transparent;
            color: var(--text-color);
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-rsvp:hover {
            background: var(--glass-border);
        }

        .btn-rsvp.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
    </style>
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
                    <span class="sidebar-icon">🏠</span>
                    Dashboard
                </a>
                <?php if ($club_admin): ?>
                    <a href="dashboard.php" class="sidebar-link">
                        <span class="sidebar-icon">🎭</span>
                        My Club Dashboard
                    </a>
                <?php endif; ?>
                <?php if ($is_admin): ?>
                    <a href="admin_dashboard.php" class="sidebar-link">
                        <span class="sidebar-icon">⚡</span>
                        Admin Panel
                    </a>
                <?php endif; ?>
                <a href="browse_event.php" class="sidebar-link <?= ($view === 'all') ? 'active' : '' ?>">
                    <span class="sidebar-icon">📅</span>
                    Browse Events
                </a>
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
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if ($view === 'my_feed'): ?>
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--primary-color);">Your Personalized Feed</h2>
                    <?php else: ?>
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--text-color);">All Events</h2>
                    <?php endif; ?>
                </div>
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

            <!-- Filter Section -->
            <?php if ($view !== 'my_feed'): ?>
            <section class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label>Filter by Club</label>
                        <select name="club" class="form-control">
                            <option value="">All Clubs</option>
                            <?php foreach ($clubs as $c): ?>
                                <option value="<?= $c['Club_ID'] ?>" <?= ($club_id == $c['Club_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Event Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['Event_Type_ID'] ?>" <?= ($type_id == $t['Event_Type_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <select name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $l): ?>
                                <option value="<?= $l['Location_ID'] ?>" <?= ($location == $l['Location_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['Building'].' '.$l['Room']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                    </div>

                    <div class="form-group">
                        <label style="visibility: hidden;">Actions</label>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">Filter</button>
                            <a href="browse_event.php" class="btn-reset">Reset</a>
                        </div>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- Event List -->
            <?php if (empty($events)): ?>
                <div class="empty-state" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                    <h3>No events found</h3>
                    <p>Try adjusting your filters or check back later!</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $e): ?>
                        <article class="event-card">
                            <?php if (!empty($e['Poster_url']) && file_exists($e['Poster_url'])): ?>
                                <img src="<?= htmlspecialchars($e['Poster_url']) ?>" alt="Event Poster" class="event-poster">
                            <?php else: ?>
                                <div class="event-poster" style="display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 3rem;">📅</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-content">
                                <h3 class="event-title"><?= htmlspecialchars($e['Title']) ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <span>📅</span>
                                        <?= date('M d, Y g:i A', strtotime($e['Start_time'])) ?>
                                    </div>
                                    <div class="event-meta-item">
                                        <span>🏛</span>
                                        <?= htmlspecialchars($e['club_name']) ?>
                                    </div>
                                    <div class="event-meta-item">
                                        <span>📍</span>
                                        <?= htmlspecialchars($e['location']) ?>
                                    </div>
                                    <div class="event-meta-item">
                                        <span>🏷</span>
                                        <?= htmlspecialchars($e['event_type']) ?>
                                    </div>
                                </div>
                                <p style="color: var(--text-muted); line-height: 1.5; margin-bottom: 0;">
                                    <?= htmlspecialchars(substr($e['Description'], 0, 100)) . (strlen($e['Description']) > 100 ? '...' : '') ?>
                                </p>
                                
                                <div class="event-actions" onclick="event.stopPropagation()">
                                    <?php 
                                        $my_status = $user_rsvps[$e['Event_ID']] ?? '';
                                    ?>
                                    <button class="btn-rsvp <?= $my_status === 'Interested' ? 'active' : '' ?>" 
                                            onclick="toggleRsvp(this, <?= $e['Event_ID'] ?>, 'Interested')">
                                        ★ Interested
                                    </button>
                                    <button class="btn-rsvp <?= $my_status === 'Going' ? 'active' : '' ?>" 
                                            onclick="toggleRsvp(this, <?= $e['Event_ID'] ?>, 'Going')">
                                        ✓ Going
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
        function toggleRsvp(btn, eventId, status) {

            // Optimistic UI update
            const parent = btn.parentElement;
            const buttons = parent.querySelectorAll('.btn-rsvp');
            const wasActive = btn.classList.contains('active');
            
            // Reset all buttons in this group
            buttons.forEach(b => b.classList.remove('active'));
            
            // If it wasn't active before, make it active now
            if (!wasActive) {
                btn.classList.add('active');
            }

            fetch('handlers/rsvp_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_id: eventId,
                    status: status
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    // Revert on failure
                    alert('Action failed');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
