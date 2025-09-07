<?php
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduMate - Accessible Science Education</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">EduMate</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['user_type'] === 'student'): ?>
                        <li><a href="student_dashboard/student_dashboard.php">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="teacher_dashboard/teacher_dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                    <li><span style="opacity: 0.8;">Welcome, <?php echo h($_SESSION['username']); ?>!</span></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Making Science Education Accessible to All</h1>
            <p>EduMate adapts experiments based on each student's abilities and learning pace, ensuring meaningful engagement with core scientific concepts regardless of disabilities.</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-large">Get Started</a>
            <?php endif; ?>
        </div>
    </section>

    <section id="features" class="features">
        <h2>How EduMate Works</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">👁️</div>
                <h3>Visual Impairments</h3>
                <p>Rich audio descriptions and haptic feedback cues for students with visual impairments.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👂</div>
                <h3>Hearing Impairments</h3>
                <p>Comprehensive visual demonstrations and sign language interpretation for hearing-impaired students.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>Universal Access</h3>
                <p>One platform that democratizes science education while ensuring no student is left behind.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Adaptive Learning</h3>
                <p>AI-powered system that adapts to each student's learning pace and abilities.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔬</div>
                <h3>Virtual Experiments</h3>
                <p>Safe, interactive virtual experiments that simulate real laboratory conditions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Progress Tracking</h3>
                <p>Comprehensive analytics to track student progress and identify areas for improvement.</p>
            </div>
        </div>
    </section>

    <section id="about" class="about">
        <h2>About EduMate</h2>
        <p>EduMate is an AI-powered platform designed to make advanced science education truly inclusive and available to all learners regardless of their school's physical resources or individual learning needs.</p>
        <p style="margin-top: 1rem;">Our mission is to break down barriers in science education by providing adaptive, accessible, and engaging learning experiences for every student.</p>
    </section>

    <?php if (!isLoggedIn()): ?>
    <section style="padding: 2rem; background-color: #f8f9fa; text-align: center;">
        <h2>Ready to Get Started?</h2>
        <p>Join thousands of students and teachers already using EduMate!</p>
        <div style="margin-top: 1rem;">
            <a href="register.php" class="btn btn-large" style="margin-right: 1rem;">Sign Up Now</a>
            <a href="login.php" class="btn btn-large" style="background-color: #28a745;">Login</a>
        </div>
    </section>
    <?php endif; ?>

    <footer id="contact">
        <p>&copy; 2024 EduMate. All rights reserved.</p>
    </footer>
</body>
</html>
