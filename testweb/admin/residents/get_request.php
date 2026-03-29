<?php
// admin/residents/get_request.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include database connection
require_once dirname(__DIR__, 2) . '/config/database.php';

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if connection is successful
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Fetch request details (removed details field)
$query = "SELECT r.id, r.user_id, r.service_id, r.request_type, r.preferred_day, 
          r.preferred_time, r.schedule_id, r.preferred_date, r.status, 
          r.admin_remarks, r.qr_token, r.request_date, r.processed_date, r.processed_by,
          u.first_name, u.last_name, u.email, u.contact_number, u.username,
          s.service_name, s.fee, s.description as service_description,
          CONCAT(a.first_name, ' ', a.last_name) as processed_by_name
          FROM resident_requests r
          JOIN users u ON r.user_id = u.id
          JOIN services s ON r.service_id = s.id
          LEFT JOIN admins a ON r.processed_by = a.id
          WHERE r.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $request_id);
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    http_response_code(404);
    echo json_encode(['error' => 'Request not found']);
    exit();
}

// Handle remarks column (check if admin_remarks exists, fallback to remarks)
$remarks = '';
if (isset($request['admin_remarks']) && $request['admin_remarks'] !== null) {
    $remarks = $request['admin_remarks'];
}

// Format response (removed details field)
$response = [
    'id' => $request['id'],
    'first_name' => $request['first_name'],
    'last_name' => $request['last_name'],
    'email' => $request['email'],
    'contact_number' => $request['contact_number'] ?? 'N/A',
    'username' => $request['username'],
    'service_name' => $request['service_name'],
    'service_fee' => $request['fee'],
    'service_description' => $request['service_description'] ?? '',
    'request_type' => $request['request_type'] ?? 'online',
    'status' => $request['status'],
    'request_date' => $request['request_date'],
    'preferred_day' => $request['preferred_day'] ?? null,
    'preferred_time' => $request['preferred_time'] ?? null,
    'preferred_date' => $request['preferred_date'] ?? null,
    'admin_remarks' => $remarks,
    'processed_by' => $request['processed_by_name'],
    'processed_date' => $request['processed_date'],
    'qr_token' => $request['qr_token'] ?? null
];

header('Content-Type: application/json');
echo json_encode($response);
?>