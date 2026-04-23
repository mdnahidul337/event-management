<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch up to 3 upcoming events for the homepage
$upcoming_events = $pdo->query("SELECT * FROM events WHERE status = 'Upcoming' ORDER BY start_date ASC LIMIT 3")->fetchAll();

// Fetch settings (e.g. site_name)
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
    <title><?php echo htmlspecialchars($site_name); ?> - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #c084fc;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); }
        
        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none;}
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: var(--primary); }
        .auth-buttons a {
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-login { color: var(--primary); margin-right: 1rem; }
        .btn-register { background: var(--primary); color: white; }
        .btn-register:hover { background: var(--primary-dark); }

        /* Hero Section */
        .hero {
            padding: 10rem 5% 5rem;
            text-align: center;
            background: linear-gradient(135deg, #f3f4f6 0%, #e0e7ff 100%);
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .hero h1 { font-size: 4rem; font-weight: 800; margin-bottom: 1.5rem; line-height: 1.2; color: #111827; }
        .hero h1 span { color: var(--primary); }
        .hero p { font-size: 1.2rem; color: var(--text-light); max-width: 600px; margin-bottom: 2.5rem; }
        .hero-btns .btn {
            display: inline-block;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 0 0.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .hero-btns .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4); }
        .hero-btns .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6); }
        .hero-btns .btn-outline { border: 2px solid var(--primary); color: var(--primary); }
        .hero-btns .btn-outline:hover { background: var(--primary); color: white; }

        /* Features/Events Preview */
        .section-title { text-align: center; margin: 5rem 0 3rem; font-size: 2.5rem; font-weight: 700; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; padding: 0 5%; margin-bottom: 5rem; }
        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .event-card:hover { transform: translateY(-5px); }
        .event-img { width: 100%; height: 200px; background: #ddd; object-fit: cover; }
        .event-content { padding: 1.5rem; }
        .event-date { color: var(--primary); font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .event-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        .event-desc { color: var(--text-light); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5; }
        
        .footer { background: #111827; color: white; text-align: center; padding: 2rem; margin-top: auto; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <header class="hero">
        <h1>Connect, Learn & <span>Build Together</span></h1>
        <p>The ultimate event management and social platform for the Computer Club. Join workshops, track activities, and connect with like-minded tech enthusiasts.</p>
        <div class="hero-btns">
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-primary">Join the Club</a>
            <?php endif; ?>
            <a href="public_events.php" class="btn btn-outline">Explore Events</a>
        </div>
    </header>

    <section id="events">
        <h2 class="section-title">Upcoming Events</h2>
        <div class="events-grid">
            <?php foreach($upcoming_events as $event): ?>
            <div class="event-card">
                <img src="https://images.unsplash.com/photo-1515187029135-18ee286d815b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Event" class="event-img">
                <div class="event-content">
                    <div class="event-date"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y • h:i A', strtotime($event['start_date'])); ?></div>
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="event-desc"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                    <a href="join_event.php?id=<?php echo $event['id']; ?>" class="btn-register" style="display:block; text-align:center; padding:0.6rem; border-radius:6px; text-decoration:none; color:white; background:var(--primary);">
                        Join Event (<?php echo $event['type']; ?>)
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($upcoming_events)): ?>
                <div style="grid-column: 1 / -1; text-align: center; color: var(--text-light); padding: 3rem;">
                    <h3>No upcoming events at the moment. Check back later!</h3>
                </div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-bottom: 5rem;">
            <a href="public_events.php" class="btn-login" style="font-weight: 600; text-decoration: none;">View All Events →</a>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All Rights Reserved.</p>
    </footer>

</body>
</html>
