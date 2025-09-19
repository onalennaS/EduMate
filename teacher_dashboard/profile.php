<?php
session_start();

// Set page-specific variables
$page_title = 'Teacher Profile';
$additional_css = '
    .profile-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    }
    .profile-image-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }
    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .profile-edit-icon {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: var(--primary-color);
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
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
';

// Include database connection
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $bio = trim($_POST['bio']);
                $department = trim($_POST['department']);
                
                try {
                    // Update user information
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ?, department = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $bio, $department, $teacher_id]);
                    
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
                $stmt->execute([$teacher_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 8) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$hashed_password, $teacher_id]);
                            
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
                        $filename = 'teacher_' . $teacher_id . '_' . time() . '.' . $file_extension;
                        $destination = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Update database with new profile picture path
                            $profile_picture_path = 'uploads/profile_pictures/' . $filename;
                            
                            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                            $stmt->execute([$profile_picture_path, $teacher_id]);
                            
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
        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$teacher_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default values
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$bio = $user['bio'] ?? '';
$department = $user['department'] ?? 'Teacher';
$profile_picture = $user['profile_picture'] ?? '';

// Get teacher name for display
$teacher_name = '';
if (!empty($first_name) && !empty($last_name)) {
    $teacher_name = $first_name . ' ' . $last_name;
} elseif (!empty($user['username'])) {
    $teacher_name = $user['username'];
} elseif (!empty($user['full_name'])) {
    $teacher_name = $user['full_name'];
}

// Update session with teacher name if not set
if (!isset($_SESSION['user_name']) && !empty($teacher_name)) {
    $_SESSION['user_name'] = $teacher_name;
}

// Get teacher statistics
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

// Total assignments created
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_assignments
                           FROM assignments
                           WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $total_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total_assignments'];
} catch (PDOException $e) {
    $total_assignments = 0;
}

// Include the header
include_once '../includes/teacher_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Teacher Profile</h1>
        <p class="text-muted mb-0">Manage your account information and settings</p>
    </div>
</div>
<!-- SweetAlert2 -->
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
        title: 'Error!',
        text: '<?php echo $error_message; ?>',
        confirmButtonColor: '#d33',
        confirmButtonText: 'OK'
    });
</script>
<?php endif; ?>


<!-- Profile Header -->
<div class="profile-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-3 text-center mb-3 mb-md-0">
            <div class="profile-image-container">
                <img src="<?php echo !empty($profile_picture) ? '../' . $profile_picture : '../assets/images/default-profile.png'; ?>" 
                     class="profile-image" alt="Profile Picture" id="profileImage">
                <div class="profile-edit-icon" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-center text-md-start">
            <h2 class="mb-1"><?php echo htmlspecialchars($teacher_name); ?></h2>
            <p class="mb-1 opacity-90"><?php echo htmlspecialchars($department); ?></p>
            <p class="mb-2 opacity-90"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($email); ?></p>
            <?php if (!empty($bio)): ?>
            <p class="mb-0 opacity-90"><?php echo htmlspecialchars($bio); ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-3 text-center text-md-end">
            <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
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
                    <i class="fas fa-users text-primary" style="font-size: 1.5rem;"></i>
                </div>
                <div class="fw-bold h4 mb-0"><?php echo $total_students; ?></div>
                <div class="text-muted">Students</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-book-open text-success" style="font-size: 1.5rem;"></i>
                </div>
                <div class="fw-bold h4 mb-0"><?php echo $subjects_taught; ?></div>
                <div class="text-muted">Subjects</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-tasks text-info" style="font-size: 1.5rem;"></i>
                </div>
                <div class="fw-bold h4 mb-0"><?php echo $total_assignments; ?></div>
                <div class="text-muted">Assignments</div>
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
                            <i class="fas fa-user-edit text-primary mb-2" style="font-size: 4rem; opacity: 0.3;"></i>
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
    
</div>

<div class="mb-3">
    <label for="department" class="form-label fw-bold">Department</label>
    <input type="text" class="form-control" id="department" name="department" 
           value="<?php echo htmlspecialchars($department); ?>">
</div>

<div class="mb-4">
    <label for="bio" class="form-label fw-bold">Bio</label>
    <textarea class="form-control" id="bio" name="bio" rows="4" 
              placeholder="Tell students a bit about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
    <div class="form-text">A short description about yourself and your teaching philosophy</div>
</div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
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
                            <i class="fas fa-lock text-primary mb-2" style="font-size: 4rem; opacity: 0.3;"></i>
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
    <div class="modal-dialog">
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
                             class="rounded-circle mb-3" id="profilePreview" style="width: 150px; height: 150px; object-fit: cover;">
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
    
    // Announce page load for screen readers
    if (document.body.getAttribute('data-screen-reader') === 'true') {
        setTimeout(() => {
            announceToScreenReader('Teacher profile page loaded. You can update your personal information and change your password.');
        }, 1000);
    }
});
</script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    .profile-header * {
    color: #000 !important;
}
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


    </style>
<?php
// Include the footer
include_once '../includes/teacher_footer.php';
?>