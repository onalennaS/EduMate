<?php
// student_dashboard/student_profile.php
session_start();

// Include database connection
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $grade = trim($_POST['grade']);
                
                try {
                    // Update user information
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, grade = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $grade, $student_id]);
                    
                    // Update session variables
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['user_email'] = $email;
                    
                    $success_message = "Profile updated successfully!";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // Duplicate entry
                        $error_message = "Email '$email' already exists. Please use a different email.";
                    } else {
                        $error_message = "Error updating profile: " . $e->getMessage();
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$student_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 8) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$hashed_password, $student_id]);
                            
                            $success_message = "Password changed successfully!";
                        } else {
                            $error_message = "New password must be at least 8 characters long.";
                        }
                    } else {
                        $error_message = "New passwords do not match.";
                    }
                } else {
                    $error_message = "Current password is incorrect.";
                }
                break;
                
            case 'update_profile_picture':
                // Handle profile picture upload
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_picture'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                        $upload_dir = '../uploads/profile_pictures/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'student_' . $student_id . '_' . time() . '.' . $file_extension;
                        $destination = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Update database with new profile picture path
                            $profile_picture_path = 'uploads/profile_pictures/' . $filename;
                            
                            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                            $stmt->execute([$profile_picture_path, $student_id]);
                            
                            $success_message = "Profile picture updated successfully!";
                        } else {
                            $error_message = "Error uploading file.";
                        }
                    } else {
                        $error_message = "Invalid file type or size. Please upload JPEG, PNG, or GIF images under 2MB.";
                    }
                } else {
                    $error_message = "Please select a valid image file.";
                }
                break;

            case 'upload_academic_report':
                // Handle academic report upload
                if (isset($_FILES['academic_report']) && $_FILES['academic_report']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['academic_report'];
                    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 10 * 1024 * 1024; // 10MB
                    
                    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                        $upload_dir = '../uploads/academic_reports/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'report_' . $student_id . '_' . time() . '.' . $file_extension;
                        $destination = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Update database with new academic report path
                            $academic_report_path = 'uploads/academic_reports/' . $filename;
                            
                            $stmt = $pdo->prepare("UPDATE users SET academic_report = ? WHERE id = ?");
                            $stmt->execute([$academic_report_path, $student_id]);
                            
                            $success_message = "Academic report uploaded successfully!";
                        } else {
                            $error_message = "Error uploading academic report.";
                        }
                    } else {
                        $error_message = "Invalid file type or size. Please upload PDF or image files under 10MB.";
                    }
                } else {
                    $error_message = "Please select a valid academic report file.";
                }
                break;

            case 'upload_id_copy':
                // Handle ID copy upload
                if (isset($_FILES['id_copy']) && $_FILES['id_copy']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['id_copy'];
                    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                        $upload_dir = '../uploads/id_copies/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'id_' . $student_id . '_' . time() . '.' . $file_extension;
                        $destination = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Update database with new ID copy path
                            $id_copy_path = 'uploads/id_copies/' . $filename;
                            
                            $stmt = $pdo->prepare("UPDATE users SET id_copy = ? WHERE id = ?");
                            $stmt->execute([$id_copy_path, $student_id]);
                            
                            $success_message = "ID copy uploaded successfully!";
                        } else {
                            $error_message = "Error uploading ID copy.";
                        }
                    } else {
                        $error_message = "Invalid file type or size. Please upload PDF or image files under 5MB.";
                    }
                } else {
                    $error_message = "Please select a valid ID copy file.";
                }
                break;

            case 'delete_document':
                $document_type = $_POST['document_type'];
                $allowed_types = ['academic_report', 'id_copy'];
                
                if (in_array($document_type, $allowed_types)) {
                    // Get current file path
                    $stmt = $pdo->prepare("SELECT $document_type FROM users WHERE id = ?");
                    $stmt->execute([$student_id]);
                    $current_file = $stmt->fetch(PDO::FETCH_ASSOC)[$document_type];
                    
                    // Delete file from server
                    if (!empty($current_file) && file_exists('../' . $current_file)) {
                        unlink('../' . $current_file);
                    }
                    
                    // Update database
                    $stmt = $pdo->prepare("UPDATE users SET $document_type = NULL WHERE id = ?");
                    $stmt->execute([$student_id]);
                    
                    $success_message = ucfirst(str_replace('_', ' ', $document_type)) . " deleted successfully!";
                } else {
                    $error_message = "Invalid document type.";
                }
                break;
        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default values
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$grade = $user['grade'] ?? '';
$profile_picture = $user['profile_picture'] ?? '';
$academic_report = $user['academic_report'] ?? '';
$id_copy = $user['id_copy'] ?? '';

