<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$site_name = $settings['site_name'] ?? 'SCC.';
$logo_path = $settings['logo_path'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.level as role_level FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['role_level'] = $user['role_level'];

        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'LOGIN', 'auth', 'User logged in', ?)");
        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

        $redirect = $_GET['redirect'] ?? '';
        if (!empty($redirect)) {
            header("Location: " . $redirect);
        } else {
            if ($user['role_level'] > 10) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: profile.php");
            }
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; }
        .login-card { background: white; padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); width: 100%; max-width: 450px; border: 1px solid var(--border); }
        .login-logo { font-size: 2.25rem; font-weight: 800; color: var(--primary); text-align: center; margin-bottom: 2.5rem; display: block; text-decoration: none; }
        .form-label { display: block; font-weight: 700; font-size: 0.85rem; color: #374151; margin-bottom: 0.5rem; }
        .form-input { width: 100%; padding: 0.85rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-md); outline: none; transition: 0.2s; font-size: 1rem; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .btn-login { width: 100%; padding: 0.85rem; background: var(--primary); color: white; border: none; border-radius: var(--radius-md); font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 1rem; }
        .btn-login:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: var(--shadow-lg); }
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 600; text-align: center; font-size: 0.9rem; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .auth-footer { margin-top: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem; }
        .auth-footer a { color: var(--primary); font-weight: 700; text-decoration: none; }
        @media (max-width: 480px) { .login-card { padding: 2rem 1.5rem; } }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <?php if (!empty($logo_path)): ?>
                <img src="assets/image/<?php echo htmlspecialchars($logo_path); ?>"
                    alt="<?php echo htmlspecialchars($site_name); ?>" style="max-height: 60px;">
            <?php else: ?>
                <?php echo htmlspecialchars($site_name); ?>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form
            action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>"
            method="POST">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="form-label" style="margin-bottom: 0;">Password</label>
                    <a href="forgot_password.php"
                        style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600;">Forgot password?</a>
                </div>
                <input type="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register</a><br><br>
            <a href="index.php" style="color: var(--text-muted);">← Back to Home</a>
        </div>
    </div>

</body>

</html>