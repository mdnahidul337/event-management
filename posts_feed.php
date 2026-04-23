<?php
session_start();
require_once 'includes/db_connect.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_likes (
            id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_like (post_id, user_id)
        );
        CREATE TABLE IF NOT EXISTS post_comments (
            id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, user_id INT NOT NULL,
            comment TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
} catch(PDOException $e){}

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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container" style="max-width: 800px;">
        <?php if(isset($_SESSION['role_level']) && $_SESSION['role_level'] >= 40): ?>
            <div class="post-card" style="padding: 1.5rem; margin-bottom: 2rem;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=4f46e5&color=fff" class="post-avatar" style="width: 40px; height: 40px;">
                    <a href="admin/posts.php?action=add" style="flex: 1; background: #f3f4f6; padding: 0.75rem 1.5rem; border-radius: 30px; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: background 0.2s;">
                        What's on your mind, <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>?
                    </a>
                </div>
                <div style="display: flex; gap: 1.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <a href="admin/posts.php?action=add" style="color: #10b981; text-decoration: none; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-image"></i> Photo
                    </a>
                    <a href="admin/posts.php?action=add" style="color: #f59e0b; text-decoration: none; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-video"></i> Video
                    </a>
                    <a href="admin/posts.php?action=add" style="color: #4f46e5; text-decoration: none; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-calendar-day"></i> Event
                    </a>
                </div>
            </div>
        <?php endif; ?>
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
                    <span class="post-author"><?php echo htmlspecialchars($post['author_name']); ?> <span class="badge badge-primary"><?php echo htmlspecialchars($post['author_role']); ?></span></span>
                    <span class="post-meta"><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y \a\t h:i A', strtotime($post['created_at'])); ?> • <span class="badge <?php echo $post['visibility'] === 'Public' ? 'badge-success' : 'badge-warning'; ?>"><i class="fa-solid <?php echo $post['visibility'] === 'Public' ? 'fa-globe' : 'fa-user-group'; ?>"></i> <?php echo $post['visibility']; ?></span></span>
                </div>
            </div>
            
            <div class="post-content">
                <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                <div class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></div>
            </div>

            <?php if(!empty($post['image'])): ?>
                <img src="assets/image/Post-image/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
            <?php endif; ?>

            <?php
            // Get Likes
            $stmt_likes = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
            $stmt_likes->execute([$post['id']]);
            $likes_count = $stmt_likes->fetchColumn();

            $is_liked = false;
            if ($is_logged_in) {
                $stmt_liked = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
                $stmt_liked->execute([$post['id'], $_SESSION['user_id']]);
                $is_liked = (bool)$stmt_liked->fetchColumn();
            }

            // Get Comments
            $stmt_comments = $pdo->prepare("SELECT c.*, u.name as author_name FROM post_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
            $stmt_comments->execute([$post['id']]);
            $comments = $stmt_comments->fetchAll();
            ?>
            <div class="post-footer">
                <div class="post-action <?php echo $is_liked ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                    <i class="fa-<?php echo $is_liked ? 'solid' : 'regular'; ?> fa-heart"></i> <span class="like-count"><?php echo $likes_count; ?></span> Likes
                </div>
                <div class="post-action" onclick="toggleComments(<?php echo $post['id']; ?>)">
                    <i class="fa-regular fa-comment"></i> <span id="comment-count-<?php echo $post['id']; ?>"><?php echo count($comments); ?></span> Comments
                </div>
                <div class="post-action" onclick="sharePost(<?php echo $post['id']; ?>)">
                    <i class="fa-solid fa-share-nodes"></i> Share
                </div>
            </div>

            <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                <div id="comment-list-<?php echo $post['id']; ?>">
                    <?php foreach($comments as $comment): ?>
                    <div class="comment">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['author_name']); ?>&background=random&color=fff" class="comment-avatar">
                        <div class="comment-box">
                            <div class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                            <div class="comment-text"><?php echo htmlspecialchars($comment['comment']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if($is_logged_in): ?>
                <div class="comment-input-container">
                    <input type="text" id="comment-input-<?php echo $post['id']; ?>" class="comment-input" placeholder="Write a comment...">
                    <button class="btn-comment" onclick="postComment(<?php echo $post['id']; ?>)">Post</button>
                </div>
                <?php else: ?>
                <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-top: 1rem;"><a href="login.php">Log in</a> to write a comment.</p>
                <?php endif; ?>
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

    <script>
    function toggleLike(postId, element) {
        fetch('post_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=toggle_like&post_id=' + postId
        }).then(r=>r.json()).then(res => {
            if(res.success) {
                element.querySelector('.like-count').innerText = res.total;
                if(res.liked) {
                    element.classList.add('liked');
                    element.querySelector('i').classList.replace('fa-regular', 'fa-solid');
                } else {
                    element.classList.remove('liked');
                    element.querySelector('i').classList.replace('fa-solid', 'fa-regular');
                }
            } else {
                alert(res.message);
            }
        });
    }

    function toggleComments(postId) {
        const el = document.getElementById('comments-' + postId);
        el.style.display = el.style.display === 'block' ? 'none' : 'block';
    }

    function postComment(postId) {
        const input = document.getElementById('comment-input-' + postId);
        if(!input.value.trim()) return;

        fetch('post_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=add_comment&post_id=' + postId + '&comment=' + encodeURIComponent(input.value)
        }).then(r=>r.json()).then(res => {
            if(res.success) {
                document.getElementById('comment-count-' + postId).innerText = res.total;
                const html = `<div class="comment"><img src="https://ui-avatars.com/api/?name=${res.avatar}&background=random&color=fff" class="comment-avatar"><div class="comment-box"><div class="comment-author">${res.author}</div><div class="comment-text">${res.comment}</div></div></div>`;
                document.getElementById('comment-list-' + postId).insertAdjacentHTML('beforeend', html);
                input.value = '';
            } else {
                alert(res.message);
            }
        });
    }

    function sharePost(postId) {
        const url = window.location.origin + window.location.pathname + '#post-' + postId;
        navigator.clipboard.writeText(url);
        alert('Link copied to clipboard!');
    }
    </script>
</body>
</html>
