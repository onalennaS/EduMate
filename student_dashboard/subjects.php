<?php
// student_dashboard/subjects.php
session_start();

// Include database connection
require_once '../config/database.php';

// Check if user is logged in and get student_id
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student's information including grade
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student_data ? $student_data['grade'] : null;

// Handle grade selection
if (($_POST['action'] ?? '') === 'set_grade') {
    $selected_grade = $_POST['selected_grade'] ?? null;
    
    if ($selected_grade && is_numeric($selected_grade) && $selected_grade >= 1 && $selected_grade <= 12) {
        $stmt = $pdo->prepare("UPDATE users SET grade = ? WHERE id = ?");
        if ($stmt->execute([$selected_grade, $student_id])) {
            $student_grade = $selected_grade;
            $success_message = "Grade successfully updated to Grade " . $student_grade . "!";
        } else {
            $error_message = "Failed to update grade. Please try again.";
        }
    } else {
        $error_message = "Please select a valid grade (1-12).";
    }
}

// Handle subject enrollment
if (($_POST['action'] ?? '') === 'enroll_subject') {
    $subject_id = $_POST['subject_id'] ?? null;
    
    if (!$student_grade) {
        $error_message = "Please set your grade level first before enrolling in subjects.";
    } elseif ($subject_id && is_numeric($subject_id)) {
        // Check if subject exists and is applicable to student's grade
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND JSON_CONTAINS(applicable_grades, ?) AND is_active = 1");
        $stmt->execute([$subject_id, json_encode((int)$student_grade)]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subject) {
            // Check if already enrolled
            $stmt = $pdo->prepare("SELECT id FROM subject_enrollments WHERE student_id = ? AND subject_id = ? AND status = 'active'");
            $stmt->execute([$student_id, $subject_id]);
            
            if ($stmt->fetch()) {
                $error_message = "You are already enrolled in this subject.";
            } else {
                // Enroll the student
                $stmt = $pdo->prepare("INSERT INTO subject_enrollments (student_id, subject_id, grade_at_enrollment, status, enrollment_date) VALUES (?, ?, ?, 'active', NOW())");
                if ($stmt->execute([$student_id, $subject_id, $student_grade])) {
                    $success_message = "Successfully enrolled in " . htmlspecialchars($subject['subject_name']) . "!";
                } else {
                    $error_message = "Failed to enroll in subject. Please try again.";
                }
            }
        } else {
            $error_message = "Subject not available for your grade level.";
        }
    }
}

// Handle subject unenrollment
if (($_POST['action'] ?? '') === 'unenroll_subject') {
    $subject_id = $_POST['subject_id'] ?? null;
    
    if ($subject_id && is_numeric($subject_id)) {
        // Get subject name for confirmation
        $stmt = $pdo->prepare("SELECT s.subject_name FROM subjects s 
                              JOIN subject_enrollments se ON s.id = se.subject_id 
                              WHERE se.student_id = ? AND se.subject_id = ? AND se.status = 'active'");
        $stmt->execute([$student_id, $subject_id]);
        $subject_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subject_data) {
            $stmt = $pdo->prepare("UPDATE subject_enrollments SET status = 'dropped', dropped_date = NOW() WHERE student_id = ? AND subject_id = ? AND status = 'active'");
            if ($stmt->execute([$student_id, $subject_id])) {
                $success_message = "Successfully unenrolled from " . htmlspecialchars($subject_data['subject_name']) . ".";
            } else {
                $error_message = "Failed to unenroll from subject.";
            }
        } else {
            $error_message = "Subject enrollment not found.";
        }
    }
}

// Include header after processing forms
include '../includes/student_header.php';

