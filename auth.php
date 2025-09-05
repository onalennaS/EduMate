<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config.php';

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
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($user_type)) {
        return "All fields are required";
    }
    
    // Trim inputs
    $username = trim($username);
    $email = trim($email);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long";
    }
    
    // Validate user type
    if (!in_array($user_type, ['student', 'teacher'])) {
        return "Invalid user type";
    }
    
    // Validate username (alphanumeric and underscore only)
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
function loginUser($email, $password) {
    global $pdo;
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        return "Email and password are required";
    }
    
    // Trim email
    $email = trim($email);
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password, user_type FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return true;
        } else {
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
    // Destroy all session data
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
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
 * Redirect if already logged in
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Create users table if it doesn't exist
 */
function createUsersTable() {
    global $pdo;
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
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
        return false;
    }
}



/**
 * Sanitize output for HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Initialize database table when this file is included
if (isset($pdo)) {
    createUsersTable();
}
?>