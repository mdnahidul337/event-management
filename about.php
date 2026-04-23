<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --bg-light: #f9fafb;
            --text-dark: #1f2937;
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

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
            background: var(--primary);
            color: white;
        }

        .page-header {
            padding: 10rem 5% 5rem;
            text-align: center;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .content {
            padding: 5rem 5%;
            max-width: 800px;
            margin: 0 auto;
            flex: 1;
            line-height: 1.8;
            font-size: 1.1rem;
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
        <h1>About the Club</h1>
        <p>Empowering the next generation of tech leaders.</p>
    </header>

    <div class="content">
        <h2>Our Mission</h2>
        <p style="margin-bottom: 2rem;">The Computer Club aims to create an environment where students can explore their
            passion for technology, learn new skills, and collaborate on innovative projects. Whether you are a beginner
            or an advanced programmer, our club provides resources, workshops, and networking opportunities to help you
            succeed.</p>

        <h2>What We Do</h2>
        <ul style="margin-left: 2rem; margin-bottom: 2rem;">
            <li>Host technical workshops and bootcamps.</li>
            <li>Organize annual hackathons and coding competitions.</li>
            <li>Provide mentorship from industry professionals.</li>
            <li>Collaborate on open-source and community projects.</li>
        </ul>

        <h2>Join Us</h2>
        <p>Membership is open to everyone with an interest in tech. <a href="register.php"
                style="color:var(--primary); font-weight:bold;">Register an account today</a> to start joining our
            exclusive events!</p>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Computer Club. All Rights Reserved.</p>
    </footer>

</body>

</html>