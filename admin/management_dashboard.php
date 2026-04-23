<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] < 70) {
    header("Location: ../login.php"); exit;
}
require_once '../includes/db_connect.php';

// Management stats
$total_members   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$members_by_role = $pdo->query("SELECT r.name, COUNT(u.id) as cnt FROM users u JOIN roles r ON u.role_id=r.id GROUP BY r.name ORDER BY r.level DESC")->fetchAll();
$new_members_week= $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$active_events   = $pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('Upcoming','Ongoing')")->fetchColumn();
$total_events    = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_posts     = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

// Recent activity logs
$logs = $pdo->query("
    SELECT l.*, u.name as user_name FROM activity_logs l
    LEFT JOIN users u ON l.user_id=u.id
    ORDER BY l.created_at DESC LIMIT 15
")->fetchAll();

// Department breakdown
$depts = $pdo->query("SELECT department, COUNT(*) as cnt FROM users WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY cnt DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Dashboard - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem; margin-bottom:2rem; }
        .kpi-card { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); display:flex; justify-content:space-between; align-items:center; border-top:4px solid var(--primary-color); }
        .kpi-value { font-size:2rem; font-weight:800; }
        .kpi-label { font-size:0.85rem; color:var(--text-muted); }
        .kpi-icon  { font-size:2rem; opacity:0.15; }
        .two-col   { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem; }
        .panel     { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); }
        .role-bar  { margin-bottom:1rem; }
        .role-bar-label { display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.3rem; }
        .role-bar-track { background:#e5e7eb; border-radius:20px; height:8px; }
        .role-bar-fill  { background:var(--primary-color); border-radius:20px; height:8px; transition:width 0.8s ease; }
        .log-item { padding:0.6rem 0; border-bottom:1px solid var(--border-color); font-size:0.85rem; }
        .log-item:last-child { border:none; }
        @media(max-width:768px) { .two-col { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-weight: 800; font-size: 1.75rem;">Management Center</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Team metrics, department breakdown, and audit trails.</p>
            </div>
            <a href="users.php" class="btn btn-primary"><i class="fa-solid fa-users-gear"></i> Manage Members</a>
        </div>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Members</h3>
                    <h2><?php echo $total_members; ?></h2>
                </div>
                <div class="stat-icon" style="background:#eef2ff; color:#6366f1;"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>New This Week</h3>
                    <h2><?php echo $new_members_week; ?></h2>
                </div>
                <div class="stat-icon" style="background:#dcfce7; color:#10b981;"><i class="fa-solid fa-user-plus"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Active Events</h3>
                    <h2><?php echo $active_events; ?></h2>
                </div>
                <div class="stat-icon" style="background:#fffbeb; color:#f59e0b;"><i class="fa-solid fa-calendar-day"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Posts</h3>
                    <h2><?php echo $total_posts; ?></h2>
                </div>
                <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="fa-solid fa-newspaper"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="table-container">
                <h3 style="margin-bottom: 2rem; font-weight:700;"><i class="fa-solid fa-id-badge"></i> Role Distribution</h3>
                <?php $max = max(array_column($members_by_role,'cnt') ?: [1]); ?>
                <?php foreach ($members_by_role as $r): ?>
                <div class="role-bar" style="margin-bottom:1.5rem;">
                    <div class="role-bar-label" style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.9rem;">
                        <span style="font-weight:600;"><?php echo htmlspecialchars($r['name']); ?></span>
                        <span style="color:var(--text-muted);"><?php echo $r['cnt']; ?> Members</span>
                    </div>
                    <div style="background:var(--bg-main); height:8px; border-radius:10px;">
                        <div style="background:var(--primary); width:<?php echo round(($r['cnt']/$max)*100); ?>%; height:100%; border-radius:10px; transition:width 1s ease;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="table-container">
                <h3 style="margin-bottom: 2rem; font-weight:700;"><i class="fa-solid fa-building-columns"></i> Departments</h3>
                <?php if ($depts): ?>
                    <canvas id="deptChart" height="250"></canvas>
                <?php else: ?>
                    <div style="text-align:center; padding:3rem; color:var(--text-muted);">
                        <i class="fa-solid fa-folder-open" style="font-size:2rem; display:block; margin-bottom:1rem;"></i>
                        No department data
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-container" style="margin-top:2rem;">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                <h3 style="font-weight:700;"><i class="fa-solid fa-list-check"></i> System Activity Log</h3>
                <a href="logs.php" class="btn" style="color:var(--primary); font-size:0.85rem; font-weight:700;">Full Audit Trail →</a>
            </div>
            <div style="display:flex; flex-direction:column; gap:0.25rem;">
                <?php foreach ($logs as $log): ?>
                <div style="padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; background:<?php echo $log['action_type']=='DELETE'?'#fff1f2':($log['action_type']=='CREATE'?'#f0fdf4':'transparent'); ?>;">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div style="width:36px; height:36px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fa-solid fa-<?php echo $log['module']=='event'?'calendar':($log['module']=='user'?'user':'bolt'); ?>" style="font-size:0.9rem; color:var(--primary);"></i>
                        </div>
                        <div>
                            <div style="font-size:0.9rem;"><strong style="font-weight:700;"><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></strong> <?php echo htmlspecialchars($log['details']); ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo date('M d, Y • g:i a', strtotime($log['created_at'])); ?></div>
                        </div>
                    </div>
                    <span class="badge" style="background:var(--bg-main); color:var(--text-muted); font-size:0.65rem; border:1px solid var(--border);"><?php echo $log['action_type']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
iv></div>

    <script src="js/script.js"></script>
    <script>
        <?php if ($depts): ?>
        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($depts,'department')); ?>,
                datasets: [{ data: <?php echo json_encode(array_column($depts,'cnt')); ?>, backgroundColor: ['#4f46e5','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#6b7280'], borderWidth:2 }]
            },
            options: { responsive:true, plugins:{ legend:{ position:'right' } } }
        });
        <?php endif; ?>
    </script>
</body>
</html>
