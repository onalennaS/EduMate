<?php
// student_dashboard/courses.php
session_start();

// Include database connection
require_once '../config/database.php';

// Include the header
include '../includes/student_header.php';

$student_id = $_SESSION['user_id'];

// Get student's grade
$stmt = $pdo->prepare("SELECT grade FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_grade = $stmt->fetch(PDO::FETCH_ASSOC)['grade'];

// Handle course enrollment
if ($_POST['action'] ?? '' === 'enroll_course') {
    $course_id = $_POST['course_id'] ?? null;
    $enrollment_key = $_POST['enrollment_key'] ?? '';
    
    if ($course_id) {
        // Check if course exists and enrollment key matches (if required)
        $stmt = $pdo->prepare("SELECT c.*, s.subject_name, g.grade_name 
                               FROM courses c 
                               JOIN subjects s ON c.subject_id = s.id 
                               JOIN grades g ON c.grade_id = g.id 
                               WHERE c.id = ? AND c.is_active = 1");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            // Check if enrollment key is required and matches
            if ($course['enrollment_key'] && $course['enrollment_key'] !== $enrollment_key) {
                $error_message = "Invalid enrollment key for this course.";
            } else {
                // Check if already enrolled
                $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
                $stmt->execute([$student_id, $course_id]);
                
                if ($stmt->fetch()) {
                    $error_message = "You are already enrolled in this course.";
                } else {
                    // Enroll the student
                    $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'active')");
                    if ($stmt->execute([$student_id, $course_id])) {
                        $success_message = "Successfully enrolled in " . $course['course_name'];
                    } else {
                        $error_message = "Failed to enroll in course. Please try again.";
                    }
                }
            }
        } else {
            $error_message = "Course not found or inactive.";
        }
    }
}

// Handle course unenrollment
if ($_POST['action'] ?? '' === 'unenroll_course') {
    $course_id = $_POST['course_id'] ?? null;
    
    if ($course_id) {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'dropped' WHERE student_id = ? AND course_id = ?");
        if ($stmt->execute([$student_id, $course_id])) {
            $success_message = "Successfully unenrolled from course.";
        } else {
            $error_message = "Failed to unenroll from course.";
        }
    }
}