// Get available grades for selection
$stmt = $pdo->prepare("SELECT * FROM grades ORDER BY grade_number ASC");
$stmt->execute();
$available_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enrolled subjects
$enrolled_subjects = [];
if ($student_grade) {
    $stmt = $pdo->prepare("
        SELECT s.*, se.enrollment_date, se.grade_at_enrollment
        FROM subjects s
        JOIN subject_enrollments se ON s.id = se.subject_id
        WHERE se.student_id = ? AND se.status = 'active' AND s.is_active = 1
        ORDER BY s.category, s.subject_name
    ");
    $stmt->execute([$student_id]);
    $enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get available subjects for enrollment
$available_subjects = [];
if ($student_grade) {
    $stmt = $pdo->prepare("
        SELECT s.*
        FROM subjects s 
        WHERE JSON_CONTAINS(s.applicable_grades, ?) AND s.is_active = 1
        AND s.id NOT IN (
            SELECT subject_id FROM subject_enrollments 
            WHERE student_id = ? AND status = 'active'
        )
        ORDER BY s.category, s.subject_name
    ");
    $stmt->execute([json_encode((int)$student_grade), $student_id]);
    $available_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subjects - EDUMATE</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #64748b;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .subject-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .subject-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .subject-header {
            position: relative;
            padding: 1.5rem;
            color: white;
            font-weight: 600;
        }

        .subject-header.core {
            background: linear-gradient(135deg, var(--success-color), #047857);
        }

        .subject-header.elective {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
        }

        .subject-header.practical {
            background: linear-gradient(135deg, var(--warning-color), #b45309);
        }

        .grade-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .grade-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .grade-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(37, 99, 235, 0.1);
        }

        .stats-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            border: none;
            color: white;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #1d4ed8, var(--primary-color));
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1rem 0;
            }
            
            .subject-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-2">My Subjects</h1>
                <p class="mb-0 opacity-75">
                    <?php if ($student_grade): ?>
                        Manage your Grade <?php echo $student_grade; ?> subjects and track your learning progress
                    <?php else: ?>
                        Set your grade level to start accessing subjects tailored for you
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if ($student_grade): ?>
                    <button class="btn btn-light" onclick="openGradeModal()">
                        <i class="fas fa-graduation-cap me-2"></i>Grade <?php echo $student_grade; ?>
                    </button>
                <?php else: ?>
                    <button class="btn btn-warning" onclick="openGradeModal()">
                        <i class="fas fa-plus me-2"></i>Set Grade
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <!-- Alert Messages -->
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!$student_grade): ?>
    <!-- Grade Selection Prompt -->
    <div class="content-card">
        <div class="empty-state">
            <i class="fas fa-graduation-cap text-primary"></i>
            <h4>Welcome to EDUMATE</h4>
            <p class="mb-4">To access subjects aligned with the South African curriculum, please select your current grade level.</p>
            <button class="btn btn-gradient btn-lg" onclick="openGradeModal()">
                <i class="fas fa-graduation-cap me-2"></i>Select My Grade
            </button>
        </div>
    </div>

    <?php else: ?>

    <!-- Enrolled Subjects Section -->
    <div class="content-card">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h4 mb-0">
                    <i class="fas fa-book-open text-primary me-2"></i>
                    My Enrolled Subjects (<?php echo count($enrolled_subjects); ?>)
                </h3>
            </div>

            <?php if (empty($enrolled_subjects)): ?>
                <div class="empty-state">
                    <i class="fas fa-book text-muted"></i>
                    <h5>No Enrolled Subjects</h5>
                    <p class="mb-4">You haven't enrolled in any subjects yet. Start your learning journey by browsing available subjects below!</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($enrolled_subjects as $subject): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="subject-card">
                            <!-- Subject Header -->
                            <div class="subject-header <?php echo $subject['category']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                        <div class="stats-badge">
                                            <?php echo htmlspecialchars($subject['subject_code']); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stats-badge">
                                            Grade <?php echo $subject['grade_at_enrollment']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subject Body -->
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    <?php 
                                    $description = $subject['description'] ?? 'No description available.';
                                    echo htmlspecialchars(strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description);
                                    ?>
                                </p>

                                <!-- Stats -->
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="small text-muted">Category</div>
                                        <div class="fw-bold text-primary text-capitalize"><?php echo $subject['category']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Status</div>
                                        <div class="fw-bold text-success">Active</div>
                                    </div>
                                </div>

                                <!-- Enrollment Info -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-calendar text-muted small"></i>
                                    </div>
                                    <div>
                                        <div class="small fw-medium">
                                            Enrolled: <?php echo date('M j, Y', strtotime($subject['enrollment_date'])); ?>
                                        </div>
                                        <div class="small text-muted text-capitalize">
                                            <?php echo $subject['category']; ?> Subject
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subject Footer -->
                            <div class="card-footer bg-transparent">
                                <div class="d-flex gap-2">
                                    <a href="subject_details.php?id=<?php echo $subject['id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="confirmUnenroll(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Available Subjects Section -->
    <?php if (!empty($available_subjects)): ?>
    <div class="content-card">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h4 mb-0">
                    <i class="fas fa-plus-circle text-success me-2"></i>
                    Available Subjects for Grade <?php echo $student_grade; ?> (<?php echo count($available_subjects); ?>)
                </h3>
            </div>

            <div class="row">
                <?php 
                // Group subjects by category
                $categories = [
                    'core' => ['name' => 'Core Subjects', 'icon' => 'fas fa-star', 'color' => 'success'],
                    'elective' => ['name' => 'Elective Subjects', 'icon' => 'fas fa-list', 'color' => 'primary'],
                    'practical' => ['name' => 'Practical Subjects', 'icon' => 'fas fa-tools', 'color' => 'warning']
                ];

                foreach ($categories as $category_key => $category_info):
                    $category_subjects = array_filter($available_subjects, function($s) use ($category_key) { 
                        return $s['category'] === $category_key; 
                    });
                    
                    if (!empty($category_subjects)): ?>
                    <div class="col-md-6 mb-4">
                        <h5 class="text-<?php echo $category_info['color']; ?> mb-3">
                            <i class="<?php echo $category_info['icon']; ?> me-2"></i>
                            <?php echo $category_info['name']; ?>
                        </h5>
                        <?php foreach ($category_subjects as $subject): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <span class="badge bg-<?php echo $category_info['color']; ?>"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                </div>
                                <p class="card-text small text-muted mb-3">
                                    <?php echo htmlspecialchars($subject['description'] ?? 'No description available.'); ?>
                                </p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="enroll_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                    <button type="submit" class="btn btn-<?php echo $category_info['color']; ?> btn-sm">
                                        <i class="fas fa-plus me-1"></i>Enroll Now
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif;
                endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<!-- Grade Selection Modal -->
<div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="gradeModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i>Select Your Grade Level
                </h5>
                <?php if ($student_grade): ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                <?php endif; ?>
            </div>
            <form method="POST" id="gradeForm">
                <input type="hidden" name="action" value="set_grade">
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">South African Education System</h6>
                        <p class="text-muted">Select your current grade to access curriculum-aligned subjects and materials.</p>
                    </div>

                    <!-- Primary School -->
                    <div class="mb-4">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-seedling me-2"></i>Primary School (Grades 1-7)
                        </h6>
                        <div class="row">
                            <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="col-lg-3 col-md-4 col-6 mb-3">
                                <div class="grade-card card text-center p-3" data-grade="<?php echo $i; ?>">
                                    <div class="text-success mb-2">
                                        <i class="fas fa-seedling"></i>
                                    </div>
                                    <div class="fw-bold">Grade <?php echo $i; ?></div>
                                    <div class="small text-muted">
                                        <?php echo $i <= 3 ? 'Foundation' : 'Intermediate'; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- High School -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-graduation-cap me-2"></i>High School (Grades 8-12)
                        </h6>
                        <div class="row">
                            <?php for ($i = 8; $i <= 12; $i++): ?>
                            <div class="col-lg-3 col-md-4 col-6 mb-3">
                                <div class="grade-card card text-center p-3" data-grade="<?php echo $i; ?>">
                                    <div class="text-primary mb-2">
                                        <i class="fas fa-<?php echo $i <= 9 ? 'book' : 'graduation-cap'; ?>"></i>
                                    </div>
                                    <div class="fw-bold">Grade <?php echo $i; ?></div>
                                    <div class="small text-muted">
                                        <?php 
                                        if ($i <= 9) echo 'Senior Phase';
                                        else echo 'FET Phase';
                                        if ($i == 12) echo ' (Matric)';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <input type="hidden" name="selected_grade" id="selectedGrade">
                </div>
                <div class="modal-footer">
                    <?php if ($student_grade): ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-gradient" id="submitGrade" disabled>
                        <i class="fas fa-check me-2"></i>
                        <?php echo $student_grade ? 'Update Grade' : 'Set My Grade'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden unenroll form -->
<form id="unenrollForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="unenroll_subject">
    <input type="hidden" name="subject_id" id="unenrollSubjectId">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grade selection functionality
    const gradeCards = document.querySelectorAll('.grade-card');
    const submitBtn = document.getElementById('submitGrade');
    const selectedGradeInput = document.getElementById('selectedGrade');

    gradeCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selection from all cards
            gradeCards.forEach(c => c.classList.remove('selected'));
            
            // Select this card
            this.classList.add('selected');
            
            // Enable submit button
            const grade = this.dataset.grade;
            selectedGradeInput.value = grade;
            submitBtn.disabled = false;
        });
    });

    // Show grade modal if no grade is set
    <?php if (!$student_grade): ?>
    const gradeModal = new bootstrap.Modal(document.getElementById('gradeModal'), {
        backdrop: 'static',
        keyboard: false
    });
    gradeModal.show();
    <?php endif; ?>

    // Highlight current grade
    <?php if ($student_grade): ?>
    const currentGradeCard = document.querySelector(`[data-grade="<?php echo $student_grade; ?>"]`);
    if (currentGradeCard) {
        currentGradeCard.classList.add('selected');
        selectedGradeInput.value = <?php echo $student_grade; ?>;
        submitBtn.disabled = false;
    }
    <?php endif; ?>
});

function openGradeModal() {
    const gradeModal = new bootstrap.Modal(document.getElementById('gradeModal'));
    gradeModal.show();
}

function confirmUnenroll(subjectId, subjectName) {
    Swal.fire({
        title: 'Confirm Unenrollment',
        text: `Are you sure you want to unenroll from "${subjectName}"? You will lose access to all materials and progress in this subject.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Unenroll',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('unenrollSubjectId').value = subjectId;
            document.getElementById('unenrollForm').submit();
        }
    });
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>

<?php
// Include footer if you have one
// include '../includes/student_footer.php';
?>