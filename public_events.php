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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <header class="page-header">
        <h1>All Events</h1>
        <p>Discover workshops, seminars, and hackathons hosted by our club.</p>
    </header>

    <div class="container">
        <div class="card-grid">
        <?php foreach($events as $event): ?>
        <div class="event-card">
            <?php 
            $img_src = 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; // Default
            if ($event['image']) {
                $img_src = (strpos($event['image'], 'http') === 0) ? $event['image'] : 'assets/image/Events/' . $event['image'];
            }
            ?>
            <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-img">
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
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
