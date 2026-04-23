<?php
require_once 'includes/db_connect.php';

// Fetch users grouped by role to display as team members
// Only fetch roles level 40 and above (SuperAdmin down to SocialMediaManager)
$team_members = $pdo->query("
    SELECT r.name as role_name, u.name, u.email, r.level
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.level >= 40
    ORDER BY r.level DESC, u.name ASC
")->fetchAll(PDO::FETCH_GROUP);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Team - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #c084fc;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .auth-buttons a {
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login {
            color: var(--primary);
            margin-right: 1rem;
        }

        .btn-register {
            background: var(--primary);
            color: white;
        }

        .btn-register:hover {
            background: var(--primary-dark);
        }

        /* Page Header */
        .page-header {
            padding: 10rem 5% 5rem;
            text-align: center;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
        }

        .page-header h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .page-header p {
            font-size: 1.2rem;
            color: #9ca3af;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Team Section */
        .team-section {
            padding: 5rem 5%;
        }

        .role-group {
            margin-bottom: 4rem;
        }

        .role-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--primary);
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }

        .team-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .team-card:hover {
            transform: translateY(-5px);
        }

        .team-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid var(--primary);
        }

        .team-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .team-role {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .team-socials a {
            color: var(--text-light);
            font-size: 1.2rem;
            margin: 0 0.5rem;
            transition: color 0.3s;
        }

        .team-socials a:hover {
            color: var(--primary);
        }

        .footer {
            background: #111827;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: auto;
        }
    </style>
</head>

<body>
       <?php include 'includes/header.php'; ?>
    <header class="page-header">
        <h1>Meet Our Team</h1>
        <p>The dedicated individuals working behind the scenes to make the Computer Club awesome.</p>
    </header>

    <section class="team-section">
        <?php if (empty($team_members)): ?>
            <div style="text-align:center; padding: 3rem; color: var(--text-light);">
                <h2>No team members found.</h2>
                <p>Register users and assign them admin/management roles to see them here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($team_members as $role_name => $member_list): ?>
                <div class="role-group">
                    <h2 class="role-title"><?php echo htmlspecialchars($role_name); ?>s</h2>
                    <div class="team-grid">
                        <?php foreach ($member_list as $member): ?>
                            <div class="team-card">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($member['name']); ?>&background=random&color=fff"
                                    alt="Profile" class="team-img">
                                <h3 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <div class="team-role"><?php echo htmlspecialchars($role_name); ?></div>
                                <div class="team-socials">
                                    <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>"><i
                                            class="fa-solid fa-envelope"></i></a>
                                    <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                                    <a href="#"><i class="fa-brands fa-github"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <footer class="footer">
        <p>&copy; 2023 Computer Club. All Rights Reserved.</p>
    </footer>

</body>

</html>