// Get enrolled courses
$stmt = $pdo->prepare("SELECT c.*, s.subject_name, s.subject_code, g.grade_name, u.full_name as teacher_name, u.username as teacher_username,
                       COUNT(DISTINCT a.id) as total_assignments,
                       COUNT(DISTINCT sub.id) as completed_assignments,
                       AVG(sub.grade) as avg_grade
                       FROM courses c
                       JOIN subjects s ON c.subject_id = s.id
                       JOIN grades g ON c.grade_id = g.id
                       JOIN users u ON c.teacher_id = u.id
                       JOIN enrollments e ON c.id = e.course_id
                       LEFT JOIN assignments a ON c.id = a.course_id
                       LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
                       WHERE e.student_id = ? AND e.status = 'active' AND c.is_active = 1
                       GROUP BY c.id
                       ORDER BY s.subject_name, c.course_name");
$stmt->execute([$student_id, $student_id]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available courses for enrollment (not already enrolled)
if ($student_grade) {
    $stmt = $pdo->prepare("SELECT c.*, s.subject_name, s.subject_code, g.grade_name, u.full_name as teacher_name, u.username as teacher_username,
                           COUNT(DISTINCT cm.id) as material_count
                           FROM courses c
                           JOIN subjects s ON c.subject_id = s.id
                           JOIN grades g ON c.grade_id = g.id
                           JOIN users u ON c.teacher_id = u.id
                           LEFT JOIN course_materials cm ON c.id = cm.course_id AND cm.is_active = 1
                           LEFT JOIN enrollments e ON c.id = e.course_id AND e.student_id = ?
                           WHERE g.grade_number = ? AND c.is_active = 1 AND e.id IS NULL
                           GROUP BY c.id
                           ORDER BY s.subject_name, c.course_name");
    $stmt->execute([$student_id, $student_grade]);
    $available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $available_courses = [];
}

// Function to get progress percentage
function getProgressPercentage($completed, $total) {
    if ($total == 0) return 0;
    return round(($completed / $total) * 100);
}

// Function to format grade display
function formatGradeDisplay($grade) {
    if ($grade === null) return 'N/A';
    return number_format($grade, 1) . '%';
}

function getGradeClass($grade) {
    if ($grade === null) return '';
    if ($grade >= 70) return 'text-success';
    if ($grade >= 60) return 'text-warning';
    return 'text-danger';
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">My Courses</h1>
        <p class="text-muted mb-0">
            <?php if ($student_grade): ?>
                Manage your Grade <?php echo $student_grade; ?> courses and explore new learning opportunities
            <?php else: ?>
                <a href="student_dashboard.php" class="text-decoration-none">Set your grade</a> to see available courses
            <?php endif; ?>
        </p>
    </div>
    <?php if ($student_grade): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#enrollModal">
        <i class="fas fa-plus"></i> Enroll in Course
    </button>
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
        <h4>Welcome to EDUMATE Courses</h4>
        <p class="text-muted mb-4">To access courses tailored for your grade level, please set your grade first.</p>
        <a href="student_dashboard.php" class="btn btn-primary btn-lg">
            <i class="fas fa-arrow-left"></i> Set My Grade
        </a>
    </div>
</div>
<?php else: ?>

<!-- Enrolled Courses Section -->
<div class="content-card mb-4">
    <div class="card-header-custom">
        <i class="fas fa-book-open me-2"></i>My Enrolled Courses (<?php echo count($enrolled_courses); ?>)
    </div>
    <div class="card-body">
        <?php if (empty($enrolled_courses)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5>No Enrolled Courses</h5>
                <p class="text-muted mb-4">You haven't enrolled in any courses yet. Browse available courses below and start your learning journey!</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#enrollModal">
                    <i class="fas fa-plus"></i> Enroll in Your First Course
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($enrolled_courses as $course): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <!-- Course Header -->
                        <div class="card-header bg-gradient" style="background: linear-gradient(135deg, var(--primary-neutral), var(--secondary-neutral));">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white mb-1"><?php echo htmlspecialchars($course['subject_name']); ?></h6>
                                    <small class="text-white-50"><?php echo htmlspecialchars($course['grade_name']); ?></small>
                                </div>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($course['subject_code']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Course Body -->
                        <div class="card-body">
                            <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($course['course_name']); ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </h5>
                            
                            <p class="card-text text-muted small mb-3">
                                <?php echo htmlspecialchars(substr($course['course_description'] ?? 'No description available.', 0, 100)); ?>
                                <?php if (strlen($course['course_description'] ?? '') > 100): ?>...<?php endif; ?>
                            </p>
                            
                            <!-- Progress Info -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="small text-muted">Assignments</div>
                                    <div class="fw-bold"><?php echo $course['total_assignments']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Completed</div>
                                    <div class="fw-bold text-success"><?php echo $course['completed_assignments']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Average</div>
                                    <div class="fw-bold <?php echo getGradeClass($course['avg_grade']); ?>">
                                        <?php echo formatGradeDisplay($course['avg_grade']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <?php $progress = getProgressPercentage($course['completed_assignments'], $course['total_assignments']); ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progress</small>
                                    <small class="text-muted"><?php echo $progress; ?>%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <!-- Teacher Info -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small fw-medium">
                                        <?php echo htmlspecialchars($course['teacher_name'] ?? $course['teacher_username']); ?>
                                    </div>
                                    <div class="small text-muted">Instructor</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Course Footer -->
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <a href="course_details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-eye"></i> View Course
                                </a>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmUnenroll(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>')">
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

<!-- Available Courses Section -->
<?php if (!empty($available_courses)): ?>
<div class="content-card mb-4">
    <div class="card-header-custom">
        <i class="fas fa-search me-2"></i>Available Courses for Grade <?php echo $student_grade; ?> (<?php echo count($available_courses); ?>)
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            Discover new courses available for your grade level. These courses are created by teachers and aligned with the South African curriculum.
        </p>
        
        <div class="row">
            <?php foreach ($available_courses as $course): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <!-- Course Header -->
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 text-dark"><?php echo htmlspecialchars($course['subject_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($course['grade_name']); ?></small>
                            </div>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($course['subject_code']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Course Body -->
                    <div class="card-body">
                        <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($course['course_name']); ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </h5>
                        
                        <p class="card-text text-muted small mb-3">
                            <?php echo htmlspecialchars(substr($course['course_description'] ?? 'No description available.', 0, 100)); ?>
                            <?php if (strlen($course['course_description'] ?? '') > 100): ?>...<?php endif; ?>
                        </p>
                        
                        <!-- Course Info -->
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="small text-muted">Materials</div>
                                <div class="fw-bold"><?php echo $course['material_count']; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Enrollment</div>
                                <div class="fw-bold">
                                    <?php echo $course['enrollment_key'] ? 'Key Required' : 'Open'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Teacher Info -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" 
                                 style="width: 32px; height: 32px;">
                                <i class="fas fa-chalkboard-teacher text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-medium">
                                    <?php echo htmlspecialchars($course['teacher_name'] ?? $course['teacher_username']); ?>
                                </div>
                                <div class="small text-muted">Instructor</div>
                            </div>
                        </div>
                        
                        <!-- Course Code -->
                        <div class="small text-muted mb-2">
                            Course Code: <code><?php echo htmlspecialchars($course['course_code']); ?></code>
                        </div>
                    </div>
                    
                    <!-- Course Footer -->
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-success w-100" 
                                onclick="enrollInCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', <?php echo $course['enrollment_key'] ? 'true' : 'false'; ?>)">
                            <i class="fas fa-plus"></i> Enroll Now
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="content-card mb-4">
    <div class="card-header-custom">
        <i class="fas fa-search me-2"></i>Available Courses
    </div>
    <div class="card-body text-center py-5">
        <i class="fas fa-book-open text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
        <h5>No Available Courses</h5>
        <p class="text-muted mb-4">
            There are currently no available courses for Grade <?php echo $student_grade; ?>. 
            Please check back later or contact your teachers.
        </p>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Enrollment Modal -->
<div class="modal fade" id="enrollModal" tabindex="-1" aria-labelledby="enrollModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollModalLabel">
                    <i class="fas fa-plus-circle"></i> Enroll in Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="enroll_course">
                <input type="hidden" name="course_id" id="modal_course_id">
                
                <div class="modal-body">
                    <div id="course_info" class="mb-3"></div>
                    
                    <div id="enrollment_key_section" style="display: none;">
                        <label for="enrollment_key" class="form-label">Enrollment Key</label>
                        <input type="text" class="form-control" id="enrollment_key" name="enrollment_key" 
                               placeholder="Enter enrollment key provided by your teacher">
                        <div class="form-text">
                            Ask your teacher for the enrollment key to access this course.
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirm Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unenrollment Form (Hidden) -->
<form id="unenrollForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="unenroll_course">
    <input type="hidden" name="course_id" id="unenroll_course_id">
</form>

<script>
function enrollInCourse(courseId, courseName, requiresKey) {
    document.getElementById('modal_course_id').value = courseId;
    
    // Update course info in modal
    document.getElementById('course_info').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Course:</strong> ${courseName}
            <br><small class="text-muted">You are about to enroll in this course.</small>
        </div>
    `;
    
    // Show/hide enrollment key section
    const keySection = document.getElementById('enrollment_key_section');
    if (requiresKey) {
        keySection.style.display = 'block';
        document.getElementById('enrollment_key').required = true;
    } else {
        keySection.style.display = 'none';
        document.getElementById('enrollment_key').required = false;
        document.getElementById('enrollment_key').value = '';
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('enrollModal'));
    modal.show();
}

function confirmUnenroll(courseId, courseName) {
    Swal.fire({
        title: 'Unenroll from Course?',
        text: `Are you sure you want to unenroll from "${courseName}"? You will lose access to all course materials and assignments.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Unenroll',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('unenroll_course_id').value = courseId;
            document.getElementById('unenrollForm').submit();
        }
    });
}

function pageInit() {
    console.log('Courses page loaded');
    
    // Announce page load for screen readers
    if (document.body.getAttribute('data-screen-reader') === 'true') {
        setTimeout(() => {
            const enrolledCount = <?php echo count($enrolled_courses); ?>;
            const availableCount = <?php echo count($available_courses); ?>;
            announceToScreenReader(`Courses page loaded. You have ${enrolledCount} enrolled courses and ${availableCount} available courses.`);
        }, 1000);
    }
    
    // Auto-focus enrollment key input when modal shows
    document.getElementById('enrollModal').addEventListener('shown.bs.modal', function() {
        const keyInput = document.getElementById('enrollment_key');
        if (keyInput.style.display !== 'none' && keyInput.offsetParent !== null) {
            keyInput.focus();
        }
    });
}

// Clear modal data when hidden
document.getElementById('enrollModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modal_course_id').value = '';
    document.getElementById('enrollment_key').value = '';
    document.getElementById('course_info').innerHTML = '';
    document.getElementById('enrollment_key_section').style.display = 'none';
});
</script>

<style>
/* Course card hover effects */
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Progress bar animation */
.progress-bar {
    transition: width 0.6s ease;
}

/* Accessibility improvements */
.card:focus-within {
    outline: 2px solid var(--primary-neutral);
    outline-offset: 2px;
}

/* High contrast mode adjustments */
.high-contrast .card {
    border: 2px solid #000 !important;
}

.high-contrast .badge {
    border: 1px solid #000 !important;
}

/* Reduced motion */
.reduced-motion .card {
    transition: none !important;
}

.reduced-motion .progress-bar {
    transition: none !important;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .card-title {
        font-size: 1rem;
    }
    
    .row.text-center > div {
        margin-bottom: 0.5rem;
    }
}
</style>

<?php
// Include the footer
include '../includes/student_footer.php';
?>