<?php
require_once '../config/config.php';

function checkAuth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        http_response_code(403);
        die("
            <div style='text-align: center; padding: 50px; background: #fce4ec;'>
                <h1 style='color: #e91e63;'>⚠️ Access Denied</h1>
                <p>You don't have permission to access this page.</p>
                <a href='../auth/login.php' style='color: #e91e63;'>Return to Login</a>
            </div>
        ");
    }
    
    return true;
}

function isProfessor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'professor';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
?>