<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

// Application settings
define('APP_NAME', 'Attendance Management System');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>