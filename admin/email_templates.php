<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] < 100) { header("Location: dashboard.php"); exit; }
require_once '../includes/db_connect.php';
require_once '../includes/mailer_helper.php';
ensure_templates_table($pdo);

$success = '';
$error   = '';
$edit_slug = $_GET['edit'] ?? null;
$preview_slug = $_GET['preview'] ?? null;

// ─── Save template ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $slug    = trim($_POST['slug']);
    $subject = trim($_POST['subject']);
    $body    = trim($_POST['body']);
    $label   = trim($_POST['label']);
    $pdo->prepare("UPDATE email_templates SET label=?, subject=?, body=? WHERE slug=?")
        ->execute([$label, $subject, $body, $slug]);
    $success = "Template '{$label}' saved!";
    $edit_slug = $slug;
}

// ─── Send test email ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $slug      = trim($_POST['test_slug']);
    $test_email = trim($_POST['test_email']);
    $ok = send_template_email($pdo, $slug, [
        'name'          => 'Test User',
        'email'         => $test_email,
        'department'    => 'CSE',
        'session'       => '2023-24',
        'blood_group'   => 'A+',
        'reset_link'    => 'http://localhost/SCC/reset_password.php?token=TESTTOKEN',
        'event_title'   => 'Annual Tech Fest 2025',
        'event_date'    => 'May 10, 2025',
        'event_location'=> 'Main Hall',
        'event_type'    => 'Free',
        'amount'        => '500',
        'method'        => 'Bkash',
        'trx_id'        => 'TXN1234567',
        'date'          => date('F j, Y g:i A'),
    ]);
    $success = $ok ? "✅ Test email sent to $test_email!" : "❌ Failed to send. Check SMTP settings.";
    $edit_slug = $_POST['test_slug'];
}

