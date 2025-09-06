<?php
// student_dashboard/student_dashboard.php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Sample data - replace with actual database queries
$student_name = $_SESSION['student_name'] ?? 'Student';
$courses_enrolled = 4;
$pending_assignments = 3;
$recent_grades = [
    ['course' => 'Mathematics', 'assignment' => 'Algebra Test', 'grade' => 'A', 'date' => '2024-03-15'],
    ['course' => 'Science', 'assignment' => 'Lab Report', 'grade' => 'B+', 'date' => '2024-03-12'],
    ['course' => 'English', 'assignment' => 'Essay', 'grade' => 'A-', 'date' => '2024-03-10']
];
$upcoming_deadlines = [
    ['course' => 'Mathematics', 'assignment' => 'Chapter 5 Quiz', 'due_date' => '2024-03-20'],
    ['course' => 'History', 'assignment' => 'Research Project', 'due_date' => '2024-03-25'],
    ['course' => 'Science', 'assignment' => 'Experiment Report', 'due_date' => '2024-03-28']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EDUMATE</title>
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
            --primary-neutral: #64748b;
            --secondary-neutral: #475569;
            --light-neutral: #f1f5f9;
            --dark-neutral: #334155;
            --accent-neutral: #94a3b8;
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
            background: linear-gradient(135deg, var(--primary-neutral) 0%, var(--dark-neutral) 100%);
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
            background: var(--primary-neutral);
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
            background: var(--secondary-neutral);
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
            background: var(--light-neutral);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--primary-neutral);
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
            background: linear-gradient(135deg, var(--primary-neutral), var(--secondary-neutral));
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(100, 116, 139, 0.2);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-neutral);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-neutral);
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header-custom {
            background: var(--light-neutral);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: var(--dark-neutral);
        }

        .grade-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .grade-a { background: #dcfce7; color: #16a34a; }
        .grade-b { background: #fef3c7; color: #d97706; }
        .grade-c { background: #fee2e2; color: #dc2626; }

        .deadline-urgent { color: #dc2626; font-weight: 600; }
        .deadline-normal { color: #059669; }

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
                background: var(--primary-neutral);
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
            <a href="student_dashboard.php" class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
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
                    <a class="nav-link active" href="student_dashboard.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="courses.php" data-bs-toggle="tooltip" data-bs-placement="right" title="My Courses">
                        <i class="fas fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="assignments.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Assignments & Tests">
                        <i class="fas fa-file-alt"></i>
                        <span>Assignments & Tests</span>
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
                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h6><?php echo $student_name; ?></h6>
                    <small>Student</small>
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
                    <h1 class="mb-3">Welcome back, <?php echo $student_name; ?>! 👋</h1>
                    <p class="mb-0 opacity-90">Ready to continue your learning journey? Check out your latest updates and upcoming assignments.</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="fas fa-user-graduate" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-book text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $courses_enrolled; ?></div>
                            <div class="text-muted">Courses Enrolled</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clipboard-list text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $pending_assignments; ?></div>
                            <div class="text-muted">Pending Assignments</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-chart-line text-success" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number">B+</div>
                            <div class="text-muted">Average Grade</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-check text-info" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number">12</div>
                            <div class="text-muted">Assignments Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="row">
            <!-- Recent Grades -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-trophy me-2"></i>Recent Grades
                    </div>
                    <div class="p-3">
                        <?php if (empty($recent_grades)): ?>
                            <p class="text-muted text-center py-3">No recent grades available</p>
                        <?php else: ?>
                            <?php foreach ($recent_grades as $grade): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?php echo $grade['assignment']; ?></div>
                                        <small class="text-muted"><?php echo $grade['course']; ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-<?php echo strtolower(substr($grade['grade'], 0, 1)); ?>">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                        <div><small class="text-muted"><?php echo date('M j', strtotime($grade['date'])); ?></small></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="assignments.php" class="btn btn-outline-primary btn-sm">View All Grades</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Deadlines -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-clock me-2"></i>Upcoming Deadlines
                    </div>
                    <div class="p-3">
                        <?php if (empty($upcoming_deadlines)): ?>
                            <p class="text-muted text-center py-3">No upcoming deadlines</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_deadlines as $deadline): ?>
                                <?php 
                                    $days_left = ceil((strtotime($deadline['due_date']) - time()) / (60 * 60 * 24));
                                    $is_urgent = $days_left <= 3;
                                ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?php echo $deadline['assignment']; ?></div>
                                        <small class="text-muted"><?php echo $deadline['course']; ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="<?php echo $is_urgent ? 'deadline-urgent' : 'deadline-normal'; ?>">
                                            <?php echo $days_left; ?> days left
                                        </div>
                                        <small class="text-muted"><?php echo date('M j', strtotime($deadline['due_date'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="assignments.php" class="btn btn-outline-primary btn-sm">View All Assignments</a>
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
                                <a href="courses.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-book me-2"></i>View Courses
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="assignments.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-file-alt me-2"></i>Assignments
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="notifications.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-bell me-2"></i>Notifications
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="profile.php" class="btn btn-outline-success w-100">
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
            // Initialize tooltips for collapsed sidebar
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