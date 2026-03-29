<?php
// admin/residents/generate_qr.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if user exists
$query = "SELECT id, first_name, last_name FROM users WHERE id = :id AND user_type = 'resident'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

// Check if QR code already exists
$check_query = "SELECT id FROM qr_codes WHERE user_id = :user_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':user_id', $user_id);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    // Update existing QR code
    $update_query = "UPDATE qr_codes SET generated_date = NOW(), is_active = 1 WHERE user_id = :user_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':user_id', $user_id);
    $update_stmt->execute();
} else {
    // Create new QR code
    $qr_data = "BARANGAY-RESIDENT-" . str_pad($user_id, 5, '0', STR_PAD_LEFT) . "-" . date('Ymd');
    $expires_date = date('Y-m-d', strtotime('+1 year'));
    
    $insert_query = "INSERT INTO qr_codes (user_id, qr_code_data, expires_date) VALUES (:user_id, :data, :expires)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':user_id', $user_id);
    $insert_stmt->bindParam(':data', $qr_data);
    $insert_stmt->bindParam(':expires', $expires_date);
    $insert_stmt->execute();
}

// Log the action (try-catch in case table doesn't exist)
try {
    $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                  VALUES (:admin_id, 'GENERATE_QR', :desc, :ip)";
    $log_stmt = $db->prepare($log_query);
    $desc = "Generated QR code for resident: " . $user['first_name'] . " " . $user['last_name'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
    $log_stmt->bindParam(':desc', $desc);
    $log_stmt->bindParam(':ip', $ip);
    $log_stmt->execute();
} catch (Exception $e) {
    // Log table might not exist, continue
}

echo json_encode(['success' => true, 'message' => 'QR Code generated successfully']);
?>