<?php
session_start();
require_once 'includes/db_connect.php';

// Determine user status for visibility
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    // Logged in members can see 'Public' and 'Members' posts
    $stmt = $pdo->query("SELECT p.*, u.name as author_name, r.name as author_role 
                         FROM posts p 
                         JOIN users u ON p.created_by = u.id 
                         JOIN roles r ON u.role_id = r.id 
                         ORDER BY p.created_at DESC");
} else {
    // Guests can only see 'Public' posts
    $stmt = $pdo->query("SELECT p.*, u.name as author_name, r.name as author_role 
                         FROM posts p 
                         JOIN users u ON p.created_by = u.id 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE p.visibility = 'Public' 
                         ORDER BY p.created_at DESC");
}
$posts = $stmt->fetchAll();

$settings = [];
$stmt_settings = $pdo->query("SELECT * FROM settings");
while ($row = $stmt_settings->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$site_name = $settings['site_name'] ?? 'SCC.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Feed - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-light: #f3f4f6;
            --card-bg: #ffffff;
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

        .feed-container { max-width: 800px; margin: 4rem auto; padding: 0 5%; flex: 1; width: 100%; }
        
        .post-card { background: var(--card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 3rem; }
        
        .post-header { padding: 1.5rem; display: flex; align-items: center; gap: 1rem; border-bottom: 1px solid #f3f4f6; }
        .post-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .post-author { font-weight: 700; color: var(--text-dark); display: block; }
        .post-meta { font-size: 0.85rem; color: var(--text-light); }
        .badge { padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; color: white; background: var(--primary); margin-left: 0.5rem; }

        .post-content { padding: 1.5rem; }
        .post-title { font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-dark); }
        .post-desc { color: var(--text-dark); line-height: 1.6; white-space: pre-wrap; }
        
        .post-image { width: 100%; max-height: 500px; object-fit: cover; display: block; }

        .post-footer { padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6; display: flex; gap: 1.5rem; color: var(--text-light); font-weight: 500;}
        .post-action { cursor: pointer; transition: color 0.3s; display: flex; align-items: center; gap: 0.5rem;}
        .post-action:hover { color: var(--primary); }

        .footer { background: #111827; color: white; text-align: center; padding: 2rem; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <header class="page-header">
        <h1>Latest Updates</h1>
        <p>News, announcements, and moments from the club.</p>
    </header>

    <div class="feed-container">
        <?php if(!$is_logged_in): ?>
            <div style="background:#e0e7ff; color:#3730a3; padding:1rem; border-radius:8px; margin-bottom:2rem; text-align:center;">
                <i class="fa-solid fa-lock"></i> Some posts are hidden. <a href="login.php" style="color:#312e81; font-weight:bold;">Log in</a> to view exclusive member-only content!
            </div>
        <?php endif; ?>

        <?php foreach($posts as $post): ?>
        <div class="post-card">
            <div class="post-header">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['author_name']); ?>&background=random&color=fff" alt="Avatar" class="post-avatar">
                <div>
                    <span class="post-author"><?php echo htmlspecialchars($post['author_name']); ?> <span class="badge"><?php echo htmlspecialchars($post['author_role']); ?></span></span>
                    <span class="post-meta"><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y \a\t h:i A', strtotime($post['created_at'])); ?> • <i class="fa-solid <?php echo $post['visibility'] === 'Public' ? 'fa-globe' : 'fa-user-group'; ?>"></i> <?php echo $post['visibility']; ?></span>
                </div>
            </div>
            
            <div class="post-content">
                <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                <div class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></div>
            </div>

            <?php if(!empty($post['image'])): ?>
                <img src="assets/image/Post-image/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
            <?php endif; ?>

            <div class="post-footer">
                <div class="post-action" onclick="alert('Like functionality coming soon!')"><i class="fa-regular fa-heart"></i> Like</div>
                <div class="post-action" onclick="alert('Comments coming soon!')"><i class="fa-regular fa-comment"></i> Comment</div>
                <div class="post-action" onclick="alert('Link copied!')"><i class="fa-solid fa-share-nodes"></i> Share</div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($posts)): ?>
            <div style="text-align: center; color: var(--text-light); padding: 3rem;">
                <h2>No posts found.</h2>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All Rights Reserved.</p>
    </footer>

</body>
</html>
