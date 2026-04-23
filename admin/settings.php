<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$error = '';
$success = '';

// Level 90+ (Admin, SuperAdmin) can access settings
$can_manage_settings = $_SESSION['role_level'] >= 90;

if (!$can_manage_settings) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $site_name = trim($_POST['site_name']);
        $contact_email = trim($_POST['contact_email']);
        $registration_fee = trim($_POST['registration_fee']);
        $registration_fee_cutoff = trim($_POST['registration_fee_cutoff']);
        $payment_instructions = trim($_POST['payment_instructions']);
        
        $github_token = trim($_POST['github_token']);
        $github_owner = trim($_POST['github_owner']);
        $github_repo = trim($_POST['github_repo']);
        $github_branch = trim($_POST['github_branch']);
        
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('facebook_url', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('twitter_url', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('logo_path', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('payment_instructions', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('registration_fee_cutoff', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('github_token', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('github_owner', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('github_repo', '')")->execute();
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('github_branch', '')")->execute();

        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $twitter_url = trim($_POST['twitter_url'] ?? '');
        
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_name'")->execute([$site_name]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'contact_email'")->execute([$contact_email]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'registration_fee'")->execute([$registration_fee]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'facebook_url'")->execute([$facebook_url]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'twitter_url'")->execute([$twitter_url]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'payment_instructions'")->execute([$payment_instructions]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'registration_fee_cutoff'")->execute([$registration_fee_cutoff]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'github_token'")->execute([$github_token]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'github_owner'")->execute([$github_owner]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'github_repo'")->execute([$github_repo]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'github_branch'")->execute([$github_branch]);

        // Handle Logo Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/image/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = 'logo_' . time() . '_' . basename($_FILES['logo']['name']);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'logo_path'")->execute([$filename]);
            }
        }

        $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'UPDATE', 'settings', 'Updated system settings', ?)")
            ->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);

        $success = "Settings updated successfully!";
    }
}

// Fetch settings
$settings_raw = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background: var(--card-bg); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); outline: none; background: var(--bg-color); color: var(--text-main); }
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
                <h2>System Settings</h2>
            </div>

            <div class="form-container">
                <form action="settings.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Registration Fee (৳)</label>
                        <input type="number" name="registration_fee" class="form-control" value="<?php echo htmlspecialchars($settings['registration_fee'] ?? '0'); ?>" required>
                        <small style="color:var(--text-muted);">Set to 0 for free registration.</small>
                    </div>
                    <div class="form-group">
                        <label>Fee Requirement Start Date (Cut-off)</label>
                        <input type="datetime-local" name="registration_fee_cutoff" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($settings['registration_fee_cutoff'] ?? '2000-01-01 00:00:00')); ?>" required>
                        <small style="color:var(--text-muted);">Members created BEFORE this date will NOT be asked for a registration fee.</small>
                    </div>
                    <div class="form-group">
                        <label>Payment Instructions (for Paid Events)</label>
                        <textarea name="payment_instructions" class="form-control" rows="4" placeholder="Enter instructions for users to pay for events..."><?php echo htmlspecialchars($settings['payment_instructions'] ?? ''); ?></textarea>
                        <small style="color:var(--text-muted);">Shown to users on the join event page for paid events.</small>
                    </div>
                    <div class="form-group">
                        <label>Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/yourclub">
                    </div>
                    <div class="form-group">
                        <label>Twitter/X URL</label>
                        <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>" placeholder="https://twitter.com/yourclub">
                    </div>
                    <div class="form-group">
                        <label>Upload Site Logo</label>
                        <?php if(!empty($settings['logo_path'])): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <img src="../assets/image/<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Current Logo" style="max-height: 50px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <small style="color:var(--text-muted);">Leave empty to keep current logo.</small>
                    </div>
                    <hr style="margin: 2rem 0; border: 0; border-top: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 1.5rem;"><i class="fa-brands fa-github"></i> GitHub Storage Settings</h3>
                    <div class="form-group">
                        <label>GitHub Token</label>
                        <input type="password" name="github_token" class="form-control" value="<?php echo htmlspecialchars($settings['github_token'] ?? ''); ?>" placeholder="github_pat_...">
                        <small style="color:var(--text-muted);">Personal Access Token with 'repo' scope.</small>
                    </div>
                    <div class="form-group">
                        <label>GitHub Owner</label>
                        <input type="text" name="github_owner" class="form-control" value="<?php echo htmlspecialchars($settings['github_owner'] ?? ''); ?>" placeholder="username">
                    </div>
                    <div class="form-group">
                        <label>GitHub Repository</label>
                        <input type="text" name="github_repo" class="form-control" value="<?php echo htmlspecialchars($settings['github_repo'] ?? ''); ?>" placeholder="repo-name">
                    </div>
                    <div class="form-group">
                        <label>GitHub Branch</label>
                        <input type="text" name="github_branch" class="form-control" value="<?php echo htmlspecialchars($settings['github_branch'] ?? 'main'); ?>">
                    </div>

                    <button type="submit" name="update_settings" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Settings</button>
                </form>
            </div>

        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
