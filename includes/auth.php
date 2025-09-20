<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, user_type, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Register a new user (students only - teachers are added by admin)
 */
function registerUser($username, $email, $password, $user_type) {
    global $pdo;
    
    // Only allow student registration
    if ($user_type !== 'student') {
        return "Only student registration is allowed. Teachers are added by administrators.";
    }
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($user_type)) {
        return "All fields are required";
    }
    
    $username = trim($username);
    $email = trim($email);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long";
    }
    
    if (!in_array($user_type, ['student'])) {
        return "Invalid user type for registration";
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        return "Username must be 3-20 characters long and contain only letters, numbers, and underscores";
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return "Username already exists";
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return "Email already exists";
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $hashedPassword, $user_type]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return "Registration failed. Please try again.";
    }
}

/**
 * Login user
 */
function loginUser($email, $password, $user_type) {
    global $pdo;
    
    if (empty($email) || empty($password) || empty($user_type)) {
        return "Email, password and user type are required";
    }
    
    $email = trim($email);
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password, user_type FROM users WHERE email = ? AND user_type = ?");
        $stmt->execute([$email, $user_type]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("Login attempt - No user found with email: " . $email . " and type: " . $user_type);
            return "Invalid credentials";
        }
        
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            error_log("Successful login for user: " . $user['email']);
            return true;
        } else {
            error_log("Login attempt - Wrong password for email: " . $email);
            return "Invalid credentials";
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return "Login failed. Please try again.";
    }
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    return true;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_type'] === $role;
}

/**
 * Require specific role for access
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Add a new teacher (admin function)
 */
function addTeacher($username, $email, $password, $added_by) {
    global $pdo;
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        return "All fields are required";
    }
    
    $username = trim($username);
    $email = trim($email);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long";
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        return "Username must be 3-20 characters long and contain only letters, numbers, and underscores";
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return "Username already exists";
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return "Email already exists";
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new teacher
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, created_at) VALUES (?, ?, ?, 'teacher', NOW())");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Add teacher error: " . $e->getMessage());
        return "Failed to add teacher. Please try again.";
    }
}

/**
 * Get all students
 */
function getAllStudents() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.created_at, sp.grade_level 
                              FROM users u 
                              LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                              WHERE u.user_type = 'student' 
                              ORDER BY u.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting students: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all teachers
 */
function getAllTeachers() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, created_at, last_login 
                              FROM users 
                              WHERE user_type = 'teacher' 
                              ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting teachers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get student profile
 */
function getStudentProfile($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.created_at, 
                              sp.grade_level, sp.id_copy_path, sp.report_path 
                              FROM users u 
                              LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                              WHERE u.id = ? AND u.user_type = 'student'");
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting student profile: " . $e->getMessage());
        return null;
    }
}

/**
 * Update student profile
 */
function updateStudentProfile($student_id, $grade_level, $id_copy_path = null, $report_path = null) {
    global $pdo;
    try {
        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$student_id]);
        
        if ($stmt->fetch()) {
            // Update existing profile
            $stmt = $pdo->prepare("UPDATE student_profiles SET grade_level = ?, id_copy_path = ?, report_path = ? WHERE user_id = ?");
            $stmt->execute([$grade_level, $id_copy_path, $report_path, $student_id]);
        } else {
            // Insert new profile
            $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, grade_level, id_copy_path, report_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $grade_level, $id_copy_path, $report_path]);
        }
        
        return true;
    } catch(PDOException $e) {
        error_log("Error updating student profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Get subjects by grade level
 */
function getSubjectsByGrade($grade_level) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE grade_level = ? ORDER BY name");
        $stmt->execute([$grade_level]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting subjects: " . $e->getMessage());
        return [];
    }
}

/**
 * Enroll student in subject
 */
function enrollStudent($student_id, $subject_id, $enrolled_by) {
    global $pdo;
    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ?");
        $stmt->execute([$student_id, $subject_id]);
        
        if ($stmt->fetch()) {
            return "Student is already enrolled in this subject";
        }
        
        // Enroll student
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject_id, enrolled_by) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $subject_id, $enrolled_by]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Error enrolling student: " . $e->getMessage());
        return "Failed to enroll student";
    }
}

/**
 * Get student enrollments
 */
function getStudentEnrollments($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT e.*, s.name as subject_name, s.grade_level 
                              FROM enrollments e 
                              JOIN subjects s ON e.subject_id = s.id 
                              WHERE e.student_id = ? 
                              ORDER BY s.name");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting enrollments: " . $e->getMessage());
        return [];
    }
}

// Initialize database tables if they don't exist
try {
    // Tables are created in database.php
} catch(Exception $e) {
    error_log("Failed to initialize database: " . $e->getMessage());
}
?>