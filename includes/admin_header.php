<?php
// Start session and check authentication if needed
session_start();
// Add authentication check here if required
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Admin Dashboard - EDUMATE</title>
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
            --primary-color: #3b82f6;  /* Changed to blue */
            --secondary-color: #2563eb;
            --light-color: #dbeafe;
            --dark-color: #1e40af;
            --accent-color: #93c5fd;
            --light-neutral: #f1f5f9;
            --dark-neutral: #334155;
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
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
            background: var(--primary-color);
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
            background: var(--secondary-color);
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
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--primary-color);
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
            background: var(--light-neutral);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: var(--dark-neutral);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

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
                background: var(--primary-color);
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

            .stat-card {
                text-align: center;
                margin-bottom: 1rem;
            }
        }

        /* IT Admin Specific Styles */
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-add {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-add:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 25px;
            border: 1px solid #e5e7eb;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background: var(--light-neutral);
            color: var(--dark-neutral);
            font-weight: 600;
            border-top: none;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .action-icon {
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 0.75rem;
        }
        
        .action-icon:hover {
            color: var(--secondary-color);
            transform: scale(1.1);
        }
        
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header {
            background: var(--light-neutral);
            color: var(--dark-neutral);
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-neutral);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        /* Footer Styles */
        .admin-footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            position: relative;
            z-index: 100;
        }
        
        .admin-footer.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        @media (max-width: 768px) {
            .admin-footer {
                margin-left: 0;
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
            <a href="it_admin_dashboard.php" class="sidebar-brand">
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
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'it_admin_dashboard.php' ? 'active' : ''; ?>" href="it_admin_dashboard.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_teachers.php' ? 'active' : ''; ?>" href="manage_teachers.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Teachers">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Manage Teachers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Students">
                        <i class="fas fa-user-graduate"></i>
                        <span>Manage Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'subject_enrollment.php' ? 'active' : ''; ?>" href="subject_enrollment.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Subject Enrollment">
                        <i class="fas fa-book"></i>
                        <span>Subject Enrollment</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'system_settings.php' ? 'active' : ''; ?>" href="system_settings.php" data-bs-toggle="tooltip" data-bs-placement="right" title="System Settings">
                        <i class="fas fa-cogs"></i>
                        <span>System Settings</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="user-details">
                    <h6>IT Administrator</h6>
                    <small>System Manager</small>
                </div>
            </div>
            <a class="nav-link mt-2" href="../logout.php" onclick="confirmLogout(event)" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

   