<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';

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
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-light: #f3f4f6;
            --text-dark: #1f2937;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: var(--bg-light); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-dark); }
        .form-group input { 
            width: 100%; 
            padding: 0.75rem; 
            border: 1px solid #d1d5db; 
            border-radius: 6px; 
            outline: none;
            transition: border-color 0.3s;
        }
        .form-group input:focus { border-color: var(--primary); }
        .btn {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: var(--primary-dark); }
        .text-center { text-align: center; margin-top: 1.5rem; color: #6b7280; font-size: 0.9rem;}
        .text-center a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo">SCC.</div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <div class="text-center">
            Don't have an account? <a href="register.php">Register</a><br><br>
            <a href="index.php">← Back to Home</a>
        </div>
    </div>

</body>
</html>
