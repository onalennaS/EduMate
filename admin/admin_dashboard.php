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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - EduMate</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 2rem;
        }
        header p {
            margin: 0.5rem 0 0;
            font-size: 1rem;
            opacity: 0.9;
        }
        main {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        .card h3 {
            margin: 0.5rem 0;
            font-size: 1.25rem;
        }
        .card p {
            font-size: 0.9rem;
            color: #475569;
            min-height: 50px;
        }
        .btn {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-green {
            background: #059669;
            color: white;
        }
        .btn-green:hover {
            background: #047857;
        }
        .btn-blue {
            background: #2563eb;
            color: white;
        }
        .btn-blue:hover {
            background: #1d4ed8;
        }
        .btn-yellow {
            background: #d97706;
            color: white;
        }
        .btn-yellow:hover {
            background: #b45309;
        }
        footer {
            margin-top: 2rem;
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
</head>
<body>
<header>
    <h1>EduMate Admin Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($adminName) ?> 👋</p>
    <a href="../logout.php" 
       style="display:inline-block;margin-top:1rem;padding:0.5rem 1rem;
              background:#dc2626;color:#fff;text-decoration:none;
              border-radius:6px;font-weight:bold;">
       🚪 Logout
    </a>
</header>

<main>
    <div class="grid">
        <!-- Open Resources -->
        <div class="card">
            <div class="icon">🌍</div>
            <h3>Open Resources</h3>
            <p>Add, edit, or remove additional study resources for all subjects.</p>
            <a href="admin_open_resources.php" class="btn btn-green">Manage</a>
        </div>

        <!-- Manage Users -->
        <div class="card">
            <div class="icon">👥</div>
            <h3>Users</h3>
            <p>View and manage students, teachers, and admins.</p>
            <a href="manage_users.php" class="btn btn-blue">Manage</a>
        </div>

        <!-- Reports -->
        <div class="card">
            <div class="icon">📊</div>
            <h3>Reports</h3>
            <p>Generate insights on subjects, enrollments, and accessibility usage.</p>
            <a href="#" class="btn btn-yellow">Coming Soon</a>
        </div>
    </div>
</main>

<footer>
    &copy; <?= date("Y") ?> EduMate. All rights reserved.
</footer>
</body>
</html>
