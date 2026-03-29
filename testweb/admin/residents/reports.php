<?php
// admin/residents/reports.php

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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $report_id = $_POST['report_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? '';
    
    try {
        // First check if admin_remarks column exists
        $check_column = "SHOW COLUMNS FROM resident_reports LIKE 'admin_remarks'";
        $stmt_check = $db->prepare($check_column);
        $stmt_check->execute();
        $column_exists = $stmt_check->rowCount() > 0;
        
        if ($column_exists) {
            $query = "UPDATE resident_reports SET 
                      status = :status, 
                      admin_remarks = :remarks, 
                      resolved_date = NOW(), 
                      resolved_by = :admin_id 
                      WHERE id = :id";
        } else {
            $query = "UPDATE resident_reports SET 
                      status = :status, 
                      remarks = :remarks, 
                      resolved_date = NOW(), 
                      resolved_by = :admin_id 
                      WHERE id = :id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':admin_id', $_SESSION['admin_id']);
        $stmt->bindParam(':id', $report_id);
        
        if ($stmt->execute()) {
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'UPDATE_REPORT', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Updated report #$report_id to status: $status";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Log table might not exist, continue
            }
            
            $_SESSION['success'] = "Report status updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update report status: " . $e->getMessage();
    }
    header('Location: reports.php');
    exit();
}

// Handle delete report - all admins can delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_report'])) {
    $report_id = $_POST['report_id'];
    
    try {
        // Get attachment info to delete file
        $get_attachment = "SELECT attachment FROM resident_reports WHERE id = :id";
        $att_stmt = $db->prepare($get_attachment);
        $att_stmt->bindParam(':id', $report_id);
        $att_stmt->execute();
        $report_data = $att_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the file if exists
        if (!empty($report_data['attachment'])) {
            $file_path = '../../uploads/reports/' . $report_data['attachment'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $query = "DELETE FROM resident_reports WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $report_id);
        
        if ($stmt->execute()) {
            // Log the action
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'DELETE_REPORT', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Deleted report #$report_id";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Log table might not exist
            }
            
            $_SESSION['success'] = "Report deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete report";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete report: " . $e->getMessage();
    }
    header('Location: reports.php');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';

// Build main query with profile_pic
$query = "SELECT r.*, 
          u.username, u.first_name, u.last_name, u.email, u.contact_number, u.profile_pic,
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

$query .= " ORDER BY 
            CASE r.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            r.reported_date DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process profile pictures
foreach ($reports as &$report) {
    if (!empty($report['profile_pic'])) {
        $profile_pic_path = '../../uploads/profiles/' . $report['profile_pic'];
        if (file_exists($profile_pic_path)) {
            $report['profile_pic_path'] = $profile_pic_path;
        } else {
            $report['profile_pic_path'] = null;
        }
    } else {
        $report['profile_pic_path'] = null;
    }
}

// Get statistics with proper column names
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_count,
                SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_priority_count,
                SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_priority_count
                FROM resident_reports";

try {
    $stats_stmt = $db->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'closed' => 0,
            'high_priority_count' => 0,
            'medium_priority_count' => 0,
            'low_priority_count' => 0
        ];
    }
} catch (Exception $e) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed' => 0,
        'high_priority_count' => 0,
        'medium_priority_count' => 0,
        'low_priority_count' => 0
    ];
}

