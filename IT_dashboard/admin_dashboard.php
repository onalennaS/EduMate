<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    die(" No session found. Please <a href='../login.php'>login</a>.");
}

if (($_SESSION['user_type'] ?? '') !== 'admin') {
    die(" Access denied. You are logged in as <strong>{$_SESSION['user_type']}</strong>, not admin.");
}

require_once '../config/database.php';

$adminName = $_SESSION['username'] ?? 'Admin';

// Get dashboard statistics
try {
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'student'");
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total teachers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'teacher'");
    $total_teachers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total subjects (if subjects table exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM subjects");
        $total_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        $total_subjects = 0;
    }

    // Recent registrations (last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $recent_registrations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    $total_students = 0;
    $total_teachers = 0;
    $total_subjects = 0;
    $recent_registrations = 0;
}

// Sidebar + header
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduMate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            display: flex;
        }

        /* Align content with sidebar */
        .content {
            margin-left: 260px; /* same width as sidebar */
            padding: 2rem;
            flex: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-header h1 {
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }

        .welcome-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card.students { border-left-color: #10b981; }
        .stat-card.teachers { border-left-color: #3b82f6; }
        .stat-card.subjects { border-left-color: #f59e0b; }
        .stat-card.recent { border-left-color: #8b5cf6; }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.students .stat-icon { background: #ecfdf5; color: #10b981; }
        .stat-card.teachers .stat-icon { background: #eff6ff; color: #3b82f6; }
        .stat-card.subjects .stat-icon { background: #fffbeb; color: #f59e0b; }
        .stat-card.recent .stat-icon { background: #f3e8ff; color: #8b5cf6; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Management Cards */
        .management-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .management-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            text-align: center;
        }

        .management-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .management-icon {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .card-students .management-icon { background: #ecfdf5; color: #10b981; }
        .card-teachers .management-icon { background: #eff6ff; color: #3b82f6; }
        .card-resources .management-icon { background: #fff7ed; color: #ea580c; }
        .card-reports .management-icon { background: #f0f9ff; color: #0ea5e9; }

        .management-card h3 {
            font-size: 1.25rem;
            color: #1f2937;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }

        .management-card p {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0 0 1.5rem 0;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
            text-decoration: none;
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            text-decoration: none;
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
            text-decoration: none;
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
            text-decoration: none;
            color: white;
        }

        footer {
            text-align: center;
            padding: 2rem 0;
            color: #6b7280;
            font-size: 0.9rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 1rem;
            }

            .welcome-header {
                padding: 1.5rem;
            }

            .welcome-header h1 {
                font-size: 1.5rem;
            }

            .stats-grid,
            .management-grid {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="container">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <div class="welcome-content">
                    <h1>EduMate Admin Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars($adminName) ?>! Here's your system overview.</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card students">
                    <div class="stat-icon">👨‍🎓</div>
                    <div class="stat-number"><?= $total_students ?></div>
                    <p class="stat-label">Total Students</p>
                </div>

                <div class="stat-card teachers">
                    <div class="stat-icon">👩‍🏫</div>
                    <div class="stat-number"><?= $total_teachers ?></div>
                    <p class="stat-label">Total Teachers</p>
                </div>

                <div class="stat-card subjects">
                    <div class="stat-icon">📚</div>
                    <div class="stat-number"><?= $total_subjects ?></div>
                    <p class="stat-label">Subjects Available</p>
                </div>

            </div>

            <!-- Management Section -->
            <div class="management-section">
                <h2 class="section-title">System Management</h2>
                <div class="management-grid">
                    <!-- Manage Students -->
                    <div class="management-card card-students">
                        <div class="management-icon">👥</div>
                        <h3>Manage Students</h3>
                        <p>Add, edit, view student profiles, documents, and academic information. Monitor student progress and accessibility needs.</p>
                        <a href="manage_students.php" class="btn btn-success">Manage Students</a>
                    </div>

                    <!-- Manage Teachers -->
                    <div class="management-card card-teachers">
                        <div class="management-icon">🎓</div>
                        <h3>Manage Teachers</h3>
                        <p>Create teacher accounts, update profiles, and manage teaching staff credentials and permissions.</p>
                        <a href="manage_teachers.php" class="btn btn-primary">Manage Teachers</a>
                    </div>

                    <!-- Open Resources -->
                    <div class="management-card card-resources">
                        <div class="management-icon">📖</div>
                        <h3>Learning Resources</h3>
                        <p>Upload and manage educational resources, study materials, and additional learning content for all subjects.</p>
                        <a href="admin_open_resources.php" class="btn btn-warning">Manage Resources</a>
                    </div>

                    <!-- System Reports -->
                    <div class="management-card card-reports">
                        <div class="management-icon">📊</div>
                        <h3>System Reports</h3>
                        <p>Generate comprehensive reports on user activity, system usage, and educational analytics.</p>
                        <a href="#" class="btn btn-secondary">Coming Soon</a>
                    </div>
                </div>
            </div>

            <footer>
                &copy; <?= date("Y") ?> EduMate Educational Management System. All rights reserved.
            </footer>
        </div>
    </div>
</body>
</html>

<?php 
include '../includes/admin_footer.php';
?>