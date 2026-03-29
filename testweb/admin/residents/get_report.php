<?php
// admin/residents/get_report.php

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

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid report ID']);
    exit();
}

$query = "SELECT r.*, 
          u.first_name, u.last_name, u.username, u.email, u.contact_number,
          CONCAT(a.first_name, ' ', a.last_name) as resolved_by_name
          FROM resident_reports r
          JOIN users u ON r.user_id = u.id
          LEFT JOIN admins a ON r.resolved_by = a.id
          WHERE r.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit();
}

header('Content-Type: application/json');
echo json_encode($report);
?>