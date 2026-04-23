<?php
require_once 'includes/db_connect.php';

try { $pdo->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN session VARCHAR(50) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN blood_group VARCHAR(10) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL"); } catch (PDOException $e) {}

// Fetch users grouped by role to display as team members
// Only fetch roles level 40 and above (SuperAdmin down to SocialMediaManager)
session_start();
$is_upper_role = isset($_SESSION['role_level']) && $_SESSION['role_level'] >= 40;

$team_members = $pdo->query("
    SELECT r.name as role_name, u.name, u.email, u.phone, u.department, u.session, u.blood_group, u.profile_pic, r.level
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.level >= 10
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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabs-container { display: flex; justify-content: center; gap: 1rem; margin-bottom: 3rem; flex-wrap: wrap; }
        .tab-btn { padding: 0.8rem 2.5rem; font-size: 1rem; font-weight: 700; border: none; border-radius: 30px; background: #eef2ff; color: var(--primary); cursor: pointer; transition: all 0.3s; }
        .tab-btn.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .role-title { font-size: 1.75rem; font-weight: 800; margin-bottom: 2rem; color: #111827; padding-left: 1rem; border-left: 4px solid var(--primary); }
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
            
            <div class="tabs-container">
                <button class="tab-btn active" onclick="switchTab('team', this)">Our Team</button>
                <?php if(isset($team_members['Member'])): ?>
                    <button class="tab-btn" onclick="switchTab('members', this)">Members</button>
                <?php endif; ?>
            </div>

            <div id="tab-team" class="tab-content active">
                <?php foreach ($team_members as $role_name => $member_list): ?>
                    <?php if ($role_name === 'Member') continue; ?>
                    <div class="role-group">
                        <h2 class="role-title"><?php echo htmlspecialchars($role_name); ?>s</h2>
                        <div class="team-grid">
                            <?php foreach ($member_list as $member): ?>
                                <div class="team-card">
                                    <?php 
                                    if (!empty($member['profile_pic'])) {
                                        $avatar_url = "assets/image/Profile/" . htmlspecialchars($member['profile_pic']);
                                    } else {
                                        $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($member['name']) . "&background=random&color=fff";
                                    }
                                    ?>
                                    <img src="<?php echo $avatar_url; ?>" alt="Profile" class="team-img" style="object-fit:cover;">
                                    <h3 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                    <div class="team-role"><?php echo htmlspecialchars($role_name); ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-light); margin-top:0.5rem; line-height:1.6;">
                                        <?php if(!empty($member['department'])): ?><strong>Dep:</strong> <?php echo htmlspecialchars($member['department']); ?><br><?php endif; ?>
                                        <?php if(!empty($member['session'])): ?><strong>Session:</strong> <?php echo htmlspecialchars($member['session']); ?><br><?php endif; ?>
                                        <?php if(!empty($member['blood_group'])): ?><strong>Blood:</strong> <span style="color:#ef4444; font-weight:600;"><?php echo htmlspecialchars($member['blood_group']); ?></span><br><?php endif; ?>
                                        
                                        <?php if($is_upper_role): ?>
                                            <?php if(!empty($member['phone'])): ?><strong>Phone:</strong> <span style="color:var(--text-dark);"><?php echo htmlspecialchars($member['phone']); ?></span><br><?php endif; ?>
                                            <?php if(!empty($member['email'])): ?><strong>Email:</strong> <span style="color:var(--text-dark);"><?php echo htmlspecialchars($member['email']); ?></span><br><?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="team-socials">
                                        <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                                        <a href="#"><i class="fa-brands fa-github"></i></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if(isset($team_members['Member'])): ?>
            <div id="tab-members" class="tab-content">
                <div class="role-group">
                    <div class="team-grid">
                        <?php foreach ($team_members['Member'] as $member): ?>
                            <div class="team-card">
                                <?php 
                                if (!empty($member['profile_pic'])) {
                                    $avatar_url = "assets/image/Profile/" . htmlspecialchars($member['profile_pic']);
                                } else {
                                    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($member['name']) . "&background=random&color=fff";
                                }
                                ?>
                                <img src="<?php echo $avatar_url; ?>" alt="Profile" class="team-img" style="object-fit:cover;">
                                <h3 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <div class="team-role">Member</div>
                                <div style="font-size:0.85rem; color:var(--text-light); margin-top:0.5rem; line-height:1.6;">
                                    <?php if(!empty($member['department'])): ?><strong>Dep:</strong> <?php echo htmlspecialchars($member['department']); ?><br><?php endif; ?>
                                    <?php if(!empty($member['session'])): ?><strong>Session:</strong> <?php echo htmlspecialchars($member['session']); ?><br><?php endif; ?>
                                    <?php if(!empty($member['blood_group'])): ?><strong>Blood:</strong> <span style="color:#ef4444; font-weight:600;"><?php echo htmlspecialchars($member['blood_group']); ?></span><br><?php endif; ?>
                                    
                                    <?php if($is_upper_role): ?>
                                        <?php if(!empty($member['phone'])): ?><strong>Phone:</strong> <span style="color:var(--text-dark);"><?php echo htmlspecialchars($member['phone']); ?></span><br><?php endif; ?>
                                        <?php if(!empty($member['email'])): ?><strong>Email:</strong> <span style="color:var(--text-dark);"><?php echo htmlspecialchars($member['email']); ?></span><br><?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="team-socials">
                                    <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                                    <a href="#"><i class="fa-brands fa-github"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
    </script>
</body>
</html>