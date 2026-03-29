<?php
// admin/residents/export_residents.php

// Start session first - BEFORE any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT u.*, ri.* 
          FROM users u
          LEFT JOIN resident_info ri ON u.id = ri.user_id
          WHERE u.user_type = 'resident'";

$params = array();

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search OR u.address LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status_filter)) {
    if ($status_filter == 'online') {
        $query .= " AND u.is_online = 1 AND u.last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    } elseif ($status_filter == 'offline') {
        $query .= " AND (u.is_online = 0 OR u.last_activity <= DATE_SUB(NOW(), INTERVAL 15 MINUTE))";
    }
}

$query .= " ORDER BY u.last_name, u.first_name";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="residents_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'ID',
    'Username',
    'First Name',
    'Last Name',
    'Email',
    'Contact Number',
    'Address',
    'Birth Date',
    'Age',
    'Gender',
    'Civil Status',
    'Occupation',
    'Status',
    'Last Activity',
    'Registered Date'
]);

// Add data rows
foreach ($residents as $resident) {
    $is_online = ($resident['is_online'] ?? 0) && strtotime($resident['last_activity'] ?? '2000-01-01') > time() - 900;
    
    fputcsv($output, [
        '#' . str_pad($resident['id'], 5, '0', STR_PAD_LEFT),
        $resident['username'] ?? '',
        $resident['first_name'] ?? '',
        $resident['last_name'] ?? '',
        $resident['email'] ?? '',
        $resident['contact_number'] ?? 'N/A',
        $resident['address'] ?? '',
        $resident['birth_date'] ?? '',
        $resident['age'] ?? '',
        ucfirst($resident['gender'] ?? ''),
        ucfirst($resident['civil_status'] ?? ''),
        $resident['occupation'] ?? '',
        $is_online ? 'Online' : 'Offline',
        $resident['last_activity'] ?? '',
        $resident['created_at'] ?? ''
    ]);
}

fclose($output);
exit();
?>