<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] < 80) {
    header("Location: ../login.php"); exit;
}
require_once '../includes/db_connect.php';

// ─── Auto-migrate funds table ─────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS funds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Sponsorship','Collection','TeamMember','Other') NOT NULL DEFAULT 'Other',
    source VARCHAR(200) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    collected_by INT,
    collected_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL
)");

$error = '';
$success = '';
$can_delete_funds = $_SESSION['role_level'] >= 90; // Only Admin & SuperAdmin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_fund'])) {
        $type         = $_POST['fund_type'];
        $source       = trim($_POST['source']);
        $description  = trim($_POST['description']);
        $amount       = floatval($_POST['amount']);
        $collected_by = !empty($_POST['collected_by']) ? intval($_POST['collected_by']) : null;
        $collected_at = $_POST['collected_at'];
        $stmt = $pdo->prepare("INSERT INTO funds (type, source, description, amount, collected_by, collected_at) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$type, $source, $description, $amount, $collected_by, $collected_at])) {
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'funds', ?, ?)")
                ->execute([$_SESSION['user_id'], "Added fund: $source ৳$amount ($type)", $_SERVER['REMOTE_ADDR']]);
            $success = "Fund entry added successfully!";
        } else { $error = "Failed to add fund."; }
    }
    if (isset($_POST['delete_fund'])) {
        if (!$can_delete_funds) {
            $error = "Only Admin or SuperAdmin can delete fund entries.";
        } else {
            $fid = intval($_POST['fund_id']);
            $pdo->prepare("DELETE FROM funds WHERE id=?")->execute([$fid]);
            $success = "Fund entry deleted.";
        }
    }
}

// ─── Stats ─────────────────────────────────────────────────────────────────
$total_sponsorship  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM funds WHERE type='Sponsorship'")->fetchColumn();
$total_collection   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM funds WHERE type='Collection'")->fetchColumn();
$total_team_member  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM funds WHERE type='TeamMember'")->fetchColumn();
$total_other        = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM funds WHERE type='Other'")->fetchColumn();
$grand_total_funds  = $total_sponsorship + $total_collection + $total_team_member + $total_other;

$payment_revenue    = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Approved'")->fetchColumn();
$grand_total        = $grand_total_funds + $payment_revenue;

