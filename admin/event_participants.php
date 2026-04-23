<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$event_id = intval($_GET['id'] ?? 0);
if (!$event_id) {
    header("Location: events.php");
    exit;
}

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: events.php");
    exit;
}

// Fetch participants
$stmt = $pdo->prepare("
    SELECT p.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.department, u.session
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE p.event_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$event_id]);
$participants = $stmt->fetchAll();

// Statistics
$total_joined = count($participants);
$total_amount = array_sum(array_column($participants, 'amount'));
$approved_count = count(array_filter($participants, function($p) { return $p['status'] === 'Approved'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants - <?php echo htmlspecialchars($event['title']); ?> - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stat-card { background: var(--card-bg); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); text-align: center; border: 1px solid var(--border-color); }
        .stat-val { font-size: 1.5rem; font-weight: 800; color: var(--primary-color); margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .participant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .participant-card { background: var(--card-bg); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border-color); }
        .code-badge { background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.85rem; }
        .status-badge { font-size: 0.75rem; font-weight: 700; padding: 2px 10px; border-radius: 20px; }
        .status-Approved { background: #d1fae5; color: #065f46; }
        .status-Pending  { background: #fef3c7; color: #92400e; }
        .status-Rejected { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="content-area">
        <div style="margin-bottom:2rem;">
            <a href="events.php" style="color:var(--text-muted);text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>
            <h2 style="margin-top:0.75rem;"><i class="fa-solid fa-users"></i> Participants: <?php echo htmlspecialchars($event['title']); ?></h2>
        </div>

        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1.5rem; margin-bottom:2.5rem;">
            <div class="stat-card">
                <div class="stat-val"><?php echo $total_joined; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo $approved_count; ?></div>
                <div class="stat-label">Approved Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-val">৳<?php echo number_format($total_amount, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo $event['type']; ?></div>
                <div class="stat-label">Event Type</div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket Code</th>
                        <th>Member Details</th>
                        <th>Payment Info</th>
                        <th>Status</th>
                        <th>Joined At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['join_code']): ?>
                                <span class="code-badge"><?php echo htmlspecialchars($p['join_code']); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-size:0.8rem;">Pending Code</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['user_name']); ?></strong><br>
                            <span style="font-size:0.8rem; color:var(--text-muted);">
                                <?php echo htmlspecialchars($p['department']); ?> (<?php echo htmlspecialchars($p['session']); ?>)<br>
                                <?php echo htmlspecialchars($p['user_phone']); ?>
                            </span>
                            <?php if(!empty($p['user_note'])): ?>
                                <div style="margin-top:0.5rem; padding:0.5rem; background:#fefce8; border-radius:4px; font-size:0.75rem; color:#854d0e; border:1px solid #fef08a;">
                                    <i class="fa-solid fa-comment-dots"></i> <?php echo htmlspecialchars($p['user_note']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($event['type'] === 'Paid'): ?>
                                <strong style="color:var(--primary-color);">৳<?php echo number_format($p['amount'], 2); ?></strong> via <?php echo $p['method']; ?><br>
                                <span style="font-size:0.8rem; color:var(--text-muted);">TrxID: <?php echo htmlspecialchars($p['trx_id']); ?></span>
                            <?php else: ?>
                                <span style="color:#10b981; font-weight:600;">Free Entry</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span>
                        </td>
                        <td><?php echo date('M d, Y h:i A', strtotime($p['created_at'])); ?></td>
                        <td>
                            <a href="payments.php" class="btn btn-primary" style="padding:4px 10px; font-size:0.75rem;">Manage Payment</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($participants)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:4rem; color:var(--text-muted);"><i class="fa-solid fa-user-slash" style="font-size:2.5rem; display:block; margin-bottom:1rem;"></i> No one has joined this event yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
