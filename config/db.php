<?php
/**
 * Database Configuration & Connection
 * CSE 391: Programming for the Internet - Assignment 3
 * Car Workshop Appointment System
 */

// Database credentials - customize if needed
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'car_workshop_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a PDO database connection instance.
 * Automatically handles connection and charset.
 */
function getDBConnection($selectDatabase = true) {
    try {
        if ($selectDatabase) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database does not exist yet and we attempted to select it
        if ($selectDatabase && $e->getCode() == 1049) {
            header('Location: ' . getBaseUrl() . '/database/setup.php?error=db_missing');
            exit;
        }
        
        // Return null or rethrow
        throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
    }
}

/**
 * Helper to determine base URL dynamically
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Find the relative path to project root
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dirName = dirname($scriptName);
    
    // Normalize path
    $dirName = str_replace('\\', '/', $dirName);
    
    // If inside a subfolder like /admin, /api, /database, go up
    if (preg_match('#/(admin|api|database|includes|config)$#i', $dirName)) {
        $dirName = preg_replace('#/(admin|api|database|includes|config)$#i', '', $dirName);
    }
    
    $dirName = rtrim($dirName, '/');
    return $protocol . $host . $dirName;
}
?>

