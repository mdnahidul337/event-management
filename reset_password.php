<?php
session_start();
require_once 'includes/db_connect.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!$token) {
    die("Invalid or missing token.");
}

$stmt = $pdo->prepare("SELECT id, name FROM users WHERE reset_token = ? AND reset_token_expire > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "This password reset link is invalid or has expired. Please request a new one.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?")
            ->execute([$hashed, $user['id']]);
            
        $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'UPDATE', 'auth', 'Password successfully reset', ?)")
            ->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
            
        $success = "Your password has been reset successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --bg-light: #f3f4f6; --text-dark: #1f2937; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; justify-content: center; align-items: center; height: 100vh; }
        .auth-container { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; }
        .logo { text-align: center; font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem; }
        .subtitle { text-align: center; color: #6b7280; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-dark); }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; }
        .form-control:focus { border-color: var(--primary); }
        .btn { width: 100%; padding: 0.8rem; background: var(--primary); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .text-center { text-align: center; margin-top: 1.5rem; }
        .text-center a { color: var(--primary); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">SCC.</div>
        <div class="subtitle">Set New Password</div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <a href="login.php" class="btn" style="display:block; text-align:center; text-decoration:none; margin-top:1rem;">Go to Login</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($user): ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
