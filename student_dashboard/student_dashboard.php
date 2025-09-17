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

// Get student's grade
$stmt = $pdo->prepare("SELECT grade FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student_data ? $student_data['grade'] : null;

// Handle grade selection BEFORE including header
if (($_POST['action'] ?? '') === 'set_grade') {
    $selected_grade = $_POST['selected_grade'] ?? null;
    
    if ($selected_grade && is_numeric($selected_grade) && $selected_grade >= 1 && $selected_grade <= 12) {
        // Update student's grade
        $stmt = $pdo->prepare("UPDATE users SET grade = ? WHERE id = ?");
        if ($stmt->execute([$selected_grade, $student_id])) {
            // Update the local variable
            $student_grade = $selected_grade;
            $success_message = "Grade successfully set to Grade " . $student_grade . "!";
        } else {
            $error_message = "Failed to set grade. Please try again.";
        }
    } else {
        $error_message = "Please select a valid grade (1-12).";
    }
}

// Handle subject enrollment BEFORE including header
if (($_POST['action'] ?? '') === 'enroll_subject') {
    $subject_id = $_POST['subject_id'] ?? null;
    
    // First check if student has a grade set
    if (!$student_grade) {
        $error_message = "Please set your grade level first before enrolling in subjects.";
    } elseif ($subject_id && is_numeric($subject_id)) {
        // Check if subject exists and is applicable to student's grade
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND JSON_CONTAINS(applicable_grades, ?) AND is_active = 1");
        $stmt->execute([$subject_id, json_encode((int)$student_grade)]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subject) {
            // Check if already enrolled in this subject
            $stmt = $pdo->prepare("SELECT id FROM subject_enrollments WHERE student_id = ? AND subject_id = ? AND status = 'active'");
            $stmt->execute([$student_id, $subject_id]);
            
            if ($stmt->fetch()) {
                $error_message = "You are already enrolled in this subject.";
            } else {
                // Enroll the student in the subject
                $stmt = $pdo->prepare("INSERT INTO subject_enrollments (student_id, subject_id, grade_at_enrollment, status, enrollment_date) VALUES (?, ?, ?, 'active', NOW())");
                if ($stmt->execute([$student_id, $subject_id, $student_grade])) {
                    $success_message = "Successfully enrolled in " . htmlspecialchars($subject['subject_name']) . "!";
                } else {
                    $error_message = "Failed to enroll in subject. Please try again.";
                }
            }
        } else {
            $error_message = "Subject not found, inactive, or not available for your grade.";
        }
    } else {
        $error_message = "Invalid subject selected.";
    }
}

// Handle subject unenrollment BEFORE including header
if (($_POST['action'] ?? '') === 'unenroll_subject') {
    $subject_id = $_POST['subject_id'] ?? null;
    
    if ($subject_id && is_numeric($subject_id)) {
        // Get subject name for success message
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
    } else {
        $error_message = "Invalid subject selected.";
    }
}

// NOW include the header after all potential redirects
include '../includes/student_header.php';

// Re-fetch student grade in case it was updated
$stmt = $pdo->prepare("SELECT grade FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student_data ? $student_data['grade'] : null;

// Get all available grades for selection
$stmt = $pdo->prepare("SELECT * FROM grades ORDER BY grade_number ASC");
$stmt->execute();
$available_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enrolled subjects
$enrolled_subjects = [];
if ($student_grade) {
    $stmt = $pdo->prepare("SELECT s.*, se.enrollment_date, se.grade_at_enrollment
                           FROM subjects s
                           JOIN subject_enrollments se ON s.id = se.subject_id
                           WHERE se.student_id = ? AND se.status = 'active' AND s.is_active = 1
                           ORDER BY s.category, s.subject_name");
    $stmt->execute([$student_id]);
    $enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get available subjects for student's grade (not already enrolled)
$available_subjects = [];
if ($student_grade) {
    $stmt = $pdo->prepare("SELECT s.*
                          FROM subjects s 
                          WHERE JSON_CONTAINS(s.applicable_grades, ?) AND s.is_active = 1
                          AND s.id NOT IN (
                              SELECT subject_id FROM subject_enrollments 
                              WHERE student_id = ? AND status = 'active'
                          )
                          ORDER BY s.category, s.subject_name");
    $stmt->execute([json_encode((int)$student_grade), $student_id]);
    $available_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle success messages from redirects
if (isset($_GET['grade_set']) && $_GET['grade_set'] == '1') {
    $success_message = "Grade successfully set to Grade " . $student_grade . "!";
}

if (isset($_GET['enrolled']) && $_GET['enrolled'] == '1' && isset($_GET['subject'])) {
    $success_message = "Successfully enrolled in " . htmlspecialchars($_GET['subject']) . "!";
}

if (isset($_GET['unenrolled']) && $_GET['unenrolled'] == '1') {
    $success_message = "Successfully unenrolled from subject.";
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">My Subjects</h1>
        <p class="text-muted mb-0">
            <?php if ($student_grade): ?>
                Manage your Grade <?php echo $student_grade; ?> subjects
                <br>
                <button class="btn btn-link btn-sm p-0 text-decoration-none" onclick="openGradeSelection()">
                    <i class="fas fa-edit"></i> Change Grade Level
                </button>
            <?php else: ?>
                <button class="btn btn-primary btn-sm" onclick="openGradeSelection()">
                    <i class="fas fa-graduation-cap"></i> Set Your Grade
                </button>
                to see available subjects
            <?php endif; ?>
        </p>
    </div>
    <?php if ($student_grade): ?>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="openGradeSelection()" title="Change Grade Level">
            <i class="fas fa-graduation-cap"></i> Grade <?php echo $student_grade; ?>
        </button>
    </div>
    <?php endif; ?>
</div>

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
    <div class="card-body text-center py-5">
        <i class="fas fa-graduation-cap text-primary mb-3" style="font-size: 4rem; opacity: 0.5;"></i>
        <h4>Welcome to EDUMATE Subjects</h4>
        <p class="text-muted mb-4">To access subjects tailored for your grade level, please set your grade first.</p>
        <button class="btn btn-primary btn-lg" onclick="openGradeSelection()">
            <i class="fas fa-graduation-cap"></i> Set My Grade
        </button>
    </div>
</div>
<?php else: ?>

<!-- Enrolled Subjects Section -->
<div class="content-card mb-4">
    <div class="card-header-custom">
        <i class="fas fa-book-open me-2"></i>My Enrolled Subjects (<?php echo count($enrolled_subjects); ?>)
    </div>
    <div class="card-body">
        <?php if (empty($enrolled_subjects)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5>No Enrolled Subjects</h5>
                <p class="text-muted mb-4">You haven't enrolled in any subjects yet. Browse available subjects below to start your learning journey!</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($enrolled_subjects as $subject): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <!-- Subject Header -->
                        <div class="card-header bg-gradient" style="background: linear-gradient(135deg, 
                            <?php 
                            if ($subject['category'] === 'core') echo 'var(--success-color), var(--success-light)';
                            elseif ($subject['category'] === 'elective') echo 'var(--primary-color), var(--primary-light)';
                            else echo 'var(--warning-color), var(--warning-light)';
                            ?>);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <small class="text-white-50">Grade <?php echo $subject['grade_at_enrollment']; ?></small>
                                </div>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Subject Body -->
                        <div class="card-body">
                            <p class="card-text text-muted small mb-3">
                                <?php echo htmlspecialchars(substr($subject['description'] ?? 'No description available.', 0, 120)); ?>
                                <?php if (strlen($subject['description'] ?? '') > 120): ?>...<?php endif; ?>
                            </p>
                            
                            <!-- Subject Info -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="small text-muted">Category</div>
                                    <div class="fw-bold text-capitalize">
                                        <?php echo htmlspecialchars($subject['category']); ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Status</div>
                                    <div class="fw-bold text-success">Enrolled</div>
                                </div>
                            </div>
                            
                            <!-- Enrollment Date -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-calendar text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small fw-medium">
                                        Enrolled: <?php echo date('M j, Y', strtotime($subject['enrollment_date'])); ?>
                                    </div>
                                    <div class="small text-muted">
                                        Active enrollment
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subject Footer -->
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm flex-grow-1" disabled>
                                    <i class="fas fa-book"></i> Subject Details
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmUnenrollSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
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
<div class="content-card mb-4">
    <div class="card-header-custom">
        <i class="fas fa-search me-2"></i>Available Subjects for Grade <?php echo $student_grade; ?> (<?php echo count($available_subjects); ?>)
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            Discover new subjects available for your grade level. These subjects are aligned with the South African curriculum.
        </p>
        
        <div class="row">
            <?php 
            // Group subjects by category
            $core_subjects = array_filter($available_subjects, function($s) { return $s['category'] === 'core'; });
            $elective_subjects = array_filter($available_subjects, function($s) { return $s['category'] === 'elective'; });
            $practical_subjects = array_filter($available_subjects, function($s) { return $s['category'] === 'practical'; });
            ?>
            
            <?php if (!empty($core_subjects)): ?>
            <div class="col-md-6 mb-4">
                <h5 class="text-success mb-3"><i class="fas fa-star me-2"></i>Core Subjects</h5>
                <div class="row">
                    <?php foreach ($core_subjects as $subject): ?>
                    <div class="col-12 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                <p class="card-text small text-muted"><?php echo htmlspecialchars($subject['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-success"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                    <span class="badge bg-secondary">Core Subject</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="enroll_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="fas fa-plus"></i> Enroll in Subject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($elective_subjects)): ?>
            <div class="col-md-6 mb-4">
                <h5 class="text-primary mb-3"><i class="fas fa-list me-2"></i>Elective Subjects</h5>
                <div class="row">
                    <?php foreach ($elective_subjects as $subject): ?>
                    <div class="col-12 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                <p class="card-text small text-muted"><?php echo htmlspecialchars($subject['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                    <span class="badge bg-secondary">Elective</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="enroll_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-plus"></i> Enroll in Subject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($practical_subjects)): ?>
            <div class="col-md-6 mb-4">
                <h5 class="text-warning mb-3"><i class="fas fa-flask me-2"></i>Practical Subjects</h5>
                <div class="row">
                    <?php foreach ($practical_subjects as $subject): ?>
                    <div class="col-12 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                <p class="card-text small text-muted"><?php echo htmlspecialchars($subject['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-warning"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                    <span class="badge bg-secondary">Practical</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="enroll_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm w-100">
                                        <i class="fas fa-plus"></i> Enroll in Subject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="content-card mb-4">
        <div class="card-header-custom">
            <i class="fas fa-search me-2"></i>Available Subjects
        </div>
        <div class="card-body text-center py-5">
            <i class="fas fa-book-open text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
            <h5>No Available Subjects</h5>
            <p class="text-muted mb-4">
                There are currently no available subjects for Grade <?php echo $student_grade; ?>. 
                Please check back later or contact your administrators.
            </p>
        </div>
    </div>
<?php endif; ?>

<?php endif; ?>

<!-- Grade Selection Modal -->
<div class="modal fade" id="gradeSelectionModal" tabindex="-1" aria-labelledby="gradeSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="gradeSelectionModalLabel">
                    <i class="fas fa-graduation-cap"></i> Select Your Grade Level
                </h5>
                <?php if ($student_grade): ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                <?php endif; ?>
            </div>
            <form method="POST" id="gradeSelectionForm">
                <input type="hidden" name="action" value="set_grade">
                
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3">Choose Your Current Grade</h6>
                            <p class="text-muted mb-4">
                                Select your current grade level to access subjects tailored for you. 
                                This helps us show you the most relevant learning materials aligned with the South African curriculum.
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-school text-primary mb-2" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <!-- Primary School (Grades 1-7) -->
                    <div class="mb-4">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-child me-2"></i>Primary School (Foundation & Intermediate Phase)
                        </h6>
                        <div class="row">
                            <?php foreach ($available_grades as $grade): ?>
                                <?php if ($grade['grade_number'] >= 1 && $grade['grade_number'] <= 7): ?>
                                <div class="col-lg-3 col-md-4 col-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input grade-radio" type="radio" name="selected_grade" 
                                               value="<?php echo $grade['grade_number']; ?>" 
                                               id="grade<?php echo $grade['grade_number']; ?>"
                                               <?php echo ($student_grade == $grade['grade_number']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100" for="grade<?php echo $grade['grade_number']; ?>">
                                            <div class="card grade-card h-100 <?php echo ($student_grade == $grade['grade_number']) ? 'border-success bg-success bg-opacity-10' : ''; ?>">
                                                <div class="card-body text-center py-3">
                                                    <div class="fs-4 text-success mb-2">
                                                        <i class="fas fa-seedling"></i>
                                                    </div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($grade['grade_name']); ?></div>
                                                    <div class="small text-muted">
                                                        <?php echo ($grade['grade_number'] <= 3) ? 'Foundation' : 'Intermediate'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- High School (Grades 8-12) -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-user-graduate me-2"></i>High School (Senior Phase & FET Phase)
                        </h6>
                        <div class="row">
                            <?php foreach ($available_grades as $grade): ?>
                                <?php if ($grade['grade_number'] >= 8 && $grade['grade_number'] <= 12): ?>
                                <div class="col-lg-3 col-md-4 col-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input grade-radio" type="radio" name="selected_grade" 
                                               value="<?php echo $grade['grade_number']; ?>" 
                                               id="grade<?php echo $grade['grade_number']; ?>"
                                               <?php echo ($student_grade == $grade['grade_number']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100" for="grade<?php echo $grade['grade_number']; ?>">
                                            <div class="card grade-card h-100 <?php echo ($student_grade == $grade['grade_number']) ? 'border-primary bg-primary bg-opacity-10' : ''; ?>">
                                                <div class="card-body text-center py-3">
                                                    <div class="fs-4 text-primary mb-2">
                                                        <?php if ($grade['grade_number'] <= 9): ?>
                                                            <i class="fas fa-book"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-graduation-cap"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($grade['grade_name']); ?></div>
                                                    <div class="small text-muted">
                                                        <?php echo ($grade['grade_number'] <= 9) ? 'Senior Phase' : 'FET Phase'; ?>
                                                    </div>
                                                    <?php if ($grade['grade_number'] == 12): ?>
                                                        <div class="small text-warning mt-1">
                                                            <i class="fas fa-star"></i> Matric
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php if ($student_grade): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Current Grade:</strong> Grade <?php echo $student_grade; ?>
                        <br><small>You can change your grade selection at any time, but this will affect which subjects are available to you.</small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer">
                    <?php if ($student_grade): ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary" id="setGradeBtn" disabled>
                        <i class="fas fa-check"></i> 
                        <?php echo $student_grade ? 'Update Grade' : 'Set My Grade'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unenrollment Form (Hidden) -->
<form id="unenrollSubjectForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="unenroll_subject">
    <input type="hidden" name="subject_id" id="unenroll_subject_id">
</form>

<script>
// Grade selection functionality
document.addEventListener('DOMContentLoaded', function() {
    const gradeRadios = document.querySelectorAll('.grade-radio');
    const setGradeBtn = document.getElementById('setGradeBtn');
    const gradeCards = document.querySelectorAll('.grade-card');
    
    // Enable submit button when a grade is selected
    gradeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            setGradeBtn.disabled = false;
            
            // Update card styling
            gradeCards.forEach(card => {
                card.classList.remove('border-success', 'bg-success', 'bg-opacity-10', 'border-primary', 'bg-primary');
            });
            
            const selectedCard = this.closest('.form-check').querySelector('.grade-card');
            if (this.value <= 7) {
                selectedCard.classList.add('border-success', 'bg-success', 'bg-opacity-10');
            } else {
                selectedCard.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            }
        });
    });
    
    // Show modal automatically if no grade is set
    <?php if (!$student_grade): ?>
    const gradeModal = new bootstrap.Modal(document.getElementById('gradeSelectionModal'), {
        backdrop: 'static',
        keyboard: false
    });
    gradeModal.show();
    <?php endif; ?>
});

function openGradeSelection() {
    const gradeModal = new bootstrap.Modal(document.getElementById('gradeSelectionModal'));
    gradeModal.show();
}

function confirmUnenrollSubject(subjectId, subjectName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Unenroll from Subject?',
            text: `Are you sure you want to unenroll from "${subjectName}"? You will lose access to all materials in this subject.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Unenroll',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('unenroll_subject_id').value = subjectId;
                document.getElementById('unenrollSubjectForm').submit();
            }
        });
    } else {
        // Fallback for when SweetAlert is not available
        if (confirm(`Are you sure you want to unenroll from "${subjectName}"? You will lose access to all materials in this subject.`)) {
            document.getElementById('unenroll_subject_id').value = subjectId;
            document.getElementById('unenrollSubjectForm').submit();
        }
    }
}

function pageInit() {
    console.log('Subjects page loaded');
    
    // Announce page load for screen readers
    if (document.body.getAttribute('data-screen-reader') === 'true') {
        setTimeout(() => {
            const enrolledCount = <?php echo count($enrolled_subjects); ?>;
            const availableCount = <?php echo count($available_subjects); ?>;
            
            <?php if (!$student_grade): ?>
            announceToScreenReader('Grade selection required. Please select your grade level to access subjects.');
            <?php else: ?>
            announceToScreenReader(`Subjects page loaded. You have ${enrolledCount} enrolled subjects and ${availableCount} available subjects.`);
            <?php endif; ?>
        }, 1000);
    }
}
</script>

<style>
/* Subject card hover effects */
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Grade selection styles */
.grade-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.grade-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.form-check-input:checked + .form-check-label .grade-card {
    transform: scale(1.02);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.form-check {
    margin: 0;
}

.form-check-label {
    cursor: pointer;
}

.form-check-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Category-based gradient colors */
:root {
    --success-color: #198754;
    --success-light: #d1e7dd;
    --primary-color: #0d6efd;
    --primary-light: #cfe2ff;
    --warning-color: #ffc107;
    --warning-light: #fff3cd;
}

/* Accessibility improvements */
.card:focus-within {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode adjustments */
.high-contrast .card {
    border: 2px solid #000 !important;
}

.high-contrast .badge {
    border: 1px solid #000 !important;
}

.high-contrast .grade-card {
    border: 2px solid #000 !important;
}

.high-contrast .form-check-input:checked + .form-check-label .grade-card {
    background-color: #ffff00 !important;
    color: #000 !important;
}

/* Reduced motion */
.reduced-motion .card {
    transition: none !important;
}

.reduced-motion .grade-card {
    transition: none !important;
}

.reduced-motion .form-check-input:checked + .form-check-label .grade-card {
    transform: none !important;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .card-title {
        font-size: 1rem;
    }
    
    .row.text-center > div {
        margin-bottom: 0.5rem;
    }
    
    .grade-card .card-body {
        padding: 0.75rem 0.5rem;
    }
    
    .grade-card .fs-4 {
        font-size: 1.25rem !important;
    }
}
</style>

<?php
// Include the footer
include '../includes/student_footer.php';
?>