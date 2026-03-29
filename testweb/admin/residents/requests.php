<?php
// admin/residents/requests.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection with correct path
require_once dirname(__DIR__, 2) . '/config/database.php';

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Include permissions with correct path
require_once dirname(__DIR__) . '/includes/permissions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check permission
if (!can('view_requests')) {
    $_SESSION['error'] = "Access denied.";
    header('Location: ../dashboard.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? '';
    
    try {
        $check_column = "SHOW COLUMNS FROM resident_requests LIKE 'admin_remarks'";
        $stmt_check = $db->prepare($check_column);
        $stmt_check->execute();
        $has_admin_remarks = $stmt_check->rowCount() > 0;
        
        if ($has_admin_remarks) {
            $query = "UPDATE resident_requests SET 
                      status = :status, 
                      admin_remarks = :remarks, 
                      processed_date = NOW(), 
                      processed_by = :admin_id 
                      WHERE id = :id";
        } else {
            $query = "UPDATE resident_requests SET 
                      status = :status, 
                      remarks = :remarks, 
                      processed_date = NOW(), 
                      processed_by = :admin_id 
                      WHERE id = :id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':admin_id', $_SESSION['admin_id']);
        $stmt->bindParam(':id', $request_id);
        
        if ($stmt->execute()) {
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'UPDATE_REQUEST', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Updated request #$request_id to status: $status";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Log table might not exist
            }
            
            $_SESSION['success'] = "Request status updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update request status: " . $e->getMessage();
    }
    header('Location: requests.php');
    exit();
}

// Handle delete request - No permission check, all admins can delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_request'])) {
    $request_id = $_POST['request_id'];
    
    try {
        // Get request details for logging
        $info_query = "SELECT id, service_id, user_id FROM resident_requests WHERE id = :id";
        $info_stmt = $db->prepare($info_query);
        $info_stmt->bindParam(':id', $request_id);
        $info_stmt->execute();
        $request_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request_info) {
            // Delete the request
            $query = "DELETE FROM resident_requests WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $request_id);
            
            if ($stmt->execute()) {
                // Log the action
                try {
                    $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                                  VALUES (:admin_id, 'DELETE_REQUEST', :desc, :ip)";
                    $log_stmt = $db->prepare($log_query);
                    $desc = "Deleted request #$request_id";
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                    $log_stmt->bindParam(':desc', $desc);
                    $log_stmt->bindParam(':ip', $ip);
                    $log_stmt->execute();
                } catch (Exception $e) {
                    // Log table might not exist
                }
                
                $_SESSION['success'] = "Request deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete request";
            }
        } else {
            $_SESSION['error'] = "Request not found";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete request: " . $e->getMessage();
    }
    header('Location: requests.php');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$service_filter = isset($_GET['service']) ? $_GET['service'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';

// Build main query with profile_pic
$query = "SELECT r.id, r.user_id, r.service_id, r.request_type, r.preferred_day, 
          r.preferred_time, r.schedule_id, r.preferred_date, r.status, 
          r.admin_remarks, r.qr_token, r.request_date, r.processed_date, r.processed_by,
          u.username, u.first_name, u.last_name, u.email, u.contact_number, u.profile_pic,
          s.service_name, s.fee,
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

if (!empty($service_filter)) {
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

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process profile pictures
foreach ($requests as &$request) {
    if (!empty($request['profile_pic'])) {
        $profile_pic_path = '../../uploads/profiles/' . $request['profile_pic'];
        if (file_exists($profile_pic_path)) {
            $request['profile_pic_path'] = $profile_pic_path;
        } else {
            $request['profile_pic_path'] = null;
        }
    } else {
        $request['profile_pic_path'] = null;
    }
}

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM resident_requests";

try {
    $stats_stmt = $db->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'completed' => 0
        ];
    }
} catch (Exception $e) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'completed' => 0
    ];
}

