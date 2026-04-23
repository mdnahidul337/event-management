<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once '../includes/db_connect.php';

$action = $_GET['action'] ?? 'list';
$view_post_id = intval($_GET['post_id'] ?? 0);
$error = '';
$success = '';
$can_manage_posts = $_SESSION['role_level'] >= 40;

// ─── Auto-migrate comment replies ─────────────────────────────────────────
try { $pdo->exec("ALTER TABLE post_comments ADD COLUMN parent_id INT DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE post_comments ADD FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE CASCADE"); } catch (PDOException $e) {}

// ─── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_posts) {

    if (isset($_POST['add_post'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $visibility = $_POST['visibility'];
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/image/Post-image/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) $image = $filename;
        }
        $stmt = $pdo->prepare("INSERT INTO posts (title, description, image, visibility, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $image, $visibility, $_SESSION['user_id']])) {
            $post_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'posts', ?, ?)")
                ->execute([$_SESSION['user_id'], "Created post ID $post_id", $_SERVER['REMOTE_ADDR']]);
            $success = "Post published!";
            $action = 'list';
        } else { $error = "Failed to add post."; }
    }

    if (isset($_POST['delete_post'])) {
        $pid = intval($_POST['post_id']);
        $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$pid]);
        $success = "Post deleted.";
        $action = 'list';
    }

    if (isset($_POST['delete_comment'])) {
        $cid = intval($_POST['comment_id']);
        $pid = intval($_POST['post_id_ref']);
        $pdo->prepare("DELETE FROM post_comments WHERE id=?")->execute([$cid]);
        $success = "Comment deleted.";
        $action = 'comments';
        $view_post_id = $pid;
    }

    if (isset($_POST['reply_comment'])) {
        $parent_id = intval($_POST['parent_id']);
        $post_id_r = intval($_POST['post_id_ref']);
        $reply_text = trim($_POST['reply_text']);
        if ($reply_text) {
            $pdo->prepare("INSERT INTO post_comments (post_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)")
                ->execute([$post_id_r, $_SESSION['user_id'], $reply_text, $parent_id]);
            $success = "Reply posted.";
        }
        $action = 'comments';
        $view_post_id = $post_id_r;
    }
}

// ─── Fetch data ──────────────────────────────────────────────────────────────
$posts    = [];
$comments = [];
$post_info= null;