// Monthly chart data
$monthly = $pdo->query("
    SELECT DATE_FORMAT(collected_at,'%b %Y') as month, SUM(amount) as total
    FROM funds WHERE collected_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(collected_at), MONTH(collected_at)
    ORDER BY collected_at ASC
")->fetchAll();

// All fund entries
$funds = $pdo->query("
    SELECT f.*, u.name as collector_name FROM funds f
    LEFT JOIN users u ON f.collected_by = u.id
    ORDER BY f.collected_at DESC
")->fetchAll();

// Team members for dropdown
$team_users = $pdo->query("SELECT u.id, u.name, r.name as role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE r.level >= 40 ORDER BY r.level DESC, u.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funds & Sponsorship - SCC Accounting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem; margin-bottom:2rem; }
        .kpi-card { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); border-left:4px solid var(--primary-color); }
        .kpi-value { font-size:1.8rem; font-weight:800; }
        .kpi-label { font-size:0.82rem; color:var(--text-muted); margin-top:0.25rem; }
        .two-col { display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; margin-bottom:2rem; }
        .panel { background:var(--card-bg); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow-sm); }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; font-weight:600; margin-bottom:0.4rem; font-size:0.88rem; }
        .form-control { width:100%; padding:0.7rem; border:1.5px solid var(--border-color); border-radius:var(--radius); background:var(--bg-color); color:var(--text-main); outline:none; }
        .form-control:focus { border-color:var(--primary-color); }
        .alert { padding:1rem 1.2rem; border-radius:var(--radius); margin-bottom:1.5rem; border-left:4px solid; }
        .alert-success { background:#d1fae5; color:#047857; border-color:#10b981; }
        .alert-error   { background:#fee2e2; color:#b91c1c; border-color:#ef4444; }
        .type-badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:0.75rem; font-weight:700; }
        .type-Sponsorship  { background:#e0e7ff; color:#3730a3; }
        .type-Collection   { background:#d1fae5; color:#065f46; }
        .type-TeamMember   { background:#fef3c    <div class="content-area">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-weight: 800; font-size: 1.75rem;">Funds & Sponsorship</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Manage manual collections, sponsors, and member funds.</p>
            </div>
            <a href="accounting_dashboard.php" class="btn" style="background:var(--bg-main); font-weight:700;"><i class="fa-solid fa-arrow-left"></i> Accounting Hub</a>
        </div>

        <div class="cards-grid">
            <div class="stat-card" style="border-left: 4px solid #6366f1;">
                <div class="stat-info">
                    <h3>Grand Total</h3>
                    <h2>৳<?php echo number_format($grand_total, 0); ?></h2>
                </div>
                <div class="stat-icon" style="background:#eef2ff; color:#6366f1;"><i class="fa-solid fa-vault"></i></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                <div class="stat-info">
                    <h3>Sponsorships</h3>
                    <h2>৳<?php echo number_format($total_sponsorship, 0); ?></h2>
                </div>
                <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="fa-solid fa-handshake"></i></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <div class="stat-info">
                    <h3>Collections</h3>
                    <h2>৳<?php echo number_format($total_collection, 0); ?></h2>
                </div>
                <div class="stat-icon" style="background:#dcfce7; color:#10b981;"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                <div class="stat-info">
                    <h3>Team Funds</h3>
                    <h2>৳<?php echo number_format($total_team_member, 0); ?></h2>
                </div>
                <div class="stat-icon" style="background:#fffbeb; color:#f59e0b;"><i class="fa-solid fa-users-gear"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="table-container">
                <h3 style="margin-bottom: 2rem; font-weight:700;"><i class="fa-solid fa-list-ul"></i> Fund Ledger</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Amount</th>
                                <th>Collector</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($funds as $f): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background:var(--bg-main); color:var(--text-muted); font-size:0.6rem; border:1px solid var(--border); margin-bottom:0.25rem; display:inline-block;"><?php echo $f['type']; ?></span>
                                    <div style="font-weight:700;"><?php echo htmlspecialchars($f['source']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($f['description']); ?></div>
                                </td>
                                <td><strong style="color:#10b981; font-size:1rem;">৳<?php echo number_format($f['amount'], 0); ?></strong></td>
                                <td><div style="font-size:0.85rem;"><?php echo htmlspecialchars($f['collector_name'] ?? 'System'); ?></div></td>
                                <td><div style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($f['collected_at'])); ?></div></td>
                                <td>
                                    <?php if($can_delete_funds): ?>
                                    <form method="POST" onsubmit="return confirm('Delete this entry?')">
                                        <input type="hidden" name="fund_id" value="<?php echo $f['id']; ?>">
                                        <button type="submit" name="delete_fund" class="btn" style="background:#fef2f2; color:#ef4444; padding:0.5rem;"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container">
                <h3 style="margin-bottom: 2rem; font-weight:700;"><i class="fa-solid fa-circle-plus"></i> New Entry</h3>
                <form method="POST">
                    <div class="form-group">
                        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Fund Type</label>
                        <select name="fund_type" class="btn" style="width:100%; background:var(--bg-main); border:1px solid var(--border); appearance:auto;" required>
                            <option value="Sponsorship">Sponsorship</option>
                            <option value="Collection">Manual Collection</option>
                            <option value="TeamMember">Team Member Fund</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Source / Sponsor</label>
                        <input type="text" name="source" class="btn" style="width:100%; background:var(--bg-main); border:1px solid var(--border); text-align:left;" placeholder="e.g. Annual Sponsor" required>
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Amount (৳)</label>
                        <input type="number" name="amount" class="btn" style="width:100%; background:var(--bg-main); border:1px solid var(--border); text-align:left;" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Collector</label>
                        <select name="collected_by" class="btn" style="width:100%; background:var(--bg-main); border:1px solid var(--border); appearance:auto;">
                            <option value="">-- Select Member --</option>
                            <?php foreach ($team_users as $tu): ?>
                                <option value="<?php echo $tu['id']; ?>"><?php echo htmlspecialchars($tu['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Date</label>
                        <input type="date" name="collected_at" class="btn" style="width:100%; background:var(--bg-main); border:1px solid var(--border); text-align:left;" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" name="add_fund" class="btn btn-primary" style="width:100%; justify-content:center; padding:1rem; margin-top:1rem;"><i class="fa-solid fa-cloud-arrow-up"></i> Record Fund Entry</button>
                </form>
            </div>
        </div>
    </div>
</div>
                    <button type="submit" name="add_fund" class="btn btn-primary" style="width:100%;padding:0.9rem;"><i class="fa-solid fa-plus"></i> Add Fund Entry</button>
                </form>
            </div>
        </div>
    </div></div>

    <script src="js/script.js"></script>
    <?php if($monthly): ?>
    <script>
        new Chart(document.getElementById('fundChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly,'month')); ?>,
                datasets: [{
                    label: 'Funds (৳)',
                    data: <?php echo json_encode(array_column($monthly,'total')); ?>,
                    backgroundColor: 'rgba(79,70,229,0.1)',
                    borderColor: '#4f46e5',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5'
                }]
            },
            options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
        });
    </script>
    <?php endif; ?>
</body>
</html>
