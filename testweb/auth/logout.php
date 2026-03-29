<?php
// auth/logout.php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Function to log resident activity
function logResidentActivity($db, $user_id, $action, $description, $ip_address = null) {
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    try {
        $query = "INSERT INTO resident_logs (user_id, action, description, ip_address) 
                  VALUES (:user_id, :action, :description, :ip_address)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $ip_address
        ]);
    } catch (Exception $e) {
        // Log error silently
        error_log("Failed to log resident activity: " . $e->getMessage());
    }
}

// Update user online status and log logout
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Log logout activity
        $description = "User " . $_SESSION['username'] . " logged out";
        logResidentActivity($db, $_SESSION['user_id'], 'LOGOUT', $description);
        
        $update = "UPDATE users SET is_online = 0, last_activity = NOW() WHERE id = :id";
        $stmt = $db->prepare($update);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
    } catch (Exception $e) {
        // Column might not exist, continue logout
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to home page
header('Location: ../index.php');
exit();
?>