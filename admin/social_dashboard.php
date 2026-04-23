<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] < 40) {
    header("Location: ../login.php"); exit;
}
require_once '../includes/db_connect.php';

$total_posts   = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$public_posts  = $pdo->query("SELECT COUNT(*) FROM posts WHERE visibility='Public'")->fetchColumn();
$members_posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE visibility='Members'")->fetchColumn();
$total_likes   = $pdo->query("SELECT COUNT(*) FROM post_likes")->fetchColumn();
$total_comments= $pdo->query("SELECT COUNT(*) FROM post_comments")->fetchColumn();

// Top posts by engagement
$top_posts = $pdo->query("
    SELECT p.*, u.name as author,
           COUNT(DISTINCT pl.id) as likes,
           COUNT(DISTINCT pc.id) as comments
    FROM posts p
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN post_likes pl ON pl.post_id = p.id
    LEFT JOIN post_comments pc ON pc.post_id = p.id
    GROUP BY p.id ORDER BY (COUNT(DISTINCT pl.id)+COUNT(DISTINCT pc.id)) DESC LIMIT 5
")->fetchAll();

// Recent posts
$recent_posts = $pdo->query("
    SELECT p.*, u.name as author FROM posts p
    LEFT JOIN users u ON p.created_by = u.id
    ORDER BY p.created_at DESC LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media Manager Dashboard - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1.5rem; margin-bottom:2rem; }
        .kpi-card { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); display:flex; justify-content:space-between; align-items:center; }
        .kpi-value { font-size:2rem; font-weight:800; }
        .kpi-label { font-size:0.82rem; color:var(--text-muted); }
        .kpi-icon  { font-size:2rem; opacity:0.15; }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
        .panel { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); }
        .post-row { display:flex; align-items:center; gap:1rem; padding:0.75rem 0; border-bottom:1px solid var(--border-color); }
        .post-row:last-child { border:none; }
        .post-thumb { width:60px; height:40px; object-fit:cover; border-radius:6px; background:#e5e7eb; flex-shrink:0; }
        .engagement-bar { background:#e5e7eb; border-radius:20px; height:6px; margin-top:4px; }
        .engagement-fill { background:linear-gradient(90deg,#4f46e5,#c084fc); border-radius:20px; height:6px; }
        @media(max-width:768px){ .two-col { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-weight: 800; font-size: 1.75rem;">Social Hub</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Monitor engagement, reach, and community interaction.</p>
            </div>
            <a href="posts.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Social Post</a>
        </div>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Posts</h3>
                    <h2><?php echo $total_posts; ?></h2>
                </div>
                <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="fa-solid fa-newspaper"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Public Reach</h3>
                    <h2><?php echo $public_posts; ?></h2>
                </div>
                <div class="stat-icon" style="background:#f0f9ff; color:#0ea5e9;"><i class="fa-solid fa-globe"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Likes</h3>
                    <h2><?php echo number_format($total_likes); ?></h2>
                </div>
                <div class="stat-icon" style="background:#fff1f2; color:#f43f5e;"><i class="fa-solid fa-heart"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Comments</h3>
                    <h2><?php echo number_format($total_comments); ?></h2>
                </div>
                <div class="stat-icon" style="background:#fdf4ff; color:#d946ef;"><i class="fa-solid fa-comments"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="table-container">
                <h3 style="margin-bottom: 2rem; font-weight:700;"><i class="fa-solid fa-fire"></i> High Engagement Posts</h3>
                <?php $max_eng = max(array_sum([array_column($top_posts,'likes')[0]??0, array_column($top_posts,'comments')[0]??0]),1); ?>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <?php foreach ($top_posts as $p): ?>
                    <div style="display:flex; align-items:center; gap:1.25rem; padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border); background:var(--bg-main);">
                        <?php if ($p['image']): ?>
                            <img src="../assets/image/<?php echo htmlspecialchars($p['image']); ?>" style="width:64px; height:48px; object-fit:cover; border-radius:8px;" alt="">
                        <?php else: ?>
                            <div style="width:64px; height:48px; background:white; display:flex; align-items:center; justify-content:center; color:#cbd5e1; border-radius:8px;"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                        
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:0.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($p['title']); ?></div>
                            <div style="margin-top:0.5rem; display:flex; gap:1rem;">
                                <div style="font-size:0.75rem; font-weight:600;"><i class="fa-solid fa-heart" style="color:#f43f5e;"></i> <?php echo $p['likes']; ?></div>
                                <div style="font-size:0.75rem; font-weight:600;"><i class="fa-solid fa-comment" style="color:#6366f1;"></i> <?php echo $p['comments']; ?></div>
                            </div>
                            <div style="background:white; height:6px; border-radius:10px; margin-top:0.75rem;">
                                <div style="background:linear-gradient(to right, #6366f1, #ec4899); width:<?php echo min(100, round((($p['likes']+$p['comments'])/max($max_eng,1))*100)); ?>%; height:100%; border-radius:10px;"></div>
                            </div>
                        </div>
                        <a href="posts.php?action=edit&id=<?php echo $p['id']; ?>" class="btn" style="background:white; padding:0.5rem;"><i class="fa-solid fa-pen"></i></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="table-container">
                <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                    <h3 style="font-weight:700;"><i class="fa-solid fa-clock-rotate-left"></i> Latest Content</h3>
                    <a href="posts.php" class="btn" style="color:var(--primary); font-size:0.85rem; font-weight:700;">All Posts</a>
                </div>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($recent_posts as $p): ?>
                    <div style="display:flex; align-items:center; gap:1rem; padding:0.75rem; border-bottom:1px solid var(--border);">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($p['title']); ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">By <?php echo htmlspecialchars($p['author']); ?> • <?php echo date('M d', strtotime($p['created_at'])); ?></div>
                        </div>
                        <span class="badge" style="background:<?php echo $p['visibility']==='Public' ? '#dcfce7' : '#fef3c7'; ?>; color:<?php echo $p['visibility']==='Public' ? '#166534' : '#92400e'; ?>;"><?php echo $p['visibility']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
div></div>
    <script src="js/script.js"></script>
</body>
</html>
