<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] < 80) {
    header("Location: ../login.php"); exit;
}
require_once '../includes/db_connect.php';

// Accounting stats
$total_revenue   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Approved'")->fetchColumn();
$pending_revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Pending'")->fetchColumn();
$rejected_count  = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='Rejected'")->fetchColumn();
$total_txns      = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();

// Monthly revenue (last 6 months)
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') as month, SUM(amount) as total
    FROM payments WHERE status='Approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at ASC
")->fetchAll();

// Recent transactions
$recent_payments = $pdo->query("
    SELECT p.*, u.name as user_name, e.title as event_title
    FROM payments p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN events e ON p.event_id = e.id
    ORDER BY p.created_at DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Dashboard - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .kpi-card { background: var(--card-bg); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary-color); display: flex; justify-content: space-between; align-items: center; }
        .kpi-card.green  { border-color: #10b981; }
        .kpi-card.yellow { border-color: #f59e0b; }
        .kpi-card.red    { border-color: #ef4444; }
        .kpi-card.blue   { border-color: #3b82f6; }
        .kpi-value { font-size: 2rem; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; }
        .kpi-icon  { font-size: 2rem; opacity: 0.2; }
        .chart-card { background: var(--card-bg); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-weight: 800; font-size: 1.75rem;">Accounting Center</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Financial overview, transactions, and revenue growth.</p>
            </div>
            <a href="funds.php" class="btn btn-primary"><i class="fa-solid fa-hand-holding-dollar"></i> Manage Club Funds</a>
        </div>

        <div class="cards-grid">
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <h2>৳<?php echo number_format($total_revenue, 0); ?></h2>
                </div>
                <div class="stat-icon" style="background:#dcfce7; color:#10b981;"><i class="fa-solid fa-sack-dollar"></i></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                <div class="stat-info">
                    <h3>Pending</h3>
                    <h2>৳<?php echo number_format($pending_revenue, 0); ?></h2>
                </div>
                <div class="stat-icon" style="background:#fffbeb; color:#f59e0b;"><i class="fa-solid fa-clock-rotate-left"></i></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                <div class="stat-info">
                    <h3>Transactions</h3>
                    <h2><?php echo $total_txns; ?></h2>
                </div>
                <div class="stat-icon" style="background:#dbeafe; color:#3b82f6;"><i class="fa-solid fa-receipt"></i></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ef4444;">
                <div class="stat-info">
                    <h3>Rejected</h3>
                    <h2><?php echo $rejected_count; ?></h2>
                </div>
                <div class="stat-icon" style="background:#fef2f2; color:#ef4444;"><i class="fa-solid fa-ban"></i></div>
            </div>
        </div>

        <div class="table-container" style="margin-bottom:2.5rem;">
            <h3 style="margin-bottom:1.5rem; font-weight:700;"><i class="fa-solid fa-chart-line"></i> Revenue Trend</h3>
            <div style="height: 300px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="table-container">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                <h3 style="font-weight:700;">Recent Transactions</h3>
                <a href="payments.php" class="btn" style="color:var(--primary); font-size:0.85rem; font-weight:700;">Full Ledger →</a>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($p['user_name']); ?></div>
                                <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo $p['method']; ?> - <?php echo $p['trx_id']; ?></div>
                            </td>
                            <td><div style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($p['event_title'] ?? 'General Registration'); ?></div></td>
                            <td><strong style="color:var(--text-main);">৳<?php echo number_format($p['amount'],0); ?></strong></td>
                            <td>
                                <?php
                                $status = $p['status'];
                                $bg = ['Approved'=>'#dcfce7','Pending'=>'#fef3c7','Rejected'=>'#fef2f2'][$status] ?? '#f1f5f9';
                                $fg = ['Approved'=>'#166534','Pending'=>'#92400e','Rejected'=>'#991b1b'][$status] ?? '#475569';
                                ?>
                                <span class="badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;"><?php echo $status; ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
   </div></div>

    <script src="js/script.js"></script>
    <script>
        const months = <?php echo json_encode(array_column($monthly, 'month')); ?>;
        const totals = <?php echo json_encode(array_column($monthly, 'total')); ?>;
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{ label: 'Revenue (৳)', data: totals, backgroundColor: 'rgba(79,70,229,0.8)', borderRadius: 8 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
