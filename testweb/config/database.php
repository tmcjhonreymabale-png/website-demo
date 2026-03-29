<?php
// config/database.php

// Start session if not already started (for helper functions that use session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Database {
    private $host = "localhost";
    private $db_name = "barangay_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Helper function to check if user is logged in (resident)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if admin is logged in
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to get current admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Helper function to log admin actions
function logAdminAction($admin_id, $action, $description, $db = null) {
    if (!$db) {
        $database = new Database();
        $db = $database->getConnection();
    }
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (:admin_id, :action, :desc, :ip)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':ip', $ip);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}
?>