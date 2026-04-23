<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --bg-light: #f9fafb;
            --text-dark: #1f2937;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }
        
        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 5%; background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px); position: fixed; width: 100%; top: 0; z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none;}
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: var(--primary); }
        .auth-buttons a { padding: 0.5rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; background: var(--primary); color: white; }

        .page-header { padding: 10rem 5% 5rem; text-align: center; background: linear-gradient(135deg, #1f2937 0%, #111827 100%); color: white; }
        .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
        
        .contact-container { padding: 5rem 5%; max-width: 800px; margin: 0 auto; flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; }
        .btn { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 6px; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        
        .contact-info { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .info-item { margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem; }
        .info-item i { font-size: 1.5rem; color: var(--primary); }

        .footer { background: #111827; color: white; text-align: center; padding: 2rem; margin-top: auto; }

        @media (max-width: 768px) {
            .contact-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <header class="page-header">
        <h1>Contact Us</h1>
        <p>Have questions? We'd love to hear from you.</p>
    </header>

    <div class="contact-container">
        <div>
            <h2>Send a Message</h2>
            <p style="color:#6b7280; margin-bottom: 2rem; margin-top:0.5rem;">Fill out the form and our team will get back to you shortly.</p>
            <form action="#" method="POST" onsubmit="alert('Message sent! (Mockup)'); return false;">
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn">Send Message</button>
            </form>
        </div>

        <div class="contact-info">
            <h2 style="margin-bottom: 2rem;">Contact Information</h2>
            <div class="info-item">
                <i class="fa-solid fa-location-dot"></i>
                <div>
                    <strong>Location</strong><br>
                    <span style="color:#6b7280;">University Campus, Building 4</span>
                </div>
            </div>
            <div class="info-item">
                <i class="fa-solid fa-envelope"></i>
                <div>
                    <strong>Email</strong><br>
                    <span style="color:#6b7280;">contact@computerclub.edu</span>
                </div>
            </div>
            <div class="info-item">
                <i class="fa-solid fa-phone"></i>
                <div>
                    <strong>Phone</strong><br>
                    <span style="color:#6b7280;">+880 1700-000000</span>
                </div>
            </div>

            <?php if(!empty($global_settings['facebook_url']) || !empty($global_settings['twitter_url'])): ?>
            <div class="info-item" style="margin-top: 2rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                <div style="display:flex; gap: 1rem; font-size: 1.5rem;">
                    <?php if(!empty($global_settings['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($global_settings['facebook_url']); ?>" target="_blank" style="color: #1877F2;"><i class="fa-brands fa-facebook"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($global_settings['twitter_url'])): ?>
                        <a href="<?php echo htmlspecialchars($global_settings['twitter_url']); ?>" target="_blank" style="color: #1DA1F2;"><i class="fa-brands fa-twitter"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Computer Club. All Rights Reserved.</p>
    </footer>

</body>
</html>
