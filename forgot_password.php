<?php
session_start();
require_once 'includes/db_connect.php';

// Add columns safely if they don't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS reset_token_expire DATETIME DEFAULT NULL");
} catch (PDOException $e) {}

$message = '';
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
    
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE id = ?")
            ->execute([$token, $expire, $user['id']]);
            
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

        // Send real email via SMTPMailer template
        require_once 'includes/mailer_helper.php';
        send_template_email($pdo, 'forgot_password', [
            'name'       => $user['name'],
            'email'      => $email,
            'reset_link' => $reset_link,
        ]);

        $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'UPDATE', 'auth', 'Password reset requested', ?)")
            ->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

        $message = "A password reset link has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox.";
    } else {
        $error = "If that email exists in our system, a reset link will be sent to it."; // Security best practice
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; line-height: 1.5; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;}
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .text-center { text-align: center; margin-top: 1.5rem; }
        .text-center a { color: var(--primary); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <?php if(!empty($logo_path)): ?>
                <img src="assets/image/<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" style="max-height: 50px;">
            <?php else: ?>
                <?php echo htmlspecialchars($site_name); ?>
            <?php endif; ?>
        </div>
        <div class="subtitle">Reset your password</div>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-info"><?php echo $error; ?></div> <!-- Using info style on purpose -->
        <?php endif; ?>

        <?php if(!$message): ?>
        <form action="forgot_password.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <?php endif; ?>
        
        <div class="text-center">
            Remembered your password? <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