// Get student name for display
$student_name = '';
if (!empty($first_name) && !empty($last_name)) {
    $student_name = $first_name . ' ' . $last_name;
} elseif (!empty($user['username'])) {
    $student_name = $user['username'];
} elseif (!empty($user['full_name'])) {
    $student_name = $user['full_name'];
}

// Update session with student name if not set
if (!isset($_SESSION['user_name']) && !empty($student_name)) {
    $_SESSION['user_name'] = $student_name;
}

// Get student statistics
// Total subjects enrolled
$stmt = $pdo->prepare("SELECT COUNT(*) as total_subjects
                       FROM subject_enrollments
                       WHERE student_id = ? AND status = 'active'");
$stmt->execute([$student_id]);
$total_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_subjects'];

// Total assignments completed
try {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) as completed_assignments
                           FROM assignments a
                           JOIN assignment_submissions s ON a.id = s.assignment_id
                           WHERE s.student_id = ? AND s.status = 'submitted'");
    $stmt->execute([$student_id]);
    $completed_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['completed_assignments'];
} catch (PDOException $e) {
    $completed_assignments = 0;
}

// Average grade
try {
    $stmt = $pdo->prepare("SELECT AVG(s.grade) as average_grade
                           FROM assignment_submissions s
                           WHERE s.student_id = ? AND s.grade IS NOT NULL");
    $stmt->execute([$student_id]);
    $average_grade = $stmt->fetch(PDO::FETCH_ASSOC)['average_grade'];
    $average_grade = $average_grade ? round($average_grade, 1) : 'N/A';
} catch (PDOException $e) {
    $average_grade = 'N/A';
}

// Include the header
include '../includes/student_header.php';

// Set page-specific CSS
$additional_css = '
    .profile-header {
        position: relative;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        overflow: hidden;
        min-height: 180px;
    }
    .profile-header::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("../assets/images/profile-bg-pattern.png") no-repeat center center;
        background-size: cover;
        opacity: 0.1;
    }
    .profile-header-content {
        position: relative;
        z-index: 2;
    }
    .profile-image-container {
        position: relative;
        width: 90px;
        height: 90px;
        margin: 0 auto;
    }
    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .profile-edit-icon {
        position: absolute;
        bottom: 3px;
        right: 3px;
        background: var(--primary-color);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        z-index: 3;
        font-size: 0.7rem;
    }
    .profile-info {
        padding: 10px 0;
    }
    .profile-info h2 {
        font-size: 1.5rem;
        margin-bottom: 0.3rem;
    }
    .profile-info p {
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
    }
    .btn-change-photo {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        backdrop-filter: blur(10px);
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }
    .btn-change-photo:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
    }
    .stat-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        border: 0;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .nav-pills .nav-link {
        color: #495057;
        font-weight: 500;
    }
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
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    .document-item {
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    .document-item.has-file {
        background: #e8f5e8;
        border-color: #28a745;
        border-style: solid;
    }
    .document-item:hover {
        border-color: var(--primary-color);
        background: #f0f7ff;
    }
    .document-preview {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .file-info {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0.75rem;
        margin-top: 1rem;
        text-align: left;
    }
    .delete-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s ease;
    }
    .delete-btn:hover {
        opacity: 1;
    }
    @media (max-width: 768px) {
        .profile-image-container {
            width: 80px;
            height: 80px;
        }
        .profile-edit-icon {
            width: 22px;
            height: 22px;
            bottom: 2px;
            right: 2px;
        }
        .profile-info h2 {
            font-size: 1.3rem;
        }
    }
';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Student Profile</h1>
        <p class="text-muted mb-0">Manage your account information and settings</p>
    </div>
</div>

<!-- SweetAlert2 CSS & JS -->
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

<!-- Profile Header -->
<div class="profile-header mb-4">
    <div class="profile-header-content">
        <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <div class="profile-image-container">
                    <img src="<?php echo !empty($profile_picture) ? '../' . $profile_picture : '../assets/images/default-profile.png'; ?>" 
                         class="profile-image" alt="Profile Picture" id="profileImage">
                    <div class="profile-edit-icon" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-7 text-center text-md-start profile-info">
                <h2 class="mb-1"><?php echo htmlspecialchars($student_name); ?></h2>
                <p class="mb-1 opacity-90">Student</p>
                <?php if (!empty($grade)): ?>
                <p class="mb-1 opacity-90">Grade <?php echo htmlspecialchars($grade); ?></p>
                <?php endif; ?>
                <p class="mb-0 opacity-90"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($email); ?></p>
            </div>
            <div class="col-md-3 text-center text-md-end">
                <button class="btn btn-change-photo" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                    <i class="fas fa-camera me-1"></i> Change Photo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-book text-primary" style="font-size: 1.5rem;"></i>
                </div>
                <div class="fw-bold h4 mb-0"><?php echo $total_subjects; ?></div>
                <div class="text-muted">Subjects</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                </div>
                <div class="fw-bold h4 mb-0"><?php echo $completed_assignments; ?></div>
                <div class="text-muted">Assignments Completed</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-chart-line text-info" style="font-size: 1.5rem;"></i>
                </div>
                <div class="fw-bold h4 mb-0"><?php echo $average_grade; ?></div>
                <div class="text-muted">Average Grade</div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Content -->
<div class="content-card">
    <div class="card-header-custom">
        <ul class="nav nav-pills card-header-pills" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="personal-tab" data-bs-toggle="pill" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                    <i class="fas fa-user me-2"></i>Personal Information
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="documents-tab" data-bs-toggle="pill" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                    <i class="fas fa-file-upload me-2"></i>Documents
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                    <i class="fas fa-lock me-2"></i>Change Password
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="profileTabsContent">
            <!-- Personal Information Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3">Personal Details</h6>
                            <p class="text-muted mb-4">
                                Update your personal information to keep your profile current.
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-user-edit text-primary mb-2" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label fw-bold">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required 
                                   value="<?php echo htmlspecialchars($first_name); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label fw-bold">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                   value="<?php echo htmlspecialchars($last_name); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-bold">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="grade" class="form-label fw-bold">Grade</label>
                            <select class="form-select" id="grade" name="grade">
                                <option value="">Select Grade</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($grade == $i) ? 'selected' : ''; ?>>
                                        Grade <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Documents Tab -->
            <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h6 class="text-primary mb-3">Academic Documents</h6>
                        <p class="text-muted mb-4">
                            Upload your academic reports and identification documents for verification purposes.
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-file-upload text-primary mb-2" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>

                <div class="row">
                    <!-- Academic Report Upload -->
                    <div class="col-md-6 mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-graduation-cap me-2 text-info"></i>Academic Report
                        </h6>
                        
                        <div class="document-item <?php echo !empty($academic_report) ? 'has-file' : ''; ?>" style="position: relative;">
                            <?php if (!empty($academic_report)): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_document">
                                    <input type="hidden" name="document_type" value="academic_report">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this document?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                
                                <?php 
                                $file_extension = strtolower(pathinfo($academic_report, PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="../<?php echo $academic_report; ?>" class="document-preview" alt="Academic Report">
                                <?php else: ?>
                                    <i class="fas fa-file-pdf text-danger" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                                <?php endif; ?>
                                
                                <div class="file-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Academic Report</strong><br>
                                            <small class="text-muted">
                                                <?php echo strtoupper($file_extension); ?> • 
                                                <?php echo date('M d, Y', filemtime('../' . $academic_report)); ?>
                                            </small>
                                        </div>
                                        <a href="../<?php echo $academic_report; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-cloud-upload-alt text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p class="text-muted mb-2">No academic report uploaded</p>
                                <small class="text-muted">Click below to upload your latest academic report</small>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="action" value="upload_academic_report">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="academic_report" accept=".pdf,.jpg,.jpeg,.png,.gif" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-1"></i>Upload
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">PDF or Image files, max 10MB</small>
                            </form>
                        </div>
                    </div>

                    <!-- ID Copy Upload -->
                    <div class="col-md-6 mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-id-card me-2 text-warning"></i>ID Copy
                        </h6>
                        
                        <div class="document-item <?php echo !empty($id_copy) ? 'has-file' : ''; ?>" style="position: relative;">
                            <?php if (!empty($id_copy)): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_document">
                                    <input type="hidden" name="document_type" value="id_copy">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this document?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                
                                <?php 
                                $file_extension = strtolower(pathinfo($id_copy, PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="../<?php echo $id_copy; ?>" class="document-preview" alt="ID Copy">
                                <?php else: ?>
                                    <i class="fas fa-file-pdf text-danger" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                                <?php endif; ?>
                                
                                <div class="file-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>ID Copy</strong><br>
                                            <small class="text-muted">
                                                <?php echo strtoupper($file_extension); ?> • 
                                                <?php echo date('M d, Y', filemtime('../' . $id_copy)); ?>
                                            </small>
                                        </div>
                                        <a href="../<?php echo $id_copy; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-cloud-upload-alt text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p class="text-muted mb-2">No ID copy uploaded</p>
                                <small class="text-muted">Click below to upload a copy of your identification</small>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="action" value="upload_id_copy">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="id_copy" accept=".pdf,.jpg,.jpeg,.png,.gif" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-1"></i>Upload
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">PDF or Image files, max 5MB</small>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Document Guidelines -->
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>Document Upload Guidelines
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Academic Report:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Latest term/semester report card</li>
                              
                                <li>Maximum file size: 10MB</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong>ID Copy:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Student ID card</li>
                                <li>Birth certificate</li>
                                <li>Passport or national ID</li>
                                <li>Maximum file size: 5MB</li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <small class="mb-0">
                        <strong>Note:</strong> All documents are securely stored and used only for verification purposes. 
                        Accepted formats: PDF, JPG, PNG, GIF.
                    </small>
                </div>
            </div>
            
            <!-- Change Password Tab -->
            <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3">Change Password</h6>
                            <p class="text-muted mb-4">
                                Update your password to keep your account secure.
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-lock text-primary mb-2" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="current_password" class="form-label fw-bold">Current Password *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label fw-bold">New Password *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Must be at least 8 characters long</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label fw-bold">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Password Requirements:</strong> Your password must be at least 8 characters long and should include a mix of letters, numbers, and symbols for better security.
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1" aria-labelledby="profilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="profilePictureModalLabel">
                    <i class="fas fa-camera me-1"></i> Update Profile Picture
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile_picture">
                
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <img src="<?php echo !empty($profile_picture) ? '../' . $profile_picture : '../assets/images/default-profile.png'; ?>" 
                             class="rounded-circle mb-3" id="profilePreview" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label fw-bold">Select New Image</label>
                        <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        <div class="form-text">Allowed formats: JPG, PNG, GIF. Max size: 2MB</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Upload Picture
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview profile picture before upload
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
            // Also update the main profile image preview
            document.getElementById('profileImage').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Initialize tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const profileTabs = document.getElementById('profileTabs');
    if (profileTabs) {
        const triggerTabList = [].slice.call(profileTabs.querySelectorAll('button[data-bs-toggle="pill"]'));
        triggerTabList.forEach(function(triggerEl) {
            triggerEl.addEventListener('click', function(e) {
                e.preventDefault();
                const target = e.target.getAttribute('data-bs-target');
                const tab = new bootstrap.Tab(e.target);
                tab.show();
            });
        });
    }
    
    // File upload progress indication
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const submitBtn = this.parentNode.querySelector('button[type="submit"]');
            if (this.files.length > 0 && submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ready to Upload';
            }
        });
    });
    
    // Form submission handling
    const uploadForms = document.querySelectorAll('form[enctype="multipart/form-data"]');
    uploadForms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
            }
        });
    });
    
    // Announce page load for screen readers
    if (document.body.getAttribute('data-screen-reader') === 'true') {
        setTimeout(() => {
            announceToScreenReader('Student profile page loaded. You can update your personal information, upload documents, and change your password.');
        }, 1000);
    }
});
</script>