// Get all services for filter dropdown
$services_query = "SELECT id, service_name FROM services WHERE is_active = 1 ORDER BY service_name";
$services_stmt = $db->query($services_query);
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include dirname(__DIR__) . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Modal Styles - Fixed scrollbar issue */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow-y: auto;
    }
    
    .modal-content {
        position: relative;
        background-color: #fff;
        margin: 3% auto;
        border-radius: 16px;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        animation: modalFadeIn 0.2s ease;
        overflow: hidden;
    }
    
    .modal-content.small {
        max-width: 500px;
    }
    
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background: #fff;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
    }
    
    .modal-header .close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6c757d;
        transition: color 0.2s;
    }
    
    .modal-header .close:hover {
        color: #dc3545;
    }
    
    .modal-body {
        padding: 1.5rem;
        max-height: 65vh;
        overflow-y: auto;
    }
    
    /* Custom scrollbar for modal body */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .modal-footer {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-top: 1px solid #e9ecef;
    }
    
    /* Info row styling */
    .info-row {
        display: flex;
        flex-wrap: wrap;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }
    
    .info-label {
        width: 140px;
        font-weight: 600;
        color: #475569;
    }
    
    .info-value {
        flex: 1;
        color: #1e293b;
    }
    
    .remarks-box {
        background: #fef3c7;
        border-left: 3px solid #f59e0b;
    }
    
    .schedule-badge {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        margin-left: 0.5rem;
    }
    
    /* Form styles */
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #1e293b;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: inherit;
        transition: all 0.2s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    /* Button styles */
    .btn-cancel {
        padding: 0.6rem 1.2rem;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }
    
    .btn-submit {
        padding: 0.6rem 1.2rem;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    
    .btn-submit:hover {
        background: #218838;
        transform: translateY(-1px);
    }
    
    .btn-danger {
        padding: 0.6rem 1.2rem;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    
    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-1px);
    }
    
    /* Schedule info */
    .schedule-info {
        background: #f0f9ff;
        border-radius: 8px;
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        color: #0369a1;
    }
    
    /* Status badge */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
    }
    
    /* Action buttons */
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-icon:hover {
        background: #f1f5f9;
        color: #2563eb;
    }
    
    .btn-icon.delete:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    
    /* Profile picture styles */
    .resident-avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
    }
    
    .resident-avatar {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .modal-content {
            margin: 5% auto;
            width: 95%;
        }
        
        .info-label {
            width: 100%;
            margin-bottom: 0.25rem;
        }
        
        .info-row {
            flex-direction: column;
        }
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .spinner {
        width: 2rem;
        height: 2rem;
        border: 3px solid #e2e8f0;
        border-top-color: #667eea;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto;
    }
</style>

<div class="content-wrapper">
    <!-- Module Header -->
    <div class="module-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div class="module-title">
            <h1 style="font-size: 1.8rem; font-weight: 600; color: #1e293b; margin-bottom: 0.25rem;">Resident Requests</h1>
            <p style="color: #64748b; font-size: 0.95rem;">Manage and track all service requests from residents</p>
        </div>
        <div class="header-actions">
            <button onclick="exportRequests()" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer;">
                <i class="fas fa-download"></i> Export Requests
            </button>
        </div>
    </div>

    <!-- Display Session Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444;">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #e3f2fd; color: #1976d2;">
                <i class="fas fa-clipboard-list" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['total'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">Total Requests</p>
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #fff3e0; color: #f57c00;">
                <i class="fas fa-hourglass-half" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['pending'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">Pending</p>
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #d4edda; color: #155724;">
                <i class="fas fa-check-circle" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['approved'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">Approved</p>
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f8d7da; color: #721c24;">
                <i class="fas fa-times-circle" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['rejected'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">Rejected</p>
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #d1ecf1; color: #0c5460;">
                <i class="fas fa-check-double" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['completed'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">Completed</p>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="filters-card" style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form method="GET" id="filterForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: flex-end;">
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">Search</label>
                <input type="text" name="search" placeholder="Search by name or service..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
            </div>
            
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">Status</label>
                <select name="status" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">Service</label>
                <select name="service" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
                    <option value="">All Services</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['id']; ?>" <?php echo $service_filter == $service['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">From Date</label>
                <input type="date" name="from" value="<?php echo $date_from; ?>" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
            </div>
            
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">To Date</label>
                <input type="date" name="to" value="<?php echo $date_to; ?>" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
            </div>
            
            <div class="filter-actions" style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn-filter" style="padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="requests.php" class="btn-clear" style="padding: 0.75rem 1.5rem; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-eraser"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <div class="table-card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto;">
        <table class="data-table" id="requestsTable" style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">ID</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Resident</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Service</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Schedule</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Type</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Status</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Request Date</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Actions</th>
                   </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: #6c757d;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #dee2e6;"></i>
                            <p>No requests found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef; font-weight: 600; color: #007bff;">#<?php echo str_pad($request['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php 
                                $profile_pic_path = $request['profile_pic_path'] ?? null;
                                
                                if (!empty($profile_pic_path) && file_exists($profile_pic_path)): 
                                ?>
                                    <img src="<?php echo $profile_pic_path; ?>" class="resident-avatar-img" alt="Profile">
                                <?php else: ?>
                                    <div class="resident-avatar">
                                        <?php echo strtoupper(substr($request['first_name'] ?? '?', 0, 1) . substr($request['last_name'] ?? '?', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 500; color: #2c3e50;"><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></div>
                                    <div style="font-size: 0.75rem; color: #6c757d;"><?php echo htmlspecialchars($request['email'] ?? ''); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($request['service_name'] ?? 'N/A'); ?></div>
                                <div style="font-size: 0.7rem; color: #6c757d;">Fee: <?php echo $request['fee'] > 0 ? '₱' . number_format($request['fee'], 2) : 'Free'; ?></div>
                            </div>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div class="schedule-info">
                                <?php 
                                $has_schedule = false;
                                if (!empty($request['preferred_date']) && $request['preferred_date'] != '0000-00-00' && $request['preferred_date'] != '1970-01-01'):
                                    $has_schedule = true;
                                    $date_timestamp = strtotime($request['preferred_date']);
                                    if ($date_timestamp && $date_timestamp > 0):
                                ?>
                                    <div><i class="fas fa-calendar-alt"></i> <strong><?php echo date('M d, Y', $date_timestamp); ?></strong></div>
                                    <?php if (!empty($request['preferred_time'])): ?>
                                    <div><i class="fas fa-clock"></i> <?php echo htmlspecialchars($request['preferred_time']); ?></div>
                                    <?php endif; ?>
                                <?php 
                                    endif;
                                endif;
                                if (!empty($request['preferred_day']) && empty($request['preferred_date'])):
                                    $has_schedule = true;
                                ?>
                                    <div><i class="fas fa-calendar-day"></i> <strong><?php echo htmlspecialchars($request['preferred_day']); ?></strong></div>
                                    <?php if (!empty($request['preferred_time'])): ?>
                                    <div><i class="fas fa-clock"></i> <?php echo htmlspecialchars($request['preferred_time']); ?></div>
                                    <?php endif; ?>
                                    <div class="schedule-badge">
                                        <i class="fas fa-redo-alt"></i> Weekly
                                    </div>
                                <?php 
                                endif;
                                if (!$has_schedule):
                                ?>
                                    <span style="color: #94a3b8;"><i class="fas fa-minus-circle"></i> No schedule</span>
                                <?php endif; ?>
                            </div>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <span style="background: #e9ecef; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem;"><?php echo ucfirst($request['request_type'] ?? 'online'); ?></span>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <span class="status-badge" style="background: <?php echo $request['status'] == 'pending' ? '#fff3cd' : ($request['status'] == 'approved' ? '#d4edda' : ($request['status'] == 'rejected' ? '#f8d7da' : '#d1ecf1')); ?>; color: <?php echo $request['status'] == 'pending' ? '#856404' : ($request['status'] == 'approved' ? '#155724' : ($request['status'] == 'rejected' ? '#721c24' : '#0c5460')); ?>;">
                                <i class="fas fa-<?php echo $request['status'] == 'pending' ? 'clock' : ($request['status'] == 'approved' ? 'check-circle' : ($request['status'] == 'rejected' ? 'times-circle' : 'check-double')); ?>"></i>
                                <?php echo ucfirst($request['status'] ?? 'pending'); ?>
                            </span>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div>
                                <?php echo date('M d, Y', strtotime($request['request_date'] ?? 'now')); ?>
                                <small style="display: block; font-size: 0.75rem; color: #6c757d;"><?php echo date('h:i A', strtotime($request['request_date'] ?? 'now')); ?></small>
                            </div>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="viewRequest(<?php echo $request['id']; ?>)" class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="updateStatus(<?php echo $request['id']; ?>)" class="btn-icon" title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteRequest(<?php echo $request['id']; ?>)" class="btn-icon delete" title="Delete Request">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                         </div>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Request Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-file-alt" style="color: #3b82f6;"></i> Request Details</h2>
            <button class="close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="requestDetails">
            <div style="text-align: center; padding: 2rem;">
                <div class="spinner"></div>
                <p style="margin-top: 1rem; color: #64748b;">Loading request details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content small">
        <div class="modal-header">
            <h2><i class="fas fa-edit" style="color: #10b981;"></i> Update Status</h2>
            <button class="close" onclick="closeModal('updateModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="request_id" id="update-request-id">
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Status</label>
                    <select name="status" id="update-status" class="form-control" required>
                        <option value="pending">⏳ Pending</option>
                        <option value="approved">✅ Approved</option>
                        <option value="rejected">❌ Rejected</option>
                        <option value="completed">🎉 Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Admin Remarks</label>
                    <textarea name="remarks" id="update-remarks" class="form-control" rows="4" placeholder="Add remarks about this request..."></textarea>
                    <small style="color: #64748b; display: block; margin-top: 0.3rem;">These remarks will be visible to the resident</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('updateModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_status" class="btn-submit">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content small">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i> Confirm Delete</h2>
            <button class="close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this request?</p>
            <p style="color: #dc3545; margin-top: 0.5rem;">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('deleteConfirmModal')" class="btn-cancel">Cancel</button>
            <button type="button" onclick="confirmDeleteNow()" class="btn-danger">Delete</button>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="request_id" id="delete-request-id">
    <input type="hidden" name="delete_request" value="1">
</form>

<script>
let pendingDeleteId = null;

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// View request details - WITHOUT username
function viewRequest(id) {
    const modal = document.getElementById('viewModal');
    const detailsDiv = document.getElementById('requestDetails');
    
    modal.style.display = 'block';
    detailsDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner"></div><p style="margin-top: 1rem;">Loading...</p></div>';
    
    fetch('get_request.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            let statusColor = '', statusTextColor = '', statusIcon = '', statusText = '';
            if (data.status === 'pending') {
                statusColor = '#fff3cd';
                statusTextColor = '#856404';
                statusIcon = 'clock';
                statusText = 'PENDING';
            } else if (data.status === 'approved') {
                statusColor = '#d4edda';
                statusTextColor = '#155724';
                statusIcon = 'check-circle';
                statusText = 'APPROVED';
            } else if (data.status === 'rejected') {
                statusColor = '#f8d7da';
                statusTextColor = '#721c24';
                statusIcon = 'times-circle';
                statusText = 'REJECTED';
            } else if (data.status === 'completed') {
                statusColor = '#d1ecf1';
                statusTextColor = '#0c5460';
                statusIcon = 'check-double';
                statusText = 'COMPLETED';
            }
            
            let html = '';
            
            // Request ID and Status
            html += '<div class="info-row"><div class="info-label">Request #</div><div class="info-value"><strong>#' + String(data.id).padStart(5, '0') + '</strong></div></div>';
            html += '<div class="info-row"><div class="info-label">Status</div><div class="info-value"><span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 500; background: ' + statusColor + '; color: ' + statusTextColor + ';"><i class="fas fa-' + statusIcon + '"></i> ' + statusText + '</span></div></div>';
            
            // Resident Information - WITHOUT username
            html += '<div class="info-row"><div class="info-label">Resident</div><div class="info-value"><strong>' + (data.first_name || '') + ' ' + (data.last_name || '') + '</strong></div></div>';
            html += '<div class="info-row"><div class="info-label">Email</div><div class="info-value">' + (data.email || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Contact</div><div class="info-value">' + (data.contact_number || 'N/A') + '</div></div>';
            
            // Service Information
            html += '<div class="info-row"><div class="info-label">Service</div><div class="info-value">' + (data.service_name || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Type</div><div class="info-value">' + (data.request_type || 'online') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Fee</div><div class="info-value">' + (data.service_fee > 0 ? '₱' + parseFloat(data.service_fee).toFixed(2) : 'Free') + '</div></div>';
            
            // Schedule
            let hasSchedule = false;
            if (data.preferred_date && data.preferred_date !== '0000-00-00') {
                const dateObj = new Date(data.preferred_date);
                if (!isNaN(dateObj.getTime())) {
                    html += '<div class="info-row"><div class="info-label">Preferred Date</div><div class="info-value"><strong>' + dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + '</strong></div></div>';
                    hasSchedule = true;
                    if (data.preferred_time) {
                        html += '<div class="info-row"><div class="info-label">Preferred Time</div><div class="info-value">' + data.preferred_time + '</div></div>';
                    }
                }
            }
            if (data.preferred_day && !hasSchedule) {
                html += '<div class="info-row"><div class="info-label">Preferred Day</div><div class="info-value"><strong>' + data.preferred_day + '</strong> <span class="schedule-badge"><i class="fas fa-redo-alt"></i> Weekly</span></div></div>';
                if (data.preferred_time) {
                    html += '<div class="info-row"><div class="info-label">Preferred Time</div><div class="info-value">' + data.preferred_time + '</div></div>';
                }
            }
            if (!hasSchedule && !data.preferred_day) {
                html += '<div class="info-row"><div class="info-label">Schedule</div><div class="info-value" style="color: #94a3b8;">Not specified</div></div>';
            }
            
            // Request Date
            html += '<div class="info-row"><div class="info-label">Request Date</div><div class="info-value">' + new Date(data.request_date).toLocaleString() + '</div></div>';
            
            // Admin Remarks
            if (data.admin_remarks) {
                html += '<div class="info-row remarks-box"><div class="info-label">Admin Remarks</div><div class="info-value">' + data.admin_remarks + '</div></div>';
                if (data.processed_by) {
                    html += '<div class="info-row"><div class="info-label">Processed By</div><div class="info-value">' + data.processed_by + '</div></div>';
                }
                if (data.processed_date) {
                    html += '<div class="info-row"><div class="info-label">Processed Date</div><div class="info-value">' + new Date(data.processed_date).toLocaleString() + '</div></div>';
                }
            }
            
            detailsDiv.innerHTML = html;
        })
        .catch(error => {
            detailsDiv.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;"><i class="fas fa-exclamation-circle" style="font-size: 2rem;"></i><p>Error loading details</p><button onclick="viewRequest(' + id + ')" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Retry</button></div>';
        });
}

// Update request status
function updateStatus(id) {
    fetch('get_request.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('update-request-id').value = id;
            document.getElementById('update-status').value = data.status || 'pending';
            document.getElementById('update-remarks').value = data.admin_remarks || '';
            document.getElementById('updateModal').style.display = 'block';
        })
        .catch(error => {
            document.getElementById('update-request-id').value = id;
            document.getElementById('update-status').value = 'pending';
            document.getElementById('update-remarks').value = '';
            document.getElementById('updateModal').style.display = 'block';
        });
}

// Delete request
function deleteRequest(id) {
    pendingDeleteId = id;
    document.getElementById('deleteConfirmModal').style.display = 'block';
}

function confirmDeleteNow() {
    if (pendingDeleteId) {
        document.getElementById('delete-request-id').value = pendingDeleteId;
        document.getElementById('deleteForm').submit();
    }
    closeModal('deleteConfirmModal');
}

// Export requests
function exportRequests() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();
    window.location.href = 'export_requests.php?' + params;
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Handle escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        openModals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>