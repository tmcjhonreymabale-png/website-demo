<?php
// admin/residents/export_reports.php

// Start session first
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
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';

// Build query
$query = "SELECT r.*, 
          u.first_name, u.last_name, u.username, u.email, u.contact_number,
          CONCAT(a.first_name, ' ', a.last_name) as resolved_by_name
          FROM resident_reports r
          JOIN users u ON r.user_id = u.id
          LEFT JOIN admins a ON r.resolved_by = a.id
          WHERE 1=1";

$params = array();

if (!empty($status_filter)) {
    $query .= " AND r.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($priority_filter)) {
    $query .= " AND r.priority = :priority";
    $params[':priority'] = $priority_filter;
}

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR r.subject LIKE :search OR r.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(r.reported_date) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(r.reported_date) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY r.reported_date DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reports_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Report ID',
    'Resident Name',
    'Username',
    'Email',
    'Contact Number',
    'Report Type',
    'Subject',
    'Description',
    'Priority',
    'Status',
    'Reported Date',
    'Resolved Date',
    'Resolved By',
    'Admin Remarks'
]);

// Add data rows
foreach ($reports as $report) {
    fputcsv($output, [
        '#' . str_pad($report['id'], 5, '0', STR_PAD_LEFT),
        ($report['first_name'] ?? '') . ' ' . ($report['last_name'] ?? ''),
        $report['username'] ?? '',
        $report['email'] ?? '',
        $report['contact_number'] ?? 'N/A',
        $report['report_type'] ?? 'N/A',
        $report['subject'] ?? '',
        $report['description'] ?? '',
        ucfirst($report['priority'] ?? 'low'),
        ucfirst(str_replace('-', ' ', $report['status'] ?? 'pending')),
        date('Y-m-d H:i:s', strtotime($report['reported_date'] ?? 'now')),
        $report['resolved_date'] ? date('Y-m-d H:i:s', strtotime($report['resolved_date'])) : 'Not resolved',
        $report['resolved_by_name'] ?? 'N/A',
        $report['admin_remarks'] ?? ''
    ]);
}

fclose($output);
exit();
?>