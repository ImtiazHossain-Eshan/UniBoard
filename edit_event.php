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

$event_id = (int)($_GET['id'] ?? 0);

if ($event_id <= 0) {
    set_flash('dashboard', 'Invalid event', 'error');
    redirect('dashboard.php');
}

// Fetch event details
try {
    $stmt = $pdo->prepare("
        SELECT e.*, em.Media_url as Poster_url
        FROM Event e
        LEFT JOIN EventMedia em ON e.Event_ID = em.Event_ID
        WHERE e.Event_ID = ? AND e.Club_ID = ?
    ");
    $stmt->execute([$event_id, $club_info['Club_ID']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        set_flash('dashboard', 'Event not found or access denied', 'error');
        redirect('dashboard.php');
    }
} catch (PDOException $e) {
    set_flash('dashboard', 'Error loading event', 'error');
    redirect('dashboard.php');
}

// Fetch event types
try {
    $stmt = $pdo->query("SELECT Event_Type_ID, Name FROM EventType");
    $event_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $event_types = [];
}

// Fetch locations
try {
    $stmt = $pdo->query("SELECT Location_ID, Address FROM Location");
    $locations = $stmt->fetchAll();
} catch (PDOException $e) {
    $locations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?php echo htmlspecialchars($club_info['Short_name']); ?></title>
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
                    <span class="sidebar-icon"></span> Dashboard
                </a>
                <a href="create_event.php" class="sidebar-link">
                    <span class="sidebar-icon">➕</span> Create Event
                </a>
                <a href="create_notice.php" class="sidebar-link">
                    <span class="sidebar-icon">📢</span> Create Notice
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
                <h1 class="dashboard-title">Edit Event</h1>
            </div>

            <?php display_flash('event'); ?>

            <div class="form-page" style="display: block; padding: 0;">
                <div class="form-container" style="max-width: 700px;">
                    <h2>Edit Event</h2>
                    <p>Update event details for <?php echo htmlspecialchars($club_info['Name']); ?></p>

                    <form action="handlers/edit_event_handler.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        
                        <div class="form-group">
                            <label for="event_name">Event Name *</label>
                            <input type="text" id="event_name" name="event_name" required 
                                   value="<?php echo htmlspecialchars($event['Title']); ?>"
                                   placeholder="e.g., Programming Contest 2024">
                        </div>

                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4" required 
                                      style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color); font-family: inherit;"
                                      placeholder="Describe your event..."><?php echo htmlspecialchars($event['Description']); ?></textarea>
                        </div>

                        <?php if (!empty($event['Poster_url']) && file_exists($event['Poster_url'])): ?>
                            <div class="form-group">
                                <label>Current Poster</label>
                                <div style="margin-bottom: 1rem;">
                                    <img src="<?php echo htmlspecialchars($event['Poster_url']); ?>" 
                                         alt="Current Poster" 
                                         style="max-width: 300px; max-height: 200px; object-fit: cover; border-radius: 8px;">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="event_poster">New Event Poster (Optional)</label>
                            <input type="file" id="event_poster" name="event_poster" accept="image/*"
                                   style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Upload a new poster to replace the existing one (JPG, PNG, WebP)</small>
                        </div>

                        <div class="form-group">
                            <label for="start_time">Start Date & Time *</label>
                            <input type="datetime-local" id="start_time" name="start_time" required
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($event['Start_time'])); ?>">
                        </div>

                        <div class="form-group">
                            <label for="location_id">Location *</label>
                            <select id="location_id" name="location_id" required
                                    style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                                <option value="">Select a location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['Location_ID']; ?>" 
                                            <?php echo ($event['Location_ID'] == $location['Location_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location['Address']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="event_type_id">Event Type *</label>
                            <select id="event_type_id" name="event_type_id" required
                                    style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color);">
                                <option value="">Select event type</option>
                                <?php foreach ($event_types as $type): ?>
                                    <option value="<?php echo $type['Event_Type_ID']; ?>"
                                            <?php echo ($event['Event_Type_ID'] == $type['Event_Type_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Update Event</button>
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
