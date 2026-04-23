<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Level 40+ (SocialMediaManager and above) can manage posts
$can_manage_posts = $_SESSION['role_level'] >= 40;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_posts) {
    if (isset($_POST['add_post'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $visibility = $_POST['visibility'];
        $image = '';

        // Handle image upload basic logic (assuming assets/image/Post-image exists)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/image/Post-image/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image = $filename;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO posts (title, description, image, visibility, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $image, $visibility, $_SESSION['user_id']])) {
            $post_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'posts', ?, ?)")
                ->execute([$_SESSION['user_id'], "Created post ID $post_id", $_SERVER['REMOTE_ADDR']]);
            $success = "Post added successfully!";
            $action = 'list';
        } else {
            $error = "Failed to add post.";
        }
    }
}

// Fetch posts
if ($action === 'list') {
    $posts = $pdo->query("SELECT p.*, u.name as author_name FROM posts p JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background: var(--card-bg); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); outline: none; background: var(--bg-color); color: var(--text-main); }
        .form-control:focus { border-color: var(--primary-color); }
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .post-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

            <?php if ($action === 'list'): ?>
                <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                    <h2>Manage Posts</h2>
                    <?php if($can_manage_posts): ?>
                        <a href="posts.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Post</a>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Visibility</th>
                                <th>Author</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($posts as $p): ?>
                            <tr>
                                <td>
                                    <?php if($p['image']): ?>
                                        <img src="../assets/image/Post-image/<?php echo htmlspecialchars($p['image']); ?>" class="post-img">
                                    <?php else: ?>
                                        <div style="width:60px; height:60px; background:#e5e7eb; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#9ca3af;"><i class="fa-solid fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $p['visibility'] === 'Public' ? '#10b981' : '#f59e0b'; ?>;">
                                        <?php echo htmlspecialchars($p['visibility']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($p['author_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($posts)): ?>
                            <tr><td colspan="5" style="text-align:center;">No posts found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action === 'add' && $can_manage_posts): ?>
                <div style="margin-bottom: 2rem;">
                    <a href="posts.php" style="color:var(--text-muted); text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Posts</a>
                    <h2 style="margin-top: 1rem;">Create New Post</h2>
                </div>

                <div class="form-container">
                    <form action="posts.php?action=add" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Post Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description / Content</label>
                            <textarea name="description" class="form-control" rows="6" required></textarea>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>Image Upload</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Visibility</label>
                                <select name="visibility" class="form-control" required>
                                    <option value="Public">Public (Everyone)</option>
                                    <option value="Members">Members Only</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_post" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Publish Post</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-error">You do not have permission to view this page.</div>
            <?php endif; ?>

        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
