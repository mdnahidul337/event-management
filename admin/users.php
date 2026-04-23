<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$error = '';
$success = '';

// Level 90+ (Admin, SuperAdmin only) can update user roles
$can_manage_users = $_SESSION['role_level'] >= 90;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_users) {
    if (isset($_POST['update_role'])) {
        $user_id = $_POST['user_id'];
        $new_role_id = $_POST['role_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        if ($stmt->execute([$new_role_id, $user_id])) {
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'UPDATE', 'users', ?, ?)")
                ->execute([$_SESSION['user_id'], "Updated role for user ID $user_id to role ID $new_role_id", $_SERVER['REMOTE_ADDR']]);
            $success = "User role updated successfully.";
        } else {
            $error = "Failed to update role.";
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT u.*, r.name as role_name, r.level as role_level FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC")->fetchAll();

// Fetch roles for dropdown
$roles = [];
if ($can_manage_users) {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY level DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #047857; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .role-select { padding: 0.4rem; border-radius: 4px; border: 1px solid var(--border-color); margin-right: 0.5rem; }
        .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.8rem; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
                <h2>Manage Users</h2>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Current Role</th>
                            <th>Joined Date</th>
                            <?php if($can_manage_users): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($u['role_name']); ?>"><?php echo htmlspecialchars($u['role_name']); ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            
                            <?php if($can_manage_users): ?>
                            <td>
                                <!-- Prevent SuperAdmin from being downgraded easily in this basic view unless desired, but allowing for demo -->
                                <form method="POST" style="display:inline-flex; align-items:center;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role_id" class="role-select" required>
                                        <?php foreach($roles as $r): ?>
                                            <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == $u['role_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($r['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($users)): ?>
                        <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
