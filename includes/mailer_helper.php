<?php
/**
 * SCC Mailer Helper
 * Loads email templates from DB, replaces merge tags, and sends via SMTPMailer.
 * Usage: send_template_email($pdo, 'welcome', ['name'=>..., 'email'=>...])
 */
require_once __DIR__ . '/SMTPMailer.php';

// ─── Auto-create email_templates table ────────────────────────────────────
function ensure_templates_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(80) NOT NULL UNIQUE,
        label VARCHAR(120) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Seed default templates (INSERT IGNORE keeps user edits safe)
    $defaults = [
        [
            'slug'    => 'welcome',
            'label'   => 'Registration Welcome',
            'subject' => 'Welcome to SCC Computer Club, {{name}}! 🎉',
            'body'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:26px;">SCC Computer Club</h1>
    <p style="margin:8px 0 0;opacity:0.85;">Welcome to the family!</p>
  </div>
  <div style="background:white;padding:28px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h2 style="color:#1f2937;">Hello, {{name}}! 🎉</h2>
    <p style="color:#374151;line-height:1.7;">Your account has been <strong>successfully created</strong>. Here are your details:</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Email</td><td style="padding:10px 14px;">{{email}}</td></tr>
      <tr><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Department</td><td style="padding:10px 14px;">{{department}}</td></tr>
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Session</td><td style="padding:10px 14px;">{{session}}</td></tr>
    </table>
    <p style="color:#374151;line-height:1.7;">You can now log in to the portal and explore upcoming events!</p>
    <div style="text-align:center;margin-top:24px;">
      <a href="{{site_url}}/login.php" style="background:#4f46e5;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Login to Portal</a>
    </div>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · {{site_url}}</p>
</div>',
        ],
        [
            'slug'    => 'forgot_password',
            'label'   => 'Forgot Password',
            'subject' => 'Reset your SCC password, {{name}}',
            'body'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#ef4444,#dc2626);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:24px;">🔒 Password Reset</h1>
    <p style="margin:8px 0 0;opacity:0.85;">SCC Computer Club</p>
  </div>
  <div style="background:white;padding:28px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h2 style="color:#1f2937;">Hello, {{name}}</h2>
    <p style="color:#374151;line-height:1.7;">We received a request to reset your password. Click the button below to set a new password. This link expires in <strong>1 hour</strong>.</p>
    <div style="text-align:center;margin:28px 0;">
      <a href="{{reset_link}}" style="background:#ef4444;color:white;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px;">Reset My Password</a>
    </div>
    <p style="color:#6b7280;font-size:13px;line-height:1.6;">If you did not request this, please ignore this email. Your password will remain unchanged.</p>
    <p style="color:#6b7280;font-size:12px;word-break:break-all;">Or copy this link: {{reset_link}}</p>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · {{site_url}}</p>
</div>',
        ],
        [
            'slug'    => 'password_changed',
            'label'   => 'Password Changed Confirmation',
            'subject' => 'Your SCC password was changed',
            'body'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#10b981,#059669);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:24px;">✅ Password Updated</h1>
    <p style="margin:8px 0 0;opacity:0.85;">SCC Computer Club</p>
  </div>
  <div style="background:white;padding:28px;border-radius:10px;">
    <h2 style="color:#1f2937;">Hello, {{name}}</h2>
    <p style="color:#374151;line-height:1.7;">Your SCC Computer Club account password was successfully changed on <strong>{{date}}</strong>.</p>
    <p style="color:#374151;line-height:1.7;">If you did not make this change, please contact us immediately or reset your password.</p>
    <div style="text-align:center;margin-top:24px;">
      <a href="{{site_url}}/forgot_password.php" style="background:#10b981;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Reset Password</a>
    </div>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · {{site_url}}</p>
</div>',
        ],
        [
            'slug'    => 'event_registered',
            'label'   => 'Event Registration Confirmation',
            'subject' => 'You are registered for {{event_title}}! 🎫',
            'body'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:24px;">🎫 Registration Confirmed</h1>
    <p style="margin:8px 0 0;opacity:0.85;">SCC Computer Club</p>
  </div>
  <div style="background:white;padding:28px;border-radius:10px;">
    <h2 style="color:#1f2937;">Hello, {{name}}!</h2>
    <p style="color:#374151;line-height:1.7;">You are officially registered for <strong>{{event_title}}</strong>.</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Event</td><td style="padding:10px 14px;">{{event_title}}</td></tr>
      <tr><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Date</td><td style="padding:10px 14px;">{{event_date}}</td></tr>
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Location</td><td style="padding:10px 14px;">{{event_location}}</td></tr>
      <tr><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Type</td><td style="padding:10px 14px;">{{event_type}}</td></tr>
    </table>
    <p style="color:#374151;line-height:1.7;">We look forward to seeing you there. Stay tuned for any updates!</p>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · {{site_url}}</p>
</div>',
        ],
        [
            'slug'    => 'payment_approved',
            'label'   => 'Payment Approved',
            'subject' => 'Payment Approved ✅ — SCC Computer Club',
            'body'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#10b981,#047857);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:24px;">✅ Payment Approved</h1>
    <p style="margin:8px 0 0;opacity:0.85;">SCC Computer Club</p>
  </div>
  <div style="background:white;padding:28px;border-radius:10px;">
    <h2 style="color:#1f2937;">Hello, {{name}}</h2>
    <p style="color:#374151;line-height:1.7;">Your payment of <strong>৳{{amount}}</strong> has been <strong style="color:#10b981;">approved</strong>.</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Amount</td><td style="padding:10px 14px;">৳{{amount}}</td></tr>
      <tr><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Method</td><td style="padding:10px 14px;">{{method}}</td></tr>
      <tr style="background:#f3f4f6;"><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Transaction ID</td><td style="padding:10px 14px;">{{trx_id}}</td></tr>
      <tr><td style="padding:10px 14px;font-weight:600;color:#6b7280;">Event</td><td style="padding:10px 14px;">{{event_title}}</td></tr>
    </table>
    <p style="color:#374151;line-height:1.7;">Thank you for your contribution to the SCC Computer Club!</p>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · {{site_url}}</p>
</div>',
        ],
        [
            'slug'    => 'payment_rejected',
            'label'   => 'Payment Rejected',
            'subject' => 'Payment Rejected ❌ — SCC Computer Club',
            'body'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9fafb;padding:20px;border-radius:12px;">
  <div style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:30px;border-radius:10px;text-align:center;color:white;margin-bottom:20px;">
    <h1 style="margin:0;font-size:24px;">❌ Payment Rejected</h1>
    <p style="margin:8px 0 0;opacity:0.85;">SCC Computer Club</p>
  </div>
  <div style="background:white;padding:28px;border-radius:10px;">
    <h2 style="color:#1f2937;">Hello, {{name}}</h2>
    <p style="color:#374151;line-height:1.7;">Unfortunately your payment of <strong>৳{{amount}}</strong> via <strong>{{method}}</strong> (TrxID: <code>{{trx_id}}</code>) could not be verified and has been <strong style="color:#ef4444;">rejected</strong>.</p>
    <p style="color:#374151;line-height:1.7;">If you believe this is a mistake, please contact us with your transaction details.</p>
    <div style="text-align:center;margin-top:24px;">
      <a href="{{site_url}}" style="background:#ef4444;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Contact Support</a>
    </div>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:20px;">© SCC Computer Club · {{site_url}}</p>
</div>',
        ],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO email_templates (slug, label, subject, body) VALUES (?, ?, ?, ?)");
    foreach ($defaults as $t) {
        $stmt->execute([$t['slug'], $t['label'], $t['subject'], $t['body']]);
    }
}

