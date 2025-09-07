<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Create users table if it doesn't exist
 */
function createUsersTable() {
    global $pdo;
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('student', 'teacher') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        return true;
    } catch(PDOException $e) {
        error_log("Error creating users table: " . $e->getMessage());
        throw new Exception("Failed to create users table: " . $e->getMessage());
    }
}

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
 * Register a new user
 */
function registerUser($username, $email, $password, $user_type) {
    global $pdo;
    
    if (!isset($pdo)) {
        error_log("Database connection not available in registerUser");
        return "Database connection error";
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
    
    if (!in_array($user_type, ['student', 'teacher'])) {
        return "Invalid user type";
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
 * Login user - Fixed version
 */
function loginUser($email, $password) {
    global $pdo;
    
    if (!isset($pdo)) {
        error_log("Database connection not available in loginUser");
        return "Database connection error";
    }
    
    if (empty($email) || empty($password)) {
        return "Email and password are required";
    }
    
    $email = trim($email);
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password, user_type FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("Login attempt - No user found with email: " . $email);
            return "Invalid email or password";
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
            return "Invalid email or password";
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
 * Redirect if not logged in - Simple version
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Sanitize output for HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Initialize database table if it doesn't exist
try {
    createUsersTable();
} catch(Exception $e) {
    error_log("Failed to initialize database: " . $e->getMessage());
}
?>
