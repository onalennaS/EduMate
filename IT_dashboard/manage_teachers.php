<?php
session_start();
require_once '../config/database.php';

// Ensure only admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = null;

// --- Handle Add Teacher ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, created_at) VALUES (?, ?, ?, 'teacher', NOW())");
            if ($stmt->execute([$username, $email, $hashed])) {
                $success = "Teacher account created successfully.";
            } else {
                $errors[] = "Failed to create teacher.";
            }
        }
    }
}

// --- Handle Delete Teacher ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $teacher_id = (int) $_POST['teacher_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND user_type='teacher'");
    if ($stmt->execute([$teacher_id])) {
        $success = "Teacher deleted.";
    } else {
        $errors[] = "Failed to delete teacher.";
    }
}

// --- Handle Edit Teacher ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $teacher_id = (int) $_POST['teacher_id'];
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (!$username || !$email) {
        $errors[] = "Username and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email=? OR username=?) AND id<>?");
        $stmt->execute([$email, $username, $teacher_id]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already taken.";
        } else {
            // Build query dynamically
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=? WHERE id=? AND user_type='teacher'");
                $ok = $stmt->execute([$username, $email, $hashed, $teacher_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=? WHERE id=? AND user_type='teacher'");
                $ok = $stmt->execute([$username, $email, $teacher_id]);
            }
            if ($ok) {
                $success = !empty($new_password) ? "Teacher updated (password reset included)." : "Teacher updated successfully.";
            } else {
                $errors[] = "Failed to update teacher.";
            }
        }
    }
}

// --- Fetch Teachers ---
$stmt = $pdo->query("SELECT id, username, email, created_at FROM users WHERE user_type='teacher' ORDER BY created_at DESC");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Teachers - EduMate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            display: flex;
        }

        /* Ensure sidebar + content alignment */
        .content {
            margin-left: 260px;
            /* match your sidebar width */
            padding: 2rem;
            flex: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: #1e3a8a;
        }

        label {
            display: block;
            margin-top: 0.5rem;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-top: 0.25rem;
            box-sizing: border-box;
        }

        button {
            margin-top: 0.75rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-green {
            background: #059669;
            color: white;
        }

        .btn-red {
            background: #dc2626;
            color: white;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            font-weight: bold;
        }

        img.thumb {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .teacher-details {
            font-size: 0.9rem;
        }

        .teacher-name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .teacher-info {
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .subject-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 0.5rem;
            }
        }
    </style>
    <script>
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete teacher "${name}"? This action cannot be undone.`)) {
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-form').submit();
            }
        }

        function toggleTeacherForm(id) {
            const form = document.getElementById('teacher-form-' + id);
            const btn = document.getElementById('toggle-btn-' + id);
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                btn.textContent = 'Cancel Edit';
                btn.className = 'btn-red';
            } else {
                form.style.display = 'none';
                btn.textContent = 'Edit';
                btn.className = 'btn-blue';
            }
        }
    </script>
</head>

<body>
    <div class="content">
        <div class="container">
            <h1>Manage Teachers</h1>

            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="error">
                    <?php foreach ($errors as $e): ?>
                        <?= htmlspecialchars($e) ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Add Teacher -->
            <div class="card">
                <h2>Add New Teacher</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div>
                            <label>Username <input type="text" name="username" required></label>
                        </div>
                        <div>
                            <label>Email <input type="email" name="email" required></label>
                        </div>
                    </div>
                    
                    <div class="form-full">
                        <label>Password <input type="password" name="password" required></label>
                    </div>
                    
                    <button type="submit" class="btn-green">Create Teacher</button>
                </form>
            </div>

            <!-- Teacher List -->
            <div class="card">
                <h2>All Teachers (<?= count($teachers) ?>)</h2>
                <?php if (empty($teachers)): ?>
                    <p>No teachers found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Teacher Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td><?= $t['id'] ?></td>
                                    <td>
                                        <div class="teacher-details">
                                            <div class="teacher-name">
                                                <?= htmlspecialchars($t['username']) ?>
                                            </div>
                                            <div class="teacher-info">
                                                <strong>Email:</strong> <?= htmlspecialchars($t['email']) ?><br>
                                                <strong>Created:</strong> <?= date("M d, Y", strtotime($t['created_at'])) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" id="toggle-btn-<?= $t['id'] ?>" class="btn-blue" onclick="toggleTeacherForm(<?= $t['id'] ?>)">Edit</button>
                                        <button type="button" class="btn-red" onclick="confirmDelete(<?= $t['id'] ?>, '<?= htmlspecialchars($t['username']) ?>')">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div id="teacher-form-<?= $t['id'] ?>" style="display: none; padding: 1rem; background: #f9fafb; border-radius: 6px; margin: 0.5rem 0;">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                                
                                                <h4>Edit Teacher: <?= htmlspecialchars($t['username']) ?></h4>
                                                
                                                <div class="form-row">
                                                    <div>
                                                        <label>Username <input type="text" name="username" value="<?= htmlspecialchars($t['username']) ?>"></label>
                                                    </div>
                                                    <div>
                                                        <label>Email <input type="email" name="email" value="<?= htmlspecialchars($t['email']) ?>"></label>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-full">
                                                    <label>New Password (leave blank to keep) <input type="password" name="new_password" placeholder="Enter new password"></label>
                                                </div>
                                                
                                                <button type="submit" class="btn-green">Update Teacher</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="delete-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="teacher_id" id="delete-id">
    </form>
</body>

</html>
<?php
include '../includes/admin_footer.php';
?>