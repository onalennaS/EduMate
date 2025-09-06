 <?php
// teacher_dashboard/teacher_dashboard.php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Sample data - replace with actual database queries
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$total_students = 45;
$courses_taught = 3;
$pending_to_grade = 8;
$recent_submissions = [
    ['student' => 'John Doe', 'assignment' => 'Math Quiz 3', 'course' => 'Mathematics', 'submitted_at' => '2024-03-15 14:30'],
    ['student' => 'Jane Smith', 'assignment' => 'Science Lab Report', 'course' => 'Physics', 'submitted_at' => '2024-03-15 13:45'],
    ['student' => 'Mike Johnson', 'assignment' => 'Essay Assignment', 'course' => 'English', 'submitted_at' => '2024-03-15 12:20']
];
$announcements = [
    ['title' => 'New Grading System Update', 'date' => '2024-03-14'],
    ['title' => 'Parent-Teacher Conference', 'date' => '2024-03-20'],
    ['title' => 'Midterm Exam Schedule', 'date' => '2024-03-25']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - EDUMATE</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --primary-blue: #1e3a5f;
            --secondary-blue: #2563eb;
            --light-blue: #dbeafe;
            --dark-blue: #1e40af;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-x: hidden;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            min-height: 80px;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .sidebar-brand i {
            margin-right: 0.75rem;
            font-size: 1.8rem;
        }

        .sidebar.collapsed .sidebar-brand span {
            opacity: 0;
            width: 0;
        }

        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: -15px;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .sidebar-toggle:hover {
            background: var(--secondary-blue);
            transform: scale(1.1);
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 1rem;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-link span {
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }

        .sidebar.collapsed .nav-link {
            margin-right: 0;
            border-radius: 0;
            justify-content: center;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
            padding: 2rem;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--primary-blue);
            font-weight: bold;
        }

        .user-details h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .user-details small {
            opacity: 0.8;
            font-size: 0.75rem;
        }

        .sidebar.collapsed .user-info {
            justify-content: center;
        }

        .sidebar.collapsed .user-details {
            display: none;
        }

        /* Main Content Styles */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(30, 58, 95, 0.2);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-blue);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-blue);
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header-custom {
            background: var(--light-blue);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: var(--dark-blue);
        }

        .priority-high { color: #dc2626; font-weight: 600; }
        .priority-normal { color: #059669; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-toggle {
                position: fixed;
                top: 20px;
                left: 20px;
                background: var(--primary-blue);
                color: white;
                border: none;
                border-radius: 8px;
                padding: 0.5rem;
                z-index: 1001;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .welcome-card {
                padding: 1.5rem;
                text-align: center;
            }

            .stat-card {
                text-align: center;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle d-md-none" onclick="toggleMobileSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay d-md-none" onclick="toggleMobileSidebar()"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="teacher_dashboard.php" class="sidebar-brand">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>EDUMATE</span>
            </a>
            <!-- Desktop Toggle Button -->
            <button class="sidebar-toggle d-none d-md-block" onclick="toggleSidebar()">
                <i class="fas fa-angle-left" id="toggleIcon"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="teacher_dashboard.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_courses.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Courses">
                        <i class="fas fa-book-open"></i>
                        <span>Manage Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="assignments.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Assignments & Tests">
                        <i class="fas fa-file-alt"></i>
                        <span>Assignments & Tests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="students.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Students">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="notifications.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Profile">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h6><?php echo $teacher_name; ?></h6>
                    <small>Teacher</small>
                </div>
            </div>
            <a class="nav-link mt-2" href="../logout.php" onclick="confirmLogout(event)" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3">Welcome back, <?php echo $teacher_name; ?>! 👨‍🏫</h1>
                    <p class="mb-0 opacity-90">Ready to inspire and educate? Check out your latest updates and student progress.</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-users text-success" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $total_students; ?></div>
                            <div class="text-muted">Total Students</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-book-open text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $courses_taught; ?></div>
                            <div class="text-muted">Courses Teaching</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clipboard-check text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $pending_to_grade; ?></div>
                            <div class="text-muted">Pending to Grade</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-chart-line text-info" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number">A-</div>
                            <div class="text-muted">Average Class Grade</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="row">
            <!-- Recent Submissions -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-upload me-2"></i>Recent Student Submissions
                    </div>
                    <div class="p-3">
                        <?php if (empty($recent_submissions)): ?>
                            <p class="text-muted text-center py-3">No recent submissions</p>
                        <?php else: ?>
                            <?php foreach ($recent_submissions as $submission): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?php echo $submission['student']; ?></div>
                                        <small class="text-muted"><?php echo $submission['assignment']; ?> - <?php echo $submission['course']; ?></small>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-outline-success btn-sm" onclick="gradeSubmission(<?php echo rand(1,100); ?>)">
                                            <i class="fas fa-check me-1"></i>Grade
                                        </button>
                                        <div><small class="text-muted"><?php echo date('M j, g:i A', strtotime($submission['submitted_at'])); ?></small></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="assignments.php" class="btn btn-outline-success btn-sm">View All Submissions</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Announcements -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-bullhorn me-2"></i>System Announcements
                    </div>
                    <div class="p-3">
                        <?php if (empty($announcements)): ?>
                            <p class="text-muted text-center py-3">No announcements</p>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?php echo $announcement['title']; ?></div>
                                        <small class="text-muted">Posted on <?php echo date('M j, Y', strtotime($announcement['date'])); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="notifications.php" class="btn btn-outline-success btn-sm">View All Announcements</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </div>
                    <div class="p-3">
                        <div class="row">
                            <div class="col-md-3 col-6 mb-2">
                                <a href="manage_courses.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-book-open me-2"></i>Manage Courses
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="assignments.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-file-alt me-2"></i>Create Assignment
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="students.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-users me-2"></i>View Students
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="profile.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar Toggle Functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleIcon = document.getElementById('toggleIcon');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-angle-left');
                toggleIcon.classList.add('fa-angle-right');
            } else {
                toggleIcon.classList.remove('fa-angle-right');
                toggleIcon.classList.add('fa-angle-left');
            }
            
            updateTooltips();
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        function updateTooltips() {
            const sidebar = document.getElementById('sidebar');
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            
            tooltips.forEach(tooltip => {
                const bsTooltip = bootstrap.Tooltip.getInstance(tooltip);
                if (bsTooltip) bsTooltip.dispose();
                
                if (sidebar.classList.contains('collapsed')) {
                    new bootstrap.Tooltip(tooltip);
                }
            });
        }

        // Grade submission function
        function gradeSubmission(submissionId) {
            Swal.fire({
                title: 'Grade Submission',
                text: 'Opening grading interface...',
                icon: 'info',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                // Redirect to grading page
                window.location.href = 'assignments.php?grade=' + submissionId;
            });
        }

        // Logout confirmation with SweetAlert2
        function confirmLogout(event) {
            event.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will be logged out of your account.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('collapsed')) {
                updateTooltips();
            }
        });

        // Close mobile sidebar when clicking on nav links
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    toggleMobileSidebar();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    </script>
</body>
</html>