if ($action === 'list') {
    $posts = $pdo->query("
        SELECT p.*, u.name as author_name,
               COUNT(DISTINCT pl.id) as likes,
               COUNT(DISTINCT pc.id) as comments
        FROM posts p
        JOIN users u ON p.created_by = u.id
        LEFT JOIN post_likes pl ON pl.post_id = p.id
        LEFT JOIN post_comments pc ON pc.post_id = p.id
        GROUP BY p.id ORDER BY p.created_at DESC
    ")->fetchAll();
}

if ($action === 'comments' && $view_post_id) {
    $s = $pdo->prepare("SELECT p.*, u.name as author_name FROM posts p JOIN users u ON p.created_by=u.id WHERE p.id=?");
    $s->execute([$view_post_id]);
    $post_info = $s->fetch();

    // Fetch all comments (including replies)
    $cs = $pdo->prepare("
        SELECT c.*, u.name as commenter_name, u.profile_pic
        FROM post_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $cs->execute([$view_post_id]);
    $all_comments = $cs->fetchAll();

    // Build tree: parent => [children]
    $comment_tree = [];
    $comment_map  = [];
    foreach ($all_comments as $c) {
        $c['children'] = [];
        $comment_map[$c['id']] = $c;
    }
    foreach ($comment_map as $id => $c) {
        if ($c['parent_id']) {
            $comment_map[$c['parent_id']]['children'][] = &$comment_map[$id];
        } else {
            $comment_tree[] = &$comment_map[$id];
        }
    }
    $comments = $comment_tree;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background:var(--card-bg); padding:2rem; border-radius:var(--radius); box-shadow:var(--shadow-sm); max-width:800px; }
        .form-group { margin-bottom:1.5rem; }
        .form-group label { display:block; margin-bottom:0.5rem; font-weight:600; }
        .form-control { width:100%; padding:0.75rem; border:1.5px solid var(--border-color); border-radius:var(--radius); outline:none; background:var(--bg-color); color:var(--text-main); }
        .form-control:focus { border-color:var(--primary-color); }
        .alert { padding:1rem 1.2rem; border-radius:var(--radius); margin-bottom:1.5rem; font-weight:500; }
        .alert-success { background:#d1fae5; color:#047857; border-left:4px solid #10b981; }
        .alert-error   { background:#fee2e2; color:#b91c1c; border-left:4px solid #ef4444; }
        .post-img { width:60px; height:60px; object-fit:cover; border-radius:6px; }
        .btn-icon { padding:0.3rem 0.7rem; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.8rem; }
        .btn-view   { background:#e0e7ff; color:#3730a3; }
        .btn-delete { background:#fee2e2; color:#b91c1c; }

        /* Comments UI */
        .comment-block { background:var(--card-bg); border-radius:var(--radius); padding:1rem 1.2rem; margin-bottom:1rem; border:1px solid var(--border-color); }
        .comment-block.reply-block { margin-left:3rem; background:var(--bg-color); border-left:3px solid var(--primary-color); }
        .comment-meta { font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem; }
        .comment-avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; margin-right:0.75rem; flex-shrink:0; }
        .reply-form { margin-top:0.75rem; display:none; }
        .reply-form.open { display:block; }
        .reply-input { width:100%; padding:0.6rem 0.8rem; border:1.5px solid var(--border-color); border-radius:var(--radius); background:var(--bg-color); color:var(--text-main); font-family:inherit; }
        .reply-toggle { font-size:0.82rem; color:var(--primary-color); cursor:pointer; background:none; border:none; font-weight:600; padding:0; margin-top:0.5rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <?php if ($action === 'list'): ?>
        <!-- ═══ LIST ═══ -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
            <h2><i class="fa-solid fa-pen-to-square"></i> Manage Posts</h2>
            <?php if($can_manage_posts): ?>
                <a href="posts.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Post</a>
            <?php endif; ?>
        </div>
        <div class="table-container">
            <table>
                <thead><tr><th>Image</th><th>Title</th><th>Engagement</th><th>Visibility</th><th>Author</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($posts as $p): ?>
                    <tr>
                        <td><?php if($p['image']): ?><img src="../assets/image/Post-image/<?php echo htmlspecialchars($p['image']); ?>" class="post-img"><?php else: ?><div style="width:60px;height:60px;background:#e5e7eb;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#9ca3af;"><i class="fa-solid fa-image"></i></div><?php endif; ?></td>
                        <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                        <td style="font-size:0.85rem;">
                            <span style="color:#ef4444;"><i class="fa-solid fa-heart"></i> <?php echo $p['likes']; ?></span>
                            &nbsp;<span style="color:#8b5cf6;"><i class="fa-solid fa-comment"></i> <?php echo $p['comments']; ?></span>
                        </td>
                        <td><span class="badge" style="background:<?php echo $p['visibility']==='Public'?'#d1fae5;color:#065f46':'#fef3c7;color:#92400e'; ?>;"><?php echo $p['visibility']; ?></span></td>
                        <td><?php echo htmlspecialchars($p['author_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                        <td style="white-space:nowrap;">
                            <a href="posts.php?action=comments&post_id=<?php echo $p['id']; ?>" class="btn-icon btn-view"><i class="fa-solid fa-comments"></i> Comments</a>
                            <?php if($can_manage_posts): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?')">
                                <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="delete_post" class="btn-icon btn-delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($posts)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No posts yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($action === 'add' && $can_manage_posts): ?>
        <!-- ═══ ADD FORM ═══ -->
        <div style="margin-bottom:2rem;">
            <a href="posts.php" style="color:var(--text-muted);text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <h2 style="margin-top:0.75rem;">Create New Post</h2>
        </div>
        <div class="form-container">
            <form action="posts.php?action=add" method="POST" enctype="multipart/form-data">
                <div class="form-group"><label>Post Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="form-group"><label>Content</label><textarea name="description" class="form-control" rows="6" required></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <div class="form-group"><label>Image Upload</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                    <div class="form-group">
                        <label>Visibility</label>
                        <select name="visibility" class="form-control">
                            <option value="Public">Public (Everyone)</option>
                            <option value="Members">Members Only</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_post" class="btn btn-primary" style="width:100%;padding:1rem;">Publish Post</button>
            </form>
        </div>

        <?php elseif ($action === 'comments' && $view_post_id && $post_info): ?>
        <!-- ═══ COMMENTS & REPLIES ═══ -->
        <div style="margin-bottom:1.5rem;">
            <a href="posts.php" style="color:var(--text-muted);text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Posts</a>
            <h2 style="margin-top:0.75rem;"><i class="fa-solid fa-comments"></i> Comments — <em><?php echo htmlspecialchars($post_info['title']); ?></em></h2>
        </div>

        <?php if (empty($comments)): ?>
            <div style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fa-solid fa-comment-slash" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>No comments yet.</div>
        <?php else: ?>
            <?php
            // Render comments recursively
            function render_comment($c, $post_id, $depth = 0) {
                $is_reply = $depth > 0;
                $avatar = !empty($c['profile_pic'])
                    ? "../assets/image/Profile/" . htmlspecialchars($c['profile_pic'])
                    : "https://ui-avatars.com/api/?name=" . urlencode($c['commenter_name']) . "&background=4f46e5&color=fff&size=40";
                ?>
                <div class="comment-block <?php echo $is_reply ? 'reply-block' : ''; ?>">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                        <img src="<?php echo $avatar; ?>" class="comment-avatar" alt="">
                        <div style="flex:1;">
                            <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($c['commenter_name']); ?></strong>
                            <div class="comment-meta"><?php echo date('M d, Y g:i a', strtotime($c['created_at'])); ?></div>
                            <p style="margin-top:0.5rem;font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>

                            <div style="display:flex;gap:1rem;margin-top:0.5rem;align-items:center;">
                                <?php if ($depth === 0): ?>
                                <button class="reply-toggle" onclick="toggleReply(<?php echo $c['id']; ?>)"><i class="fa-solid fa-reply"></i> Reply</button>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this comment?')">
                                    <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="post_id_ref" value="<?php echo $post_id; ?>">
                                    <button type="submit" name="delete_comment" style="font-size:0.78rem;color:#ef4444;background:none;border:none;cursor:pointer;font-weight:600;"><i class="fa-solid fa-trash"></i> Delete</button>
                                </form>
                            </div>

                            <?php if ($depth === 0): ?>
                            <div class="reply-form" id="reply-<?php echo $c['id']; ?>">
                                <form method="POST">
                                    <input type="hidden" name="parent_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="post_id_ref" value="<?php echo $post_id; ?>">
                                    <textarea name="reply_text" class="reply-input" rows="2" placeholder="Write a reply..." required></textarea>
                                    <div style="display:flex;gap:0.5rem;margin-top:0.5rem;">
                                        <button type="submit" name="reply_comment" class="btn btn-primary" style="padding:0.4rem 1rem;font-size:0.85rem;"><i class="fa-solid fa-paper-plane"></i> Post Reply</button>
                                        <button type="button" onclick="toggleReply(<?php echo $c['id']; ?>)" style="padding:0.4rem 0.8rem;border:1px solid var(--border-color);background:var(--card-bg);border-radius:var(--radius);cursor:pointer;font-size:0.85rem;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                foreach ($c['children'] as $child) {
                    render_comment($child, $post_id, $depth + 1);
                }
            }
            foreach ($comments as $c) render_comment($c, $view_post_id);
            ?>
        <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-error">No permission or invalid post.</div>
        <?php endif; ?>
    </div></div>

    <script src="js/script.js"></script>
    <script>
        function toggleReply(id) {
            const el = document.getElementById('reply-' + id);
            el.classList.toggle('open');
        }
    </script>
</body>
</html>
