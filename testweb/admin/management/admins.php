<?php
// admin/management/admins.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../includes/permissions.php';

// Check if admin is logged in and is Main Admin
if (!isset($_SESSION['admin_id']) || !isMainAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header('Location: ../dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'admins';

// ==================== ADMIN ACCOUNT HANDLERS ====================
// Handle add admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $admin_type = $_POST['admin_type'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        // Check if username or email already exists
        $check = $db->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->rowCount() > 0) {
            $_SESSION['error'] = "Username or email already exists.";
        } else {
            $stmt = $db->prepare("INSERT INTO admins (username, email, password, first_name, last_name, admin_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $first_name, $last_name, $admin_type, $is_active]);
            
            // Log the action
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'ADD_ADMIN', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Added new admin: $first_name $last_name ($username)";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Log table might not exist
            }
            
            $_SESSION['success'] = "Admin added successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: admins.php?tab=admins');
    exit();
}

// Handle update admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_admin'])) {
    $id = $_POST['admin_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $admin_type = $_POST['admin_type'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        // Check if email exists for another admin
        $check = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $check->execute([$email, $id]);
        if ($check->rowCount() > 0) {
            $_SESSION['error'] = "Email already used by another admin.";
        } else {
            $stmt = $db->prepare("UPDATE admins SET first_name=?, last_name=?, email=?, admin_type=?, is_active=? WHERE id=?");
            $stmt->execute([$first_name, $last_name, $email, $admin_type, $is_active, $id]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pass_stmt = $db->prepare("UPDATE admins SET password=? WHERE id=?");
                $pass_stmt->execute([$pass, $id]);
            }
            
            // Log the action
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'UPDATE_ADMIN', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Updated admin: $first_name $last_name (ID: $id)";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Log table might not exist
            }
            
            $_SESSION['success'] = "Admin updated successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: admins.php?tab=admins');
    exit();
}

// Handle delete admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_admin'])) {
    $id = $_POST['admin_id'];
    if ($id == $_SESSION['admin_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        try {
            // Get admin info for logging
            $info = $db->prepare("SELECT first_name, last_name FROM admins WHERE id = ?");
            $info->execute([$id]);
            $admin = $info->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("DELETE FROM admins WHERE id=?");
            $stmt->execute([$id]);
            
            // Log the action
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'DELETE_ADMIN', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Deleted admin: " . ($admin['first_name'] ?? '') . " " . ($admin['last_name'] ?? '') . " (ID: $id)";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Log table might not exist
            }
            
            $_SESSION['success'] = "Admin deleted successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
    header('Location: admins.php?tab=admins');
    exit();
}

// ==================== RESIDENT ACCOUNT HANDLERS ====================
// Handle update resident
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_resident'])) {
    $id = $_POST['resident_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $check = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? AND (user_type = 'resident' OR user_type IS NULL)");
        $check->execute([$username, $email, $id]);
        if ($check->rowCount() > 0) {
            $_SESSION['error'] = "Username or email already used by another resident.";
        } else {
            $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=?, username=?, is_active=? WHERE id=?");
            $stmt->execute([$first_name, $last_name, $email, $username, $is_active, $id]);
            
            if (!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pass_stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
                $pass_stmt->execute([$pass, $id]);
            }
            
            try {
                $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                              VALUES (:admin_id, 'UPDATE_RESIDENT', :desc, :ip)";
                $log_stmt = $db->prepare($log_query);
                $desc = "Updated resident account: $first_name $last_name (ID: $id)";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $log_stmt->bindParam(':desc', $desc);
                $log_stmt->bindParam(':ip', $ip);
                $log_stmt->execute();
            } catch (Exception $e) {}
            
            $_SESSION['success'] = "Resident account updated successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: admins.php?tab=residents');
    exit();
}

// Handle delete resident
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_resident'])) {
    $id = $_POST['resident_id'];
    
    try {
        $info = $db->prepare("SELECT first_name, last_name, username FROM users WHERE id = ?");
        $info->execute([$id]);
        $resident = $info->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        
        try {
            $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                          VALUES (:admin_id, 'DELETE_RESIDENT', :desc, :ip)";
            $log_stmt = $db->prepare($log_query);
            $desc = "Deleted resident account: " . ($resident['first_name'] ?? '') . " " . ($resident['last_name'] ?? '') . " (Username: " . ($resident['username'] ?? '') . ")";
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
            $log_stmt->bindParam(':desc', $desc);
            $log_stmt->bindParam(':ip', $ip);
            $log_stmt->execute();
        } catch (Exception $e) {}
        
        $_SESSION['success'] = "Resident account deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: admins.php?tab=residents');
    exit();
}

// Handle toggle resident status via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $id = $_POST['resident_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        $action_text = $new_status == 1 ? 'activated' : 'deactivated';
        
        $info = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $info->execute([$id]);
        $resident = $info->fetch(PDO::FETCH_ASSOC);
        
        try {
            $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                          VALUES (:admin_id, 'TOGGLE_RESIDENT_STATUS', :desc, :ip)";
            $log_stmt = $db->prepare($log_query);
            $desc = "Resident account " . ($resident['first_name'] ?? '') . " " . ($resident['last_name'] ?? '') . " has been $action_text";
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
            $log_stmt->bindParam(':desc', $desc);
            $log_stmt->bindParam(':ip', $ip);
            $log_stmt->execute();
        } catch (Exception $e) {}
        
        $_SESSION['success'] = "Resident account has been " . $action_text . ".";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: admins.php?tab=residents');
    exit();
}

// ==================== FETCH DATA ====================
// Fetch all admins
$admins = [];
try {
    $stmt = $db->query("SELECT * FROM admins ORDER BY id ASC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $admins = [];
}

// Fetch residents with filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$residents = [];
try {
    $query = "SELECT * FROM users WHERE user_type = 'resident' OR user_type IS NULL";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR username LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $query .= " AND is_active = :status";
        $params[':status'] = $status_filter;
    }
    
    $query .= " ORDER BY id DESC";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $residents = [];
}

include '../includes/admin_header.php';
?>

<style>
/* Admin Management Styles - Consistent for both tabs */
.admin-management {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Tabs Styles */
.tabs-container {
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
}

.tabs {
    display: flex;
    gap: 0.5rem;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    background: none;
    border: none;
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: #2563eb;
}

.tab-btn.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Action Buttons */
.action-header {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 1.5rem;
}

.btn-primary {
    background: #2563eb;
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideIn 0.3s ease;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border-left: 4px solid #22c55e;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.table-header {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.table-header h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.table-header .badge {
    background: #2563eb;
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.data-table th {
    background: #fafcff;
    padding: 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Fixed column widths for consistent alignment */
.data-table th:nth-child(1), .data-table td:nth-child(1) { width: 5%; }
.data-table th:nth-child(2), .data-table td:nth-child(2) { width: 18%; }
.data-table th:nth-child(3), .data-table td:nth-child(3) { width: 15%; }
.data-table th:nth-child(4), .data-table td:nth-child(4) { width: 22%; }
.data-table th:nth-child(5), .data-table td:nth-child(5) { width: 10%; }
.data-table th:nth-child(6), .data-table td:nth-child(6) { width: 12%; }
.data-table th:nth-child(7), .data-table td:nth-child(7) { width: 18%; }

.data-table tbody tr:hover {
    background: #f8fafc;
}

.data-table td:nth-child(6) {
    white-space: nowrap;
}

/* Badges */
.admin-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
}

.admin-badge.main {
    background: #2563eb;
    color: white;
}

.admin-badge.staff {
    background: #10b981;
    color: white;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.active {
    background: #dcfce7;
    color: #166534;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.data-table tr.inactive-row {
    background: #fef9f9;
    opacity: 0.85;
}

.data-table tr.inactive-row td {
    color: #6c6f78;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: nowrap;
}

.btn-icon {
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.4rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    text-decoration: none;
    width: 32px;
    height: 32px;
}

.btn-icon .material-icons {
    font-size: 1.2rem;
}

.btn-icon:hover {
    background: #f1f5f9;
    color: #2563eb;
}

.btn-icon.delete:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* Filter Section */
.filter-section {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.filter-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.1);
}

.btn-filter, .btn-reset {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-filter {
    background: #2563eb;
    color: white;
}

.btn-filter:hover {
    background: #1d4ed8;
}

.btn-reset {
    background: #e2e8f0;
    color: #475569;
    text-decoration: none;
}

.btn-reset:hover {
    background: #cbd5e1;
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
    background: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-content {
    background: white;
    margin: 2rem auto;
    border-radius: 12px;
    max-width: 550px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    animation: modalFade 0.3s;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 4rem);
}

.modal-content.large {
    max-width: 700px;
}

@keyframes modalFade {
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
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 12px 12px 0 0;
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-header .close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #94a3b8;
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-header .close:hover {
    background: #f3f4f6;
    color: #ef4444;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 0 0 12px 12px;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    flex-shrink: 0;
}

/* Custom Confirmation Modal - Enhanced Styles */
.confirm-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
    align-items: center;
    justify-content: center;
}

.confirm-modal.show {
    display: flex;
}

.confirm-modal-content {
    background: white;
    border-radius: 24px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    overflow: hidden;
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.25);
    animation: modalPop 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}

@keyframes modalPop {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.confirm-modal-icon {
    padding-top: 1.8rem;
}

.confirm-modal-icon .material-icons {
    font-size: 3.5rem;
}

.confirm-modal-icon.warning .material-icons {
    color: #f59e0b;
}

.confirm-modal-icon.danger .material-icons {
    color: #dc2626;
}

.confirm-modal-header {
    padding: 0 1.5rem 0.5rem;
}

.confirm-modal-header h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.confirm-modal-body {
    padding: 0.5rem 1.5rem 1rem;
}

.confirm-modal-body p {
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0;
}

.confirm-modal-body .resident-name {
    font-weight: 700;
    color: #2563eb;
    margin-top: 0.75rem;
    padding: 0.5rem 1rem;
    background: #eff6ff;
    border-radius: 12px;
    font-size: 0.9rem;
    display: inline-block;
}

.confirm-modal-body .warning-text {
    color: #b91c1c;
    font-size: 0.75rem;
    margin-top: 0.75rem;
    padding: 0.5rem;
    background: #fee2e2;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.confirm-modal-body .warning-text .material-icons {
    font-size: 0.9rem;
}

.confirm-modal-footer {
    padding: 1rem 1.5rem 1.5rem;
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    border-top: 1px solid #eef2f6;
    background: #fafcff;
}

.confirm-modal-footer button {
    padding: 0.6rem 1.5rem;
    border: none;
    border-radius: 40px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.confirm-modal-footer .btn-cancel {
    background: #e2e8f0;
    color: #475569;
}

.confirm-modal-footer .btn-cancel:hover {
    background: #cbd5e1;
    transform: translateY(-1px);
}

.confirm-modal-footer .btn-confirm {
    background: #dc2626;
    color: white;
}

.confirm-modal-footer .btn-confirm:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

.confirm-modal-footer .btn-confirm.activate {
    background: #22c55e;
}

.confirm-modal-footer .btn-confirm.activate:hover {
    background: #16a34a;
}

/* Form Styles */
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: #475569;
}

.form-group label .required {
    color: #dc2626;
    margin-left: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 0.7rem 0.8rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    box-sizing: border-box;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 0;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.checkbox-group label {
    margin-bottom: 0;
    cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.btn-save, .btn-cancel {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-save {
    background: #22c55e;
    color: white;
}

.btn-save:hover {
    background: #16a34a;
}

.btn-cancel {
    background: #e2e8f0;
    color: #475569;
}

.text-center {
    text-align: center;
}

small {
    color: #64748b;
    font-size: 0.7rem;
    display: inline-block;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .tabs {
        flex-direction: column;
        border-bottom: none;
    }
    
    .tab-btn {
        border-bottom: 1px solid #e2e8f0;
        justify-content: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem auto;
    }
    
    .modal-footer {
        flex-direction: column-reverse;
    }
    
    .btn-save, .btn-cancel {
        width: 100%;
        justify-content: center;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
    
    .data-table {
        min-width: 700px;
    }
}
</style>

<div class="admin-management">
    <div class="page-header">
        <h1>Admin Settings</h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span class="material-icons">check_circle</span>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <span class="material-icons">error</span>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-btn <?php echo $active_tab == 'admins' ? 'active' : ''; ?>" onclick="switchTab('admins')">
                <span class="material-icons">admin_panel_settings</span>
                Admin Accounts
            </button>
            <button class="tab-btn <?php echo $active_tab == 'residents' ? 'active' : ''; ?>" onclick="switchTab('residents')">
                <span class="material-icons">people</span>
                Resident Accounts
            </button>
        </div>
    </div>

    <!-- Admin Accounts Tab -->
    <div id="admins-tab" class="tab-content <?php echo $active_tab == 'admins' ? 'active' : ''; ?>">
        <div class="action-header">
            <button class="btn-primary" onclick="showAddAdminModal()">
                <span class="material-icons">person_add</span> Add Admin
            </button>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h2><span class="material-icons">admin_panel_settings</span> Admin Accounts</h2>
                <span class="badge"><?php echo count($admins); ?> admins</span>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Type</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr><td colspan="7" class="text-center" style="padding: 2rem; color: #94a3b8;">No admins found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #2563eb;">#<?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><span class="admin-badge <?php echo $admin['admin_type'] == 'main_admin' ? 'main' : 'staff'; ?>"><?php echo $admin['admin_type'] == 'main_admin' ? 'Main Admin' : 'Staff Admin'; ?></span></td>
                                    <td><span class="status-badge <?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick='editAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)' class="btn-icon" title="Edit"><span class="material-icons">edit</span></button>
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <button onclick="showDeleteAdminModal(<?php echo $admin['id']; ?>, '<?php echo addslashes($admin['first_name'] . ' ' . $admin['last_name']); ?>')" class="btn-icon delete" title="Delete"><span class="material-icons">delete</span></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Resident Accounts Tab -->
    <div id="residents-tab" class="tab-content <?php echo $active_tab == 'residents' ? 'active' : ''; ?>">
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <input type="hidden" name="tab" value="residents">
                <div class="filter-group">
                    <label>🔍 SEARCH</label>
                    <input type="text" name="search" placeholder="Search by name, username, or email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>📊 STATUS</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter"><span class="material-icons">search</span> Search</button>
                    <a href="admins.php?tab=residents" class="btn-reset"><span class="material-icons">refresh</span> Reset</a>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h2><span class="material-icons">people</span> Resident Accounts</h2>
                <span class="badge"><?php echo count($residents); ?> residents</span>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                            <tr><td colspan="7" class="text-center" style="padding: 2rem; color: #94a3b8;"><span class="material-icons" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">people_outline</span>No resident accounts found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($residents as $resident): 
                                $is_active = ($resident['is_active'] ?? 1);
                                $row_class = !$is_active ? 'inactive-row' : '';
                            ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td style="font-weight: 600; color: #2563eb;">#<?php echo $resident['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($resident['username']); ?></td>
                                    <td><?php echo htmlspecialchars($resident['email']); ?></td>
                                    <td><span class="status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>"><span class="material-icons" style="font-size: 0.7rem;"><?php echo $is_active ? 'check_circle' : 'cancel'; ?></span> <?php echo $is_active ? 'Active' : 'Inactive'; ?></span></td>
                                    <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($resident['created_at'] ?? 'now')); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick='editResident(<?php echo htmlspecialchars(json_encode($resident)); ?>)' class="btn-icon" title="Edit Account"><span class="material-icons">edit</span></button>
                                            <?php if ($is_active): ?>
                                                <button onclick="showToggleModal(<?php echo $resident['id']; ?>, '<?php echo addslashes($resident['first_name'] . ' ' . $resident['last_name']); ?>', 'deactivate')" class="btn-icon" title="Deactivate Account"><span class="material-icons">block</span></button>
                                            <?php else: ?>
                                                <button onclick="showToggleModal(<?php echo $resident['id']; ?>, '<?php echo addslashes($resident['first_name'] . ' ' . $resident['last_name']); ?>', 'activate')" class="btn-icon" title="Activate Account"><span class="material-icons">check_circle</span></button>
                                            <?php endif; ?>
                                            <button onclick="showDeleteResidentModal(<?php echo $resident['id']; ?>, '<?php echo addslashes($resident['first_name'] . ' ' . $resident['last_name']); ?>')" class="btn-icon delete" title="Delete Account"><span class="material-icons">delete</span></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2><span class="material-icons">person_add</span> Add New Admin</h2>
            <button class="close" onclick="closeModal('addAdminModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control" required></div>
                    <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Username <span class="required">*</span></label><input type="text" name="username" class="form-control" required></div>
                    <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Password <span class="required">*</span></label><input type="password" name="password" class="form-control" required minlength="6"><small>Minimum 6 characters</small></div>
                    <div class="form-group"><label>Admin Type <span class="required">*</span></label><select name="admin_type" class="form-control" required><option value="staff_admin">Staff Admin</option><option value="main_admin">Main Admin</option></select></div>
                </div>
                <div class="checkbox-group"><input type="checkbox" name="is_active" id="add_admin_active" checked><label for="add_admin_active">Active Account</label></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addAdminModal')">Cancel</button>
                <button type="submit" name="add_admin" class="btn-save">Add Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div id="editAdminModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2><span class="material-icons">edit</span> Edit Admin</h2>
            <button class="close" onclick="closeModal('editAdminModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="admin_id" id="edit_admin_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" id="edit_first_name" class="form-control" required></div>
                    <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" id="edit_last_name" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                    <div class="form-group"><label>Admin Type <span class="required">*</span></label><select name="admin_type" id="edit_admin_type" class="form-control" required><option value="staff_admin">Staff Admin</option><option value="main_admin">Main Admin</option></select></div>
                </div>
                <div class="form-group"><label>New Password</label><input type="password" name="password" class="form-control" minlength="6"><small>Leave blank to keep current password</small></div>
                <div class="checkbox-group"><input type="checkbox" name="is_active" id="edit_is_active"><label for="edit_is_active">Active Account</label></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editAdminModal')">Cancel</button>
                <button type="submit" name="update_admin" class="btn-save">Update Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Resident Modal -->
<div id="editResidentModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2><span class="material-icons">edit</span> Edit Resident Account</h2>
            <button class="close" onclick="closeModal('editResidentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="resident_id" id="edit_resident_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" id="edit_resident_first_name" class="form-control" required></div>
                    <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" id="edit_resident_last_name" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Username <span class="required">*</span></label><input type="text" name="username" id="edit_resident_username" class="form-control" required></div>
                    <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" id="edit_resident_email" class="form-control" required></div>
                </div>
                <div class="form-group"><label>New Password</label><input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current password"><small>Minimum 6 characters - only fill if you want to change password</small></div>
                <div class="checkbox-group"><input type="checkbox" name="is_active" id="edit_resident_is_active"><label for="edit_resident_is_active">Active Account</label></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editResidentModal')">Cancel</button>
                <button type="submit" name="update_resident" class="btn-save">Update Resident</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Confirmation Modal for Toggle (Activate/Deactivate) -->
<div id="toggleConfirmModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <div class="confirm-modal-icon" id="toggleModalIcon"><span class="material-icons">warning</span></div>
        <div class="confirm-modal-header"><h3 id="toggleModalTitle">Deactivate Account</h3></div>
        <div class="confirm-modal-body">
            <p id="toggleModalMessage">Are you sure you want to deactivate this resident account?</p>
            <div id="toggleResidentName" class="resident-name"></div>
            <div id="toggleWarningText" class="warning-text"><span class="material-icons">info</span><span>They will not be able to log in.</span></div>
        </div>
        <div class="confirm-modal-footer">
            <button class="btn-cancel" onclick="closeToggleModal()">Cancel</button>
            <button id="toggleConfirmBtn" class="btn-confirm">Deactivate</button>
        </div>
    </div>
</div>

<!-- Custom Confirmation Modal for Delete (Admin/Resident) -->
<div id="deleteConfirmModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <div class="confirm-modal-icon danger"><span class="material-icons">warning</span></div>
        <div class="confirm-modal-header"><h3 id="deleteModalTitle">Delete Account</h3></div>
        <div class="confirm-modal-body">
            <p id="deleteModalMessage">Are you sure you want to permanently delete this account?</p>
            <div id="deleteAccountName" class="resident-name"></div>
            <div id="deleteWarningText" class="warning-text"><span class="material-icons">error</span><span>This action cannot be undone!</span></div>
        </div>
        <div class="confirm-modal-footer">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button id="deleteConfirmBtn" class="btn-confirm">Delete</button>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="deleteAdminForm" method="POST" style="display: none;"><input type="hidden" name="admin_id" id="delete_admin_id"><input type="hidden" name="delete_admin" value="1"></form>
<form id="deleteResidentForm" method="POST" style="display: none;"><input type="hidden" name="resident_id" id="delete_resident_id"><input type="hidden" name="delete_resident" value="1"></form>
<form id="toggleResidentForm" method="POST" style="display: none;"><input type="hidden" name="resident_id" id="toggle_resident_id"><input type="hidden" name="new_status" id="toggle_new_status"><input type="hidden" name="toggle_status" value="1"></form>

<script>
// Tab switching
function switchTab(tab) {
    window.location.href = 'admins.php?tab=' + tab;
}

// Admin modals
function showAddAdminModal() {
    document.getElementById('addAdminModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function editAdmin(admin) {
    document.getElementById('edit_admin_id').value = admin.id;
    document.getElementById('edit_first_name').value = admin.first_name;
    document.getElementById('edit_last_name').value = admin.last_name;
    document.getElementById('edit_email').value = admin.email;
    document.getElementById('edit_admin_type').value = admin.admin_type;
    document.getElementById('edit_is_active').checked = admin.is_active == 1;
    document.getElementById('editAdminModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

// Resident modals
function editResident(resident) {
    document.getElementById('edit_resident_id').value = resident.id;
    document.getElementById('edit_resident_first_name').value = resident.first_name;
    document.getElementById('edit_resident_last_name').value = resident.last_name;
    document.getElementById('edit_resident_username').value = resident.username;
    document.getElementById('edit_resident_email').value = resident.email;
    document.getElementById('edit_resident_is_active').checked = (resident.is_active == 1);
    document.getElementById('editResidentModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

// Custom confirmation for toggle (activate/deactivate)
let pendingToggleId = null;
let pendingToggleStatus = null;

function showToggleModal(id, name, action) {
    pendingToggleId = id;
    pendingToggleStatus = action;
    
    const modal = document.getElementById('toggleConfirmModal');
    const iconDiv = document.getElementById('toggleModalIcon');
    const title = document.getElementById('toggleModalTitle');
    const message = document.getElementById('toggleModalMessage');
    const residentName = document.getElementById('toggleResidentName');
    const warningDiv = document.getElementById('toggleWarningText');
    const confirmBtn = document.getElementById('toggleConfirmBtn');
    
    if (action === 'deactivate') {
        iconDiv.className = 'confirm-modal-icon warning';
        iconDiv.innerHTML = '<span class="material-icons">warning</span>';
        title.innerHTML = 'Deactivate Account';
        message.innerHTML = 'Are you sure you want to deactivate this resident account?';
        residentName.innerHTML = name;
        warningDiv.innerHTML = '<span class="material-icons">info</span><span>They will not be able to log in.</span>';
        confirmBtn.innerHTML = 'Deactivate';
        confirmBtn.className = 'btn-confirm';
    } else {
        iconDiv.className = 'confirm-modal-icon';
        iconDiv.innerHTML = '<span class="material-icons">check_circle</span>';
        title.innerHTML = 'Activate Account';
        message.innerHTML = 'Are you sure you want to activate this resident account?';
        residentName.innerHTML = name;
        warningDiv.innerHTML = '<span class="material-icons">check</span><span>They will be able to log in again.</span>';
        confirmBtn.innerHTML = 'Activate';
        confirmBtn.className = 'btn-confirm activate';
    }
    
    modal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeToggleModal() {
    document.getElementById('toggleConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
    pendingToggleId = null;
    pendingToggleStatus = null;
}

function confirmToggleAction() {
    if (pendingToggleId && pendingToggleStatus) {
        const newStatus = pendingToggleStatus === 'activate' ? 1 : 0;
        document.getElementById('toggle_resident_id').value = pendingToggleId;
        document.getElementById('toggle_new_status').value = newStatus;
        document.getElementById('toggleResidentForm').submit();
    }
    closeToggleModal();
}

// Custom confirmation for delete
let pendingDeleteId = null;
let pendingDeleteType = null; // 'admin' or 'resident'
let pendingDeleteName = null;

function showDeleteAdminModal(id, name) {
    pendingDeleteId = id;
    pendingDeleteType = 'admin';
    pendingDeleteName = name;
    
    const modal = document.getElementById('deleteConfirmModal');
    document.getElementById('deleteModalTitle').innerHTML = 'Delete Admin Account';
    document.getElementById('deleteModalMessage').innerHTML = 'Are you sure you want to permanently delete this admin account?';
    document.getElementById('deleteAccountName').innerHTML = name;
    document.getElementById('deleteConfirmBtn').innerHTML = 'Delete Admin';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
}

function showDeleteResidentModal(id, name) {
    pendingDeleteId = id;
    pendingDeleteType = 'resident';
    pendingDeleteName = name;
    
    const modal = document.getElementById('deleteConfirmModal');
    document.getElementById('deleteModalTitle').innerHTML = 'Delete Resident Account';
    document.getElementById('deleteModalMessage').innerHTML = 'Are you sure you want to permanently delete this resident account? All associated data will be removed.';
    document.getElementById('deleteAccountName').innerHTML = name;
    document.getElementById('deleteConfirmBtn').innerHTML = 'Delete Resident';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
    pendingDeleteId = null;
    pendingDeleteType = null;
}

function confirmDeleteAction() {
    if (pendingDeleteId && pendingDeleteType) {
        if (pendingDeleteType === 'admin') {
            document.getElementById('delete_admin_id').value = pendingDeleteId;
            document.getElementById('deleteAdminForm').submit();
        } else if (pendingDeleteType === 'resident') {
            document.getElementById('delete_resident_id').value = pendingDeleteId;
            document.getElementById('deleteResidentForm').submit();
        }
    }
    closeDeleteModal();
}

// Close modals
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.classList.remove('modal-open');
}

// Set confirm button handlers
document.getElementById('toggleConfirmBtn').onclick = confirmToggleAction;
document.getElementById('deleteConfirmBtn').onclick = confirmDeleteAction;

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
    if (event.target.classList.contains('confirm-modal')) {
        closeToggleModal();
        closeDeleteModal();
    }
}
</script>

<?php include '../includes/admin_footer.php'; ?>