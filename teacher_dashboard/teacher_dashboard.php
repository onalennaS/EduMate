<?php
// teacher_dashboard/teacher_dashboard.php

// Set page-specific variables before including header
$page_title = 'Teacher Dashboard';

// Additional CSS for this specific page
$additional_css = '
    .subject-card {
        transition: transform 0.2s ease-in-out;
    }
    .subject-card:hover {
        transform: translateY(-5px);
    }
    .enrollment-stats {
        font-size: 0.85rem;
    }
';

// Additional JavaScript for this page
$additional_js = '
    // Function to show the create subject modal
    function showCreateSubjectModal() {
        // Reset form
        document.getElementById("createSubjectForm").reset();
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById("createSubjectModal"));
        modal.show();
    }
    
    // Function to confirm subject selection
    function confirmSelectSubject(subjectId, subjectName) {
        Swal.fire({
            title: "Teach This Subject?",
            text: "Are you sure you want to teach \"" + subjectName + "\"?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Teach This",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit selection form
                document.getElementById("select_subject_id").value = subjectId;
                document.getElementById("selectSubjectForm").submit();
            }
        });
    }
    
    // Function to confirm subject removal
    function confirmRemoveSubject(subjectId, subjectName) {
        Swal.fire({
            title: "Stop Teaching This Subject?",
            text: "Are you sure you want to stop teaching \"" + subjectName + "\"?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Remove",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit removal form
                document.getElementById("remove_subject_id").value = subjectId;
                document.getElementById("removeSubjectForm").submit();
            }
        });
    }
';

// Include the common header
include_once '../includes/teacher_header.php';

// Database connection
require_once '../config/database.php';

$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_subject':
                $subject_name = $_POST['subject_name'];
                $subject_code = $_POST['subject_code'];
                $category = $_POST['category'];
                $applicable_grades = isset($_POST['applicable_grades']) ? json_encode($_POST['applicable_grades']) : '[]';
                $description = $_POST['description'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, category, applicable_grades, description) 
                                           VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$subject_name, $subject_code, $category, $applicable_grades, $description]);
                    
                    // Get the inserted subject ID
                    $subject_id = $pdo->lastInsertId();
                    
                    // Also add this subject to teacher's subjects
                    $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                    $stmt->execute([$teacher_id, $subject_id]);
                    
                    $success_message = "Subject '$subject_name' created successfully and added to your teaching list!";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // Duplicate entry
                        $error_message = "Subject code '$subject_code' already exists. Please use a different code.";
                    } else {
                        $error_message = "Error creating subject: " . $e->getMessage();
                    }
                }
                break;
                
            case 'select_subject':
                // Validate subject_id
                if (!isset($_POST['subject_id']) || empty($_POST['subject_id'])) {
                    $error_message = "Invalid subject selection.";
                    break;
                }
                
                $subject_id = $_POST['subject_id'];
                
                // Check if already selected
                $stmt = $pdo->prepare("SELECT * FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
                $stmt->execute([$teacher_id, $subject_id]);
                
                if ($stmt->rowCount() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                    if ($stmt->execute([$teacher_id, $subject_id])) {
                        $success_message = "Subject added to your teaching list!";
                    } else {
                        $error_message = "Error adding subject to your teaching list.";
                    }
                } else {
                    $error_message = "You are already teaching this subject.";
                }
                break;
                
            case 'remove_subject':
                // Validate subject_id
                if (!isset($_POST['subject_id']) || empty($_POST['subject_id'])) {
                    $error_message = "Invalid subject selection.";
                    break;
                }
                
                $subject_id = $_POST['subject_id'];
                
                $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
                if ($stmt->execute([$teacher_id, $subject_id])) {
                    $success_message = "Subject removed from your teaching list!";
                } else {
                    $error_message = "Error removing subject from your teaching list.";
                }
                break;
        }
    }
}

// Get accurate statistics
// Total students enrolled in teacher's courses
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.student_id) as total_students
                       FROM enrollments e
                       JOIN courses c ON e.course_id = c.id
                       JOIN teacher_subjects ts ON c.subject_id = ts.subject_id
                       WHERE ts.teacher_id = ? AND e.status = 'active'");
