<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] < 50) {
    header("Location: ../login.php"); exit;
}
require_once '../includes/db_connect.php';

$total_events     = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcoming_events  = $pdo->query("SELECT COUNT(*) FROM events WHERE status='Upcoming'")->fetchColumn();
$ongoing_events   = $pdo->query("SELECT COUNT(*) FROM events WHERE status='Ongoing'")->fetchColumn();
$completed_events = $pdo->query("SELECT COUNT(*) FROM events WHERE status='Completed'")->fetchColumn();

$events_data = $pdo->query("
    SELECT e.*, u.name as creator_name,
           COUNT(p.id) as reg_count,
           SUM(CASE WHEN p.status='Approved' THEN p.amount ELSE 0 END) as revenue
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    LEFT JOIN payments p ON p.event_id = e.id
    GROUP BY e.id ORDER BY e.start_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager Dashboard - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem; margin-bottom:2rem; }
        .kpi-card { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); text-align:center; border-top:4px solid var(--primary-color); }
        .kpi-value { font-size:2.5rem; font-weight:800; }
        .kpi-label { font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem; }
        .events-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; }
        .event-card { background:var(--card-bg); border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); }
        .event-card-img { width:100%; height:140px; object-fit:cover; background:#e5e7eb; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:2rem; }
        .event-card-body { padding:1rem; }
        .event-card-footer { padding:0.75rem 1rem; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; font-size:0.82rem; color:var(--text-muted); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-weight: 800; font-size: 1.75rem;">Event Management</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Track registrations, revenue, and upcoming milestones.</p>
            </div>
            <a href="events.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create New Event</a>
        </div>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Events</h3>
                    <h2><?php echo $total_events; ?></h2>
                </div>
                <div class="stat-icon" style="background:#f1f5f9; color:#475569;"><i class="fa-solid fa-layer-group"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Upcoming</h3>
                    <h2><?php echo $upcoming_events; ?></h2>
                </div>
                <div class="stat-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="fa-solid fa-calendar-plus"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Ongoing</h3>
                    <h2><?php echo $ongoing_events; ?></h2>
                </div>
                <div class="stat-icon" style="background:#dcfce7; color:#16a34a;"><i class="fa-solid fa-spinner"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Completed</h3>
                    <h2><?php echo $completed_events; ?></h2>
                </div>
                <div class="stat-icon" style="background:#f1f5f9; color:#94a3b8;"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>

        <h3 style="margin-bottom: 1.5rem; font-weight: 700;">Live Event Performance</h3>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
            <?php foreach ($events_data as $e): ?>
            <div class="stat-card" style="flex-direction:column; align-items:stretch; padding:0; overflow:hidden;">
                <?php if ($e['image']): ?>
                    <img src="<?php echo (strpos($e['image'], 'http') === 0) ? $e['image'] : '../assets/image/Events/' . htmlspecialchars($e['image']); ?>" style="width:100%; height:160px; object-fit:cover;" alt="">
                <?php else: ?>
                    <div style="width:100%; height:160px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:3rem;"><i class="fa-solid fa-calendar-day"></i></div>
                <?php endif; ?>
                
                <div style="padding:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                        <h4 style="font-weight:700; font-size:1.1rem; line-height:1.3;"><?php echo htmlspecialchars($e['title']); ?></h4>
                        <span class="badge" style="background:<?php echo $e['status']=='Upcoming'?'#dbeafe':($e['status']=='Ongoing'?'#dcfce7':'#f1f5f9'); ?>; color:<?php echo $e['status']=='Upcoming'?'#1e40af':($e['status']=='Ongoing'?'#166534':'#475569'); ?>;"><?php echo $e['status']; ?></span>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border);">
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Registrations</div>
                            <div style="font-weight:800; font-size:1.2rem;"><?php echo $e['reg_count']; ?></div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Revenue</div>
                            <div style="font-weight:800; font-size:1.2rem; color:#16a34a;">৳<?php echo number_format($e['revenue'],0); ?></div>
                        </div>
                    </div>
                    
                    <div style="margin-top:1.5rem; display:flex; gap:0.5rem;">
                        <a href="events.php?action=edit&id=<?php echo $e['id']; ?>" class="btn btn-primary" style="flex:1; justify-content:center;"><i class="fa-solid fa-pen"></i> Manage</a>
                        <a href="../public_events.php#event-<?php echo $e['id']; ?>" target="_blank" class="btn" style="background:var(--bg-main);"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
></div>
    <script src="js/script.js"></script>
</body>
</html>