include dirname(__DIR__) . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Resident Avatar Styles */
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
    
    /* Modal Styles */
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
    
    .modal-content.large {
        max-width: 900px;
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
    
    /* Attachment Image Styles */
    .attachment-container {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 12px;
        text-align: center;
    }
    
    .attachment-image {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .attachment-image:hover {
        transform: scale(1.02);
    }
    
    .attachment-pdf {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: #fff;
        border-radius: 8px;
        text-decoration: none;
        color: #dc2626;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    
    .attachment-pdf:hover {
        background: #fef2f2;
        border-color: #dc2626;
    }
    
    .attachment-icon {
        font-size: 2rem;
        color: #dc2626;
    }
    
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
    
    /* Image Preview Modal */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 1100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        cursor: pointer;
    }
    
    .image-modal-content {
        position: relative;
        margin: auto;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    
    .image-modal-img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }
    
    .image-modal-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    
    .image-modal-close:hover {
        color: #bbb;
    }
    
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
            <h1 style="font-size: 1.8rem; font-weight: 600; color: #1e293b; margin-bottom: 0.25rem;">Resident Reports</h1>
            <p style="color: #64748b; font-size: 0.95rem;">Manage and track all reports submitted by residents</p>
        </div>
        <div class="header-actions">
            <button onclick="exportReports()" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer;">
                <i class="fas fa-download"></i> Export Reports
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
                <p style="color: #6c757d; font-size: 0.875rem;">Total Reports</p>
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
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #cce5ff; color: #004085;">
                <i class="fas fa-spinner" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['in_progress'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">In Progress</p>
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #d4edda; color: #155724;">
                <i class="fas fa-check-circle" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['resolved'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">Resolved</p>
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="stat-icon" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #ffebee; color: #c62828;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-details">
                <h3 style="font-size: 1.8rem; margin-bottom: 0.25rem; color: #2c3e50;"><?php echo $stats['high_priority_count'] ?? 0; ?></h3>
                <p style="color: #6c757d; font-size: 0.875rem;">High Priority</p>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="filters-card" style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form method="GET" id="filterForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: flex-end;">
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">Search</label>
                <input type="text" name="search" placeholder="Search by name or subject..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
            </div>
            
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">Status</label>
                <select name="status" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in-progress" <?php echo $status_filter == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            
            <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #2c3e50;">Priority</label>
                <select name="priority" style="padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.875rem; width: 100%;">
                    <option value="">All Priorities</option>
                    <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
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
                <a href="reports.php" class="btn-clear" style="padding: 0.75rem 1.5rem; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-eraser"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="table-card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto;">
        <table class="data-table" id="reportsTable" style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">ID</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Resident</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Subject</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Attachment</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Priority</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Status</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Reported Date</th>
                    <th style="text-align: left; padding: 1rem; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #e9ecef;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: #6c757d;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #dee2e6;"></i>
                            <p>No reports found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef; font-weight: 600; color: #007bff;">#<?php echo str_pad($report['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php 
                                $profile_pic_path = $report['profile_pic_path'] ?? null;
                                
                                if (!empty($profile_pic_path) && file_exists($profile_pic_path)): 
                                ?>
                                    <img src="<?php echo $profile_pic_path; ?>" class="resident-avatar-img" alt="Profile">
                                <?php else: ?>
                                    <div class="resident-avatar">
                                        <?php echo strtoupper(substr($report['first_name'] ?? '?', 0, 1) . substr($report['last_name'] ?? '?', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 500; color: #2c3e50;"><?php echo htmlspecialchars(($report['first_name'] ?? '') . ' ' . ($report['last_name'] ?? '')); ?></div>
                                    <div style="font-size: 0.75rem; color: #6c757d;"><?php echo htmlspecialchars($report['email'] ?? ''); ?></div>
                                </div>
                            </div>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($report['subject'] ?? 'N/A'); ?></div>
                                <div style="font-size: 0.7rem; color: #6c757d;">Type: <?php echo htmlspecialchars($report['report_type'] ?? 'N/A'); ?></div>
                            </div>
                         </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <?php if (!empty($report['attachment'])): 
                                $file_ext = pathinfo($report['attachment'], PATHINFO_EXTENSION);
                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                if (in_array(strtolower($file_ext), $image_extensions)):
                            ?>
                                <img src="../../uploads/reports/<?php echo $report['attachment']; ?>" 
                                     alt="Attachment" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                                     onclick="viewImage('../../uploads/reports/<?php echo $report['attachment']; ?>')"
                                     title="Click to view full image">
                            <?php else: ?>
                                <a href="../../uploads/reports/<?php echo $report['attachment']; ?>" 
                                   target="_blank" 
                                   style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: #fef2f2; color: #dc2626; border-radius: 6px; text-decoration: none; font-size: 0.7rem;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;"><i class="fas fa-paperclip"></i> No attachment</span>
                            <?php endif; ?>
                        </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 500; background: <?php echo $report['priority'] == 'high' ? '#ffebee' : ($report['priority'] == 'medium' ? '#fff3e0' : '#e8f5e9'); ?>; color: <?php echo $report['priority'] == 'high' ? '#c62828' : ($report['priority'] == 'medium' ? '#f57c00' : '#2e7d32'); ?>;">
                                <i class="fas fa-<?php echo $report['priority'] == 'high' ? 'arrow-up' : ($report['priority'] == 'medium' ? 'minus' : 'arrow-down'); ?>"></i>
                                <?php echo ucfirst($report['priority'] ?? 'low'); ?>
                            </span>
                        </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 500; background: <?php echo $report['status'] == 'pending' ? '#fff3cd' : ($report['status'] == 'in-progress' ? '#cce5ff' : ($report['status'] == 'resolved' ? '#d4edda' : '#e2e3e5')); ?>; color: <?php echo $report['status'] == 'pending' ? '#856404' : ($report['status'] == 'in-progress' ? '#004085' : ($report['status'] == 'resolved' ? '#155724' : '#383d41')); ?>;">
                                <i class="fas fa-<?php echo $report['status'] == 'pending' ? 'clock' : ($report['status'] == 'in-progress' ? 'spinner' : ($report['status'] == 'resolved' ? 'check-circle' : 'check-double')); ?>"></i>
                                <?php echo $report['status'] == 'in-progress' ? 'In Progress' : ucfirst($report['status'] ?? 'pending'); ?>
                            </span>
                        </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div>
                                <?php echo date('M d, Y', strtotime($report['reported_date'] ?? 'now')); ?>
                                <small style="display: block; font-size: 0.75rem; color: #6c757d;"><?php echo date('h:i A', strtotime($report['reported_date'] ?? 'now')); ?></small>
                            </div>
                        </div>
                        <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="viewReport(<?php echo $report['id']; ?>)" class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="updateStatus(<?php echo $report['id']; ?>)" class="btn-icon" title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteReport(<?php echo $report['id']; ?>)" class="btn-icon delete" title="Delete Report">
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

<!-- Image Preview Modal -->
<div id="imagePreviewModal" class="image-modal" onclick="closeImageModal()">
    <span class="image-modal-close">&times;</span>
    <div class="image-modal-content">
        <img id="previewImage" class="image-modal-img" src="">
    </div>
</div>

<!-- View Report Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2><i class="fas fa-file-alt" style="color: #3b82f6;"></i> Report Details</h2>
            <button class="close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="reportDetails">
            <div style="text-align: center; padding: 2rem;">
                <div class="spinner"></div>
                <p style="margin-top: 1rem;">Loading report details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content small">
        <div class="modal-header">
            <h2><i class="fas fa-edit" style="color: #10b981;"></i> Update Report Status</h2>
            <button class="close" onclick="closeModal('updateModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="report_id" id="update-report-id">
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Status</label>
                    <select name="status" id="update-status" class="form-control" required>
                        <option value="pending">⏳ Pending</option>
                        <option value="in-progress">🔄 In Progress</option>
                        <option value="resolved">✅ Resolved</option>
                        <option value="closed">🔒 Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Admin Remarks</label>
                    <textarea name="remarks" id="update-remarks" class="form-control" rows="4" placeholder="Add remarks about this report..."></textarea>
                    <small style="color: #64748b; display: block; margin-top: 0.3rem;">These remarks will be visible to the resident</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('updateModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_status" class="btn-submit">Update Status</button>
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
            <p>Are you sure you want to delete this report?</p>
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
    <input type="hidden" name="report_id" id="delete-report-id">
    <input type="hidden" name="delete_report" value="1">
</form>

<script>
let pendingDeleteId = null;

// View image fullscreen
function viewImage(imageUrl) {
    const modal = document.getElementById('imagePreviewModal');
    const img = document.getElementById('previewImage');
    img.src = imageUrl;
    modal.style.display = 'block';
}

function closeImageModal() {
    document.getElementById('imagePreviewModal').style.display = 'none';
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// View report details without profile picture and username
function viewReport(id) {
    const modal = document.getElementById('viewModal');
    const detailsDiv = document.getElementById('reportDetails');
    
    modal.style.display = 'block';
    detailsDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner"></div><p>Loading...</p></div>';
    
    fetch('get_report.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            let statusText = data.status == 'in-progress' ? 'In Progress' : ucfirst(data.status || 'pending');
            let priorityColor = data.priority == 'high' ? '#c62828' : (data.priority == 'medium' ? '#f57c00' : '#2e7d32');
            let priorityBg = data.priority == 'high' ? '#ffebee' : (data.priority == 'medium' ? '#fff3e0' : '#e8f5e9');
            let statusColor = data.status == 'pending' ? '#856404' : (data.status == 'in-progress' ? '#004085' : (data.status == 'resolved' ? '#155724' : '#383d41'));
            let statusBg = data.status == 'pending' ? '#fff3cd' : (data.status == 'in-progress' ? '#cce5ff' : (data.status == 'resolved' ? '#d4edda' : '#e2e3e5'));
            
            let html = '';
            html += '<div class="info-row"><div class="info-label">Report #</div><div class="info-value"><strong>#' + String(data.id).padStart(5, '0') + '</strong></div></div>';
            html += '<div class="info-row"><div class="info-label">Status</div><div class="info-value"><span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 500; background: ' + statusBg + '; color: ' + statusColor + ';"><i class="fas fa-' + (data.status == 'pending' ? 'clock' : (data.status == 'in-progress' ? 'spinner' : (data.status == 'resolved' ? 'check-circle' : 'check-double'))) + '"></i> ' + statusText + '</span></div></div>';
            
            // Resident Information - WITHOUT profile picture and WITHOUT username
            html += '<div class="info-row"><div class="info-label">Resident</div><div class="info-value"><strong>' + (data.first_name || '') + ' ' + (data.last_name || '') + '</strong></div></div>';
            html += '<div class="info-row"><div class="info-label">Email</div><div class="info-value">' + (data.email || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Contact</div><div class="info-value">' + (data.contact_number || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Report Type</div><div class="info-value">' + (data.report_type || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Subject</div><div class="info-value">' + (data.subject || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Description</div><div class="info-value">' + (data.description || 'No description provided') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Location</div><div class="info-value">' + (data.location || 'Not specified') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Priority</div><div class="info-value"><span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 500; background: ' + priorityBg + '; color: ' + priorityColor + ';"><i class="fas fa-' + (data.priority == 'high' ? 'arrow-up' : (data.priority == 'medium' ? 'minus' : 'arrow-down')) + '"></i> ' + ucfirst(data.priority || 'low') + '</span></div></div>';
            html += '<div class="info-row"><div class="info-label">Reported Date</div><div class="info-value">' + new Date(data.reported_date).toLocaleString() + '</div></div>';
            
            // Attachment display
            if (data.attachment) {
                const fileExt = data.attachment.split('.').pop().toLowerCase();
                const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                html += '<div class="info-row"><div class="info-label">Attachment</div><div class="info-value">';
                if (imageExts.includes(fileExt)) {
                    html += '<div class="attachment-container">';
                    html += '<img src="../../uploads/reports/' + data.attachment + '" class="attachment-image" onclick="viewImage(\'../../uploads/reports/' + data.attachment + '\')" title="Click to enlarge">';
                    html += '</div>';
                } else if (fileExt === 'pdf') {
                    html += '<div class="attachment-container">';
                    html += '<a href="../../uploads/reports/' + data.attachment + '" target="_blank" class="attachment-pdf">';
                    html += '<i class="fas fa-file-pdf attachment-icon"></i> View PDF Document';
                    html += '</a>';
                    html += '</div>';
                } else {
                    html += '<a href="../../uploads/reports/' + data.attachment + '" target="_blank" class="attachment-pdf">';
                    html += '<i class="fas fa-file-download"></i> Download Attachment';
                    html += '</a>';
                }
                html += '</div></div>';
            }
            
            if (data.admin_remarks || data.remarks) {
                html += '<div class="info-row remarks-box"><div class="info-label">Admin Remarks</div><div class="info-value">' + (data.admin_remarks || data.remarks || '') + '</div></div>';
            }
            
            if (data.resolved_date) {
                html += '<div class="info-row"><div class="info-label">Resolved Date</div><div class="info-value">' + new Date(data.resolved_date).toLocaleString() + '</div></div>';
            }
            
            if (data.resolved_by_name) {
                html += '<div class="info-row"><div class="info-label">Resolved By</div><div class="info-value">' + data.resolved_by_name + '</div></div>';
            }
            
            detailsDiv.innerHTML = html;
        })
        .catch(error => {
            detailsDiv.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;"><i class="fas fa-exclamation-circle" style="font-size: 2rem;"></i><p>Error loading report details</p><button onclick="viewReport(' + id + ')" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Retry</button></div>';
        });
}

// Delete report
function deleteReport(id) {
    pendingDeleteId = id;
    document.getElementById('deleteConfirmModal').style.display = 'block';
}

function confirmDeleteNow() {
    if (pendingDeleteId) {
        document.getElementById('delete-report-id').value = pendingDeleteId;
        document.getElementById('deleteForm').submit();
    }
    closeModal('deleteConfirmModal');
}

// Export reports
function exportReports() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();
    window.location.href = 'export_reports.php?' + params;
}

// Helper function
function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
    if (event.target.classList.contains('image-modal')) {
        closeImageModal();
    }
}

// Handle escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        openModals.forEach(modal => {
            modal.style.display = 'none';
        });
        closeImageModal();
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>