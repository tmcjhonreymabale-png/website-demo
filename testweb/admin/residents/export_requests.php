<?php
// admin/residents/export_requests.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once dirname(__DIR__, 2) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('Database connection failed');
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$service_filter = isset($_GET['service']) ? (int)$_GET['service'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['from']) ? trim($_GET['from']) : '';
$date_to = isset($_GET['to']) ? trim($_GET['to']) : '';

// Build query (removed processing_time as it doesn't exist)
$query = "SELECT r.id, r.user_id, r.service_id, r.request_type, r.preferred_day, 
          r.preferred_time, r.schedule_id, r.preferred_date, r.status, 
          r.admin_remarks, r.qr_token, r.request_date, r.processed_date, r.processed_by,
          u.first_name, u.last_name, u.username, u.email, u.contact_number,
          s.service_name, s.fee as service_fee,
          CONCAT(a.first_name, ' ', a.last_name) as processed_by_name
          FROM resident_requests r
          JOIN users u ON r.user_id = u.id
          JOIN services s ON r.service_id = s.id
          LEFT JOIN admins a ON r.processed_by = a.id
          WHERE 1=1";

$params = array();

if (!empty($status_filter)) {
    $query .= " AND r.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($service_filter) && $service_filter > 0) {
    $query .= " AND r.service_id = :service";
    $params[':service'] = $service_filter;
}

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.username LIKE :search OR s.service_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(r.request_date) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(r.request_date) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY r.request_date DESC";

// Prepare and execute query with error handling
try {
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Export Error: " . $e->getMessage());
    die("Error preparing export data. Please try again.");
}

if (empty($requests)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>No Data to Export</title>
        <meta charset="utf-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: #f5f5f5;
            }
            .message {
                background: white;
                border-radius: 8px;
                padding: 30px;
                display: inline-block;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h3 {
                color: #856404;
                margin-bottom: 10px;
            }
            a {
                color: #007bff;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="message">
            <h3>No Requests Found</h3>
            <p>There are no requests matching your filters to export.</p>
            <p><a href="requests.php">← Back to Requests</a></p>
        </div>
    </body>
    </html>';
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="requests_export_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
header('Pragma: public');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Headers
$headers = [
    'Request ID',
    'Resident Name',
    'Username',
    'Email',
    'Contact Number',
    'Service',
    'Service Fee',
    'Request Type',
    'Status',
    'Request Date',
    'Request Time',
    'Preferred Date',
    'Preferred Time',
    'Preferred Day',
    'Admin Remarks',
    'Processed Date',
    'Processed By'
];

fputcsv($output, $headers);

// Helper function to safely get values
function safeValue($data, $key, $default = '') {
    return isset($data[$key]) && $data[$key] !== null && $data[$key] !== '' ? $data[$key] : $default;
}

foreach ($requests as $request) {
    // Get remarks
    $remarks = safeValue($request, 'admin_remarks');
    
    // Format request date and time
    $request_date = '';
    $request_time = '';
    if (!empty($request['request_date']) && $request['request_date'] != '0000-00-00 00:00:00') {
        $request_datetime = strtotime($request['request_date']);
        if ($request_datetime && $request_datetime > 0) {
            $request_date = date('Y-m-d', $request_datetime);
            $request_time = date('H:i:s', $request_datetime);
        }
    }
    
    // Format processed date
    $processed_date = '';
    if (!empty($request['processed_date']) && $request['processed_date'] != '0000-00-00 00:00:00') {
        $processed_datetime = strtotime($request['processed_date']);
        if ($processed_datetime && $processed_datetime > 0) {
            $processed_date = date('Y-m-d H:i:s', $processed_datetime);
        }
    }
    
    // Format preferred date if exists
    $preferred_date = '';
    if (!empty($request['preferred_date']) && $request['preferred_date'] != '0000-00-00') {
        $pref_datetime = strtotime($request['preferred_date']);
        if ($pref_datetime && $pref_datetime > 0) {
            $preferred_date = date('Y-m-d', $pref_datetime);
        }
    }
    
    $row = [
        '#' . str_pad($request['id'], 5, '0', STR_PAD_LEFT),
        trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')),
        $request['username'] ?? '',
        $request['email'] ?? '',
        $request['contact_number'] ?? 'N/A',
        $request['service_name'] ?? 'N/A',
        isset($request['service_fee']) && $request['service_fee'] > 0 ? '₱' . number_format($request['service_fee'], 2) : 'Free',
        ucfirst($request['request_type'] ?? 'online'),
        ucfirst($request['status'] ?? 'pending'),
        $request_date,
        $request_time,
        $preferred_date,
        safeValue($request, 'preferred_time'),
        safeValue($request, 'preferred_day'),
        $remarks,
        $processed_date,
        safeValue($request, 'processed_by_name', 'Not processed')
    ];
    
    fputcsv($output, $row);
}

fclose($output);

// Log the export action (optional)
try {
    $checkLogsTable = $db->query("SHOW TABLES LIKE 'admin_logs'");
    if ($checkLogsTable->rowCount() > 0) {
        $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address, created_at) 
                      VALUES (:admin_id, 'EXPORT_REQUESTS', :desc, :ip, NOW())";
        $log_stmt = $db->prepare($log_query);
        $desc = "Exported " . count($requests) . " request(s) to CSV";
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
        $log_stmt->bindParam(':desc', $desc);
        $log_stmt->bindParam(':ip', $ip);
        $log_stmt->execute();
    }
} catch (Exception $e) {
    // Silently fail if logging doesn't work
    error_log("Failed to log export action: " . $e->getMessage());
}

exit();
?>