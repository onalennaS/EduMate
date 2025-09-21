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
            max-width: 1000px;
            margin: 0 auto;
        }

        h1 {
            color: #1e3a8a;
        }

        .card {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-top: 0.5rem;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-top: 0.25rem;
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

        th,
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
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
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this teacher?")) {
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</head>

<body>
    <div class="content">
        <h1>Manage Teachers</h1>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="error"><?php foreach ($errors as $e)
                echo htmlspecialchars($e) . "<br>"; ?></div><?php endif; ?>

        <!-- Add Teacher -->
        <div class="card">
            <h2>Add New Teacher</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <label>Username <input type="text" name="username" required></label>
                <label>Email <input type="email" name="email" required></label>
                <label>Password <input type="password" name="password" required></label>
                <button type="submit" class="btn-green">Create Teacher</button>
            </form>
        </div>

        <!-- Teacher List -->
        <div class="card">
            <h2>All Teachers</h2>
            <?php if (empty($teachers)): ?>
                <p>No teachers found.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Details (Edit)</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                    <label>Username <input type="text" name="username"
                                            value="<?= htmlspecialchars($t['username']) ?>"></label>
                                    <label>Email <input type="email" name="email"
                                            value="<?= htmlspecialchars($t['email']) ?>"></label>
                                    <label>New Password (leave blank to keep unchanged)
                                        <input type="password" name="new_password" placeholder="Enter new password">
                                    </label>
                                    <small>Created: <?= date("M d, Y", strtotime($t['created_at'])) ?></small><br>
                                    <button type="submit" class="btn-blue">Update</button>
                                </form>
                            </td>
                            <td>
                                <button type="button" class="btn-red" onclick="confirmDelete(<?= $t['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
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