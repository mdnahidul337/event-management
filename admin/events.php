<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Check permission for adding/editing (Level 50 = EventManager or higher)
$can_manage_events = $_SESSION['role_level'] >= 50;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_events) {
    if (isset($_POST['add_event'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $location = trim($_POST['location']);
        $start_date = $_POST['start_date'];
        $type = $_POST['type']; // Free or Paid
        
        $stmt = $pdo->prepare("INSERT INTO events (title, description, location, start_date, type, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $location, $start_date, $type, $_SESSION['user_id']])) {
            // Log activity
            $event_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'event', ?, ?)")
                ->execute([$_SESSION['user_id'], "Created event ID $event_id", $_SERVER['REMOTE_ADDR']]);
            
            $success = "Event added successfully!";
            $action = 'list';
        } else {
            $error = "Failed to add event.";
        }
    }
}

// Fetch events for list view
if ($action === 'list') {
    $events = $pdo->query("SELECT e.*, u.name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.start_date DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background: var(--card-bg); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); outline: none; background: var(--bg-color); color: var(--text-main); }
        .form-control:focus { border-color: var(--primary-color); }
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

            <?php if ($action === 'list'): ?>
                <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                    <h2>Manage Events</h2>
                    <?php if($can_manage_events): ?>
                        <a href="events.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Event</a>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($events as $e): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($e['title']); ?></strong></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($e['start_date'])); ?></td>
                                <td><?php echo htmlspecialchars($e['location']); ?></td>
                                <td>
                                    <?php if($e['type'] === 'Paid'): ?>
                                        <span class="badge" style="background:#f59e0b;">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-accounting">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($e['status'] === 'Upcoming'): ?>
                                        <span style="color:#3b82f6;"><i class="fa-solid fa-circle"></i> Upcoming</span>
                                    <?php elseif($e['status'] === 'Ongoing'): ?>
                                        <span style="color:#10b981;"><i class="fa-solid fa-circle"></i> Ongoing</span>
                                    <?php else: ?>
                                        <span style="color:#6b7280;"><i class="fa-solid fa-circle"></i> Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($e['creator_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($events)): ?>
                            <tr><td colspan="6" style="text-align:center;">No events found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action === 'add' && $can_manage_events): ?>
                <div style="margin-bottom: 2rem;">
                    <a href="events.php" style="color:var(--text-muted); text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>
                    <h2 style="margin-top: 1rem;">Create New Event</h2>
                </div>

                <div class="form-container">
                    <form action="events.php?action=add" method="POST">
                        <div class="form-group">
                            <label>Event Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="5"></textarea>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Start Date & Time</label>
                                <input type="datetime-local" name="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Event Type</label>
                            <select name="type" class="form-control" required>
                                <option value="Free">Free</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                        <button type="submit" name="add_event" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Event</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-error">You do not have permission to view this page.</div>
            <?php endif; ?>

        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
