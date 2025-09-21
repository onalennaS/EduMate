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
        foreach ($students as $sid) {
            foreach ($subjects as $subid) {
                $stmt->execute([$sid, $subid]);
            }
        }
        $success = "Selected students enrolled successfully.";
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
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE user_type='student' AND grade=? ORDER BY username");
    $stmt->execute([$selected_grade]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Current Enrollments
    $stmt = $pdo->prepare("
        SELECT u.id AS student_id, u.username, u.email, s.id AS subject_id, s.subject_name
        FROM subject_enrollments se
        JOIN users u ON se.student_id = u.id
        JOIN subjects s ON se.subject_id = s.id
        WHERE u.user_type='student' AND u.grade = ?
        ORDER BY u.username, s.subject_name
    ");
    $stmt->execute([$selected_grade]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $current_enrollments[$row['student_id']]['name'] = $row['username'];
        $current_enrollments[$row['student_id']]['email'] = $row['email'];
        $current_enrollments[$row['student_id']]['subjects'][] = [
            'id' => $row['subject_id'],
            'name' => $row['subject_name']
        ];
    }
}

include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Enroll Students - EduMate</title>
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
            margin-bottom: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin: 0.5rem 0;
        }

        select,
        input[type=checkbox] {
            margin: 0.25rem 0;
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
</head>

<body>
    <div class="content">
        <h1>Enroll Students</h1>
        <p>Please select grade, subjects, and students.</p>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="error"><?php foreach ($errors as $e)
                echo htmlspecialchars($e) . "<br>"; ?></div><?php endif; ?>

        <!-- Select Grade -->
        <div class="card">
            <h2>Select Grade</h2>
            <form method="GET">
                <label>Grade:
                    <select name="grade_id" onchange="this.form.submit()">
                        <option value="">-- Select Grade --</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['grade_number'] ?>" <?= ($selected_grade == $g['grade_number']) ? 'selected' : '' ?>>
                                Grade <?= $g['grade_number'] ?> (<?= htmlspecialchars($g['grade_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </div>

        <?php if ($selected_grade): ?>
            <!-- Enroll Students -->
            <div class="card">
                <h2>Enroll Students into Subjects</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="grade_id" value="<?= $selected_grade ?>">

                    <h3>Subjects</h3>
                    <?php foreach ($subjects as $s): ?>
                        <label><input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['subject_name']) ?></label>
                    <?php endforeach; ?>

                    <h3>Students in Grade <?= $selected_grade ?></h3>
                    <?php foreach ($students as $st): ?>
                        <label><input type="checkbox" name="students[]" value="<?= $st['id'] ?>">
                            <?= htmlspecialchars($st['username']) ?> (<?= htmlspecialchars($st['email']) ?>)</label>
                    <?php endforeach; ?>

                    <button type="submit" class="btn-green">Enroll Selected Students</button>
                </form>
            </div>

            <!-- Current Enrollments -->
            <div class="card">
                <h2>Current Enrollments (Grade <?= $selected_grade ?>)</h2>
                <?php if (empty($current_enrollments)): ?>
                    <p>No students are enrolled in subjects yet.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Enrolled Subjects</th>
                        </tr>
                        <?php foreach ($current_enrollments as $id => $data): ?>
                            <tr>
                                <td><?= htmlspecialchars($data['name']) ?></td>
                                <td><?= htmlspecialchars($data['email']) ?></td>
                                <td>
                                    <?php foreach ($data['subjects'] as $sub): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="unenroll">
                                            <input type="hidden" name="student_id" value="<?= $id ?>">
                                            <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                                            <?= htmlspecialchars($sub['name']) ?>
                                            <button type="submit" class="btn-red"
                                                style="padding:0.2rem 0.5rem;font-size:0.8rem;">Remove</button><br>
                                        </form>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>

<?php include '../includes/admin_footer.php'; ?>