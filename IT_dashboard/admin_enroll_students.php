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

// --- Handle Enrollment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    $grade_id = (int) $_POST['grade_id'];
    $subjects = $_POST['subjects'] ?? [];
    $students = $_POST['students'] ?? [];

    if (!$grade_id || empty($subjects) || empty($students)) {
        $errors[] = "Please select grade, subjects, and students.";
    } else {
        $stmt = $pdo->prepare("INSERT IGNORE INTO subject_enrollments (student_id, subject_id, enrollment_date) VALUES (?, ?, NOW())");
        $enrolled_count = 0;
        foreach ($students as $sid) {
            foreach ($subjects as $subid) {
                $stmt->execute([$sid, $subid]);
                $enrolled_count++;
            }
        }
        $success = "Successfully enrolled " . count($students) . " students in " . count($subjects) . " subjects (" . $enrolled_count . " total enrollments).";
    }
}

// --- Handle Unenroll ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unenroll') {
    $student_id = (int) $_POST['student_id'];
    $subject_id = (int) $_POST['subject_id'];

    $stmt = $pdo->prepare("DELETE FROM subject_enrollments WHERE student_id=? AND subject_id=?");
    if ($stmt->execute([$student_id, $subject_id])) {
        $success = "Student unenrolled successfully.";
    } else {
        $errors[] = "Failed to unenroll student.";
    }
}

// --- Fetch Grades ---
$grades = $pdo->query("SELECT id, grade_number, grade_name FROM grades ORDER BY grade_number")->fetchAll(PDO::FETCH_ASSOC);

$selected_grade = $_GET['grade_id'] ?? null;
$subjects = [];
$students = [];
$current_enrollments = [];

