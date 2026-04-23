<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role_level = $_SESSION['role_level'] ?? 0;
?>
<nav class="sidebar">
    <div class="brand">
        <a href="../index.php" style="color:white; text-decoration:none;"><i class="fa-solid fa-desktop"></i> SCC Admin</a>
    </div>
    <ul class="nav-links">
        <?php if($role_level >= 40): ?>
            <li><a href="dashboard.php" <?php if($current_page == 'dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="posts.php" <?php if($current_page == 'posts.php') echo 'class="active"'; ?>><i class="fa-solid fa-pen-to-square"></i> Posts</a></li>
        <?php endif; ?>

        <?php if($role_level >= 50): ?>
            <li><a href="events.php" <?php if($current_page == 'events.php') echo 'class="active"'; ?>><i class="fa-solid fa-calendar-check"></i> Events</a></li>
        <?php endif; ?>

        <?php if($role_level >= 60): ?>
            <li><a href="logs.php" <?php if($current_page == 'logs.php') echo 'class="active"'; ?>><i class="fa-solid fa-shield-halved"></i> System Logs</a></li>
        <?php endif; ?>

        <?php if($role_level >= 70): ?>
            <li><a href="users.php" <?php if($current_page == 'users.php') echo 'class="active"'; ?>><i class="fa-solid fa-users"></i> Users</a></li>
        <?php endif; ?>

        <?php if($role_level >= 80): ?>
            <li><a href="payments.php" <?php if($current_page == 'payments.php') echo 'class="active"'; ?>><i class="fa-solid fa-money-bill-wave"></i> Payments</a></li>
        <?php endif; ?>

        <?php if($role_level >= 90): ?>
            <li><a href="settings.php" <?php if($current_page == 'settings.php') echo 'class="active"'; ?>><i class="fa-solid fa-gear"></i> Settings</a></li>
        <?php endif; ?>

        <?php if($role_level >= 100): ?>
            <li><a href="mailer.php" <?php if($current_page == 'mailer.php') echo 'class="active"'; ?>><i class="fa-solid fa-envelope"></i> Mailer</a></li>
        <?php endif; ?>
        
        <li style="margin-top: auto;"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</nav>

<div class="main-content">
    <header class="header">
        <div class="menu-toggle" id="menuToggle" style="display: none; cursor: pointer; font-size: 1.5rem;">
            <i class="fa-solid fa-bars"></i>
        </div>
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass text-muted"></i>
            <input type="text" placeholder="Search...">
        </div>
        <div class="user-profile">
            <a href="../profile.php" style="color:var(--text-muted); margin-right: 1.5rem; text-decoration:none; font-weight:500;"><i class="fa-solid fa-user"></i> Public Profile</a>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'Admin'); ?>&background=4f46e5&color=fff" alt="Profile">
            <div>
                <strong style="display:block; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
                <span class="badge badge-<?php echo strtolower(htmlspecialchars($_SESSION['user_role'] ?? 'admin')); ?>" style="font-size: 0.6rem;"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Admin'); ?></span>
            </div>
        </div>
    </header>