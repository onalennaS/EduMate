<?php
// teacher_dashboard/manage_courses.php
session_start();


// Include database connection
require_once '../config/database.php';

// Include the header
include '../includes/teacher_header.php';

$teacher_id = $_SESSION['user_id'];

// Get the subject ID from URL if provided
$subject_id = $_GET['subject_id'] ?? null;

// Get teacher's subjects
$stmt = $pdo->prepare("SELECT s.* FROM subjects s
                      JOIN teacher_subjects ts ON s.id = ts.subject_id
                      WHERE ts.teacher_id = ?
                      ORDER BY s.subject_name");
$stmt->execute([$teacher_id]);
$teacher_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get courses for the selected subject
$courses = [];
if ($subject_id) {
    $stmt = $pdo->prepare("SELECT c.*, g.grade_name, COUNT(e.id) as enrollment_count
                          FROM courses c
                          JOIN grades g ON c.grade_id = g.id
                          LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
                          WHERE c.subject_id = ? AND c.teacher_id = ?
                          GROUP BY c.id
                          ORDER BY g.grade_number, c.course_name");
    $stmt->execute([$subject_id, $teacher_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all grades for the grade dropdown
$stmt = $pdo->prepare("SELECT * FROM grades ORDER BY grade_number");
$stmt->execute();
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_course') {
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];
    $course_description = $_POST['course_description'];
    $enrollment_key = $_POST['enrollment_key'] ?? '';
    $grade_id = $_POST['grade_id'];
    $max_students = $_POST['max_students'] ?? 50;
    $subject_id = $_POST['subject_id']; // Get subject_id from form
    
    // Validate required fields
    if (empty($course_name) || empty($course_code) || empty($grade_id) || empty($subject_id)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (teacher_id, subject_id, grade_id, course_name, course_code, course_description, enrollment_key, max_students) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$teacher_id, $subject_id, $grade_id, $course_name, $course_code, $course_description, $enrollment_key, $max_students]);
            
            $success_message = "Course '$course_name' created successfully!";
            
            // Refresh courses list
            $stmt = $pdo->prepare("SELECT c.*, g.grade_name, COUNT(e.id) as enrollment_count
                                  FROM courses c
                                  JOIN grades g ON c.grade_id = g.id
                                  LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
                                  WHERE c.subject_id = ? AND c.teacher_id = ?
                                  GROUP BY c.id
                                  ORDER BY g.grade_number, c.course_name");
            $stmt->execute([$subject_id, $teacher_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error_message = "Course code '$course_code' already exists. Please use a different code.";
            } else {
                $error_message = "Error creating course: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Manage Courses</h1>
        <p class="text-muted mb-0">Create and manage courses for your subjects</p>
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

<div class="row">
    <!-- Subject Selection Sidebar -->
    <div class="col-md-3 mb-4">
        <div class="content-card">
            <div class="card-header-custom">
                <i class="fas fa-chalkboard me-2"></i>My Subjects
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($teacher_subjects)): ?>
                        <div class="p-3 text-center">
                            <i class="fas fa-chalkboard text-muted mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                            <p class="text-muted mb-0">No subjects found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teacher_subjects as $subject): ?>
                            <a href="manage_courses.php?subject_id=<?php echo $subject['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo ($subject_id == $subject['id']) ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                                    <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Management Area -->
    <div class="col-md-9">
        <?php if ($subject_id): ?>
            <?php
            // Get the selected subject
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
            $stmt->execute([$subject_id]);
            $selected_subject = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div class="content-card mb-4">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-book me-2"></i>
                        Courses for <?php echo htmlspecialchars($selected_subject['subject_name']); ?>
                    </span>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                        <i class="fas fa-plus"></i> Create Course
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($courses)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book-open text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <h5>No Courses Yet</h5>
                            <p class="text-muted mb-4">Create your first course for this subject to start enrolling students.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                <i class="fas fa-plus"></i> Create Your First Course
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Name</th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Enrollment Key</th>
                                        <th>Students</th>
                                        <th>Max Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($course['course_description'], 0, 50)); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['grade_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td>
                                            <?php if ($course['enrollment_key']): ?>
                                                <code><?php echo htmlspecialchars($course['enrollment_key']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $course['enrollment_count']; ?> / <?php echo $course['max_students']; ?></span>
                                        </td>
                                        <td>
                                            <?php echo $course['max_students']; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $course['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="course_details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="content-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chalkboard text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h4>Select a Subject</h4>
                    <p class="text-muted mb-4">Choose a subject from the sidebar to view and manage its courses.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Course Modal -->
<div class="modal fade" id="createCourseModal" tabindex="-1" aria-labelledby="createCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCourseModalLabel">
                    <i class="fas fa-plus-circle"></i> Create New Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_course">
                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name *</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_code" class="form-label">Course Code *</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" required>
                            <div class="form-text">Unique code for this course</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="grade_id" class="form-label">Grade *</label>
                            <select class="form-select" id="grade_id" name="grade_id" required>
                                <option value="">Select Grade</option>
                                <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo $grade['id']; ?>"><?php echo htmlspecialchars($grade['grade_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="course_description" class="form-label">Description</label>
                        <textarea class="form-control" id="course_description" name="course_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="enrollment_key" class="form-label">Enrollment Key (Optional)</label>
                            <input type="text" class="form-control" id="enrollment_key" name="enrollment_key">
                            <div class="form-text">Students will need this key to enroll</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="max_students" class="form-label">Max Students</label>
                            <input type="number" class="form-control" id="max_students" name="max_students" value="50" min="1" max="200">
                            <div class="form-text">Maximum number of students allowed</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function pageInit() {
    console.log('Manage courses page loaded');
    
    // Announce page load for screen readers
    if (document.body.getAttribute('data-screen-reader') === 'true') {
        setTimeout(() => {
            const subjectCount = <?php echo count($teacher_subjects); ?>;
            announceToScreenReader(`Manage courses page loaded. You have ${subjectCount} subjects.`);
        }, 1000);
    }
}
</script>

<?php
// Include the footer
include '../includes/teacher_footer.php';
?>