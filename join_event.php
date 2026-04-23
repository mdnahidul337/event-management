<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=public_events.php");
    exit;
}

require_once 'includes/db_connect.php';

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    header("Location: public_events.php");
    exit;
}

// Fetch settings
$settings = [];
$stmt_settings = $pdo->query("SELECT * FROM settings");
while ($row = $stmt_settings->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$registration_fee = floatval($settings['registration_fee'] ?? 0);
$registration_fee_cutoff = $settings['registration_fee_cutoff'] ?? '2000-01-01 00:00:00';
$payment_instructions = $settings['payment_instructions'] ?? "Please send the fee to our official number and provide the Transaction ID below.";

// Fetch user creation date
$stmt_user = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user_data = $stmt_user->fetch();
$user_created_at = $user_data['created_at'];

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: public_events.php");
    exit;
}

$error = '';
$success = '';
$join_code = '';
$registration_fee_status = 'N/A'; // N/A, Paid, Unpaid

// ── Check Registration Fee Status ───────────────────────────────────────────
if ($registration_fee > 0 && $user_created_at >= $registration_fee_cutoff) {
    $stmt_reg = $pdo->prepare("SELECT status FROM payments WHERE user_id = ? AND event_id IS NULL ORDER BY created_at DESC LIMIT 1");
    $stmt_reg->execute([$_SESSION['user_id']]);
    $reg_pay = $stmt_reg->fetch();
    
    if (!$reg_pay || $reg_pay['status'] !== 'Approved') {
        $registration_fee_status = $reg_pay ? $reg_pay['status'] : 'Unpaid';
    } else {
        $registration_fee_status = 'Paid';
    }
} else {
    $registration_fee_status = 'Paid'; // No fee required for old members or if fee is 0
}

