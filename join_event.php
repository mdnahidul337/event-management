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

// Check if already joined
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? AND event_id = ?");
$stmt->execute([$_SESSION['user_id'], $event_id]);
$existing_payment = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($existing_payment) {
        $error = "You have already submitted a request for this event.";
    } else {
        if ($event['type'] === 'Free') {
            // Auto approve free event join
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, method, sender_number, trx_id, status) VALUES (?, ?, 0, 'Bkash', 'N/A', ?, 'Approved')");
            $trx = 'FREE_' . time() . rand(1000, 9999);
            $stmt->execute([$_SESSION['user_id'], $event_id, $trx]);
            $success = "Successfully joined the free event!";
            $existing_payment = true; // prevent resubmit
        } else {
            // Paid event logic
            $method = $_POST['method'];
            $sender_number = trim($_POST['sender_number']);
            $trx_id = trim($_POST['trx_id']);
            $amount = floatval($_POST['amount']);

            // Simple validation
            if (empty($method) || empty($sender_number) || empty($trx_id) || $amount <= 0) {
                $error = "Please fill out all payment fields correctly.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, method, sender_number, trx_id, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
                try {
                    $stmt->execute([$_SESSION['user_id'], $event_id, $amount, $method, $sender_number, $trx_id]);
                    $success = "Payment submitted successfully. Please wait for accounting approval.";
                    $existing_payment = true;
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
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-light: #f3f4f6;
            --text-dark: #1f2937;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }
        
        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 5%; background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px); position: fixed; width: 100%; top: 0; z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none;}
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.3s; }
        .btn-register { padding: 0.5rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; background: var(--primary); color: white; }

        .join-container { margin: 8rem auto 4rem; background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 600px; }
        .event-info { margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 2rem; }
        .event-info h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; background: var(--primary); display: inline-block; margin-bottom: 1rem; }
        .badge-paid { background: #f59e0b; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; }
        .btn { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 6px; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .alert-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="join-container">
        <div class="event-info">
            <span class="badge <?php echo $event['type'] === 'Paid' ? 'badge-paid' : ''; ?>"><?php echo $event['type']; ?> Event</span>
            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
            <p style="color:#6b7280; margin-bottom: 0.5rem;"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y • h:i A', strtotime($event['start_date'])); ?></p>
            <p style="color:#6b7280;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['location']); ?></p>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <?php if ($existing_payment): ?>
            <div class="alert alert-info">
                <strong>Status:</strong> 
                You have already requested to join this event. 
                <?php if(isset($existing_payment['status'])) echo "(Current Status: " . htmlspecialchars($existing_payment['status']) . ")"; ?>
            </div>
            <a href="public_events.php" style="display:block; text-align:center; margin-top:2rem; color:var(--primary); font-weight:600; text-decoration:none;">← Back to Events</a>
        <?php else: ?>

            <?php if ($event['type'] === 'Free'): ?>
                <form method="POST">
                    <p style="margin-bottom: 1.5rem; text-align:center;">This is a free event. Click below to confirm your attendance.</p>
                    <button type="submit" class="btn">Confirm Join</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <div style="background:#f9fafb; padding:1.5rem; border-radius:6px; margin-bottom:1.5rem; border:1px solid #e5e7eb;">
                        <h3 style="margin-bottom:1rem;">Payment Instructions</h3>
                        <p style="font-size:0.9rem; color:#4b5563; margin-bottom:0.5rem;">1. Send the event fee via Bkash or Nagad to <strong>017XXXXXXXX</strong></p>
                        <p style="font-size:0.9rem; color:#4b5563;">2. Enter your transaction details below to verify your ticket.</p>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="method" class="form-control" required>
                            <option value="Bkash">Bkash</option>
                            <option value="Nagad">Nagad</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sender Number</label>
                        <input type="text" name="sender_number" class="form-control" placeholder="e.g. 01712345678" required>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID (TrxID)</label>
                        <input type="text" name="trx_id" class="form-control" placeholder="e.g. 8H3KJ9LD" required>
                    </div>
                    <div class="form-group">
                        <label>Amount Sent (৳)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 200" required>
                    </div>

                    <button type="submit" class="btn">Submit Payment</button>
                </form>
            <?php endif; ?>

        <?php endif; ?>
    </div>

</body>
</html>
