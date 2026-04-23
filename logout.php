<?php
session_start();
require_once 'includes/db_connect.php';

if (isset($_SESSION['user_id'])) {
    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'LOGOUT', 'auth', 'User logged out', ?)");
    $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
