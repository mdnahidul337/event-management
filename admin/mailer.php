<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once '../includes/db_connect.php';
require_once '../includes/SMTPMailer.php';

if ($_SESSION['role_level'] < 100) { header("Location: dashboard.php"); exit; }

// ─── Auto-migrate mail_logs table ─────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS mail_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sent_by INT,
    target_label VARCHAR(100),
    recipient_count INT DEFAULT 0,
    subject VARCHAR(255),
    message TEXT,
    status ENUM('Sent','Failed','Simulated') DEFAULT 'Simulated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
)");

$success = '';
$error   = '';
$tab     = $_GET['tab'] ?? 'compose';

// ─── Seed default SMTP settings (INSERT IGNORE — won't overwrite saved values) ──
$smtp_defaults = [
    'smtp_host'       => 'mail.miniearn.site',
    'smtp_port'       => '465',
    'smtp_user'       => 'no-reply@miniearn.site',
    'smtp_pass'       => '01798283092As@',
    'smtp_from_name'  => 'SCC Computer Club',
    'smtp_from_email' => 'no-reply@miniearn.site',
    'smtp_secure'     => 'ssl',
];
foreach ($smtp_defaults as $key => $val) {
    $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)")
        ->execute([$key, $val]);
}

// ─── Load SMTP settings ────────────────────────────────────────────────────
$smtp_keys = ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','smtp_secure'];
$smtp = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'")->fetchAll();
foreach ($rows as $r) $smtp[$r['setting_key']] = $r['setting_value'];

// ─── Handle SMTP Save ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    foreach ($smtp_keys as $key) {
        $val = trim($_POST[$key] ?? '');
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $val]);
        $smtp[$key] = $val;
    }
    $success = "SMTP settings saved successfully!";
    $tab = 'smtp';
}

// Load all users for autocomplete + merge-tag personalization
$all_users = $pdo->query("SELECT u.name, u.email, u.department, u.session, u.blood_group, r.name as role_name FROM users u JOIN roles r ON u.role_id=r.id ORDER BY u.name")->fetchAll();
$user_emails_json = json_encode(array_map(fn($u) => ['name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role_name']], $all_users));

// ─── Handle Send Mail ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_mail'])) {
    $target  = $_POST['target'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Recipients: fetch full user rows for merge-tag personalization
    $recipients = [];
    if ($target === 'all') {
        $recipients = $all_users;
        $label = 'All Users';
    } elseif ($target === 'members') {
        $recipients = array_filter($all_users, fn($u) => $u['role_name'] === 'Member');
        $label = 'Members Only';
    } elseif ($target === 'team') {
        $recipients = $pdo->query("SELECT u.name, u.email, u.department, u.session, u.blood_group, r.name as role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE r.level>=40")->fetchAll();
        $label = 'Team Members';
    } elseif ($target === 'custom') {
        // Support comma-separated list of emails
        $raw_emails = array_map('trim', explode(',', $_POST['custom_email']));
        foreach ($raw_emails as $ce) {
            if (!filter_var($ce, FILTER_VALIDATE_EMAIL)) continue;
            // Find matching user for merge tags, or stub
            $match = array_filter($all_users, fn($u) => strtolower($u['email']) === strtolower($ce));
            $recipients[] = $match ? array_values($match)[0] : ['name'=>$ce,'email'=>$ce,'role_name'=>'','department'=>'','session'=>'','blood_group'=>''];
        }
        $label = implode(', ', array_column($recipients, 'email'));
    }

    if (empty($recipients)) {
        $error = "No valid recipients found.";
    } elseif (!$subject || !$message) {
        $error = "Subject and message cannot be empty.";
    } else {
        $use_smtp = !empty($smtp['smtp_host']) && !empty($smtp['smtp_user']) && !empty($smtp['smtp_pass']);

        if ($use_smtp) {
            $mailer = new SMTPMailer([
                'host'       => $smtp['smtp_host'],
                'port'       => $smtp['smtp_port'],
                'user'       => $smtp['smtp_user'],
                'pass'       => $smtp['smtp_pass'],
                'from_email' => $smtp['smtp_from_email'],
                'from_name'  => $smtp['smtp_from_name'],
                'secure'     => $smtp['smtp_secure'],
            ]);
            $result     = $mailer->sendBulk(array_values((array)$recipients), $subject, $message);
            $sent_count = $result['sent'];
            $status     = $result['failed'] === 0 ? 'Sent' : ($result['sent'] > 0 ? 'Sent' : 'Failed');
            if (!empty($result['errors'])) {
                $error = "Some failed: " . implode('; ', array_slice($result['errors'], 0, 3));
            }
        } else {
            $sent_count = count((array)$recipients);
            $status     = 'Simulated';
        }

        $pdo->prepare("INSERT INTO mail_logs (sent_by, target_label, recipient_count, subject, message, status) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], $label, $sent_count, $subject, $message, $status]);
        $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'mailer', ?, ?)")
            ->execute([$_SESSION['user_id'], "Broadcast to $sent_count recipients. Subject: $subject", $_SERVER['REMOTE_ADDR']]);

        $success = $use_smtp
            ? "Email sent to $sent_count recipients via SMTP!"
            : "Simulated sending to $sent_count recipients (configure SMTP for real delivery).";
        $tab = 'sent';
    }
}

