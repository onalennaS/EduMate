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
function handleUpload($fieldName, $existingFile = null, $uploadDir = 'uploads/')
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $existingFile; // keep old file if no new upload
    }
    
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . "_" . time() . "." . $ext;
    
    // Create specific directories based on field name
    if ($fieldName === 'profile_picture') {
        $uploadDir = 'uploads/profile_pictures/';
    } elseif ($fieldName === 'id_copy') {
        $uploadDir = 'uploads/id_copies/';
    } elseif ($fieldName === 'academic_report') {
        $uploadDir = 'uploads/academic_reports/';
    }
    
    $targetPath = "../" . $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir("../" . $uploadDir)) {
        mkdir("../" . $uploadDir, 0777, true);
    }

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return $uploadDir . $filename;
    }
    return $existingFile; // fallback
}

// Check if document columns exist
function checkColumnsExist($pdo) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'academic_report'");
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

$columns_exist = checkColumnsExist($pdo);

// --- Add Student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
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
            $id_copy = $columns_exist ? handleUpload('id_copy') : null;
            $academic_report = $columns_exist ? handleUpload('academic_report') : null;

            if ($columns_exist) {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, first_name, last_name, grade, profile_picture, accessibility_needs, id_copy, academic_report, created_at) 
                                       VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?, NOW())");
                $success = $stmt->execute([$username, $email, $hashed, $first_name, $last_name, $grade, $profile_pic, $accessibility, $id_copy, $academic_report]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, first_name, last_name, grade, profile_picture, accessibility_needs, created_at) 
                                       VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, NOW())");
                $success = $stmt->execute([$username, $email, $hashed, $first_name, $last_name, $grade, $profile_pic, $accessibility]);
            }
            
            if ($success) {
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
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
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
            if ($columns_exist) {
                $stmt = $pdo->prepare("SELECT profile_picture, id_copy, academic_report FROM users WHERE id=? AND user_type='student'");
            } else {
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id=? AND user_type='student'");
            }
            $stmt->execute([$student_id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);

            $profile_pic = handleUpload('profile_picture', $old['profile_picture'] ?? null);
            $id_copy = $columns_exist ? handleUpload('id_copy', $old['id_copy'] ?? null) : null;
            $academic_report = $columns_exist ? handleUpload('academic_report', $old['academic_report'] ?? null) : null;

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                if ($columns_exist) {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, grade=?, accessibility_needs=?, password=?, profile_picture=?, id_copy=?, academic_report=? WHERE id=? AND user_type='student'");
                    $ok = $stmt->execute([$username, $email, $first_name, $last_name, $grade, $accessibility, $hashed, $profile_pic, $id_copy, $academic_report, $student_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, grade=?, accessibility_needs=?, password=?, profile_picture=? WHERE id=? AND user_type='student'");
                    $ok = $stmt->execute([$username, $email, $first_name, $last_name, $grade, $accessibility, $hashed, $profile_pic, $student_id]);
                }
            } else {
                if ($columns_exist) {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, grade=?, accessibility_needs=?, profile_picture=?, id_copy=?, academic_report=? WHERE id=? AND user_type='student'");
                    $ok = $stmt->execute([$username, $email, $first_name, $last_name, $grade, $accessibility, $profile_pic, $id_copy, $academic_report, $student_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, grade=?, accessibility_needs=?, profile_picture=? WHERE id=? AND user_type='student'");
                    $ok = $stmt->execute([$username, $email, $first_name, $last_name, $grade, $accessibility, $profile_pic, $student_id]);
                }
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
if ($columns_exist) {
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, grade, profile_picture, accessibility_needs, id_copy, academic_report, created_at 
                         FROM users WHERE user_type='student' ORDER BY created_at DESC");
} else {
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, grade, profile_picture, accessibility_needs, created_at 
                         FROM users WHERE user_type='student' ORDER BY created_at DESC");
}
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

        .btn-view {
            background: #6366f1;
            color: white;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 2px;
        }

        .btn-view:hover {
            background: #4f46e5;
            text-decoration: none;
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

        .document-status {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-uploaded {
            background: #10b981;
        }

        .status-missing {
            background: #ef4444;
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

        .student-details {
            font-size: 0.9rem;
        }

        .student-name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .student-info {
            color: #6b7280;
            margin-bottom: 0.25rem;
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
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this student? This action cannot be undone.")) {
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-form').submit();
            }
        }

        function toggleStudentForm(id) {
            const form = document.getElementById('student-form-' + id);
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
            <h1>Manage Students</h1>

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

            <!-- Add Student -->
            <div class="card">
                <h2>Add New Student</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div>
                            <label>First Name <input type="text" name="first_name" required></label>
                        </div>
                        <div>
                            <label>Last Name <input type="text" name="last_name" required></label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label>Username <input type="text" name="username" required></label>
                        </div>
                        <div>
                            <label>Email <input type="email" name="email" required></label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label>Password <input type="password" name="password" required></label>
                        </div>
                        <div>
                            <label>Grade
                                <select name="grade" required>
                                    <option value="">-- Select Grade --</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>">Grade <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-full">
                        <label>Accessibility Needs <textarea name="accessibility_needs" rows="2"></textarea></label>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label>Profile Picture <input type="file" name="profile_picture" accept="image/*"></label>
                        </div>
                        <?php if ($columns_exist): ?>
                        <div>
                            <label>ID Copy <input type="file" name="id_copy" accept=".pdf,.jpg,.jpeg,.png,.gif"></label>
                        </div>
                    </div>
                    
                    <div class="form-full">
                        <label>Academic Report <input type="file" name="academic_report" accept=".pdf,.jpg,.jpeg,.png,.gif"></label>
                    </div>
                        <?php else: ?>
                        <div>
                            <small style="color: #6b7280;">Document upload features will be available after database update.</small>
                        </div>
                    </div>
                        <?php endif; ?>
                    
                    <button type="submit" class="btn-green">Create Student</button>
                </form>
            </div>

            <!-- Student List -->
            <div class="card">
                <h2>All Students (<?= count($students) ?>)</h2>
                <?php if (empty($students)): ?>
                    <p>No students found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profile</th>
                                <th>Student Details</th>
                                <?php if ($columns_exist): ?>
                                <th>Documents</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= $s['id'] ?></td>
                                    <td>
                                        <?php if ($s['profile_picture']): ?>
                                            <img src="../<?= $s['profile_picture'] ?>" class="thumb" alt="Profile">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #6b7280;">
                                                <?= strtoupper(substr($s['username'], 0, 2)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="student-details">
                                            <div class="student-name">
                                                <?= htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: $s['username']) ?>
                                            </div>
                                            <div class="student-info">
                                                <strong>Username:</strong> <?= htmlspecialchars($s['username']) ?><br>
                                                <strong>Email:</strong> <?= htmlspecialchars($s['email']) ?><br>
                                                <strong>Grade:</strong> <?= $s['grade'] ?><br>
                                                <strong>Created:</strong> <?= date("M d, Y", strtotime($s['created_at'])) ?>
                                            </div>
                                            <?php if (!empty($s['accessibility_needs'])): ?>
                                                <div class="student-info">
                                                    <strong>Accessibility:</strong> <?= htmlspecialchars($s['accessibility_needs']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php if ($columns_exist): ?>
                                    <td>
                                        <div class="document-status">
                                            <div class="document-item">
                                                <div class="status-indicator <?= !empty($s['id_copy']) ? 'status-uploaded' : 'status-missing' ?>"></div>
                                                <span>ID Copy</span>
                                                <?php if (!empty($s['id_copy'])): ?>
                                                    <a href="../<?= $s['id_copy'] ?>" target="_blank" class="btn-view">View</a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="document-item">
                                                <div class="status-indicator <?= !empty($s['academic_report']) ? 'status-uploaded' : 'status-missing' ?>"></div>
                                                <span>Academic Report</span>
                                                <?php if (!empty($s['academic_report'])): ?>
                                                    <a href="../<?= $s['academic_report'] ?>" target="_blank" class="btn-view">View</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <button type="button" id="toggle-btn-<?= $s['id'] ?>" class="btn-blue" onclick="toggleStudentForm(<?= $s['id'] ?>)">Edit</button>
                                        <button type="button" class="btn-red" onclick="confirmDelete(<?= $s['id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="<?= $columns_exist ? '5' : '4' ?>">
                                        <div id="student-form-<?= $s['id'] ?>" style="display: none; padding: 1rem; background: #f9fafb; border-radius: 6px; margin: 0.5rem 0;">
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                
                                                <h4>Edit Student: <?= htmlspecialchars($s['username']) ?></h4>
                                                
                                                <div class="form-row">
                                                    <div>
                                                        <label>First Name <input type="text" name="first_name" value="<?= htmlspecialchars($s['first_name'] ?? '') ?>"></label>
                                                    </div>
                                                    <div>
                                                        <label>Last Name <input type="text" name="last_name" value="<?= htmlspecialchars($s['last_name'] ?? '') ?>"></label>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-row">
                                                    <div>
                                                        <label>Username <input type="text" name="username" value="<?= htmlspecialchars($s['username']) ?>"></label>
                                                    </div>
                                                    <div>
                                                        <label>Email <input type="email" name="email" value="<?= htmlspecialchars($s['email']) ?>"></label>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-row">
                                                    <div>
                                                        <label>Grade
                                                            <select name="grade">
                                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                                    <option value="<?= $i ?>" <?= ($s['grade'] == $i ? 'selected' : '') ?>>Grade <?= $i ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </label>
                                                    </div>
                                                    <div>
                                                        <label>New Password (leave blank to keep) <input type="password" name="new_password"></label>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-full">
                                                    <label>Accessibility Needs <textarea name="accessibility_needs" rows="2"><?= htmlspecialchars($s['accessibility_needs']) ?></textarea></label>
                                                </div>
                                                
                                                <div class="form-row">
                                                    <div>
                                                        <label>Profile Picture <input type="file" name="profile_picture" accept="image/*"></label>
                                                        <?php if ($s['profile_picture']): ?>
                                                            <small>Current: <a href="../<?= $s['profile_picture'] ?>" target="_blank">View Current</a></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($columns_exist): ?>
                                                    <div>
                                                        <label>ID Copy <input type="file" name="id_copy" accept=".pdf,.jpg,.jpeg,.png,.gif"></label>
                                                        <?php if (!empty($s['id_copy'])): ?>
                                                            <small>Current: <a href="../<?= $s['id_copy'] ?>" target="_blank">View Current</a></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-full">
                                                    <label>Academic Report <input type="file" name="academic_report" accept=".pdf,.jpg,.jpeg,.png,.gif"></label>
                                                    <?php if (!empty($s['academic_report'])): ?>
                                                        <small>Current: <a href="../<?= $s['academic_report'] ?>" target="_blank">View Current</a></small>
                                                    <?php endif; ?>
                                                </div>
                                                    <?php else: ?>
                                                    <div>
                                                        <small style="color: #6b7280;">Document upload features not available.</small>
                                                    </div>
                                                </div>
                                                    <?php endif; ?>
                                                
                                                <button type="submit" class="btn-green">Update Student</button>
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
        <input type="hidden" name="student_id" id="delete-id">
    </form>
</body>

</html>
<?php include '../includes/admin_footer.php'; ?>