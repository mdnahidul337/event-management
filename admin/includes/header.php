<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role_level = $_SESSION['role_level'] ?? 0;
?>
<nav class="sidebar">
    <div class="brand">
        <a href="../index.php"><i class="fa-solid fa-rocket"></i> <span>SCC Admin</span></a>
    </div>
    <ul class="nav-links">
        <?php if($role_level >= 40): ?>
            <li><a href="dashboard.php" <?php if($current_page == 'dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <?php endif; ?>

        <?php if($role_level >= 40 && $role_level < 50): // SocialMediaManager ?>
            <li><a href="social_dashboard.php" <?php if($current_page == 'social_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-share-nodes"></i> Social Dashboard</a></li>
        <?php endif; ?>

        <?php if($role_level >= 50 && $role_level < 60): // EventManager ?>
            <li><a href="event_dashboard.php" <?php if($current_page == 'event_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-calendar-star"></i> Event Dashboard</a></li>
        <?php endif; ?>

        <?php if($role_level >= 70 && $role_level < 80): // Management ?>
            <li><a href="management_dashboard.php" <?php if($current_page == 'management_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-users-gear"></i> Management</a></li>
        <?php endif; ?>

        <?php if($role_level >= 80 && $role_level < 90): // Accounting ?>
            <li><a href="accounting_dashboard.php" <?php if($current_page == 'accounting_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-calculator"></i> Accounting</a></li>
            <li><a href="funds.php" <?php if($current_page == 'funds.php') echo 'class="active"'; ?>><i class="fa-solid fa-hand-holding-dollar"></i> Funds</a></li>
        <?php endif; ?>

        <?php if($role_level >= 90): // Admin / SuperAdmin see all role dashboards ?>
            <li><a href="social_dashboard.php" <?php if($current_page == 'social_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-share-nodes"></i> Social</a></li>
            <li><a href="event_dashboard.php" <?php if($current_page == 'event_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-calendar-star"></i> Events View</a></li>
            <li><a href="management_dashboard.php" <?php if($current_page == 'management_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-users-gear"></i> Management</a></li>
            <li><a href="accounting_dashboard.php" <?php if($current_page == 'accounting_dashboard.php') echo 'class="active"'; ?>><i class="fa-solid fa-calculator"></i> Accounting</a></li>
            <li><a href="funds.php" <?php if($current_page == 'funds.php') echo 'class="active"'; ?>><i class="fa-solid fa-hand-holding-dollar"></i> Funds</a></li>
        <?php endif; ?>

        <li style="height:1px;background:rgba(255,255,255,0.1);margin:0.5rem 0;list-style:none;"></li>

        <?php if($role_level >= 40): ?>
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
            <li><a href="email_templates.php" <?php if($current_page == 'email_templates.php') echo 'class="active"'; ?>><i class="fa-solid fa-envelope-circle-check"></i> Email Templates</a></li>
        <?php endif; ?>
        
        <li style="margin-top: auto;"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
    <header class="header">
        <div class="menu-toggle" id="menuToggle" style="cursor: pointer; font-size: 1.5rem; margin-right: 1rem;">
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