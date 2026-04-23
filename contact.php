<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 3rem; align-items: start; }
        .contact-form-card { background: white; padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); }
        .contact-info-card { background: white; padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); }
        .form-label { display: block; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem; }
        .form-input { width: 100%; padding: 0.9rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--bg-main); font-size: 1rem; outline: none; transition: border 0.3s; }
        .form-input:focus { border-color: var(--primary); }
        
        @media (max-width: 900px) {
            .contact-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <header class="page-header">
        <h1>Contact Us</h1>
        <p>Have questions? We'd love to hear from you.</p>
    </header>

    <div class="container">
        <div class="contact-grid">
            <div class="contact-form-card">
                <h2 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.75rem;">Send a Message</h2>
                <form action="#" method="POST" onsubmit="alert('Message sent! (Mockup)'); return false;">
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-input" placeholder="Enter your name" required>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input" placeholder="Enter your email" required>
                    </div>
                    <div style="margin-bottom: 2rem;">
                        <label class="form-label">Your Message</label>
                        <textarea class="form-input" rows="6" placeholder="How can we help?" required></textarea>
                    </div>
                    <button type="submit" class="btn-register" style="width: 100%; padding: 1rem; border: none; font-size: 1rem; cursor: pointer;">Send Message <i class="fa-solid fa-paper-plane" style="margin-left: 0.5rem;"></i></button>
                </form>
            </div>

            <div class="contact-info-card">
                <h2 style="font-weight: 800; margin-bottom: 2rem; font-size: 1.75rem;">Information</h2>
                <div style="display:flex; flex-direction:column; gap:2rem;">
                    <div style="display:flex; gap:1.25rem;">
                        <div style="width:50px; height:50px; background:#eef2ff; color:var(--primary); border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa-solid fa-location-dot" style="font-size:1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:1.1rem; margin-bottom:0.25rem;">Office Location</div>
                            <div style="color:var(--text-muted);">M/3, Section-14, Mirpur,<br>Dhaka-1216</div>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:1.25rem;">
                        <div style="width:50px; height:50px; background:#f0fdf4; color:#16a34a; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa-solid fa-envelope" style="font-size:1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:1.1rem; margin-bottom:0.25rem;">Email Address</div>
                            <div style="color:var(--text-muted);">simt.dhaka@gmail.com</div>
                        </div>
                    </div>

                    <div style="display:flex; gap:1.25rem;">
                        <div style="width:50px; height:50px; background:#fffbeb; color:#d97706; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa-solid fa-phone" style="font-size:1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:1.1rem; margin-bottom:0.25rem;">Phone Number</div>
                            <div style="color:var(--text-muted);">01936-005818-16</div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 3rem; border-top: 1px solid var(--border); padding-top: 2rem; display:flex; gap:1rem;">
                    <a href="#" style="width:40px; height:40px; background:var(--bg-main); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--text-muted); transition:0.3s;"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" style="width:40px; height:40px; background:var(--bg-main); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--text-muted); transition:0.3s;"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" style="width:40px; height:40px; background:var(--bg-main); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--text-muted); transition:0.3s;"><i class="fa-brands fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Computer Club. All Rights Reserved.</p>
    </footer>

</body>
</html>
