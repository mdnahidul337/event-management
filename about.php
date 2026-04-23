<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - SCC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <header class="page-header">
        <h1>About the Club</h1>
        <p>Empowering the next generation of tech leaders.</p>
    </header>

    <div class="container" style="max-width: 800px;">
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