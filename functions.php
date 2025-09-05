<?php
// Utility functions for EduMate application

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M d, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    // Remove any path info to prevent directory traversal
    $filename = basename($filename);
    
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove any non-alphanumeric characters except dots, dashes, and underscores
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    return $filename;
}

/**
 * Get user's gravatar URL
 */
function getGravatar($email, $size = 80, $default = 'mp') {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $seconds = $current - $time;
    
    if ($seconds < 60) {
        return 'just now';
    }
    
    $minutes = round($seconds / 60);
    if ($minutes < 60) {
        return $minutes == 1 ? '1 minute ago' : $minutes . ' minutes ago';
    }
    
    $hours = round($seconds / 3600);
    if ($hours < 24) {
        return $hours == 1 ? '1 hour ago' : $hours . ' hours ago';
    }
    
    $days = round($seconds / 86400);
    if ($days < 30) {
        return $days == 1 ? '1 day ago' : $days . ' days ago';
    }
    
    $months = round($seconds / 2629746);
    if ($months < 12) {
        return $months == 1 ? '1 month ago' : $months . ' months ago';
    }
    
    $years = round($seconds / 31556952);
    return $years == 1 ? '1 year ago' : $years . ' years ago';
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    global $pdo;
    
    try {
        // Create activity log table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Check if string is JSON
 */
function isJson($string) {
    json_decode($string);
    return json_last_error() == JSON_ERROR_NONE;
}

/**
 * Convert bytes to human readable format
 */
function bytesToHuman($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

/**
 * Clean input data
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>