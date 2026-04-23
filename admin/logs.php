<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

// Level 60+ (Technical and above) can view logs
$can_view_logs = $_SESSION['role_level'] >= 60;

if (!$can_view_logs) {
    header("Location: dashboard.php");
    exit;
}

// Fetch logs
$logs = $pdo->query("SELECT l.*, u.name as user_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .log-module { font-weight: 600; text-transform: uppercase; font-size: 0.8rem; padding: 0.2rem 0.5rem; background: #e5e7eb; border-radius: 4px; }
        .log-action { font-weight: bold; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="content-area">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                <h2>System Activity Logs</h2>
                <span style="color:var(--text-muted); font-size:0.9rem;">Showing last 100 entries</span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['user_name']); ?></strong></td>
                            <td class="log-action">
                                <?php 
                                    $color = '#374151';
                                    if($log['action_type'] === 'LOGIN') $color = '#3b82f6';
                                    if($log['action_type'] === 'CREATE') $color = '#10b981';
                                    if($log['action_type'] === 'UPDATE') $color = '#f59e0b';
                                ?>
                                <span style="color: <?php echo $color; ?>;"><?php echo htmlspecialchars($log['action_type']); ?></span>
                            </td>
                            <td><span class="log-module"><?php echo htmlspecialchars($log['module']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td style="color:var(--text-muted); font-size:0.85rem;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($logs)): ?>
                        <tr><td colspan="6" style="text-align:center;">No logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