// ─── Main send function ────────────────────────────────────────────────────
function send_template_email(PDO $pdo, string $slug, array $vars = []): bool
{
    ensure_templates_table($pdo);

    // Load template
    $stmt = $pdo->prepare("SELECT subject, body FROM email_templates WHERE slug = ?");
    $stmt->execute([$slug]);
    $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tpl) return false;

    // Load SMTP settings
    $rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'")->fetchAll();
    $smtp = [];
    foreach ($rows as $r) $smtp[$r['setting_key']] = $r['setting_value'];

    if (empty($smtp['smtp_host']) || empty($smtp['smtp_user']) || empty($smtp['smtp_pass'])) {
        return false; // SMTP not configured
    }

    // Add common vars
    $vars['site_url'] = $vars['site_url'] ?? 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/SCC';
    $vars['date']     = $vars['date']     ?? date('F j, Y g:i A');

    // Replace tags
    $find    = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));
    $replace = array_values($vars);
    $subject = str_replace($find, $replace, $tpl['subject']);
    $body    = str_replace($find, $replace, $tpl['body']);

    $mailer = new SMTPMailer([
        'host'       => $smtp['smtp_host'],
        'port'       => $smtp['smtp_port']       ?? 465,
        'user'       => $smtp['smtp_user'],
        'pass'       => $smtp['smtp_pass'],
        'from_email' => $smtp['smtp_from_email'] ?? $smtp['smtp_user'],
        'from_name'  => $smtp['smtp_from_name']  ?? 'SCC Club',
        'secure'     => $smtp['smtp_secure']     ?? 'ssl',
    ]);

    $to_name  = $vars['name']  ?? $vars['email'] ?? 'Member';
    $to_email = $vars['email'] ?? '';
    if (!$to_email) return false;

    $ok = $mailer->send($to_email, $to_name, $subject, $body);
    $mailer->quit();
    return $ok;
}