$stmt->execute([$teacher_id]);
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

// Courses taught by the teacher
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) as courses_taught
                       FROM courses c
                       JOIN teacher_subjects ts ON c.subject_id = ts.subject_id
                       WHERE ts.teacher_id = ?");
$stmt->execute([$teacher_id]);
$courses_taught = $stmt->fetch(PDO::FETCH_ASSOC)['courses_taught'];

// Pending assignments to grade
$stmt = $pdo->prepare("SELECT COUNT(*) as pending_to_grade
                       FROM submissions s
                       JOIN assignments a ON s.assignment_id = a.id
                       JOIN courses c ON a.course_id = c.id
                       JOIN teacher_subjects ts ON c.subject_id = ts.subject_id
                       WHERE ts.teacher_id = ? AND s.grade IS NULL");
$stmt->execute([$teacher_id]);
$pending_to_grade = $stmt->fetch(PDO::FETCH_ASSOC)['pending_to_grade'];

// Get teacher's subjects (subjects they teach)
$stmt = $pdo->prepare("SELECT s.*, 
                      (SELECT COUNT(*) FROM courses c WHERE c.subject_id = s.id AND c.teacher_id = ?) as course_count,
                      (SELECT COUNT(*) FROM enrollments e 
                       JOIN courses c ON e.course_id = c.id 
                       WHERE c.subject_id = s.id AND c.teacher_id = ?) as student_count
                      FROM subjects s
                      JOIN teacher_subjects ts ON s.id = ts.subject_id
                      WHERE ts.teacher_id = ?
                      ORDER BY s.subject_name");
$stmt->execute([$teacher_id, $teacher_id, $teacher_id]);
$teacher_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available subjects (for selection)
$stmt = $pdo->prepare("SELECT s.* FROM subjects s
                      WHERE s.id NOT IN (
                          SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?
                      )
                      ORDER BY s.subject_name");
$stmt->execute([$teacher_id]);
$available_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Welcome Section -->
<div class="welcome-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-3">Welcome back, <?php echo htmlspecialchars($teacher_name); ?>! 👨‍🏫</h1>
            <p class="mb-0 opacity-90">Ready to inspire and educate? Check out your latest updates and student progress.</p>
        </div>
        <div class="col-md-4 text-end d-none d-md-block">
            <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-users text-success" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="text-muted">Total Students</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-book-open text-primary" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo $courses_taught; ?></div>
                    <div class="text-muted">Courses Teaching</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-clipboard-check text-warning" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo $pending_to_grade; ?></div>
                    <div class="text-muted">Pending to Grade</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-chalkboard text-info" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo count($teacher_subjects); ?></div>
                    <div class="text-muted">Subjects Teaching</div>
                </div>
            </div>
        </div>
    </div>
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

<!-- My Subjects Section -->
<div class="content-card mb-4">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="fas fa-chalkboard me-2"></i>My Teaching Subjects (<?php echo count($teacher_subjects); ?>)</span>
        <button class="btn btn-primary" onclick="showCreateSubjectModal()">
            <i class="fas fa-plus"></i> Create New Subject
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($teacher_subjects)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chalkboard text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5>No Subjects Added Yet</h5>
                <p class="text-muted mb-4">Add subjects to your teaching list to start organizing your courses.</p>
                <button class="btn btn-primary" onclick="showCreateSubjectModal()">
                    <i class="fas fa-plus"></i> Create Your First Subject
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($teacher_subjects as $subject): 
                    $applicable_grades = json_decode($subject['applicable_grades'], true);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card subject-card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                            <small class="opacity-75"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($subject['description']); ?></p>
                            
                            <div class="mb-3">
                                <span class="badge bg-<?php echo $subject['category'] === 'core' ? 'success' : ($subject['category'] === 'elective' ? 'info' : 'warning'); ?>">
                                    <?php echo ucfirst($subject['category']); ?>
                                </span>
                                <span class="badge bg-<?php echo $subject['is_active'] ? 'success' : 'secondary'; ?> ms-1">
                                    <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <div class="enrollment-stats">
                                <div class="d-flex justify-content-between">
                                    <span>Applicable Grades:</span>
                                    <span>
                                        <?php if (!empty($applicable_grades) && is_array($applicable_grades)): ?>
                                            Grades <?php echo implode(', ', $applicable_grades); ?>
                                        <?php else: ?>
                                            Not specified
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Courses:</span>
                                    <span><?php echo $subject['course_count']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Students Enrolled:</span>
                                    <span><?php echo $subject['student_count']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <a href="manage_courses.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="fas fa-cog"></i> Manage Courses
                                </a>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmRemoveSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-times"></i> Remove
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
        <i class="fas fa-search me-2"></i>Available Subjects to Teach
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">Select from these available subjects to add them to your teaching list:</p>
        
        <div class="row">
            <?php foreach ($available_subjects as $subject): 
                $applicable_grades = json_decode($subject['applicable_grades'], true);
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                    </div>
                    <div class="card-body">
                        <p class="card-text small"><?php echo htmlspecialchars($subject['description']); ?></p>
                        
                        <div class="mb-2">
                            <span class="badge bg-<?php echo $subject['category'] === 'core' ? 'success' : ($subject['category'] === 'elective' ? 'info' : 'warning'); ?>">
                                <?php echo ucfirst($subject['category']); ?>
                            </span>
                        </div>
                        
                        <div class="small text-muted">
                            Applicable Grades: 
                            <?php if (!empty($applicable_grades) && is_array($applicable_grades)): ?>
                                Grades <?php echo implode(', ', $applicable_grades); ?>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-success w-100" 
                                onclick="confirmSelectSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
                            <i class="fas fa-plus"></i> Add to My Subjects
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="content-card mb-4">
    <div class="card-header-custom">
        <i class="fas fa-bolt me-2"></i>Quick Actions
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-6 mb-2">
                <a href="manage_courses.php" class="btn btn-outline-success w-100">
                    <i class="fas fa-book-open me-2"></i>Manage Courses
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="assignments.php" class="btn btn-outline-warning w-100">
                    <i class="fas fa-file-alt me-2"></i>Create Assignment
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="students.php" class="btn btn-outline-info w-100">
                    <i class="fas fa-users me-2"></i>View Students
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="profile.php" class="btn btn-outline-primary w-100">
                    <i class="fas fa-user me-2"></i>My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Create Subject Modal -->
<div class="modal fade" id="createSubjectModal" tabindex="-1" aria-labelledby="createSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSubjectModalLabel">
                    <i class="fas fa-plus-circle"></i> Create New Subject
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createSubjectForm" method="POST">
                <input type="hidden" name="action" value="create_subject">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_name" class="form-label">Subject Name *</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subject_code" class="form-label">Subject Code *</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                            <div class="form-text">Unique code for this subject (e.g., MATH101)</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="core">Core</option>
                                <option value="elective">Elective</option>
                                <option value="practical">Practical</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Applicable Grades</label>
                            <div class="grades-checkbox">
                                <?php for ($grade = 7; $grade <= 12; $grade++): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="grade_<?php echo $grade; ?>" 
                                           name="applicable_grades[]" value="<?php echo $grade; ?>">
                                    <label class="form-check-label" for="grade_<?php echo $grade; ?>">Grade <?php echo $grade; ?></label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Create Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms for Subject Selection/Removal -->
<form id="selectSubjectForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="select_subject">
    <input type="hidden" name="subject_id" id="select_subject_id">
</form>

<form id="removeSubjectForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="remove_subject">
    <input type="hidden" name="subject_id" id="remove_subject_id">
</form>

<script>
function pageInit() {
    console.log('Teacher dashboard loaded');
    
    // Announce page load for screen readers
    if (document.body.getAttribute('data-screen-reader') === 'true') {
        setTimeout(() => {
            const subjectCount = <?php echo count($teacher_subjects); ?>;
            announceToScreenReader(`Teacher dashboard loaded. You are teaching ${subjectCount} subjects.`);
        }, 1000);
    }
}
</script>

<?php
// Include the common footer
include_once '../includes/teacher_footer.php';
?>