// --- If grade is selected ---
if ($selected_grade) {
    // Subjects for grade
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE FIND_IN_SET(?, REPLACE(REPLACE(applicable_grades, '[',''),']',''))");
    $stmt->execute([$selected_grade]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Students in grade
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE user_type='student' AND grade=? ORDER BY username");
    $stmt->execute([$selected_grade]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Current Enrollments
    $stmt = $pdo->prepare("
        SELECT u.id AS student_id, u.username, u.email, u.first_name, u.last_name, s.id AS subject_id, s.subject_name, se.enrollment_date
        FROM subject_enrollments se
        JOIN users u ON se.student_id = u.id
        JOIN subjects s ON se.subject_id = s.id
        WHERE u.user_type='student' AND u.grade = ?
        ORDER BY u.username, s.subject_name
    ");
    $stmt->execute([$selected_grade]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $student_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: $row['username'];
        $current_enrollments[$row['student_id']]['name'] = $student_name;
        $current_enrollments[$row['student_id']]['username'] = $row['username'];
        $current_enrollments[$row['student_id']]['email'] = $row['email'];
        $current_enrollments[$row['student_id']]['subjects'][] = [
            'id' => $row['subject_id'],
            'name' => $row['subject_name'],
            'enrollment_date' => $row['enrollment_date']
        ];
    }
}

include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Enrollment - EduMate</title>
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
            padding: 2rem;
            flex: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
        }

        .page-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .page-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            color: #1f2937;
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        select, input[type="checkbox"] {
            margin: 0.25rem 0;
        }

        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            font-size: 0.9rem;
            color: #374151;
        }

        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .checkbox-item:hover {
            background-color: #f9fafb;
        }

        .checkbox-item input[type="checkbox"] {
            margin: 0 0.75rem 0 0;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
            flex: 1;
        }

        button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .btn-select-all {
            background: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.8rem;
        }

        .btn-select-all:hover {
            background: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .student-info {
            font-size: 0.9rem;
        }

        .student-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .student-details {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .subject-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .subject-name {
            font-weight: 500;
            color: #374151;
        }

        .enrollment-date {
            font-size: 0.75rem;
            color: #6b7280;
            margin-left: 0.5rem;
        }

        .success {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #166534;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #10b981;
            font-weight: 500;
        }

        .error {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #ef4444;
            font-weight: 500;
        }

        .stats-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .form-row,
            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                flex-direction: column;
                gap: 1rem;
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
        function selectAllCheckboxes(groupName, button) {
            const checkboxes = document.querySelectorAll(`input[name="${groupName}[]"]`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            
            button.textContent = allChecked ? 'Select All' : 'Deselect All';
        }

        function confirmUnenroll(studentName, subjectName) {
            return confirm(`Are you sure you want to unenroll ${studentName} from ${subjectName}?`);
        }
    </script>
</head>

<body>
    <div class="content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Subject Enrollment Management</h1>
                <p>Manage student enrollments across subjects and grades</p>
            </div>

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

            <!-- Select Grade -->
            <div class="card">
                <h2>Step 1: Select Grade</h2>
                <form method="GET">
                    <div class="form-group">
                        <label for="grade_id">Choose Grade Level:</label>
                        <select name="grade_id" id="grade_id" onchange="this.form.submit()">
                            <option value="">-- Select Grade --</option>
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= $g['grade_number'] ?>" <?= ($selected_grade == $g['grade_number']) ? 'selected' : '' ?>>
                                    Grade <?= $g['grade_number'] ?> (<?= htmlspecialchars($g['grade_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($selected_grade): ?>
                <!-- Statistics Bar -->
                <div class="stats-bar">
                    <div class="stat-item">
                        <div class="stat-number"><?= count($subjects) ?></div>
                        <div class="stat-label">Available Subjects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count($students) ?></div>
                        <div class="stat-label">Students in Grade</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count($current_enrollments) ?></div>
                        <div class="stat-label">Students Enrolled</div>
                    </div>
                </div>

                <!-- Enroll Students -->
                <div class="card">
                    <h2>Step 2: Enroll Students in Subjects</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="enroll">
                        <input type="hidden" name="grade_id" value="<?= $selected_grade ?>">

                        <div class="form-row">
                            <div>
                                <label>Select Subjects for Grade <?= $selected_grade ?>:</label>
                                <button type="button" class="btn-select-all" onclick="selectAllCheckboxes('subjects', this)">Select All</button>
                                <div class="checkbox-grid">
                                    <?php foreach ($subjects as $s): ?>
                                        <div class="checkbox-item">
                                            <input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>" id="subject_<?= $s['id'] ?>">
                                            <label for="subject_<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <label>Select Students to Enroll:</label>
                                <button type="button" class="btn-select-all" onclick="selectAllCheckboxes('students', this)">Select All</button>
                                <div class="checkbox-grid">
                                    <?php foreach ($students as $st): ?>
                                        <?php 
                                        $student_name = trim(($st['first_name'] ?? '') . ' ' . ($st['last_name'] ?? '')) ?: $st['username'];
                                        ?>
                                        <div class="checkbox-item">
                                            <input type="checkbox" name="students[]" value="<?= $st['id'] ?>" id="student_<?= $st['id'] ?>">
                                            <label for="student_<?= $st['id'] ?>">
                                                <?= htmlspecialchars($student_name) ?>
                                                <br><small><?= htmlspecialchars($st['email']) ?></small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-success">Enroll Selected Students</button>
                    </form>
                </div>

                <!-- Current Enrollments -->
                <div class="card">
                    <h2>Current Enrollments for Grade <?= $selected_grade ?></h2>
                    <?php if (empty($current_enrollments)): ?>
                        <p style="text-align: center; color: #6b7280; padding: 2rem;">
                            No students are enrolled in subjects yet. Use the form above to enroll students.
                        </p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Contact</th>
                                    <th>Enrolled Subjects</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_enrollments as $id => $data): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-name"><?= htmlspecialchars($data['name']) ?></div>
                                                <div class="student-details">Username: <?= htmlspecialchars($data['username']) ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($data['email']) ?>" style="color: #3b82f6; text-decoration: none;">
                                                <?= htmlspecialchars($data['email']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php foreach ($data['subjects'] as $sub): ?>
                                                <div class="subject-item">
                                                    <div>
                                                        <span class="subject-name"><?= htmlspecialchars($sub['name']) ?></span>
                                                        <span class="enrollment-date">
                                                            Enrolled: <?= date('M d, Y', strtotime($sub['enrollment_date'])) ?>
                                                        </span>
                                                    </div>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirmUnenroll('<?= htmlspecialchars($data['name']) ?>', '<?= htmlspecialchars($sub['name']) ?>')">
                                                        <input type="hidden" name="action" value="unenroll">
                                                        <input type="hidden" name="student_id" value="<?= $id ?>">
                                                        <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                                                        <button type="submit" class="btn-danger">Remove</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php include '../includes/admin_footer.php'; ?>