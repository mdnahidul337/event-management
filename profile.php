<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

// Fetch user details
$stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch joined events / payment status
$stmt = $pdo->prepare("
    SELECT p.*, e.title as event_title, e.start_date, e.location 
    FROM payments p 
    LEFT JOIN events e ON p.event_id = e.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

// Global settings
$global_settings = [];
$stmt_settings = $pdo->query("SELECT * FROM settings");
while ($row = $stmt_settings->fetch()) {
    $global_settings[$row['setting_key']] = $row['setting_value'];
}
$site_name = $global_settings['site_name'] ?? 'SCC Computer Club';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-light: #f3f4f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }
        
        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 5%; background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px); position: fixed; width: 100%; top: 0; z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none;}
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: var(--primary); }
        .auth-buttons a { padding: 0.5rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; background: #ef4444; color: white; }
        
        .profile-header { padding: 10rem 5% 4rem; background: linear-gradient(135deg, #1f2937 0%, #111827 100%); color: white; }
        
        .container { max-width: 1000px; margin: -3rem auto 4rem; padding: 0 5%; flex: 1; width: 100%; }
        
        .profile-card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 2rem; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .badge { padding: 0.3rem 0.8rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; background: #e0e7ff; color: #3730a3; display: inline-block; margin-top: 0.5rem; }
        
        .tickets-section { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .tickets-section h2 { margin-bottom: 1.5rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 1rem; }
        
        .ticket-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .ticket-info h3 { margin-bottom: 0.25rem; }
        .ticket-meta { color: var(--text-light); font-size: 0.9rem; }
        
        .status-badge { padding: 0.4rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; }
        .status-approved { background: #d1fae5; color: #047857; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }

        .footer { background: #111827; color: white; text-align: center; padding: 2rem; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="profile-header">
        <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Member Dashboard</h1>
            <p style="color:#9ca3af;">Manage your profile and track your event tickets.</p>
        </div>
    </div>

    <div class="container">
        
        <div class="profile-card">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=4f46e5&color=fff&size=150" alt="Avatar" class="profile-img">
            <div>
                <h2 style="font-size: 1.8rem;"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p style="color: var(--text-light); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="badge"><i class="fa-solid fa-id-badge"></i> <?php echo htmlspecialchars($user['role_name']); ?></div>
                <?php if($_SESSION['role_level'] > 10): ?>
                    <a href="admin/dashboard.php" style="margin-left: 1rem; color: var(--primary); font-weight:600; text-decoration:none;"><i class="fa-solid fa-gauge"></i> Enter Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="tickets-section">
            <h2><i class="fa-solid fa-ticket"></i> My Event Tickets & Registrations</h2>
            
            <?php if(empty($tickets)): ?>
                <div style="text-align:center; padding: 3rem; color:var(--text-light);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 3rem; margin-bottom: 1rem; color:#d1d5db;"></i>
                    <p>You haven't joined any events yet.</p>
                    <a href="public_events.php" style="display:inline-block; margin-top:1rem; padding:0.8rem 1.5rem; background:var(--primary); color:white; border-radius:6px; text-decoration:none; font-weight:600;">Browse Events</a>
                </div>
            <?php else: ?>
                <?php foreach($tickets as $t): ?>
                    <div class="ticket-card">
                        <div class="ticket-info">
                            <h3><?php echo htmlspecialchars($t['event_title'] ?? 'General Registration Fee'); ?></h3>
                            <div class="ticket-meta">
                                <?php if($t['event_title']): ?>
                                    <span style="margin-right: 1rem;"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($t['start_date'])); ?></span>
                                    <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($t['location']); ?></span>
                                <?php endif; ?>
                                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #9ca3af;">
                                    Requested on: <?php echo date('M d, Y', strtotime($t['created_at'])); ?> • Amount: ৳ <?php echo number_format($t['amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <?php if($t['status'] === 'Approved'): ?>
                                <span class="status-badge status-approved"><i class="fa-solid fa-check"></i> Approved</span>
                            <?php elseif($t['status'] === 'Rejected'): ?>
                                <span class="status-badge status-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>
                            <?php else: ?>
                                <span class="status-badge status-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All Rights Reserved.</p>
    </footer>

</body>
</html>
