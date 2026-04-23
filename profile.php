<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN session VARCHAR(50) DEFAULT NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN blood_group VARCHAR(10) DEFAULT NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
} catch (PDOException $e) {
}

// Fetch user details
$stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $session_str = trim($_POST['session']);
    $blood_group = trim($_POST['blood_group']);

    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/image/Profile/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $filename = 'prof_' . time() . '_' . basename($_FILES['profile_pic']['name']);
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $filename)) {
            $profile_pic = $filename;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, department = ?, session = ?, blood_group = ?, profile_pic = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $department, $session_str, $blood_group, $profile_pic, $_SESSION['user_id']]);
    $_SESSION['user_name'] = $name;
    header("Location: profile.php");
    exit;
}

// Handle resend ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_ticket'])) {
    $event_id = $_POST['event_id'];
    $join_code = $_POST['join_code'];
    
    // Fetch event details
    $stmt_ev = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt_ev->execute([$event_id]);
    $ev = $stmt_ev->fetch();
    
    if ($ev) {
        require_once 'includes/mailer_helper.php';
        send_template_email($pdo, 'event_registered', [
            'name'           => $user['name'],
            'email'          => $user['email'],
            'event_title'    => $ev['title'],
            'event_date'     => date('M d, Y • h:i A', strtotime($ev['start_date'])),
            'event_location' => $ev['location'],
            'event_type'     => $ev['type'],
            'join_code'      => $join_code
        ]);
        $success_msg = "Ticket resent to your email!";
    }
}

