<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Check if user is club admin
$club_info = is_club_admin($pdo, $user_id);

if (!$club_info) {
    redirect('dashboard.php');
}

$club_id = $club_info['Club_ID'];
$club_name = $club_info['Name'];

// Fetch Analytics Data
try {
    // 1. Overview Stats (Live Data from RSVP tables)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT e.Event_ID) as Total_Events,
            (
                SELECT COUNT(*) 
                FROM Participate_in_events pie
                JOIN RSVP r ON pie.Rsvp_ID = r.Rsvp_ID
                JOIN Event ev ON pie.Event_ID = ev.Event_ID
                WHERE ev.Club_ID = ? AND r.Status = 'Interested'
            ) as Total_Interested,
            (
                SELECT COUNT(*) 
                FROM Participate_in_events pie
                JOIN RSVP r ON pie.Rsvp_ID = r.Rsvp_ID
                JOIN Event ev ON pie.Event_ID = ev.Event_ID
                WHERE ev.Club_ID = ? AND r.Status = 'Going'
            ) as Total_Going
        FROM Event e
        WHERE e.Club_ID = ?
    ");
    $stmt->execute([$club_id, $club_id, $club_id]);
    $stats = $stmt->fetch();

    // 2. Event Performance (Live Data for Interested/Going)
    $stmt = $pdo->prepare("
        SELECT 
            e.Event_ID,
            e.Title, 
            e.Start_time,
            (
                SELECT COUNT(*) 
                FROM Participate_in_events pie 
                JOIN RSVP r ON pie.Rsvp_ID = r.Rsvp_ID 
                WHERE pie.Event_ID = e.Event_ID AND r.Status = 'Interested'
            ) as Interested,
            (
                SELECT COUNT(*) 
                FROM Participate_in_events pie 
                JOIN RSVP r ON pie.Rsvp_ID = r.Rsvp_ID 
                WHERE pie.Event_ID = e.Event_ID AND r.Status = 'Going'
            ) as Going
        FROM Event e
        WHERE e.Club_ID = ?
        ORDER BY e.Start_time DESC
        LIMIT 10
    ");
    $stmt->execute([$club_id]);
    $events_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $stats = ['Total_Interested' => 0, 'Total_Going' => 0, 'Total_Events' => 0];
    $events_data = [];
}

// Prepare Data for Charts
$chart_labels = [];
$chart_engagement = [];

foreach (array_reverse($events_data) as $event) {
    $chart_labels[] = substr($event['Title'], 0, 15) . '...';
    $chart_engagement[] = $event['Interested'] + $event['Going'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?php echo htmlspecialchars($club_name); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .chart-container {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            height: 400px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">UniBoard</a>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon"></span> Dashboard
                </a>
                <a href="notifications.php" class="sidebar-link">
                    <span class="sidebar-icon">🔔</span> Notifications
                </a>
                <a href="analytics_dashboard.php" class="sidebar-link active">
                    <span class="sidebar-icon">📊</span> Analytics
                </a>
                <a href="browse_event.php" class="sidebar-link">
                    <span class="sidebar-icon">📅</span> Browse Events
                </a>
                <a href="create_event.php" class="sidebar-link">
                    <span class="sidebar-icon">➕</span> Create Event
                </a>
                <a href="create_notice.php" class="sidebar-link">
                    <span class="sidebar-icon">📢</span> Create Notice
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="handlers/logout.php" class="sidebar-link logout-link"><span class="sidebar-icon">🚪</span> Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-topbar">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <h1 class="dashboard-title">Engagement Analytics</h1>
                <div class="topbar-user">
                    <span class="user-greeting"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>

            <section style="margin-top: 2rem;">
                <!-- Overview Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['Total_Events']; ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['Total_Interested'] + $stats['Total_Going']; ?></div>
                        <div class="stat-label">Total Engagements</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="chart-container">
                    <canvas id="engagementChart"></canvas>
                </div>

                <!-- Detailed Table -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Event Performance</h3>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--glass-border);">
                                        <th style="padding: 1rem;">Event Name</th>
                                        <th style="padding: 1rem;">Date</th>
                                        <th style="padding: 1rem;">Interested</th>
                                        <th style="padding: 1rem;">Going</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events_data as $event): ?>
                                        <tr style="border-bottom: 1px solid var(--glass-border);">
                                            <td style="padding: 1rem;"><?php echo htmlspecialchars($event['Title']); ?></td>
                                            <td style="padding: 1rem;"><?php echo date('M d, Y', strtotime($event['Start_time'])); ?></td>
                                            <td style="padding: 1rem;"><?php echo $event['Interested']; ?></td>
                                            <td style="padding: 1rem;"><?php echo $event['Going']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        const ctx = document.getElementById('engagementChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Engagements (Interested + Going)',
                    data: <?php echo json_encode($chart_engagement); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Event Engagement (Last 10 Events)'
                    }
                }
            }
        });
    </script>
</body>
</html>
