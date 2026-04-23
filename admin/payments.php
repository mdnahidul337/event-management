<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$error = '';
$success = '';

// Level 80+ (Accounting, Admin, SuperAdmin) can manage payments
$can_manage_payments = $_SESSION['role_level'] >= 80;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_payments) {
    if (isset($_POST['update_status'])) {
        $payment_id = $_POST['payment_id'];
        $new_status = $_POST['status']; // 'Approved' or 'Rejected'
        
        if (in_array($new_status, ['Approved', 'Rejected'])) {
            $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $payment_id])) {
                $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'UPDATE', 'payments', ?, ?)")
                    ->execute([$_SESSION['user_id'], "Marked payment ID $payment_id as $new_status", $_SERVER['REMOTE_ADDR']]);
                $success = "Payment $new_status successfully.";
            } else {
                $error = "Failed to update payment status.";
            }
        }
    }
}

// Fetch all payments with user and event details
$payments = $pdo->query("
    SELECT p.*, u.name as user_name, u.email as user_email, e.title as event_title 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN events e ON p.event_id = e.id 
    ORDER BY p.created_at DESC
")->fetchAll();

// Fetch total approved balance
$total_approved = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'Approved'")->fetchColumn();
if (!$total_approved) $total_approved = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; margin-right: 0.25rem; }
        .btn-success { background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-danger { background-color: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .method-logo { height: 20px; vertical-align: middle; margin-right: 5px; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                <h2>Manage Payments</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); border-left: 4px solid #10b981;">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Collected Balance</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); margin-top: 0.5rem;">৳ <?php echo number_format($total_approved, 2); ?></div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Event</th>
                            <th>Amount</th>
                            <th>Method & TrxID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <?php if($can_manage_payments): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['user_name']); ?></strong><br>
                                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($p['user_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($p['event_title'] ?? 'General Fee'); ?></td>
                            <td><strong>৳ <?php echo number_format($p['amount'], 2); ?></strong></td>
                            <td>
                                <?php 
                                    $logo = $p['method'] === 'Bkash' ? '../assets/image/payment-methods/bkash-logo.png' : '../assets/image/payment-methods/nagad-logo.png';
                                ?>
                                <img src="<?php echo $logo; ?>" alt="<?php echo $p['method']; ?>" class="method-logo">
                                <?php echo htmlspecialchars($p['trx_id']); ?><br>
                                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($p['sender_number']); ?></small>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                            <td>
                                <?php if($p['status'] === 'Approved'): ?>
                                    <span style="color:#10b981; font-weight:600;"><i class="fa-solid fa-check-circle"></i> Approved</span>
                                <?php elseif($p['status'] === 'Rejected'): ?>
                                    <span style="color:#ef4444; font-weight:600;"><i class="fa-solid fa-times-circle"></i> Rejected</span>
                                <?php else: ?>
                                    <span style="color:#f59e0b; font-weight:600;"><i class="fa-solid fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            
                            <?php if($can_manage_payments): ?>
                            <td>
                                <?php if($p['status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="status" value="Approved">
                                        <button type="submit" name="update_status" class="btn-sm btn-success" title="Approve"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="status" value="Rejected">
                                        <button type="submit" name="update_status" class="btn-sm btn-danger" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">Reviewed</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($payments)): ?>
                        <tr><td colspan="<?php echo $can_manage_payments ? 7 : 6; ?>" style="text-align:center;">No payments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