// ─── Fetch all templates ────────────────────────────────────────────────────
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY id")->fetchAll();
$editing   = $edit_slug ? $pdo->prepare("SELECT * FROM email_templates WHERE slug=?") : null;
if ($editing) { $editing->execute([$edit_slug]); $editing = $editing->fetch(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .two-col { display:grid; grid-template-columns:280px 1fr; gap:1.5rem; align-items:start; }
        .panel { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); }
        .tpl-item { padding:0.75rem 1rem; border-radius:8px; cursor:pointer; margin-bottom:0.4rem; border:1.5px solid transparent; display:flex; align-items:center; gap:0.75rem; transition:all 0.2s; }
        .tpl-item:hover { background:var(--bg-color); }
        .tpl-item.active { background:var(--bg-color); border-color:var(--primary-color); }
        .tpl-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; font-weight:600; margin-bottom:0.4rem; font-size:0.88rem; }
        .form-control { width:100%; padding:0.75rem; border:1.5px solid var(--border-color); border-radius:var(--radius); background:var(--bg-color); color:var(--text-main); outline:none; font-family:'Inter',sans-serif; }
        .form-control:focus { border-color:var(--primary-color); }
        .tag-pill { display:inline-block; background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; border-radius:20px; padding:2px 9px; font-size:0.75rem; font-weight:700; cursor:pointer; margin:2px; font-family:monospace; }
        .tag-pill:hover { background:#4f46e5; color:white; }
        .alert { padding:1rem 1.2rem; border-radius:var(--radius); margin-bottom:1.5rem; border-left:4px solid; font-weight:500; }
        .alert-success { background:#d1fae5; color:#047857; border-color:#10b981; }
        .alert-error   { background:#fee2e2; color:#b91c1c; border-color:#ef4444; }
        .preview-frame { width:100%; height:450px; border:1px solid var(--border-color); border-radius:var(--radius); margin-top:1rem; }
        .test-row { display:flex; gap:0.75rem; align-items:flex-end; }
        @media(max-width:900px) { .two-col { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
            <h2><i class="fa-solid fa-envelope-circle-check"></i> Email Templates</h2>
            <a href="mailer.php" style="color:var(--text-muted);text-decoration:none;font-size:0.9rem;"><i class="fa-solid fa-arrow-left"></i> Back to Mailer</a>
        </div>

        <div class="two-col">
            <!-- Template List -->
            <div class="panel">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:1rem;">System Templates</div>
                <?php
                $icons = [
                    'welcome'          => ['fa-user-plus','#10b981','#d1fae5'],
                    'forgot_password'  => ['fa-key','#ef4444','#fee2e2'],
                    'password_changed' => ['fa-shield-halved','#f59e0b','#fef3c7'],
                    'event_registered' => ['fa-calendar-check','#8b5cf6','#ede9fe'],
                    'payment_approved' => ['fa-circle-check','#10b981','#d1fae5'],
                    'payment_rejected' => ['fa-circle-xmark','#ef4444','#fee2e2'],
                ];
                foreach ($templates as $t):
                    $ic = $icons[$t['slug']] ?? ['fa-envelope','#6b7280','#f3f4f6'];
                ?>
                <a href="email_templates.php?edit=<?php echo $t['slug']; ?>" style="text-decoration:none;color:inherit;">
                    <div class="tpl-item <?php echo $edit_slug===$t['slug']?'active':''; ?>">
                        <div class="tpl-icon" style="background:<?php echo $ic[2]; ?>;color:<?php echo $ic[1]; ?>;"><i class="fa-solid <?php echo $ic[0]; ?>"></i></div>
                        <div>
                            <div style="font-weight:600;font-size:0.88rem;"><?php echo htmlspecialchars($t['label']); ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);font-family:monospace;"><?php echo $t['slug']; ?></div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Editor / Placeholder -->
            <div>
                <?php if ($editing): ?>
                <div class="panel">
                    <h3 style="margin-bottom:1.5rem;"><i class="fa-solid fa-pen-ruler"></i> Editing: <?php echo htmlspecialchars($editing['label']); ?></h3>

                    <!-- Available merge tags -->
                    <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:var(--radius);padding:0.75rem 1rem;margin-bottom:1.5rem;">
                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);margin-bottom:0.5rem;"><i class="fa-solid fa-tags"></i> Click tag to insert:</div>
                        <?php
                        $all_tags = ['{{name}}','{{email}}','{{department}}','{{session}}','{{blood_group}}',
                                     '{{reset_link}}','{{date}}','{{event_title}}','{{event_date}}',
                                     '{{event_location}}','{{event_type}}','{{amount}}','{{method}}','{{trx_id}}','{{site_url}}'];
                        foreach ($all_tags as $tag):
                        ?>
                        <span class="tag-pill" onclick="insertBodyTag('<?php echo $tag; ?>')"><?php echo $tag; ?></span>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editing['slug']); ?>">
                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" name="label" class="form-control" value="<?php echo htmlspecialchars($editing['label']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" id="tpl_subject" class="form-control" value="<?php echo htmlspecialchars($editing['subject']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>HTML Body</label>
                            <textarea name="body" id="tpl_body" class="form-control" rows="16" style="font-family:monospace;font-size:0.82rem;" required><?php echo htmlspecialchars($editing['body']); ?></textarea>
                        </div>
                        <div style="display:flex;gap:1rem;">
                            <button type="submit" name="save_template" class="btn btn-primary" style="flex:1;padding:0.9rem;"><i class="fa-solid fa-floppy-disk"></i> Save Template</button>
                            <button type="button" onclick="previewBody()" class="btn" style="flex:0 0 auto;padding:0.9rem 1.4rem;background:var(--bg-color);color:var(--text-main);border:1.5px solid var(--border-color);"><i class="fa-solid fa-eye"></i> Preview</button>
                        </div>
                    </form>

                    <!-- Preview -->
                    <div id="previewBox" style="display:none;margin-top:1.5rem;">
                        <div style="font-weight:600;margin-bottom:0.5rem;"><i class="fa-solid fa-eye"></i> Live Preview</div>
                        <iframe id="previewFrame" class="preview-frame"></iframe>
                    </div>

                    <!-- Send test -->
                    <hr style="margin:1.5rem 0;border:none;border-top:1px solid var(--border-color);">
                    <div style="font-weight:600;margin-bottom:0.75rem;"><i class="fa-solid fa-paper-plane"></i> Send Test Email</div>
                    <form method="POST">
                        <input type="hidden" name="test_slug" value="<?php echo htmlspecialchars($editing['slug']); ?>">
                        <div class="test-row">
                            <div class="form-group" style="flex:1;margin:0;">
                                <input type="email" name="test_email" class="form-control" placeholder="recipient@example.com" required value="<?php echo htmlspecialchars($smtp['smtp_user'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="send_test" style="padding:0.75rem 1.2rem;background:#8b5cf6;color:white;border:none;border-radius:var(--radius);font-weight:600;cursor:pointer;white-space:nowrap;"><i class="fa-solid fa-vial"></i> Send Test</button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="panel" style="text-align:center;padding:4rem;color:var(--text-muted);">
                    <i class="fa-solid fa-envelope-open-text" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
                    <p>Select a template from the left to edit it.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div></div>

    <script src="js/script.js"></script>
    <script>
        let lastFocused = null;
        ['tpl_subject','tpl_body'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('focus', () => lastFocused = el);
        });
        function insertBodyTag(tag) {
            const el = lastFocused || document.getElementById('tpl_body');
            if (!el) return;
            const s = el.selectionStart, e = el.selectionEnd;
            el.value = el.value.slice(0, s) + tag + el.value.slice(e);
            el.selectionStart = el.selectionEnd = s + tag.length;
            el.focus();
        }
        function previewBody() {
            const body = document.getElementById('tpl_body').value;
            const box  = document.getElementById('previewBox');
            const frame = document.getElementById('previewFrame');
            box.style.display = 'block';
            frame.srcdoc = body;
        }
    </script>
</body>
</html>
