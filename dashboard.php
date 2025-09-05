<?php
require_once 'auth.php';

// Check if user is logged in, if not redirect to login
requireLogin();

$user = getCurrentUser();
if (!$user) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EduMate</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">EduMate</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="dashboard">
        <div class="dashboard-welcome">
            <h1>Welcome, <?php echo h($user['username']); ?>! 🎉</h1>
            <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <strong>User Type:</strong><br>
                    <?php echo ucfirst(h($user['user_type'])); ?>
                </div>
                <div style="text-align: center;">
                    <strong>Email:</strong><br>
                    <?php echo h($user['email']); ?>
                </div>
                <div style="text-align: center;">
                    <strong>Member Since:</strong><br>
                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                </div>
                <?php if ($user['last_login']): ?>
                <div style="text-align: center;">
                    <strong>Last Login:</strong><br>
                    <?php echo date('M d, Y g:i A', strtotime($user['last_login'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-options">
            <h2>What would you like to do today?</h2>
            
            <div class="option-grid">
                <?php if ($user['user_type'] === 'student'): ?>
                    <div class="option-card">
                        <h3>📚 My Courses</h3>
                        <p>Access your enrolled courses and continue your learning journey.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Course Management')">View Courses</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>🔬 Virtual Lab</h3>
                        <p>Conduct interactive experiments in our virtual laboratory environment.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Virtual Laboratory')">Enter Lab</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>📊 Progress Report</h3>
                        <p>Track your learning progress and identify areas for improvement.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Progress Tracking')">View Progress</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>⚙️ Accessibility Settings</h3>
                        <p>Customize your learning experience based on your needs and preferences.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Accessibility Settings')">Open Settings</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>🎯 Practice Quiz</h3>
                        <p>Test your knowledge with adaptive quizzes tailored to your learning needs.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Practice Quizzes')">Start Quiz</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>📖 Study Materials</h3>
                        <p>Access textbooks, videos, and other learning resources.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Study Materials')">Browse Materials</a>
                    </div>
                <?php else: // Teacher dashboard ?>
                    <div class="option-card">
                        <h3>👥 Manage Students</h3>
                        <p>View and manage your students' progress and assignments.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Student Management')">Manage Students</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>📝 Create Assignments</h3>
                        <p>Create new assignments and experiments for your students.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Assignment Creator')">Create Assignment</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>📊 Class Analytics</h3>
                        <p>View detailed analytics about your class performance.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Analytics Dashboard')">View Analytics</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>🛠️ Course Management</h3>
                        <p>Create and manage your course content and materials.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Course Builder')">Manage Courses</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>🔬 Lab Designer</h3>
                        <p>Design custom virtual experiments for your students.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Lab Designer')">Design Labs</a>
                    </div>
                    
                    <div class="option-card">
                        <h3>📈 Grade Book</h3>
                        <p>Manage grades and provide feedback to your students.</p>
                        <a href="#" class="btn" onclick="showComingSoon('Grade Management')">Open Grade Book</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Stats Section -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-top: 2rem;">
            <h3 style="color: #4a6fa5; margin-bottom: 1rem;">Quick Stats</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 2rem; color: #6c63ff;">0</div>
                    <div>Courses Enrolled</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 2rem; color: #28a745;">0</div>
                    <div>Experiments Completed</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 2rem; color: #fd7e14;">0</div>
                    <div>Hours Studied</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 2rem; color: #dc3545;">New</div>
                    <div>Account Status</div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2024 EduMate. All rights reserved.</p>
    </footer>

    <script>
        function showComingSoon(feature) {
            alert('🚀 ' + feature + ' is coming soon!\n\nThis feature is currently under development and will be available in a future update. Thank you for your patience!');
        }
        
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to option cards
            const cards = document.querySelectorAll('.option-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>