// ─── Handle Delete Log ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log'])) {
    $pdo->prepare("DELETE FROM mail_logs WHERE id=?")->execute([intval($_POST['log_id'])]);
    $success = "Log entry deleted.";
    $tab = 'sent';
}

// ─── Fetch sent logs ─────────────────────────────────────────────────────
$mail_logs = $pdo->query("
    SELECT l.*, u.name as sender_name FROM mail_logs l
    LEFT JOIN users u ON l.sent_by = u.id
    ORDER BY l.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailer - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tab-bar { display:flex; gap:0.25rem; margin-bottom:2rem; background:var(--card-bg); border-radius:var(--radius); padding:0.4rem; box-shadow:var(--shadow-sm); width:fit-content; }
        .tab-btn { padding:0.6rem 1.4rem; border:none; border-radius:calc(var(--radius) - 2px); background:transparent; color:var(--text-muted); font-weight:600; cursor:pointer; font-size:0.9rem; transition:all 0.2s; }
        .tab-btn.active { background:var(--primary-color); color:white; box-shadow:0 2px 8px rgba(79,70,229,0.3); }
        .tab-btn:hover:not(.active) { background:var(--border-color); color:var(--text-main); }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; animation:fadeIn 0.3s ease; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)} }

        .form-container { background:var(--card-bg); padding:2rem; border-radius:var(--radius); box-shadow:var(--shadow-sm); max-width:850px; }
        .form-group { margin-bottom:1.4rem; }
        .form-group label { display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.9rem; }
        .form-control { width:100%; padding:0.75rem; border:1.5px solid var(--border-color); border-radius:var(--radius); outline:none; background:var(--bg-color); color:var(--text-main); font-family:'Inter',sans-serif; transition:border-color 0.2s; }
        .form-control:focus { border-color:var(--primary-color); }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; }
        .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; }
        .alert { padding:1rem 1.2rem; border-radius:var(--radius); margin-bottom:1.5rem; border-left:4px solid; font-weight:500; }
        .alert-success { background:#d1fae5; color:#047857; border-color:#10b981; }
        .alert-error   { background:#fee2e2; color:#b91c1c; border-color:#ef4444; }
        .section-label { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); padding-bottom:0.5rem; margin-bottom:1rem; border-bottom:1px solid var(--border-color); }
        .smtp-status { display:inline-flex; align-items:center; gap:0.5rem; padding:0.3rem 0.8rem; border-radius:20px; font-size:0.8rem; font-weight:600; }
        .smtp-ok  { background:#d1fae5; color:#065f46; }
        .smtp-off { background:#fef3c7; color:#92400e; }

        /* Sent Log */
        .log-card { background:var(--card-bg); border-radius:var(--radius); border:1px solid var(--border-color); margin-bottom:1rem; overflow:hidden; }
        .log-card-header { padding:1rem 1.2rem; display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
        .log-card-header:hover { background:var(--bg-color); }
        .log-card-body { padding:1rem 1.2rem; border-top:1px solid var(--border-color); display:none; background:var(--bg-color); }
        .log-card-body.open { display:block; }
        .msg-preview { font-family:monospace; font-size:0.82rem; color:var(--text-muted); white-space:pre-wrap; max-height:200px; overflow-y:auto; background:var(--card-bg); border:1px solid var(--border-color); border-radius:6px; padding:0.75rem; }

        /* Autocomplete */
        .ac-wrapper { position:relative; }
        .ac-list { position:absolute; top:100%; left:0; right:0; background:var(--card-bg); border:1.5px solid var(--primary-color); border-radius:var(--radius); z-index:200; max-height:220px; overflow-y:auto; box-shadow:0 8px 24px rgba(0,0,0,0.12); display:none; }
        .ac-item { padding:0.65rem 1rem; cursor:pointer; font-size:0.88rem; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:0.75rem; }
        .ac-item:last-child { border:none; }
        .ac-item:hover { background:var(--bg-color); }
        .ac-avatar { width:30px; height:30px; border-radius:50%; object-fit:cover; flex-shrink:0; }
        .ac-name { font-weight:600; }
        .ac-email-text { font-size:0.78rem; color:var(--text-muted); }
        .ac-role { font-size:0.72rem; background:#e0e7ff; color:#3730a3; border-radius:10px; padding:1px 6px; }

        /* Merge Tags */
        .tag-pill { display:inline-block; background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; border-radius:20px; padding:3px 10px; font-size:0.78rem; font-weight:700; cursor:pointer; margin:3px; font-family:monospace; transition:all 0.2s; }
        .tag-pill:hover { background:#4f46e5; color:white; border-color:#4f46e5; }
        .tag-pills-box { background:var(--bg-color); border:1px solid var(--border-color); border-radius:var(--radius); padding:0.75rem 1rem; margin-bottom:1rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h2><i class="fa-solid fa-envelope-open-text"></i> Mailer</h2>
            <?php $smtp_configured = !empty($smtp['smtp_host']); ?>
            <span class="smtp-status <?php echo $smtp_configured ? 'smtp-ok' : 'smtp-off'; ?>">
                <i class="fa-solid fa-<?php echo $smtp_configured ? 'circle-check' : 'circle-exclamation'; ?>"></i>
                <?php echo $smtp_configured ? 'SMTP Configured' : 'SMTP Not Set (Simulation Mode)'; ?>
            </span>
        </div>

        <!-- Tabs -->
        <div class="tab-bar">
            <button class="tab-btn <?php echo $tab==='compose'?'active':''; ?>" onclick="switchTab('compose',this)"><i class="fa-solid fa-pen"></i> Compose</button>
            <button class="tab-btn <?php echo $tab==='sent'?'active':''; ?>" onclick="switchTab('sent',this)"><i class="fa-solid fa-paper-plane"></i> Sent Messages <span style="background:rgba(255,255,255,0.3);border-radius:20px;padding:0 6px;font-size:0.8rem;"><?php echo count($mail_logs); ?></span></button>
            <button class="tab-btn <?php echo $tab==='smtp'?'active':''; ?>" onclick="switchTab('smtp',this)"><i class="fa-solid fa-server"></i> SMTP Settings</button>
        </div>

        <!-- ═══ COMPOSE ═══ -->
        <div id="tab-compose" class="tab-panel <?php echo $tab==='compose'?'active':''; ?>">
            <div class="form-container">
                <form method="POST" id="composeForm">
                    <div class="section-label">Recipients</div>
                    <div class="form-grid" style="margin-bottom:1.2rem;">
                        <div class="form-group">
                            <label>Target Audience</label>
                            <select name="target" class="form-control" id="targetSelect" onchange="toggleCustomEmail()">
                                <option value="all">All Registered Users</option>
                                <option value="members">Members Only</option>
                                <option value="team">Team Members (Level 40+)</option>
                                <option value="custom">Custom Email Address</option>
                            </select>
                        </div>
                        <div class="form-group" id="customEmailGroup" style="display:none;">
                            <label>Custom Email — type name or address</label>
                            <div class="ac-wrapper">
                                <input type="text" id="customEmailInput" name="custom_email" class="form-control"
                                    placeholder="e.g. john, @gmail.com, multiple@a.com, b@b.com"
                                    autocomplete="off"
                                    oninput="acSearch(this.value)">
                                <div class="ac-list" id="acList"></div>
                            </div>
                            <small style="color:var(--text-muted);">Separate multiple emails with commas. Type a name or partial email to autocomplete from registered users.</small>
                        </div>
                    </div>

                    <div class="section-label">Message</div>

                    <!-- Merge Tag helpers -->
                    <div class="tag-pills-box">
                        <div style="font-size:0.78rem;font-weight:700;color:var(--text-muted);margin-bottom:0.5rem;"><i class="fa-solid fa-tags"></i> MERGE TAGS — click to insert into subject or body:</div>
                        <span class="tag-pill" onclick="insertTag('{{name}}')">{{name}}</span>
                        <span class="tag-pill" onclick="insertTag('{{email}}')">{{email}}</span>
                        <span class="tag-pill" onclick="insertTag('{{role}}')">{{role}}</span>
                        <span class="tag-pill" onclick="insertTag('{{department}}')">{{department}}</span>
                        <span class="tag-pill" onclick="insertTag('{{session}}')">{{session}}</span>
                        <span class="tag-pill" onclick="insertTag('{{blood_group}}')">{{blood_group}}</span>
                        <button type="button" onclick="loadSampleTemplate()" style="float:right;background:var(--primary-color);color:white;border:none;border-radius:6px;padding:4px 10px;font-size:0.78rem;cursor:pointer;font-weight:600;"><i class="fa-solid fa-wand-magic-sparkles"></i> Load Sample</button>
                    </div>

                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" id="mailSubject" class="form-control" placeholder="e.g. Hello {{name}}, Important Update from SCC!" required>
                    </div>
                    <div class="form-group">
                        <label>HTML Message Body</label>
                        <textarea name="message" id="mailBody" class="form-control" rows="14" required style="font-family:monospace;"></textarea>
                        <small style="color:var(--text-muted);">Supports HTML tags. Use merge tags above to personalize each recipient's email.</small>
                    </div>
                    <button type="submit" name="send_mail" class="btn btn-primary" style="width:100%;padding:1rem;">
                        <i class="fa-solid fa-paper-plane"></i>
                        <?php echo !empty($smtp['smtp_host']) ? 'Send Email via SMTP' : 'Send (Simulation Mode)'; ?>
                    </button>
                    <?php if (!$smtp_configured): ?>
                        <p style="text-align:center;color:var(--text-muted);font-size:0.82rem;margin-top:0.75rem;"><i class="fa-solid fa-info-circle"></i> Configure SMTP in the SMTP Settings tab to send real emails.</p>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- ═══ SENT MESSAGES ═══ -->
        <div id="tab-sent" class="tab-panel <?php echo $tab==='sent'?'active':''; ?>">
            <h3 style="margin-bottom:1.5rem;"><i class="fa-solid fa-inbox"></i> Sent Messages Log</h3>
            <?php if (empty($mail_logs)): ?>
                <div style="text-align:center;padding:3rem;color:var(--text-muted);background:var(--card-bg);border-radius:var(--radius);">
                    <i class="fa-solid fa-inbox" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;"></i>
                    No messages sent yet.
                </div>
            <?php else: ?>
                <?php foreach ($mail_logs as $i => $log): ?>
                <div class="log-card">
                    <div class="log-card-header" onclick="toggleLog(<?php echo $log['id']; ?>)">
                        <div>
                            <strong style="font-size:0.95rem;"><?php echo htmlspecialchars($log['subject']); ?></strong>
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:3px;">
                                To: <strong><?php echo htmlspecialchars($log['target_label']); ?></strong>
                                &bull; <?php echo $log['recipient_count']; ?> recipients
                                &bull; By <?php echo htmlspecialchars($log['sender_name'] ?? 'Unknown'); ?>
                                &bull; <?php echo date('M d, Y g:i a', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <?php
                            $sc = ['Sent'=>'#d1fae5;color:#065f46','Simulated'=>'#fef3c7;color:#92400e','Failed'=>'#fee2e2;color:#991b1b'][$log['status']] ?? '';
                            ?>
                            <span class="badge" style="background:<?php echo $sc; ?>"><?php echo $log['status']; ?></span>
                            <form method="POST" onsubmit="return confirm('Delete this log?')" style="display:inline;">
                                <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                <button type="submit" name="delete_log" onclick="event.stopPropagation();" style="background:#fee2e2;color:#b91c1c;border:none;border-radius:4px;padding:0.3rem 0.6rem;cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            <i class="fa-solid fa-chevron-down" style="color:var(--text-muted);font-size:0.8rem;"></i>
                        </div>
                    </div>
                    <div class="log-card-body" id="log-<?php echo $log['id']; ?>">
                        <p style="font-size:0.82rem;font-weight:600;color:var(--text-muted);margin-bottom:0.5rem;">Message Body:</p>
                        <div class="msg-preview"><?php echo htmlspecialchars($log['message']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ═══ SMTP SETTINGS ═══ -->
        <div id="tab-smtp" class="tab-panel <?php echo $tab==='smtp'?'active':''; ?>">
            <div class="form-container">
                <div class="section-label">SMTP Server Configuration</div>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:1.5rem;">Configure your SMTP credentials to send real emails. Works with Gmail, Brevo, Mailgun, SendGrid, and any SMTP provider.</p>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fa-solid fa-server"></i> SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" value="<?php echo htmlspecialchars($smtp['smtp_host'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-plug"></i> SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control" placeholder="587" value="<?php echo htmlspecialchars($smtp['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fa-solid fa-user"></i> SMTP Username (Email)</label>
                            <input type="text" name="smtp_user" class="form-control" placeholder="yourmail@gmail.com" value="<?php echo htmlspecialchars($smtp['smtp_user'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-key"></i> SMTP Password / App Password</label>
                            <input type="password" name="smtp_pass" class="form-control" placeholder="••••••••••••" value="<?php echo htmlspecialchars($smtp['smtp_pass'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label><i class="fa-solid fa-shield-halved"></i> Security</label>
                            <select name="smtp_secure" class="form-control">
                                <option value="tls"  <?php echo ($smtp['smtp_secure']??'tls')==='tls'  ? 'selected':'' ; ?>>TLS (Recommended)</option>
                                <option value="ssl"  <?php echo ($smtp['smtp_secure']??'')==='ssl'  ? 'selected':'' ; ?>>SSL</option>
                                <option value="none" <?php echo ($smtp['smtp_secure']??'')==='none' ? 'selected':'' ; ?>>None</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-signature"></i> From Name</label>
                            <input type="text" name="smtp_from_name" class="form-control" placeholder="SCC Computer Club" value="<?php echo htmlspecialchars($smtp['smtp_from_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-at"></i> From Email</label>
                            <input type="email" name="smtp_from_email" class="form-control" placeholder="noreply@yourclub.com" value="<?php echo htmlspecialchars($smtp['smtp_from_email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;margin-bottom:1.5rem;font-size:0.82rem;color:var(--text-muted);">
                        <strong style="color:var(--text-main);">🔐 Your Server Settings (miniearn.site):</strong>
                        <table style="margin-top:0.6rem;border-collapse:collapse;width:100%;">
                            <tr style="background:rgba(0,0,0,0.03);"><td style="padding:5px 10px;font-weight:600;white-space:nowrap;">Outgoing (SMTP)</td><td style="padding:5px 10px;"><code>mail.miniearn.site</code> &nbsp;Port <code>465</code> &nbsp;<span style="background:#d1fae5;color:#065f46;border-radius:4px;padding:1px 6px;">SSL</span></td></tr>
                            <tr><td style="padding:5px 10px;font-weight:600;">Incoming (IMAP)</td><td style="padding:5px 10px;"><code>mail.miniearn.site</code> &nbsp;Port <code>993</code> &nbsp;<span style="background:#d1fae5;color:#065f46;border-radius:4px;padding:1px 6px;">SSL</span></td></tr>
                            <tr style="background:rgba(0,0,0,0.03);"><td style="padding:5px 10px;font-weight:600;">Incoming (POP3)</td><td style="padding:5px 10px;"><code></code> &nbsp;Port <code>995</code> &nbsp;<span style="background:#d1fae5;color:#065f46;border-radius:4px;padding:1px 6px;">SSL</span></td></tr>
                            <tr><td style="padding:5px 10px;font-weight:600;">Username</td><td style="padding:5px 10px;"><code>[EMAIL_ADDRESS]</code></td></tr>
                            <tr style="background:rgba(0,0,0,0.03);"><td style="padding:5px 10px;font-weight:600;">Password</td><td style="padding:5px 10px;">Your email account password (enter above)</td></tr>
                            <tr><td style="padding:5px 10px;font-weight:600;">Auth Required</td><td style="padding:5px 10px;"><span style="color:#047857;">✔ Yes</span> — IMAP, POP3, and SMTP all require authentication</td></tr>
                        </table>
                    </div>

                    <button type="submit" name="save_smtp" class="btn btn-primary" style="width:100%;padding:1rem;"><i class="fa-solid fa-floppy-disk"></i> Save SMTP Settings</button>
                </form>
            </div>
        </div>

    </div></div>

    <script src="js/script.js"></script>
    <script>
        function switchTab(id, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + id).classList.add('active');
        }
        function toggleCustomEmail() {
            const v = document.getElementById('targetSelect').value;
            document.getElementById('customEmailGroup').style.display = v === 'custom' ? 'block' : 'none';
        }
        function toggleLog(id) {
            const el = document.getElementById('log-' + id);
            el.classList.toggle('open');
        }
    </script>
    <script>
        // ── User data for autocomplete ──────────────────────────────────────
        const allUsers = <?php echo $user_emails_json; ?>;

        function switchTab(id, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + id).classList.add('active');
        }
        function toggleCustomEmail() {
            const v = document.getElementById('targetSelect').value;
            document.getElementById('customEmailGroup').style.display = v === 'custom' ? 'block' : 'none';
        }
        function toggleLog(id) {
            document.getElementById('log-' + id).classList.toggle('open');
        }

        // ── Autocomplete ────────────────────────────────────────────────────
        function acSearch(val) {
            const list = document.getElementById('acList');
            // Get the last token (after the last comma)
            const parts = val.split(',');
            const query = parts[parts.length - 1].trim().toLowerCase();
            list.innerHTML = '';
            if (!query) { list.style.display = 'none'; return; }

            const matches = allUsers.filter(u =>
                u.name.toLowerCase().includes(query) ||
                u.email.toLowerCase().includes(query)
            ).slice(0, 8);

            if (!matches.length) { list.style.display = 'none'; return; }

            matches.forEach(u => {
                const item = document.createElement('div');
                item.className = 'ac-item';
                const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}&background=4f46e5&color=fff&size=30`;
                item.innerHTML = `
                    <img src="${avatarUrl}" class="ac-avatar" alt="">
                    <div>
                        <div class="ac-name">${u.name} <span class="ac-role">${u.role}</span></div>
                        <div class="ac-email-text">${u.email}</div>
                    </div>`;
                item.onclick = () => {
                    const input = document.getElementById('customEmailInput');
                    const current = input.value.split(',').slice(0, -1).map(s => s.trim()).filter(Boolean);
                    current.push(u.email);
                    input.value = current.join(', ') + ', ';
                    list.style.display = 'none';
                    input.focus();
                };
                list.appendChild(item);
            });
            list.style.display = 'block';
        }
        // Close autocomplete on outside click
        document.addEventListener('click', e => {
            if (!e.target.closest('.ac-wrapper')) {
                const list = document.getElementById('acList');
                if (list) list.style.display = 'none';
            }
        });

        // ── Merge Tag Inserter ──────────────────────────────────────────────
        let lastFocused = null;
        document.addEventListener('focusin', e => {
            if (e.target.id === 'mailSubject' || e.target.id === 'mailBody') {
                lastFocused = e.target;
            }
        });
        function insertTag(tag) {
            const el = lastFocused || document.getElementById('mailBody');
            if (!el) return;
            const start = el.selectionStart;
            const end   = el.selectionEnd;
            el.value = el.value.slice(0, start) + tag + el.value.slice(end);
            el.selectionStart = el.selectionEnd = start + tag.length;
            el.focus();
        }

        // ── Sample Email Template ───────────────────────────────────────────
        function loadSampleTemplate() {
            document.getElementById('mailSubject').value = 'Hello {{name}} 👋 — Welcome to SCC Computer Club!';
            document.getElementById('mailBody').value = `<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:26px;">SCC Computer Club</h1>
    <p style="margin:8px 0 0;opacity:0.85;">Science Club Community</p>
  </div>

  <div style="background:white;padding:28px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h2 style="color:#1f2937;">Hello, {{name}}! 🎉</h2>
    <p style="color:#374151;line-height:1.7;">We are excited to have you as a valued member of the <strong>SCC Computer Club</strong>. Here is a quick overview of your profile:</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Role</td><td style="padding:10px 14px;color:#1f2937;">{{role}}</td></tr>
      <tr><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Department</td><td style="padding:10px 14px;color:#1f2937;">{{department}}</td></tr>
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Session</td><td style="padding:10px 14px;color:#1f2937;">{{session}}</td></tr>
    </table>

    <p style="color:#374151;line-height:1.7;">Stay tuned for upcoming events, workshops, and club activities. We look forward to seeing you at our next event!</p>

    <div style="text-align:center;margin-top:24px;">
      <a href="http://localhost/SCC" style="background:#4f46e5;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Visit Club Portal</a>
    </div>
  </div>

  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · You received this because you are registered at our portal · {{email}}</p>
</div>`;
        }
    </script>
</body>
</html>
