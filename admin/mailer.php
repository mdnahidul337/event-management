<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

// ONLY SuperAdmin (Level 100) can access Mailer
$can_access_mailer = $_SESSION['role_level'] >= 100;

if (!$can_access_mailer) {
    header("Location: dashboard.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_mail'])) {
    $target = $_POST['target']; // 'all', 'members', 'custom'
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']); // HTML allowed
    
    $emails = [];
    
    if ($target === 'all') {
        $stmt = $pdo->query("SELECT email FROM users");
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($target === 'members') {
        $stmt = $pdo->query("SELECT u.email FROM users u JOIN roles r ON u.role_id = r.id WHERE r.level = 10");
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($target === 'custom') {
        $custom_email = trim($_POST['custom_email']);
        if (filter_var($custom_email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $custom_email;
        }
    }

    if (empty($emails)) {
        $error = "No recipients found for the selected target.";
    } elseif (empty($subject) || empty($message)) {
        $error = "Subject and Message cannot be empty.";
    } else {
        // In a real production environment, we would use PHPMailer or an SMTP service here.
        // For this MVP, we will simulate sending by logging it and displaying success.
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: admin@scc.com" . "\r\n";
        
        $sent_count = 0;
        foreach ($emails as $email) {
            // mail($email, $subject, $message, $headers); // Commented out to prevent errors on local XAMPP
            $sent_count++;
        }

        // Log the action
        $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'mailer', ?, ?)")
            ->execute([$_SESSION['user_id'], "Sent mass email to $sent_count recipients. Subject: $subject", $_SERVER['REMOTE_ADDR']]);

        $success = "Successfully simulated sending email to $sent_count recipients!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Mailer - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background: var(--card-bg); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); outline: none; background: var(--bg-color); color: var(--text-main); font-family: monospace;}
        .form-control:focus { border-color: var(--primary-color); }
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                <h2>Email Broadcaster</h2>
                <span style="color:var(--text-muted); font-size:0.9rem;">Send HTML emails directly to users</span>
            </div>

            <div class="form-container">
                <form action="mailer.php" method="POST">
                    <div class="form-group">
                        <label>Target Audience</label>
                        <select name="target" class="form-control" id="targetSelect" onchange="toggleCustomEmail()" required>
                            <option value="all">All Registered Users</option>
                            <option value="members">Only Basic Members</option>
                            <option value="custom">Custom Email Address</option>
                        </select>
                    </div>

                    <div class="form-group" id="customEmailGroup" style="display: none;">
                        <label>Custom Email Address</label>
                        <input type="email" name="custom_email" class="form-control" placeholder="user@example.com">
                    </div>

                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" required placeholder="Important Update from SCC">
                    </div>

                    <div class="form-group">
                        <label>HTML Message Body</label>
                        <textarea name="message" class="form-control" rows="12" required placeholder="<h1>Hello</h1><p>Type your HTML email here...</p>"></textarea>
                        <small style="color:var(--text-muted);">You can use standard HTML tags like &lt;b&gt;, &lt;h1&gt;, &lt;a href="..."&gt;, etc.</small>
                    </div>

                    <button type="submit" name="send_mail" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i class="fa-solid fa-paper-plane"></i> Send Broadcast</button>
                </form>
            </div>

        </div>
    </div>

    <script>
        function toggleCustomEmail() {
            const select = document.getElementById('targetSelect');
            const customGroup = document.getElementById('customEmailGroup');
            if (select.value === 'custom') {
                customGroup.style.display = 'block';
            } else {
                customGroup.style.display = 'none';
            }
        }
    </script>
</body>
</html>
