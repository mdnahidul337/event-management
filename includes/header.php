<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure database is connected
if (!isset($pdo)) {
    require_once 'includes/db_connect.php';
}

// Fetch settings
if (!isset($global_settings)) {
    $global_settings = [];
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) {
        $global_settings[$row['setting_key']] = $row['setting_value'];
    }
}

$site_name = $global_settings['site_name'] ?? 'SCC Computer Club';
$logo_path = $global_settings['logo_path'] ?? '';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav>
    <a href="index.php" class="logo">
        <?php if(!empty($logo_path)): ?>
            <img src="assets/image/<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" style="max-height: 40px; vertical-align: middle;">
        <?php else: ?>
            <?php echo htmlspecialchars($site_name); ?>
        <?php endif; ?>
    </a>
    <ul class="nav-links">
        <li><a href="index.php" <?php if ($current_page == 'index.php')
            echo 'style="color:var(--primary);"'; ?>>HOME</a>
        </li>
        <li><a href="public_events.php" <?php if ($current_page == 'public_events.php' || $current_page == 'join_event.php')
            echo 'style="color:var(--primary);"'; ?>>EVENTS</a></li>
        <li><a href="posts_feed.php" <?php if ($current_page == 'posts_feed.php')
            echo 'style="color:var(--primary);"'; ?>>NEWS FEED</a></li>
        <li><a href="team.php" <?php if ($current_page == 'team.php')
            echo 'style="color:var(--primary);"'; ?>>TEAM</a>
        </li>
        <li><a href="about.php" <?php if ($current_page == 'about.php')
            echo 'style="color:var(--primary);"'; ?>>ABOUT</a>
        </li>
        <li><a href="contact.php" <?php if ($current_page == 'contact.php')
            echo 'style="color:var(--primary);"'; ?>>CONTACT</a></li>
    </ul>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="btn-login" style="margin-right:0.5rem;"><i class="fa-solid fa-user"></i> Profile</a>
            <?php if ($_SESSION['role_level'] > 10): ?>
                <a href="admin/dashboard.php" class="btn-register" style="margin-right:0.5rem;"><i class="fa-solid fa-gauge"></i> Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-login" style="color:#ef4444; border: 1px solid #ef4444; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-weight:600;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-login" style="margin-right:1rem; color:var(--primary); text-decoration:none; font-weight:600;">Login</a>
            <a href="register.php" class="btn-register" style="background:var(--primary); color:white; padding:0.5rem 1.2rem; border-radius:6px; text-decoration:none; font-weight:600;">Register</a>
        <?php endif; ?>
    </div>
</nav>