// ── Check if already joined ──────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? AND event_id = ?");
$stmt->execute([$_SESSION['user_id'], $event_id]);
$existing_payment = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $registration_fee_status === 'Paid') {
    if (isset($_POST['resend_ticket']) && $existing_payment && $existing_payment['status'] === 'Approved') {
        require_once 'includes/mailer_helper.php';
        $user = $pdo->query("SELECT name, email FROM users WHERE id = " . $_SESSION['user_id'])->fetch();
        send_template_email($pdo, 'event_registered', [
            'name'           => $user['name'],
            'email'          => $user['email'],
            'event_title'    => $event['title'],
            'event_date'     => date('M d, Y • h:i A', strtotime($event['start_date'])),
            'event_location' => $event['location'],
            'event_type'     => $event['type'],
            'join_code'      => $existing_payment['join_code']
        ]);
        $success = "Ticket details have been resent to your email (<strong>" . htmlspecialchars($user['email']) . "</strong>).";
    } elseif ($existing_payment) {
        $error = "You have already submitted a request for this event.";
    } else {
        if ($event['type'] === 'Free') {
            // Auto approve free event join
            $trx = 'FREE_' . time() . rand(1000, 9999);
            $user_note = isset($_POST['user_note']) ? trim($_POST['user_note']) : '';
            
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, method, sender_number, trx_id, status, user_note) VALUES (?, ?, 0, 'Bkash', 'N/A', ?, 'Approved', ?)");
            $stmt->execute([$_SESSION['user_id'], $event_id, $trx, $user_note]);
            $payment_id = $pdo->lastInsertId();
            
            // Generate Join Code: CST-0000
            $join_code = 'CST-' . str_pad($payment_id, 4, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE payments SET join_code = ? WHERE id = ?")->execute([$join_code, $payment_id]);

            // Send confirmation email
            require_once 'includes/mailer_helper.php';
            $user = $pdo->query("SELECT name, email FROM users WHERE id = " . $_SESSION['user_id'])->fetch();
            send_template_email($pdo, 'event_registered', [
                'name'           => $user['name'],
                'email'          => $user['email'],
                'event_title'    => $event['title'],
                'event_date'     => date('M d, Y • h:i A', strtotime($event['start_date'])),
                'event_location' => $event['location'],
                'event_type'     => 'Free',
                'join_code'      => $join_code
            ]);

            $success = "Successfully joined! Your Ticket Code is: <strong>$join_code</strong>. A confirmation email has been sent.";
            $existing_payment = ['status' => 'Approved', 'join_code' => $join_code]; 
        } else {
            // Paid event logic
            $method = $_POST['method'];
            $sender_number = trim($_POST['sender_number']);
            $trx_id = trim($_POST['trx_id']);
            $amount = floatval($_POST['amount']);
            $user_note = isset($_POST['user_note']) ? trim($_POST['user_note']) : '';

            // Simple validation
            if (empty($method) || empty($sender_number) || empty($trx_id) || $amount < $event['fee']) {
                $error = "Please fill out all payment fields correctly. Minimum amount is " . $event['fee'] . " tk.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, method, sender_number, trx_id, status, user_note) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
                try {
                    $stmt->execute([$_SESSION['user_id'], $event_id, $amount, $method, $sender_number, $trx_id, $user_note]);
                    $payment_id = $pdo->lastInsertId();
                    
                    $success = "Payment submitted successfully! Please wait for the Accounting team to verify your transaction. Your Ticket Code will be sent to your email once approved.";
                    $existing_payment = ['status' => 'Pending'];
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000) { // Integrity constraint (duplicate trx_id)
                        $error = "This Transaction ID has already been used.";
                    } else {
                        $error = "An error occurred while processing your request.";
                    }
                }
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
    <title>Join <?php echo htmlspecialchars($event['title']); ?> - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .join-card { background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); max-width: 900px; margin: 4rem auto; border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; }
        .join-header-img { width: 100%; height: 350px; object-fit: cover; border-bottom: 1px solid var(--border); }
        .join-body { padding: 3rem; }
        .event-main-info { margin-bottom: 2.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 2rem; }
        .event-description { color: #4b5563; line-height: 1.7; margin-bottom: 2rem; font-size: 1.05rem; }
        .payment-section { background: #f8fafc; padding: 2rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; }
        .alert { padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
        
        .extra-btn { background: #e0e7ff; color: #4338ca; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 700; transition: 0.2s; }
        .extra-btn:hover { background: #c7d2fe; }
        
        @media (max-width: 768px) {
            .join-header-img { height: 200px; }
            .join-body { padding: 1.5rem; }
            .join-card { margin: 2rem 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <div class="join-card">
        <?php 
        $img_src = 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
        if ($event['image']) {
            $img_src = (strpos($event['image'], 'http') === 0) ? $event['image'] : 'assets/image/Events/' . $event['image'];
        }
        ?>
        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="join-header-img">
        
        <div class="join-body">
            <div class="event-main-info">
                <span class="badge <?php echo $event['type'] === 'Paid' ? 'badge-paid' : 'badge-free'; ?>" style="margin-bottom:1rem;"><?php echo $event['type']; ?> Event</span>
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: #111827;"><?php echo htmlspecialchars($event['title']); ?></h1>
                
                <div style="display:flex; flex-wrap:wrap; gap:1.5rem; color:#6b7280; font-weight:500;">
                    <span><i class="fa-regular fa-calendar-days" style="color:var(--primary);"></i> <?php echo date('F d, Y • h:i A', strtotime($event['start_date'])); ?></span>
                    <span><i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                    <?php if($event['type'] === 'Paid'): ?>
                        <span><i class="fa-solid fa-tags" style="color:var(--primary);"></i> Entry Fee: ৳<?php echo number_format($event['fee'], 2); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="event-description">
                <h3 style="color:#111827; margin-bottom:0.75rem;">About this Event</h3>
                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> 
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($registration_fee_status !== 'Paid'): ?>
                <div class="alert alert-warning">
                    <i class="fa-solid fa-lock" style="font-size: 1.5rem;"></i>
                    <div>
                        <h4 style="margin-bottom:0.25rem;">Registration Fee Required</h4>
                        <p style="font-size:0.9rem; font-weight:500;">
                            Your club registration fee status is <strong><?php echo $registration_fee_status; ?></strong>. 
                            You must have an approved registration fee payment (৳<?php echo $registration_fee; ?>) to join any events.
                            <?php if($registration_fee_status === 'Unpaid'): ?>
                                <br><a href="profile.php" style="color:var(--primary); text-decoration:underline;">Click here to pay your registration fee</a>.
                            <?php else: ?>
                                <br>Please wait for accounting to approve your initial registration payment.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php elseif ($existing_payment): ?>
                <div class="alert alert-info" style="justify-content:center; flex-direction:column; text-align:center; padding:2.5rem;">
                    <i class="fa-solid fa-ticket" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h2 style="margin-bottom:0.5rem;">You're Registered!</h2>
                    <p style="margin-bottom:1.5rem;">Your registration status for this event is: <strong><?php echo htmlspecialchars($existing_payment['status']); ?></strong></p>
                    
                    <?php if(!empty($existing_payment['join_code'])): ?>
                        <div style="background:white; padding:1rem 2rem; border-radius:12px; border:2px dashed var(--primary); margin-bottom:1.5rem;">
                            <span style="font-size:0.85rem; text-transform:uppercase; font-weight:700; color:#6b7280; display:block; margin-bottom:0.25rem;">Your Ticket Code</span>
                            <span style="font-size:2rem; font-weight:900; color:var(--primary); letter-spacing:2px;"><?php echo htmlspecialchars($existing_payment['join_code']); ?></span>
                        </div>
                        
                        <?php if($existing_payment['status'] === 'Approved'): ?>
                            <form method="POST">
                                <button type="submit" name="resend_ticket" class="btn" style="background:#4b5563; padding:0.6rem 1.2rem; font-size:0.9rem;">
                                    <i class="fa-solid fa-paper-plane"></i> Resend Ticket to Email
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="public_events.php" style="margin-top:2rem; color:var(--primary); font-weight:700; text-decoration:none;">← Back to All Events</a>
                </div>
            <?php else: ?>
                
                <div class="payment-section">
                    <h3 style="margin-bottom:1.5rem;"><i class="fa-solid fa-file-signature" style="color:var(--primary);"></i> Join Event</h3>
                    
                    <?php if ($event['type'] === 'Free'): ?>
                        <form method="POST">
                            <p style="margin-bottom: 2rem; color:#4b5563;">Attendance for this event is free for all active members. Please confirm your presence below.</p>
                            
                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <label class="form-label">Special Request / Note (Optional)</label>
                                <textarea name="user_note" class="form-input" rows="3" placeholder="e.g. Any dietary requirements or notes for the team..."></textarea>
                            </div>

                            <button type="submit" class="btn" style="width:100%; padding:1.25rem; font-size:1.1rem;">Confirm Attendance</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" id="paymentForm">
                            <div class="payment-instructions" style="background:white; padding:2rem; border-radius:12px; margin-bottom:2rem; border:1px solid #e5e7eb;">
                                <h4 style="margin-bottom:1rem; color:#111827;"><i class="fa-solid fa-circle-info" style="color:var(--primary);"></i> Payment Instructions</h4>
                                <div style="font-size:0.95rem; color:#4b5563; line-height:1.6;">
                                    <?php echo nl2br(htmlspecialchars($payment_instructions)); ?>
                                </div>
                            </div>

                            <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Payment Method</label>
                                    <select name="method" class="form-input" required>
                                        <option value="Bkash">Bkash</option>
                                        <option value="Nagad">Nagad</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Sender Number</label>
                                    <input type="text" name="sender_number" class="form-input" placeholder="e.g. 01712345678" required>
                                </div>
                            </div>

                            <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Transaction ID (TrxID)</label>
                                    <input type="text" name="trx_id" class="form-input" placeholder="e.g. 8H3KJ9LD" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Amount Sent (৳)</label>
                                    <div style="display:flex; gap:0.5rem; align-items:center;">
                                        <input type="number" step="1" name="amount" id="amountInput" class="form-input" value="<?php echo $event['fee']; ?>" min="<?php echo $event['fee']; ?>" required>
                                        <button type="button" class="extra-btn" onclick="addExtra(10)">+10</button>
                                    </div>
                                    <small style="color:#6b7280; display:block; margin-top:0.3rem;">Min Fee: <?php echo $event['fee']; ?> tk</small>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom:2rem;">
                                <label class="form-label">Additional Message (Optional)</label>
                                <textarea name="user_note" class="form-input" rows="3" placeholder="e.g. Reference name or message for the organizers..."></textarea>
                            </div>

                            <button type="submit" class="btn" style="width:100%; padding:1.25rem; font-size:1.1rem;">Submit Registration & Pay</button>
                        </form>
                        <script>
                            function addExtra(val) {
                                const input = document.getElementById('amountInput');
                                input.value = parseInt(input.value) + val;
                            }
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