// Fetch joined events / payment status
$stmt = $pdo->prepare("
    SELECT p.*, e.title as event_title, e.start_date, e.location 
    FROM payments p 
    LEFT JOIN events e ON p.event_id = e.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

// Global settings
$global_settings = [];
$stmt_settings = $pdo->query("SELECT * FROM settings");
while ($row = $stmt_settings->fetch()) {
    $global_settings[$row['setting_key']] = $row['setting_value'];
}
$site_name = $global_settings['site_name'] ?? 'SCC Computer Club';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 2.5rem;
            border: 1px solid var(--border);
        }

        .profile-img-container {
            position: relative;
            flex-shrink: 0;
        }

        .profile-img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-info h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .profile-info p {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }

        .info-item label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .info-item span {
            font-weight: 700;
            color: #111827;
        }

        .ticket-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.3s;
        }

        .ticket-card:hover {
            transform: scale(1.01);
            border-color: var(--primary);
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-approved {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .status-pending {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fef3c7;
        }

        .status-rejected {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .profile-card {
                flex-direction: column;
                text-align: center;
                padding: 2rem 1.5rem;
            }

            .ticket-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr 1fr;
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="page-header" style="padding-bottom: 6rem;">
        <h1>Member Profile</h1>
        <p>Manage your account and track your event participations.</p>
    </div>

    <div class="container">
        <?php if (isset($success_msg)): ?>
            <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0; font-weight: 600; text-align: center;">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0; font-weight: 600; text-align: center;">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <?php
            if (!empty($user['profile_pic'])) {
                $avatar_url = "assets/image/Profile/" . htmlspecialchars($user['profile_pic']);
            } else {
                $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=4f46e5&color=fff&size=150";
            }
            ?>
            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="profile-img" style="object-fit: cover;">
            <div style="flex:1;">
                <h2 style="font-size: 1.8rem;"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p style="color: var(--text-light); margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($user['email']); ?></p>

                <div style="margin-bottom: 1rem; font-size: 0.95rem; color: #4b5563;">
                    <strong style="color:var(--text-dark);">Phone:</strong>
                    <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?> &bull;
                    <strong style="color:var(--text-dark);">Dept:</strong>
                    <?php echo htmlspecialchars($user['department'] ?? 'Not set'); ?> &bull;
                    <strong style="color:var(--text-dark);">Session:</strong>
                    <?php echo htmlspecialchars($user['session'] ?? 'Not set'); ?> &bull;
                    <strong style="color:var(--text-dark);">Blood:</strong> <span
                        style="color:#ef4444; font-weight:600;"><?php echo htmlspecialchars($user['blood_group'] ?? 'Not set'); ?></span>
                </div>

                <div class="badge"><i class="fa-solid fa-id-badge"></i>
                    <?php echo htmlspecialchars($user['role_name']); ?></div>
                <?php if ($_SESSION['role_level'] > 10): ?>
                    <a href="admin/dashboard.php"
                        style="margin-left: 1rem; color: var(--primary); font-weight:600; text-decoration:none;"><i
                            class="fa-solid fa-gauge"></i> Admin Panel</a>
                <?php endif; ?>
                <button onclick="document.getElementById('editProfileModal').style.display='flex'"
                    style="margin-left: 1rem; padding: 0.4rem 1rem; background: var(--text-dark); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;color: black;"><i
                        class="fa-solid fa-pen"></i> Edit Profile</button>
            </div>
        </div>

        <?php
        $stmt_reg = $pdo->prepare("SELECT status, trx_id FROM payments WHERE user_id = ? AND event_id IS NULL ORDER BY created_at DESC LIMIT 1");
        $stmt_reg->execute([$_SESSION['user_id']]);
        $reg_pay = $stmt_reg->fetch();
        $registration_fee = floatval($global_settings['registration_fee'] ?? 0);
        $registration_fee_cutoff = $global_settings['registration_fee_cutoff'] ?? '2000-01-01 00:00:00';
        $user_created_at = $user['created_at'];
        $show_pay_form = false;
        
        if ($registration_fee > 0 && $user_created_at >= $registration_fee_cutoff) {
            if (!$reg_pay || $reg_pay['status'] === 'Rejected') {
                $show_pay_form = true;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_reg_fee'])) {
            $method = $_POST['method'];
            $sender_number = trim($_POST['sender_number']);
            $trx_id = trim($_POST['trx_id']);
            
            if (!empty($method) && !empty($sender_number) && !empty($trx_id)) {
                $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, method, sender_number, trx_id, status) VALUES (?, NULL, ?, ?, ?, ?, 'Pending')");
                if ($stmt->execute([$_SESSION['user_id'], $registration_fee, $method, $sender_number, $trx_id])) {
                    header("Location: profile.php?success=Payment submitted");
                    exit;
                }
            }
        }
        ?>

        <?php if ($show_pay_form): ?>
            <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: var(--radius-lg); padding: 2.5rem; margin-bottom: 2.5rem;">
                <div style="display:flex; gap:1.5rem; align-items:flex-start;">
                    <div style="background:#f59e0b; color:white; width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div style="flex:1;">
                        <h3 style="color:#92400e; font-size:1.5rem; margin-bottom:0.5rem;">Club Registration Fee Required</h3>
                        <p style="color:#b45309; margin-bottom:1.5rem; font-weight:500;">
                            To access all club features and join events, you must pay the one-time registration fee of <strong>৳<?php echo $registration_fee; ?></strong>.
                            <?php if($reg_pay && $reg_pay['status'] === 'Rejected'): ?>
                                <br><span style="color:#ef4444;">Your previous payment was rejected. Please check your TrxID and try again.</span>
                            <?php endif; ?>
                        </p>

                        <form method="POST" style="background:white; padding:2rem; border-radius:12px; border:1px solid #fde68a;">
                            <div class="payment-instructions" style="margin-bottom:1.5rem; font-size:0.9rem; color:#6b7280; padding:1rem; background:#f9fafb; border-radius:8px; line-height:1.6;">
                                <strong style="color:var(--text-dark); display:block; margin-bottom:0.25rem;">Payment Instructions:</strong>
                                <?php echo nl2br(htmlspecialchars($global_settings['payment_instructions'] ?? 'Please pay via Bkash/Nagad.')); ?>
                            </div>
                            
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem;">
                                <div class="form-group">
                                    <label class="info-item" style="display:block; margin-bottom:0.5rem; font-size:0.75rem; font-weight:700; text-transform:uppercase; color:#6b7280;">Method</label>
                                    <select name="method" style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;" required>
                                        <option value="Bkash">Bkash</option>
                                        <option value="Nagad">Nagad</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="info-item" style="display:block; margin-bottom:0.5rem; font-size:0.75rem; font-weight:700; text-transform:uppercase; color:#6b7280;">Sender Number</label>
                                    <input type="text" name="sender_number" placeholder="017XXXXXXXX" style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;" required>
                                </div>
                                <div class="form-group">
                                    <label class="info-item" style="display:block; margin-bottom:0.5rem; font-size:0.75rem; font-weight:700; text-transform:uppercase; color:#6b7280;">Transaction ID</label>
                                    <input type="text" name="trx_id" placeholder="TRX123ABC" style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;" required>
                                </div>
                            </div>
                            <button type="submit" name="pay_reg_fee" style="margin-top:1.5rem; width:100%; padding:1rem; background:#f59e0b; color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:1rem; transition:0.3s;">Submit Payment (৳<?php echo $registration_fee; ?>)</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php elseif ($reg_pay && $reg_pay['status'] === 'Pending'): ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2.5rem; display:flex; align-items:center; gap:1.5rem;">
                <div style="background:#3b82f6; color:white; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <h4 style="color:#1e40af; margin-bottom:0.25rem;">Registration Fee Pending</h4>
                    <p style="color:#3b82f6; font-size:0.9rem; font-weight:500;">We've received your registration fee payment (TrxID: <?php echo htmlspecialchars($reg_pay['trx_id']); ?>). Please wait for accounting approval to unlock all features.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="tickets-section">
            <h2><i class="fa-solid fa-ticket"></i> My Event Tickets & Registrations</h2>

            <?php if (empty($tickets)): ?>
                <div style="text-align:center; padding: 3rem; color:var(--text-light);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 3rem; margin-bottom: 1rem; color:#d1d5db;"></i>
                    <p>You haven't joined any events yet.</p>
                    <a href="public_events.php"
                        style="display:inline-block; margin-top:1rem; padding:0.8rem 1.5rem; background:var(--primary); color:white; border-radius:6px; text-decoration:none; font-weight:600;">Browse
                        Events</a>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket-card">
                        <div class="ticket-info">
                            <h3><?php echo htmlspecialchars($t['event_title'] ?? 'General Registration Fee'); ?></h3>
                            <div class="ticket-meta">
                                <?php if ($t['event_title']): ?>
                                    <span style="margin-right: 1rem;"><i class="fa-regular fa-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($t['start_date'])); ?></span>
                                    <span><i class="fa-solid fa-location-dot"></i>
                                        <?php echo htmlspecialchars($t['location']); ?></span>
                                <?php endif; ?>
                                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #9ca3af;">
                                    Requested on: <?php echo date('M d, Y', strtotime($t['created_at'])); ?> • Amount: ৳
                                    <?php echo number_format($t['amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.5rem;">
                            <?php if ($t['status'] === 'Approved'): ?>
                                <span class="status-badge status-approved"><i class="fa-solid fa-check"></i> Approved</span>
                                <?php if($t['event_id']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="event_id" value="<?php echo $t['event_id']; ?>">
                                        <input type="hidden" name="join_code" value="<?php echo $t['join_code']; ?>">
                                        <button type="submit" name="resend_ticket" style="background:none; border:none; color:var(--primary); font-size:0.75rem; font-weight:700; cursor:pointer; padding:0;">
                                            <i class="fa-solid fa-paper-plane"></i> Resend Email
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($t['status'] === 'Rejected'): ?>
                                <span class="status-badge status-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>
                            <?php else: ?>
                                <span class="status-badge status-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div
            style="background:white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-dark);">Edit Profile</h2>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div style="display: flex; gap: 1rem;">
                    <div style="margin-bottom: 1rem; flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"
                            style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;" required>
                    </div>
                    <div style="margin-bottom: 1rem; flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            placeholder="017XXXXXXXX"
                            style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <div style="margin-bottom: 1rem; flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Department</label>
                        <input type="text" name="department"
                            value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" placeholder="e.g. CSE"
                            style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;">
                    </div>
                    <div style="margin-bottom: 1rem; flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Session</label>
                        <input type="text" name="session"
                            value="<?php echo htmlspecialchars($user['session'] ?? ''); ?>" placeholder="e.g. 2021-2022"
                            style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Blood Group</label>
                    <input type="text" name="blood_group"
                        value="<?php echo htmlspecialchars($user['blood_group'] ?? ''); ?>" placeholder="e.g. A+"
                        style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/*"
                        style="width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px; background:#f9fafb;">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:1rem;">
                    <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'"
                        style="padding:0.8rem 1.5rem; border:1px solid #d1d5db; background:white; color: #4b5563; font-weight:600; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" name="update_profile"
                        style="padding:0.8rem 1.5rem; background:var(--primary); color:white; font-weight:600; border:none; border-radius:6px; cursor:pointer;">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>