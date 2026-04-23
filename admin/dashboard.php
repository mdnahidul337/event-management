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
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
            <h2>Overview Dashboard</h2>
            <?php if ($_SESSION['role_level'] >= 50): // EventManager or higher ?>
                <a href="events.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Event</a>
            <?php endif; ?>
        </div>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Members</h3>
                    <h2><?php echo number_format($total_members); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Active Events</h3>
                    <h2><?php echo number_format($active_events); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Pending Payments</h3>
                    <h2><?php echo number_format($pending_payments); ?></h2>
                </div>
                <div class="stat-icon" style="color:#f59e0b;"><i class="fa-solid fa-money-check-dollar"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <h2>৳ <?php echo number_format($total_revenue, 2); ?></h2>
                </div>
                <div class="stat-icon" style="color:#10b981;"><i class="fa-solid fa-chart-line"></i></div>
            </div>
        </div>

        <div class="table-container">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 1rem;">
                <h3>Recent Users</h3>
                <a href="users.php" style="color:var(--primary-color); text-decoration:none; font-size:0.9rem;">View
                    All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span
                                    class="badge badge-<?php echo strtolower($u['role_name']); ?>"><?php echo htmlspecialchars($u['role_name']); ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_users)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
    </div>

    <script src="js/script.js"></script>
</body>

</html>