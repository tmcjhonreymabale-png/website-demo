<?php
// admin/logout.php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_id'])) {
    // Log the logout action
    $database = new Database();
    $db = $database->getConnection();
    logAdminAction($_SESSION['admin_id'], 'LOGOUT', 'Admin logged out', $db);
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header('Location: login.php');
exit();
?>