<?php
// includes/teacher_header.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Get teacher name from session
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - EDUMATE' : 'EDUMATE - Teacher Dashboard'; ?></title>
    
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

        /* Content Card Styles */
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

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(30, 58, 95, 0.2);
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

        /* Additional page-specific styles can be added here */
        <?php if (isset($additional_css)) echo $additional_css; ?>
    </style>
    
    <!-- Additional head content can be added by individual pages -->
    <?php if (isset($additional_head)) echo $additional_head; ?>
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
                    <a class="nav-link <?php echo ($current_page == 'teacher_dashboard.php') ? 'active' : ''; ?>" 
                       href="teacher_dashboard.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'manage_subjects.php') ? 'active' : ''; ?>" 
                       href="manage_subjects.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Subjects">
                        <i class="fas fa-book-open"></i>
                        <span>Manage Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'assignments.php') ? 'active' : ''; ?>" 
                       href="assignments.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Assignments & Tests">
                        <i class="fas fa-file-alt"></i>
                        <span>Assignments & Tests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'students.php') ? 'active' : ''; ?>" 
                       href="students.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Students">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>" 
                       href="notifications.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" 
                       href="profile.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Profile">
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
                    <h6><?php echo htmlspecialchars($teacher_name); ?></h6>
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
        <!-- Content will be added by individual pages -->