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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <header class="hero">
        <h1>Connect, Learn & <span>Build Together</span></h1>
        <p>The ultimate event management and social platform for the Computer Club. Join workshops, track activities, and connect with like-minded tech enthusiasts.</p>
        <div class="hero-btns">
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn-register" style="padding: 1rem 2rem; font-size: 1.1rem;">Join the Club</a>
            <?php endif; ?>
            <a href="public_events.php" class="btn-login" style="border: 2px solid var(--primary); padding: 0.9rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 700;">Explore Events</a>
        </div>
    </header>

    <div class="container">
        <h2 class="section-title">Upcoming Events</h2>
        <div class="card-grid">
            <?php foreach($upcoming_events as $event): ?>
            <div class="card">
                <?php 
                $img_src = 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                if ($event['image']) {
                    $img_src = (strpos($event['image'], 'http') === 0) ? $event['image'] : 'assets/image/Events/' . $event['image'];
                }
                ?>
                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" style="width:100%; height:200px; object-fit:cover;">
                <div style="padding: 1.5rem;">
                    <div style="color:var(--primary); font-weight:700; font-size:0.85rem; margin-bottom:0.5rem;"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['start_date'])); ?></div>
                    <h3 style="font-size:1.25rem; font-weight:800; margin-bottom:0.75rem; color:#111827;"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem; line-height:1.6;"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                    <a href="join_event.php?id=<?php echo $event['id']; ?>" class="btn-register" style="display:block; text-align:center;">
                        View Event
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
            <?php if(empty($upcoming_events)): ?>
                <div style="text-align: center; color: var(--text-light); padding: 3rem; grid-column: 1 / -1;">
                    <h3>No upcoming events at the moment. Check back later!</h3>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 3rem; margin-bottom: 5rem;">
            <a href="public_events.php" class="btn-login" style="font-weight: 600; text-decoration: none; border: 2px solid var(--primary); padding: 0.8rem 2rem; border-radius: 8px;">View All Events →</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
