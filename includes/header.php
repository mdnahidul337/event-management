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
<head>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<nav>
    <a href="index.php" class="logo">
        <?php if(!empty($logo_path)): ?>
            <img src="assets/image/<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" style="max-height: 40px;">
        <?php else: ?>
            <?php echo htmlspecialchars($site_name); ?>
        <?php endif; ?>
    </a>

    <div class="menu-toggle" id="mobile-menu-btn">
        <i class="fa-solid fa-bars"></i>
    </div>

    <ul class="nav-links" id="nav-links">
        <li><a href="index.php" <?php if ($current_page == 'index.php') echo 'style="color:var(--primary);"'; ?>>Home</a></li>
        <li><a href="public_events.php" <?php if ($current_page == 'public_events.php' || $current_page == 'join_event.php') echo 'style="color:var(--primary);"'; ?>>Events</a></li>
        <li><a href="posts_feed.php" <?php if ($current_page == 'posts_feed.php') echo 'style="color:var(--primary);"'; ?>>News</a></li>
        <li><a href="team.php" <?php if ($current_page == 'team.php') echo 'style="color:var(--primary);"'; ?>>Team</a></li>
        <li><a href="about.php" <?php if ($current_page == 'about.php') echo 'style="color:var(--primary);"'; ?>>About</a></li>
        <li><a href="contact.php" <?php if ($current_page == 'contact.php') echo 'style="color:var(--primary);"'; ?>>Contact</a></li>
        
        <!-- Mobile Only Auth Links -->
        <li class="mobile-only" style="display:none; border-top:1px solid #eee; width:100%; padding-top:1rem; margin-top:1rem;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">My Profile</a>
                <a href="logout.php" style="color:#ef4444 !important;">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" style="color:var(--primary) !important;">Register</a>
            <?php endif; ?>
        </li>
    </ul>

    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="btn-login" title="Profile"><i class="fa-solid fa-user"></i></a>
            <?php if ($_SESSION['role_level'] > 10): ?>
                <a href="admin/dashboard.php" class="btn-login" title="Admin Panel"><i class="fa-solid fa-gauge-high"></i></a>
            <?php endif; ?>
            <a href="logout.php" class="btn-login" style="color:#ef4444;"><i class="fa-solid fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Join Us</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinks = document.getElementById('nav-links');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-xmark');
        });
    }
</script>

<style>
    @media (max-width: 768px) {
        .nav-links .mobile-only { display: flex !important; flex-direction: column; gap: 1.5rem; }
        .nav-links li { width: 100%; }
        .nav-links a { font-size: 1.1rem; }
    }
</style>