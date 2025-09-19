<?php
// teacher_dashboard/students.php
session_start();

// Include database connection
require_once '../config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$grade_filter = $_GET['grade'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$sort_by = $_GET['sort'] ?? 'name';

// Build query for fetching students
$query = "SELECT 
            u.id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            u.grade, 
            u.profile_picture,
            COUNT(DISTINCT se.subject_id) as subject_count
          FROM users u
          LEFT JOIN subject_enrollments se ON u.id = se.student_id
          WHERE u.user_type = 'student'";

$params = [];
$types = '';

// Add search condition
if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

// Add grade filter
if (!empty($grade_filter)) {
    $query .= " AND u.grade = ?";
    $params[] = $grade_filter;
    $types .= 's';
}

// Add subject filter
if (!empty($subject_filter)) {
    $query .= " AND se.subject_id = ?";
    $params[] = $subject_filter;
    $types .= 'i';
}

// Group and sort
$query .= " GROUP BY u.id";

// Add sorting
switch ($sort_by) {
    case 'grade':
        $query .= " ORDER BY u.grade ASC, u.last_name ASC";
        break;
    case 'name':
    default:
        $query .= " ORDER BY u.last_name ASC, u.first_name ASC";
}

// Prepare and execute query
$stmt = $pdo->prepare($query);

if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available grades for filter
$grade_stmt = $pdo->query("SELECT DISTINCT grade FROM users WHERE grade IS NOT NULL AND grade != '' AND user_type = 'student' ORDER BY grade");
$available_grades = $grade_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get teacher's subjects for filter
$subject_stmt = $pdo->prepare("SELECT s.id, s.subject_name 
                               FROM subjects s 
                               JOIN teacher_subjects ts ON s.id = ts.subject_id 
                               WHERE ts.teacher_id = ? 
                               ORDER BY s.subject_name");
$subject_stmt->execute([$teacher_id]);
$available_subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);


// Handle actions (view, edit, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_message':
                // Handle sending message to student
                $student_id = $_POST['student_id'];
                $message = trim($_POST['message']);
                
                if (!empty($message)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) 
                                              VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$teacher_id, $student_id, $message]);
                        $success_message = "Message sent successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error sending message: " . $e->getMessage();
                    }
                }
                break;
                
            case 'send_bulk_message':
                // Handle sending message to all filtered students
                $message = trim($_POST['message']);
                
                if (!empty($message) && !empty($students)) {
                    try {
                        $pdo->beginTransaction();
                        
                        foreach ($students as $student) {
                            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) 
                                                  VALUES (?, ?, ?, NOW())");
                            $stmt->execute([$teacher_id, $student['id'], $message]);
                        }
                        
                        $pdo->commit();
                        $success_message = "Message sent to all ".count($students)." students!";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $error_message = "Error sending messages: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Include the header
include '../includes/teacher_header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Student Management</h1>
            <p class="text-muted mb-0">View and manage all students in your classes</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendBulkMessageModal">
                <i class="fas fa-envelope me-1"></i> Send Message to All
            </button>
        </div>
    </div>

    <!-- SweetAlert2 CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- SweetAlert Messages -->
    <?php if (isset($success_message)): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $success_message; ?>',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $error_message; ?>',
            confirmButtonColor: '#d33',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="form-label">Search Students</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="grade" class="form-label">Grade</label>
                        <select class="form-select" id="grade" name="grade">
                            <option value="">All Grades</option>
                            <?php foreach ($available_grades as $grade): ?>
                                <option value="<?php echo $grade; ?>" <?php echo $grade_filter == $grade ? 'selected' : ''; ?>>
                                    Grade <?php echo $grade; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($available_subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="grade" <?php echo $sort_by == 'grade' ? 'selected' : ''; ?>>Grade</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Students (<?php echo count($students); ?>)</h5>
            <div>
                <button class="btn btn-sm btn-outline-secondary" id="toggleView">
                    <i class="fas fa-th-large"></i> Toggle View
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Table View -->
            <div class="table-responsive" id="tableView">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade</th>
                            <th>Subjects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($student['profile_picture']) ? '../' . $student['profile_picture'] : '../assets/images/default-profile.png'; ?>" 
                                                 class="rounded-circle me-3" width="60" height="60" alt="Profile Picture">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($student['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['grade'])): ?>
                                            <span class="badge bg-primary">Grade <?php echo htmlspecialchars($student['grade']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $student['subject_count'] ?? 0; ?> Subjects</span>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-success me-1 send-message-btn" 
                                                    data-student-id="<?php echo $student['id']; ?>"
                                                    data-student-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                                                    title="Send Message">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="py-3">
                                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                        <h5>No students found</h5>
                                        <p class="text-muted">Try adjusting your search or filters</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Grid View (Hidden by default) -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 p-3 d-none" id="gridView">
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="<?php echo !empty($student['profile_picture']) ? '../' . $student['profile_picture'] : '../assets/images/default-profile.png'; ?>" 
                                         class="rounded-circle mb-3" width="80" height="80" alt="Profile Picture">
                                    <h5 class="card-title"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($student['email']); ?></p>
                                    
                                    <?php if (!empty($student['grade'])): ?>
                                        <span class="badge bg-primary mb-2">Grade <?php echo htmlspecialchars($student['grade']); ?></span>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-center mt-3 mb-3">
                                        <div class="mx-2">
                                            <div class="fw-bold"><?php echo $student['subject_count'] ?? 0; ?></div>
                                            <div class="text-muted small">Subjects</div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-success me-1 send-message-btn" 
                                                data-student-id="<?php echo $student['id']; ?>"
                                                data-student-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                                                title="Send Message">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-4">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <h5>No students found</h5>
                        <p class="text-muted">Try adjusting your search or filters</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="sendMessageModalLabel">Send Message to <span id="studentName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="student_id" id="messageStudentId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" 
                                      placeholder="Type your message here..." required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Bulk Message Modal -->
    <div class="modal fade" id="sendBulkMessageModal" tabindex="-1" aria-labelledby="sendBulkMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="sendBulkMessageModalLabel">Send Message to All Students</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_bulk_message">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This message will be sent to all <?php echo count($students); ?> students matching your current filters.
                        </div>
                        
                        <div class="mb-3">
                            <label for="bulkMessage" class="form-label">Message</label>
                            <textarea class="form-control" id="bulkMessage" name="message" rows="5" 
                                      placeholder="Type your message here..." required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Send to All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Toggle between table and grid view
    document.getElementById('toggleView').addEventListener('click', function() {
        const tableView = document.getElementById('tableView');
        const gridView = document.getElementById('gridView');
        const toggleBtn = document.getElementById('toggleView');
        
        if (tableView.classList.contains('d-none')) {
            // Switch to table view
            tableView.classList.remove('d-none');
            gridView.classList.add('d-none');
            toggleBtn.innerHTML = '<i class="fas fa-th-large"></i> Grid View';
        } else {
            // Switch to grid view
            tableView.classList.add('d-none');
            gridView.classList.remove('d-none');
            toggleBtn.innerHTML = '<i class="fas fa-table"></i> Table View';
        }
    });

    // Handle send message buttons
    document.querySelectorAll('.send-message-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            
            document.getElementById('messageStudentId').value = studentId;
            document.getElementById('studentName').textContent = studentName;
            
            const modal = new bootstrap.Modal(document.getElementById('sendMessageModal'));
            modal.show();
        });
    });
    </script>
</div>

<?php
// Include the footer
include '../includes/teacher_footer.php';
?>