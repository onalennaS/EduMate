<?php
// teacher_dashboard/teacher_dashboard.php

// Set page-specific variables before including header
$page_title = 'Teacher Dashboard';

// Additional CSS for this specific page
$additional_css = '
    .subject-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    .subject-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .enrollment-stats {
        font-size: 0.85rem;
    }
    .bg-gradient {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light)) !important;
    }
    .bg-gradient.core {
        background: linear-gradient(135deg, var(--success-color), var(--success-light)) !important;
    }
    .bg-gradient.elective {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light)) !important;
    }
    .bg-gradient.practical {
        background: linear-gradient(135deg, var(--warning-color), var(--warning-light)) !important;
    }
    .available-card {
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    .available-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }
    
    /* Category-based colors */
    :root {
        --success-color: #198754;
        --success-light: #d1e7dd;
        --primary-color: #0d6efd;
        --primary-light: #cfe2ff;
        --warning-color: #ffc107;
        --warning-light: #fff3cd;
    }
    
    /* Stats card improvements */
    .stat-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    /* Welcome card improvements */
    .welcome-card {
        background: #1e40af;
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    }
    
    /* Content card improvements */
    .content-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        overflow: hidden;
    }
    
    .card-header-custom {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
        font-weight: 600;
        color: #495057;
    }
    
    /* Mobile responsive adjustments */
    @media (max-width: 768px) {
        .welcome-card {
            padding: 1.5rem;
        }
        .welcome-card h1 {
            font-size: 1.5rem;
        }
        .stat-card {
            margin-bottom: 1rem;
        }
    }
    
    /* High contrast mode */
    .high-contrast .subject-card {
        border: 2px solid #000 !important;
    }
    .high-contrast .available-card {
        border: 2px solid #000 !important;
    }
    .high-contrast .welcome-card {
        background: #000 !important;
        color: #fff !important;
    }
    
    /* Reduced motion */
    .reduced-motion .subject-card {
        transition: none !important;
    }
    .reduced-motion .available-card {
        transition: none !important;
    }
    .reduced-motion .stat-card {
        transition: none !important;
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
        if (typeof Swal !== "undefined") {
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
        } else {
            if (confirm("Are you sure you want to teach \"" + subjectName + "\"?")) {
                document.getElementById("select_subject_id").value = subjectId;
                document.getElementById("selectSubjectForm").submit();
            }
        }
    }
    
    // Function to confirm subject removal
    function confirmRemoveSubject(subjectId, subjectName) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Stop Teaching This Subject?",
                text: "Are you sure you want to stop teaching \"" + subjectName + "\"? Students will still have access to existing materials.",
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
        } else {
            if (confirm("Are you sure you want to stop teaching \"" + subjectName + "\"?")) {
                document.getElementById("remove_subject_id").value = subjectId;
                document.getElementById("removeSubjectForm").submit();
            }
        }
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
// Total students enrolled in teacher's subjects
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT se.student_id) as total_students
                       FROM subject_enrollments se
                       JOIN teacher_subjects ts ON se.subject_id = ts.subject_id
                       WHERE ts.teacher_id = ? AND se.status = 'active'");
$stmt->execute([$teacher_id]);
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

// Subjects taught by the teacher
$stmt = $pdo->prepare("SELECT COUNT(*) as subjects_taught
                       FROM teacher_subjects
                       WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$subjects_taught = $stmt->fetch(PDO::FETCH_ASSOC)['subjects_taught'];

// Pending assignments to grade (simplified query - may need adjustment based on your actual table structure)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_to_grade
                           FROM submissions s
                           WHERE s.grade IS NULL");
    $stmt->execute();
    $pending_to_grade = $stmt->fetch(PDO::FETCH_ASSOC)['pending_to_grade'];
} catch (PDOException $e) {
    $pending_to_grade = 0; // Default value if submissions table doesn't exist
}

