<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch all events
$events = $pdo->query("SELECT * FROM events ORDER BY start_date DESC")->fetchAll();

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$site_name = $settings['site_name'] ?? 'SCC.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-light: #f9fafb;
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
        .auth-buttons a { padding: 0.5rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .btn-login { color: var(--primary); margin-right: 1rem; }
        .btn-register { background: var(--primary); color: white; }

        .page-header { padding: 8rem 5% 4rem; text-align: center; background: linear-gradient(135deg, #1f2937 0%, #111827 100%); color: white; }
        .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }

        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; padding: 4rem 5%; flex: 1; }
        .event-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: flex; flex-direction: column;}
        .event-img { width: 100%; height: 200px; background: #ddd; object-fit: cover; }
        .event-content { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .event-date { color: var(--primary); font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .event-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        .event-desc { color: var(--text-light); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5; flex: 1; }
        
        .footer { background: #111827; color: white; text-align: center; padding: 2rem; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <header class="page-header">
        <h1>All Events</h1>
        <p>Discover workshops, seminars, and hackathons hosted by our club.</p>
    </header>

    <div class="events-grid">
        <?php foreach($events as $event): ?>
        <div class="event-card">
            <img src="https://images.unsplash.com/photo-1515187029135-18ee286d815b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Event" class="event-img">
            <div class="event-content">
                <div class="event-date"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y • h:i A', strtotime($event['start_date'])); ?></div>
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <p class="event-desc"><?php echo htmlspecialchars(substr($event['description'], 0, 150)) . '...'; ?></p>
                
                <div style="margin-top: 1rem;">
                    <?php if($event['status'] === 'Upcoming' || $event['status'] === 'Ongoing'): ?>
                        <a href="join_event.php?id=<?php echo $event['id']; ?>" class="btn-register" style="display:block; text-align:center; padding:0.6rem; text-decoration:none;">
                            Join Event (<?php echo $event['type']; ?>)
                        </a>
                    <?php else: ?>
                        <div style="background:#f3f4f6; color:#6b7280; text-align:center; padding:0.6rem; border-radius:6px; font-weight:600;">Completed</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($events)): ?>
            <div style="grid-column: 1 / -1; text-align: center; color: var(--text-light); padding: 3rem;">
                <h2>No events found.</h2>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All Rights Reserved.</p>
    </footer>

</body>
</html>
