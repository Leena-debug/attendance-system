<?php
// config.php - Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_attendance');
define('DB_CHARSET', 'utf8mb4');

// Optional: Log file path
define('LOG_FILE', __DIR__ . '/database_errors.log');

//Display errors for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>