<style>
    /* Make all nav links in the profile tabs black */
.content-card .nav-pills .nav-link {
    color: #000 !important;
}

/* Also make the active nav-link black */
.content-card .nav-pills .nav-link.active {
    color: #000 !important;
    /* If there is a background color you want to keep, else also override that */
    background-color: transparent !important;
}

/* If hover or focus change color, also override those */
.content-card .nav-pills .nav-link:hover,
.content-card .nav-pills .nav-link:focus {
    color: #000 !important;
}

    /* Profile Header */
.profile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: black;
    border-radius: 12px;
    padding: 1rem 1.5rem;
}

/* Profile Image */
.profile-image-container {
    position: relative;
    display: inline-block;
    width: 120px;   /* reduced width */
    height: 120px;  /* reduced height */
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #fff;
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Camera/Edit Icon */
.profile-edit-icon {
    position: absolute;
    bottom: 6px;
    right: 6px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border-radius: 50%;
    padding: 6px;
    font-size: 14px;
    cursor: pointer;
}

/* Text Styling */
.profile-info h2 {
    font-size: 1.5rem;
    font-weight: 600;
}

.profile-info p {
    font-size: 0.95rem;
}

/* Button */
.btn-change-photo {
    background: #fff;
    color: var(--primary-color);
    font-weight: 500;
    border-radius: 20px;
    padding: 6px 14px;
}

/* Document Upload Styling */
.document-item {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.document-item.has-file {
    justify-content: flex-start;
    align-items: stretch;
}

.file-info {
    margin-top: auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .document-item {
        min-height: 250px;
        margin-bottom: 1rem;
    }
    
    .document-preview {
        max-height: 150px;
    }
}
</style>

<?php
// Include the footer
include '../includes/student_footer.php';
?>