// Total assignments created (simplified query - may need adjustment based on your actual table structure)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_assignments
                           FROM assignments
                           WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $total_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total_assignments'];
} catch (PDOException $e) {
    // Try alternative structure
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_assignments FROM assignments");
        $stmt->execute();
        $total_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total_assignments'];
    } catch (PDOException $e2) {
        $total_assignments = 0; // Default value if assignments table doesn't exist
    }
}

// Get teacher's subjects (subjects they teach)
$stmt = $pdo->prepare("SELECT s.*, 
                      (SELECT COUNT(*) FROM subject_enrollments se WHERE se.subject_id = s.id AND se.status = 'active') as student_count
                      FROM subjects s
                      JOIN teacher_subjects ts ON s.id = ts.subject_id
                      WHERE ts.teacher_id = ?
                      ORDER BY s.category, s.subject_name");
$stmt->execute([$teacher_id]);
$teacher_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available subjects (for selection)
$stmt = $pdo->prepare("SELECT s.* FROM subjects s
                      WHERE s.id NOT IN (
                          SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?
                      ) AND s.is_active = 1
                      ORDER BY s.category, s.subject_name");
$stmt->execute([$teacher_id]);
$available_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group available subjects by category
$core_subjects = array_filter($available_subjects, function($s) { return $s['category'] === 'core'; });
$elective_subjects = array_filter($available_subjects, function($s) { return $s['category'] === 'elective'; });
$practical_subjects = array_filter($available_subjects, function($s) { return $s['category'] === 'practical'; });
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Teacher Dashboard</h1>
        <p class="text-muted mb-0">Manage your subjects and track student progress</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" onclick="showCreateSubjectModal()">
            <i class="fas fa-plus"></i> Create Subject
        </button>
    </div>
</div>

<!-- Welcome Section -->
<div class="welcome-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-3">Welcome back, <?php echo htmlspecialchars($teacher_name); ?>!</h1>
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
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-users text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold h4 mb-0"><?php echo $total_students; ?></div>
                        <div class="text-muted small">Total Students</div>
                        <div class="small text-success">
                            <i class="fas fa-arrow-up"></i> Active Enrollments
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-book-open text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold h4 mb-0"><?php echo $subjects_taught; ?></div>
                        <div class="text-muted small">Subjects Teaching</div>
                        <div class="small text-primary">
                            <i class="fas fa-chalkboard"></i> Active Subjects
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-clipboard-check text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold h4 mb-0"><?php echo $pending_to_grade; ?></div>
                        <div class="text-muted small">Pending to Grade</div>
                        <div class="small text-warning">
                            <i class="fas fa-clock"></i> Needs Review
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-tasks text-info" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold h4 mb-0"><?php echo $total_assignments; ?></div>
                        <div class="text-muted small">Total Assignments</div>
                        <div class="small text-info">
                            <i class="fas fa-file-alt"></i> Created
                        </div>
                    </div>
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

<!-- My Teaching Subjects Section -->
<div class="content-card mb-4">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="fas fa-chalkboard me-2"></i>My Teaching Subjects (<?php echo count($teacher_subjects); ?>)</span>
        <button class="btn btn-primary btn-sm" onclick="showCreateSubjectModal()">
            <i class="fas fa-plus"></i> Create New Subject
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($teacher_subjects)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chalkboard text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5>No Subjects Added Yet</h5>
                <p class="text-muted mb-4">Add subjects to your teaching list to start organizing your content and engaging with students.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-primary" onclick="showCreateSubjectModal()">
                        <i class="fas fa-plus"></i> Create Your First Subject
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($teacher_subjects as $subject): 
                    $applicable_grades = json_decode($subject['applicable_grades'], true);
                    $grade_class = '';
                    if ($subject['category'] === 'core') $grade_class = 'core';
                    elseif ($subject['category'] === 'elective') $grade_class = 'elective';
                    else $grade_class = 'practical';
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card subject-card h-100">
                        <!-- Subject Header -->
                        <div class="card-header bg-gradient <?php echo $grade_class; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white mb-1 fw-bold"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <small class="text-white-50"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark mb-1"><?php echo ucfirst($subject['category']); ?></span>
                                    <br>
                                    <span class="badge bg-<?php echo $subject['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subject Body -->
                        <div class="card-body">
                            <p class="card-text text-muted small mb-3">
                                <?php echo htmlspecialchars(substr($subject['description'] ?? 'No description available.', 0, 120)); ?>
                                <?php if (strlen($subject['description'] ?? '') > 120): ?>...<?php endif; ?>
                            </p>
                            
                            <!-- Subject Stats -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="small text-muted">Students Enrolled</div>
                                    <div class="fw-bold text-primary h5 mb-0"><?php echo $subject['student_count']; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Applicable Grades</div>
                                    <div class="fw-bold text-secondary">
                                        <?php if (!empty($applicable_grades) && is_array($applicable_grades)): ?>
                                            <?php echo implode(', ', $applicable_grades); ?>
                                        <?php else: ?>
                                            All
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Subject Details -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-users text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small fw-medium">
                                        Teaching Status: Active
                                    </div>
                                    <div class="small text-muted">
                                        Subject ready for student enrollment
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subject Footer -->
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <a href="manage_subject.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-cog"></i> Manage
                                </a>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmRemoveSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
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
        <i class="fas fa-search me-2"></i>Available Subjects to Teach (<?php echo count($available_subjects); ?>)
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            Select from these available subjects to add them to your teaching list. Each subject is organized by category to help you find relevant content.
        </p>
        
        <div class="row">
            <?php if (!empty($core_subjects)): ?>
            <div class="col-md-6 mb-4">
                <h5 class="text-success mb-3"><i class="fas fa-star me-2"></i>Core Subjects</h5>
                <div class="row">
                    <?php foreach ($core_subjects as $subject): 
                        $applicable_grades = json_decode($subject['applicable_grades'], true);
                    ?>
                    <div class="col-12 mb-3">
                        <div class="card available-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                </div>
                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars(substr($subject['description'] ?? '', 0, 100)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success bg-opacity-10 text-success">Core Subject</span>
                                    <small class="text-muted">
                                        Grades: <?php echo !empty($applicable_grades) ? implode(', ', $applicable_grades) : 'All'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <button class="btn btn-success btn-sm w-100" 
                                        onclick="confirmSelectSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-plus"></i> Add to My Subjects
                                </button>
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
                    <?php foreach ($elective_subjects as $subject): 
                        $applicable_grades = json_decode($subject['applicable_grades'], true);
                    ?>
                    <div class="col-12 mb-3">
                        <div class="card available-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                </div>
                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars(substr($subject['description'] ?? '', 0, 100)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Elective</span>
                                    <small class="text-muted">
                                        Grades: <?php echo !empty($applicable_grades) ? implode(', ', $applicable_grades) : 'All'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <button class="btn btn-primary btn-sm w-100" 
                                        onclick="confirmSelectSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-plus"></i> Add to My Subjects
                                </button>
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
                    <?php foreach ($practical_subjects as $subject): 
                        $applicable_grades = json_decode($subject['applicable_grades'], true);
                    ?>
                    <div class="col-12 mb-3">
                        <div class="card available-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <span class="badge bg-warning"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                </div>
                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars(substr($subject['description'] ?? '', 0, 100)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning bg-opacity-10 text-warning">Practical</span>
                                    <small class="text-muted">
                                        Grades: <?php echo !empty($applicable_grades) ? implode(', ', $applicable_grades) : 'All'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <button class="btn btn-warning btn-sm w-100" 
                                        onclick="confirmSelectSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-plus"></i> Add to My Subjects
                                </button>
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
            You're currently teaching all available subjects in the system. 
            Create a new subject to expand your teaching portfolio.
        </p>
        <button class="btn btn-primary" onclick="showCreateSubjectModal()">
            <i class="fas fa-plus"></i> Create New Subject
        </button>
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
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="assignments.php" class="btn btn-outline-warning w-100 h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="fas fa-file-alt mb-2" style="font-size: 1.5rem;"></i>
                        <div>Create Assignment</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="students.php" class="btn btn-outline-info w-100 h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="fas fa-users mb-2" style="font-size: 1.5rem;"></i>
                        <div>View Students</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="gradebook.php" class="btn btn-outline-success w-100 h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="fas fa-clipboard-check mb-2" style="font-size: 1.5rem;"></i>
                        <div>Gradebook</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="profile.php" class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="fas fa-user mb-2" style="font-size: 1.5rem;"></i>
                        <div>My Profile</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Create Subject Modal -->
<div class="modal fade" id="createSubjectModal" tabindex="-1" aria-labelledby="createSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createSubjectModalLabel">
                    <i class="fas fa-plus-circle"></i> Create New Subject
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createSubjectForm" method="POST">
                <input type="hidden" name="action" value="create_subject">
                
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3">Subject Information</h6>
                            <p class="text-muted mb-4">
                                Create a new subject that students can enroll in. Make sure to provide clear descriptions and set appropriate grade levels.
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-book-open text-primary mb-2" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_name" class="form-label fw-bold">Subject Name *</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required 
                                   placeholder="e.g., Mathematics Grade 10">
                            <div class="form-text">Enter the full name of the subject</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subject_code" class="form-label fw-bold">Subject Code *</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required 
                                   placeholder="e.g., MATH10">
                            <div class="form-text">Unique code for this subject (letters/numbers only)</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label fw-bold">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="core">Core Subject</option>
                                <option value="elective">Elective Subject</option>
                                <option value="practical">Practical Subject</option>
                            </select>
                            <div class="form-text">Choose the most appropriate category</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Applicable Grades</label>
                            <div class="border rounded p-3" style="max-height: 120px; overflow-y: auto;">
                                <div class="row">
                                    <?php for ($grade = 1; $grade <= 12; $grade++): ?>
                                    <div class="col-6 col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="grade_<?php echo $grade; ?>" 
                                                   name="applicable_grades[]" value="<?php echo $grade; ?>">
                                            <label class="form-check-label small" for="grade_<?php echo $grade; ?>">
                                                Grade <?php echo $grade; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="form-text">Select all applicable grade levels</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Provide a clear description of what students will learn in this subject..."></textarea>
                        <div class="form-text">Help students understand what this subject covers</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Once created, this subject will be automatically added to your teaching list and will be available for student enrollment.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
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
            const studentCount = <?php echo $total_students; ?>;
            announceToScreenReader(Teacher dashboard loaded. You are teaching ${subjectCount} subjects with ${studentCount} total students enrolled.);
        }, 1000);
    }
}

// Enhanced form validation for create subject modal
document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createSubjectForm');
    const subjectNameInput = document.getElementById('subject_name');
    const subjectCodeInput = document.getElementById('subject_code');
    const categorySelect = document.getElementById('category');
    
    // Auto-generate subject code based on subject name
    subjectNameInput.addEventListener('input', function() {
        if (!subjectCodeInput.value) {
            const name = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8);
            subjectCodeInput.value = name;
        }
    });
    
    // Form validation
    createForm.addEventListener('submit', function(e) {
        const gradeCheckboxes = document.querySelectorAll('input[name="applicable_grades[]"]:checked');
        
        if (gradeCheckboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one applicable grade level.');
            return false;
        }
        
        // Validate subject code format
        const codePattern = /^[A-Z0-9]+$/;
        if (!codePattern.test(subjectCodeInput.value)) {
            e.preventDefault();
            alert('Subject code must contain only letters and numbers (no spaces or special characters).');
            subjectCodeInput.focus();
            return false;
        }
    });
});
</script>

<?php
// Include the common footer
include_once '../includes/teacher_footer.php';
?>