<?php
// student_dashboard/subjects.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student_data ? $student_data['grade'] : null;

$success_message = null;
$error_message = null;

// --- Handle Grade Update ---
if (($_POST['action'] ?? '') === 'set_grade') {
    $selected_grade = $_POST['selected_grade'] ?? null;
    if ($selected_grade && is_numeric($selected_grade)) {
        $stmt = $pdo->prepare("UPDATE users SET grade = ? WHERE id = ?");
        if ($stmt->execute([$selected_grade, $student_id])) {
            $student_grade = $selected_grade;
            $success_message = "Grade successfully updated!";
        }
    }
}

// --- Handle Enrollment ---
if (($_POST['action'] ?? '') === 'enroll_subject') {
    $subject_id = $_POST['subject_id'] ?? null;
    if ($subject_id && $student_grade) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND JSON_CONTAINS(applicable_grades, ?)");
        $stmt->execute([$subject_id, json_encode((int)$student_grade)]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subject) {
            $stmt = $pdo->prepare("SELECT id FROM subject_enrollments WHERE student_id = ? AND subject_id = ? AND status='active'");
            $stmt->execute([$student_id, $subject_id]);
            if ($stmt->fetch()) {
                $error_message = "Already enrolled.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO subject_enrollments (student_id, subject_id, grade_at_enrollment, status, enrollment_date) VALUES (?, ?, ?, 'active', NOW())");
                if ($stmt->execute([$student_id, $subject_id, $student_grade])) {
                    $success_message = "Enrolled in " . htmlspecialchars($subject['subject_name']);
                }
            }
        }
    }
}

// --- Handle Unenrollment ---
if (($_POST['action'] ?? '') === 'unenroll_subject') {
    $subject_id = $_POST['subject_id'] ?? null;
    if ($subject_id) {
        $stmt = $pdo->prepare("UPDATE subject_enrollments SET status='dropped', dropped_date=NOW() WHERE student_id=? AND subject_id=? AND status='active'");
        $stmt->execute([$student_id, $subject_id]);
        $success_message = "Unenrolled successfully.";
    }
}

// --- Fetch Enrolled Subjects ---
$enrolled_subjects = [];
if ($student_grade) {
    $stmt = $pdo->prepare("
        SELECT s.*, se.enrollment_date, se.grade_at_enrollment
        FROM subjects s
        JOIN subject_enrollments se ON s.id = se.subject_id
        WHERE se.student_id = ? AND se.status='active'
    ");
    $stmt->execute([$student_id]);
    $enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Fetch Available Subjects ---
$available_subjects = [];
if ($student_grade) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE JSON_CONTAINS(applicable_grades, ?) AND is_active=1");
    $stmt->execute([json_encode((int)$student_grade)]);
    $available_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Fetch Detail View ---
$selected_subject = null;
$materials = [];
$open_resources = [];
if (!empty($_GET['view_id'])) {
    $view_id = (int)$_GET['view_id'];

    $stmt = $pdo->prepare("
        SELECT s.*, se.enrollment_date, se.grade_at_enrollment
        FROM subjects s
        JOIN subject_enrollments se ON s.id = se.subject_id
        WHERE se.student_id = ? AND s.id = ? AND se.status='active'
    ");
    $stmt->execute([$student_id, $view_id]);
    $selected_subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_subject) {
        $stmt = $pdo->prepare("SELECT sm.*, u.full_name as teacher_name FROM subject_materials sm JOIN users u ON sm.teacher_id=u.id WHERE sm.subject_id=? ORDER BY sm.created_at DESC");
        $stmt->execute([$view_id]);
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM open_resources WHERE subject_id=? ORDER BY created_at DESC");
        $stmt->execute([$view_id]);
        $open_resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include '../includes/student_header.php';
?>

<div class="container py-4">
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <?php if ($selected_subject): ?>
        <!-- Subject Detail Card -->
        <div class="subject-card mb-4">
            <div class="subject-header <?= $selected_subject['category']; ?>">
                <div class="d-flex justify-content-between">
                    <h3 class="mb-0"><?= htmlspecialchars($selected_subject['subject_name']); ?></h3>
                    <span class="badge bg-light text-dark">Grade <?= $selected_subject['grade_at_enrollment']; ?></span>
                </div>
                <div class="stats-badge mt-2"><?= htmlspecialchars($selected_subject['subject_code']); ?></div>
            </div>
            <div class="card-body">
                <p class="mb-3"><?= htmlspecialchars($selected_subject['description']); ?></p>
                <div class="mb-3">
                    <i class="fas fa-calendar me-2"></i>Enrolled: <?= date("M d, Y", strtotime($selected_subject['enrollment_date'])); ?>
                </div>
            </div>
        </div>

        <!-- Teacher Materials -->
        <div class="content-card p-4 mb-4">
            <h4 class="mb-3"><i class="fas fa-book-open text-primary me-2"></i>Teacher Materials</h4>
            <?php if (empty($materials)): ?>
                <p>No materials uploaded yet.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($materials as $m): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($m['title']); ?></h5>
                                    <p class="text-muted"><?= htmlspecialchars($m['description']); ?></p>
                                    <small>By <?= htmlspecialchars($m['teacher_name']); ?> on <?= date("M d, Y", strtotime($m['created_at'])); ?></small>
                                    <div class="mt-2">
                                        <?php if ($m['file_path']): ?>
                                            <a href="../<?= $m['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">📂 Download</a>
                                        <?php endif; ?>
                                        <?php if ($m['external_link']): ?>
                                            <a href="<?= $m['external_link']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">🔗 Open Link</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Additional Resources -->
        <div class="content-card p-4 mb-4">
            <h4 class="mb-3"><i class="fas fa-globe text-success me-2"></i>Additional Resources</h4>
            <?php if (empty($open_resources)): ?>
                <p>No additional resources.</p>
            <?php else: ?>
                <?php foreach ($open_resources as $r): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5><?= htmlspecialchars($r['title']); ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($r['description']); ?></p>
                            <a href="<?= $r['resource_link']; ?>" target="_blank" class="btn btn-sm btn-outline-success">Open Resource</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="subjects.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to My Subjects</a>

    <?php else: ?>
        <!-- Normal Subjects List -->
        <div class="content-card p-4">
            <h3 class="mb-4"><i class="fas fa-book text-primary me-2"></i>My Enrolled Subjects (<?= count($enrolled_subjects) ?>)</h3>
            <div class="row">
                <?php foreach ($enrolled_subjects as $subject): ?>
                    <div class="col-md-4 mb-4">
                        <div class="subject-card">
                            <div class="subject-header <?= $subject['category']; ?>">
                                <h5><?= htmlspecialchars($subject['subject_name']); ?></h5>
                                <div class="stats-badge"><?= htmlspecialchars($subject['subject_code']); ?></div>
                            </div>
                            <div class="card-body">
                                <p><?= htmlspecialchars($subject['description'] ?? 'No description available.'); ?></p>
                                <a href="?view_id=<?= $subject['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/student_footer.php'; ?>
