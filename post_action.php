<?php
session_start();
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Must be logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$post_id = $_POST['post_id'] ?? 0;

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid post.']);
    exit;
}

if ($action === 'toggle_like') {
    // Check if like exists
    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Unlike
        $pdo->prepare("DELETE FROM post_likes WHERE id = ?")->execute([$existing['id']]);
        $liked = false;
    } else {
        // Like
        $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
        $liked = true;
    }

    // Get total likes
    $count = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
    $count->execute([$post_id]);
    $total = $count->fetchColumn();

    echo json_encode(['success' => true, 'liked' => $liked, 'total' => $total]);
    exit;
}

if ($action === 'add_comment') {
    $comment = trim($_POST['comment'] ?? '');
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
        exit;
    }

    $pdo->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)")
        ->execute([$post_id, $user_id, $comment]);
    
    // Get total comments
    $count = $pdo->prepare("SELECT COUNT(*) FROM post_comments WHERE post_id = ?");
    $count->execute([$post_id]);
    $total = $count->fetchColumn();

    echo json_encode([
        'success' => true, 
        'total' => $total, 
        'comment' => htmlspecialchars($comment), 
        'author' => htmlspecialchars($_SESSION['user_name']),
        'avatar' => urlencode($_SESSION['user_name'])
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
