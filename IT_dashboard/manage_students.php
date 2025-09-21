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

// Helper: handle file upload
function handleUpload($fieldName, $existingFile = null)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $existingFile; // keep old file if no new upload
    }
    $ext = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . "_" . time() . "." . $ext;
    $targetPath = "../uploads/" . $filename;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return "uploads/" . $filename;
    }
    return $existingFile; // fallback
}

// --- Add Student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $grade = (int) ($_POST['grade'] ?? 0);
    $accessibility = trim($_POST['accessibility_needs'] ?? '');

    if (!$username || !$email || !$password || !$grade) {
        $errors[] = "Username, email, password, and grade are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            // Handle file uploads
            $profile_pic = handleUpload('profile_picture');
            $id_copy = handleUpload('id_copy');
            $school_report = handleUpload('school_report');

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, grade, profile_picture, accessibility_needs, id_copy, school_report, created_at) 
                                   VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$username, $email, $hashed, $grade, $profile_pic, $accessibility, $id_copy, $school_report])) {
                $success = "Student created successfully.";
            } else {
                $errors[] = "Failed to create student.";
            }
        }
    }
}

// --- Edit Student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $student_id = (int) $_POST['student_id'];
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $grade = (int) ($_POST['grade'] ?? 0);
    $accessibility = trim($_POST['accessibility_needs'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (!$username || !$email || !$grade) {
        $errors[] = "Username, email, and grade are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // check duplicates
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email=? OR username=?) AND id<>?");
        $stmt->execute([$email, $username, $student_id]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already taken.";
        } else {
            // fetch old files
            $stmt = $pdo->prepare("SELECT profile_picture, id_copy, school_report FROM users WHERE id=? AND user_type='student'");
            $stmt->execute([$student_id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);

            $profile_pic = handleUpload('profile_picture', $old['profile_picture']);
            $id_copy = handleUpload('id_copy', $old['id_copy']);
            $school_report = handleUpload('school_report', $old['school_report']);

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, grade=?, accessibility_needs=?, password=?, profile_picture=?, id_copy=?, school_report=? WHERE id=? AND user_type='student'");
                $ok = $stmt->execute([$username, $email, $grade, $accessibility, $hashed, $profile_pic, $id_copy, $school_report, $student_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, grade=?, accessibility_needs=?, profile_picture=?, id_copy=?, school_report=? WHERE id=? AND user_type='student'");
                $ok = $stmt->execute([$username, $email, $grade, $accessibility, $profile_pic, $id_copy, $school_report, $student_id]);
            }

            if ($ok) {
                $success = "Student updated successfully.";
            } else {
                $errors[] = "Failed to update student.";
            }
        }
    }
}

// --- Delete Student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $student_id = (int) $_POST['student_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND user_type='student'");
    if ($stmt->execute([$student_id])) {
        $success = "Student deleted.";
    } else {
        $errors[] = "Failed to delete student.";
    }
}

// --- Fetch Students ---
$stmt = $pdo->query("SELECT id, username, email, grade, profile_picture, accessibility_needs, id_copy, school_report, created_at 
                     FROM users WHERE user_type='student' ORDER BY created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Students - EduMate</title>
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

        .card {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #1e3a8a;
        }

        label {
            display: block;
            margin-top: 0.5rem;
            font-weight: bold;
        }

        input,
        textarea,
        select {
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
        }

        th {
            background: #f1f5f9;
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
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this student?")) {
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</head>

<body>
    <div class="content">
        <h1>Manage Students</h1>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="error"><?php foreach ($errors as $e)
                echo htmlspecialchars($e) . "<br>"; ?></div><?php endif; ?>

        <!-- Add Student -->
        <div class="card">
            <h2>Add New Student</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <label>Username <input type="text" name="username" required></label>
                <label>Email <input type="email" name="email" required></label>
                <label>Password <input type="password" name="password" required></label>
                <label>Grade
                    <select name="grade" required>
                        <option value="">-- Select Grade --</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>">Grade <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Accessibility Needs <textarea name="accessibility_needs"></textarea></label>
                <label>Profile Picture <input type="file" name="profile_picture"></label>
                <label>ID Copy <input type="file" name="id_copy"></label>
                <label>School Report <input type="file" name="school_report"></label>
                <button type="submit" class="btn-green">Create Student</button>
            </form>
        </div>

        <!-- Student List -->
        <div class="card">
            <h2>All Students</h2>
            <?php if (empty($students)): ?>
                <p>No students found.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Profile</th>
                        <th>Details</th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td>
                                <?php if ($s['profile_picture']): ?>
                                    <img src="../<?= $s['profile_picture'] ?>" class="thumb">
                                <?php else: ?>
                                    <span>No Picture</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                    <label>Username <input type="text" name="username"
                                            value="<?= htmlspecialchars($s['username']) ?>"></label>
                                    <label>Email <input type="email" name="email"
                                            value="<?= htmlspecialchars($s['email']) ?>"></label>
                                    <label>Grade
                                        <select name="grade">
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?= $i ?>" <?= ($s['grade'] == $i ? 'selected' : '') ?>>Grade <?= $i ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </label>
                                    <label>Accessibility Needs <textarea
                                            name="accessibility_needs"><?= htmlspecialchars($s['accessibility_needs']) ?></textarea></label>
                                    <label>New Password (leave blank to keep) <input type="password"
                                            name="new_password"></label>
                                    <label>Profile Picture <input type="file" name="profile_picture"></label>
                                    <label>ID Copy <input type="file" name="id_copy"></label>
                                    <label>School Report <input type="file" name="school_report"></label>
                                    <small>Created: <?= date("M d, Y", strtotime($s['created_at'])) ?></small><br>
                                    <button type="submit" class="btn-blue">Update</button>
                                </form>
                            </td>
                            <td>
                                <?php if ($s['id_copy']): ?><a href="../<?= $s['id_copy'] ?>" target="_blank">ID
                                        Copy</a><br><?php endif; ?>
                                <?php if ($s['school_report']): ?><a href="../<?= $s['school_report'] ?>" target="_blank">School
                                        Report</a><?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn-red" onclick="confirmDelete(<?= $s['id'] ?>)">Delete</button>
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
        <input type="hidden" name="student_id" id="delete-id">
    </form>
</body>

</html>
<?php include '../includes/admin_footer.php'; ?>