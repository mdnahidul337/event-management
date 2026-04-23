<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';
$success = '';

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$site_name = $settings['site_name'] ?? 'SCC.';
$logo_path = $settings['logo_path'] ?? '';

$stmt_fee = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'registration_fee'");
$fee_row = $stmt_fee->fetch();
$registration_fee = $fee_row ? floatval($fee_row['setting_value']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department'] ?? '');
    $session_str = trim($_POST['session'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');

    $profile_pic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/image/Profile/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $filename = 'prof_' . time() . '_' . basename($_FILES['profile_pic']['name']);
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $filename)) {
            $profile_pic = $filename;
        }
    }

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $stmt = $pdo->query("SELECT id FROM roles WHERE name = 'Member'");
            $role = $stmt->fetch();
            $role_id = $role ? $role['id'] : 8;

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Ensure columns exist (DDL commits transactions, so do this before starting one)
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL");
            } catch (PDOException $e) {
            }
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL");
            } catch (PDOException $e) {
            }
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS session VARCHAR(50) DEFAULT NULL");
            } catch (PDOException $e) {
            }
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS blood_group VARCHAR(10) DEFAULT NULL");
            } catch (PDOException $e) {
            }
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL");
            } catch (PDOException $e) {
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, department, session, blood_group, profile_pic, password, role_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $department, $session_str, $blood_group, $profile_pic, $hashed_password, $role_id]);
                $user_id = $pdo->lastInsertId();

                if ($registration_fee > 0) {
                    $method = $_POST['method'];
                    $sender_number = trim($_POST['sender_number']);
                    $trx_id = trim($_POST['trx_id']);

                    $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, method, sender_number, trx_id, status) VALUES (?, NULL, ?, ?, ?, ?, 'Pending')");
                    $stmt->execute([$user_id, $registration_fee, $method, $sender_number, $trx_id]);
                }

                $pdo->commit();

                // Send welcome email
                require_once 'includes/mailer_helper.php';
                send_template_email($pdo, 'welcome', [
                    'name' => $name,
                    'email' => $email,
                    'department' => $department,
                    'session' => $session_str
                ]);

                $success = "Registration successful! You can now <a href='login.php'>Login</a>.";
            } catch (Exception $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2.5rem 1rem; }
        .register-card { background: white; padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); width: 100%; max-width: 600px; border: 1px solid var(--border); }
        .logo { font-size: 2.25rem; font-weight: 800; color: var(--primary); text-align: center; margin-bottom: 2.5rem; display: block; text-decoration: none; }
        .form-label { display: block; font-weight: 700; font-size: 0.85rem; color: #374151; margin-bottom: 0.5rem; }
        .form-input { width: 100%; padding: 0.85rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-md); outline: none; transition: 0.2s; font-size: 1rem; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem; }
        .btn-register { width: 100%; padding: 0.85rem; background: var(--primary); color: white; border: none; border-radius: var(--radius-md); font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 1rem; }
        .btn-register:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: var(--shadow-lg); }
        .payment-box { background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: var(--radius-md); padding: 1.5rem; margin: 2rem 0; }
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 600; text-align: center; font-size: 0.9rem; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .auth-footer { margin-top: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem; }
        .auth-footer a { color: var(--primary); font-weight: 700; text-decoration: none; }
        @media (max-width: 600px) { .register-card { padding: 2rem 1.5rem; } .form-grid { grid-template-columns: 1fr; gap: 1.25rem; } }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">
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
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>

            <form action="register.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" placeholder="John Doe" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-input" placeholder="017XXXXXXXX" required>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-input" placeholder="e.g. CSE">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Session</label>
                        <input type="text" name="session" class="form-input" placeholder="e.g. 2021-2022">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <input type="text" name="blood_group" class="form-input" placeholder="e.g. A+">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-input" accept="image/*" style="padding: 0.6rem;">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                    </div>
                </div>

                <?php if ($registration_fee > 0): ?>
                    <div class="payment-box">
                        <h4 style="color:#4f46e5; margin-bottom: 0.5rem; font-weight: 800;"><i class="fa-solid fa-money-bill-wave"></i>
                            Registration Fee: ৳<?php echo $registration_fee; ?></h4>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($settings['payment_instructions'] ?? 'Please send the fee to our official number.')); ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <select name="method" class="form-input" required>
                                <option value="Bkash">Bkash</option>
                                <option value="Nagad">Nagad</option>
                            </select>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Sender Number</label>
                                <input type="text" name="sender_number" class="form-input" placeholder="01700000000" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" name="trx_id" class="form-input" placeholder="TRX123ABC" required>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-register">Create Account</button>
            </form>

        <?php endif; ?>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Login</a><br><br>
            <a href="index.php" style="color: var(--text-muted);">← Back to Home</a>
        </div>
    </div>

</body>

</html>