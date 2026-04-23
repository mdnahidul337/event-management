<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

// Fetch stats
$total_members = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_events = $pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('Upcoming', 'Ongoing')")->fetchColumn();
$pending_payments = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Approved'")->fetchColumn();

// Fetch recent users
$recent_users = $pdo->query("
    SELECT u.name, u.email, r.name as role_name, u.created_at 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCC Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="content-area">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-weight: 800; font-size: 1.75rem;">Overview Dashboard</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Welcome back! Here's what's happening today.</p>
            </div>
            <?php if ($_SESSION['role_level'] >= 50): ?>
                <a href="events.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Event</a>
            <?php endif; ?>
        </div>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Members</h3>
                    <h2><?php echo number_format($total_members); ?></h2>
                </div>
                <div class="stat-icon" style="background:#eef2ff; color:#6366f1;"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Active Events</h3>
                    <h2><?php echo number_format($active_events); ?></h2>
                </div>
                <div class="stat-icon" style="background:#ecfdf5; color:#10b981;"><i class="fa-solid fa-calendar-day"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Pending Payments</h3>
                    <h2><?php echo number_format($pending_payments); ?></h2>
                </div>
                <div class="stat-icon" style="background:#fffbeb; color:#f59e0b;"><i class="fa-solid fa-money-check-dollar"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <h2>৳<?php echo number_format($total_revenue, 2); ?></h2>
                </div>
                <div class="stat-icon" style="background:#fef2f2; color:#ef4444;"><i class="fa-solid fa-chart-line"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="table-container">
                <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                    <h3 style="font-weight: 700;">Recent Members</h3>
                    <a href="users.php" class="btn" style="color:var(--primary); font-size:0.85rem; font-weight:700;">View All →</a>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['name']); ?>&background=random" style="width:32px; height:32px; border-radius:50%;">
                                            <div>
                                                <div style="font-weight:600;"><?php echo htmlspecialchars($u['name']); ?></div>
                                                <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-<?php echo strtolower($u['role_name']); ?>"><?php echo htmlspecialchars($u['role_name']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container">
                <h3 style="margin-bottom: 1.5rem; font-weight: 700;">Quick Actions</h3>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <a href="events.php" class="btn" style="background:var(--bg-main); width:100%; justify-content:flex-start; padding:1rem;"><i class="fa-solid fa-calendar"></i> Manage Events</a>
                    <a href="payments.php" class="btn" style="background:var(--bg-main); width:100%; justify-content:flex-start; padding:1rem;"><i class="fa-solid fa-credit-card"></i> Payment Requests</a>
                    <a href="users.php" class="btn" style="background:var(--bg-main); width:100%; justify-content:flex-start; padding:1rem;"><i class="fa-solid fa-user-plus"></i> Add New User</a>
                    <a href="settings.php" class="btn" style="background:var(--bg-main); width:100%; justify-content:flex-start; padding:1rem;"><i class="fa-solid fa-gears"></i> Club Settings</a>
                </div>
            </div>
        </div>

    </div>
    </div>

    <script src="js/script.js"></script>
</body>

</html>