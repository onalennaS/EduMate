<?php
// Database configuration
$host = 'localhost';
$dbname = 'edumate_db';
$username = 'root';
$password = '';

// Set timezone
date_default_timezone_set('UTC');

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Check if database doesn't exist
    if ($e->getCode() == 1049) {
        try {
            // Try to create database
            $pdo_temp = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Now connect to the new database
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $e2) {
            die("Could not connect to the database or create database '$dbname': " . $e2->getMessage());
        }
    } else {
        die("Could not connect to the database: " . $e->getMessage());
    }
}
?>