<?php
// admin/management/services.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../includes/permissions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle add service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements'] ?? '');
    $fee = floatval($_POST['fee'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "INSERT INTO services (service_name, description, requirements, fee, is_active) 
                  VALUES (:name, :desc, :req, :fee, :active)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $service_name);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':req', $requirements);
        $stmt->bindParam(':fee', $fee);
        $stmt->bindParam(':active', $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Service added successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to add service: " . $e->getMessage();
    }
    header('Location: services.php');
    exit();
}

// Handle update service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements'] ?? '');
    $fee = floatval($_POST['fee'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "UPDATE services SET 
                  service_name = :name, 
                  description = :desc, 
                  requirements = :req, 
                  fee = :fee, 
                  is_active = :active 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $service_name);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':req', $requirements);
        $stmt->bindParam(':fee', $fee);
        $stmt->bindParam(':active', $is_active);
        $stmt->bindParam(':id', $service_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Service updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update service: " . $e->getMessage();
    }
    header('Location: services.php');
    exit();
}

// Handle delete service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_service'])) {
    if (isMainAdmin()) {
        $service_id = $_POST['service_id'];
        
        try {
            $name_query = "SELECT service_name FROM services WHERE id = :id";
            $name_stmt = $db->prepare($name_query);
            $name_stmt->bindParam(':id', $service_id);
            $name_stmt->execute();
            $service = $name_stmt->fetch(PDO::FETCH_ASSOC);
            
            $check_query = "SELECT COUNT(*) as count FROM resident_requests WHERE service_id = :id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':id', $service_id);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $_SESSION['error'] = "Cannot delete service with existing requests. Set it to inactive instead.";
            } else {
                $query = "DELETE FROM services WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $service_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Service deleted successfully";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to delete service: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete services";
    }
    header('Location: services.php');
    exit();
}

// Handle toggle service status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $service_id = $_POST['service_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status ? 0 : 1;
    
    try {
        $query = "UPDATE services SET is_active = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $service_id);
        
        if ($stmt->execute()) {
            $status_text = $new_status ? 'activated' : 'deactivated';
            $_SESSION['success'] = "Service $status_text successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to toggle service status";
    }
    header('Location: services.php');
    exit();
}

// Fetch all services
$query = "SELECT * FROM services ORDER BY service_name";
$stmt = $db->query($query);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                AVG(fee) as avg_fee
                FROM services";
$stats_stmt = $db->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.services-module {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.module-title h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.module-title p {
    color: #64748b;
    font-size: 0.95rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    padding: 1.2rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i {
    font-size: 1.5rem;
    color: white;
}

.stat-details h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.stat-details p {
    color: #64748b;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.service-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.service-card.inactive {
    opacity: 0.7;
    background: #f8fafc;
}

.service-header {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.service-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
}

.service-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.service-content {
    padding: 1.5rem;
    flex: 1;
}

.service-description {
    color: #475569;
    line-height: 1.5;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.service-details {
    background: #f8fafc;
    padding: 0.75rem;
    border-radius: 8px;
    margin: 1rem 0;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #475569;
}

.detail-item i {
    font-size: 1rem;
    color: #64748b;
}

.service-requirements {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #fef3c7;
    border-radius: 8px;
    color: #b45309;
    font-size: 0.85rem;
}

.service-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-edit, .btn-delete, .btn-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-edit {
    background: #2563eb;
    color: white;
}

.btn-edit:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

.btn-toggle {
    background: #f1f5f9;
    color: #475569;
}

.btn-toggle:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}

.btn-toggle.active {
    background: #22c55e;
    color: white;
}

.btn-toggle.inactive {
    background: #ef4444;
    color: white;
}

.empty-state {
    grid-column: 1 / -1;
    padding: 3rem;
    text-align: center;
    color: #94a3b8;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

/* ===== MODAL STYLES - WITH INNER SCROLLBAR ===== */
body.modal-open {
    overflow: hidden !important;
    position: fixed;
    width: 100%;
    height: 100%;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow: hidden !important;
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
    max-height: 92vh;
    display: flex;
    flex-direction: column;
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
    flex-shrink: 0;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.modal-header h2 i {
    color: #2563eb;
}

.modal-header .close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    transition: color 0.2s;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-header .close:hover {
    background: #f1f5f9;
    color: #dc3545;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto !important;
    flex: 1;
    scrollbar-width: thin;
    max-height: calc(85vh - 140px);
    min-height: 200px; /* Ensures there's a minimum height for scrolling */
}

/* Custom scrollbar styling */
.modal-body::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Firefox scrollbar */
.modal-body {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f1f1;
}

.modal-footer {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e9ecef;
    flex-shrink: 0;
}

/* Form Styles - Like Resident Information */
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #4b5563;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group label .required {
    color: #ef4444;
    margin-left: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.85rem;
    font-family: inherit;
    box-sizing: border-box;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 12px;
}

.form-row .form-group {
    flex: 1;
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
    font-size: 0.8rem;
    color: #475569;
    text-transform: none;
}

.checkbox-group input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.btn-save, .btn-cancel {
    padding: 8px 20px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-save {
    background: #3b82f6;
    color: white;
    border: none;
}

.btn-save:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-cancel {
    background: #e5e7eb;
    color: #374151;
    border: none;
}

.btn-cancel:hover {
    background: #d1d5db;
}

@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .services-module {
        padding: 0.5rem;
    }
    
    .module-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .service-footer {
        flex-direction: column;
    }
    
    .btn-edit, .btn-delete, .btn-toggle {
        width: 100%;
        justify-content: center;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem auto;
        max-height: 90vh;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .btn-save, .btn-cancel {
        width: 100%;
        justify-content: center;
    }
    
    .modal-body {
        max-height: calc(90vh - 140px);
    }
}
</style>

<div class="services-module">
    <div class="module-header">
        <div class="module-title">
            <h1>Services Management</h1>
            <p>Manage barangay services and requirements</p>
        </div>
        <div class="header-actions">
            <button onclick="openModal('addModal')" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Service
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                <i class="fas fa-concierge-bell"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                <p>Total Services</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['active'] ?? 0; ?></h3>
                <p>Active Services</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['inactive'] ?? 0; ?></h3>
                <p>Inactive Services</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-tag"></i>
            </div>
            <div class="stat-details">
                <h3>₱<?php echo number_format($stats['avg_fee'] ?? 0, 2); ?></h3>
                <p>Average Fee</p>
            </div>
        </div>
    </div>

    <div class="services-grid">
        <?php if (empty($services)): ?>
            <div class="empty-state">
                <i class="fas fa-concierge-bell"></i>
                <p>No services found</p>
                <button onclick="openModal('addModal')" class="btn-primary" style="margin-top: 1rem;">Add Your First Service</button>
            </div>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
            <div class="service-card <?php echo !$service['is_active'] ? 'inactive' : ''; ?>">
                <div class="service-header">
                    <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                    <div class="service-status">
                        <span class="status-badge status-<?php echo $service['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="service-content">
                    <div class="service-description">
                        <?php echo nl2br(htmlspecialchars(substr($service['description'], 0, 150))); ?>
                        <?php if (strlen($service['description']) > 150): ?>...<?php endif; ?>
                    </div>
                    
                    <div class="service-details">
                        <div class="detail-item">
                            <i class="fas fa-tag"></i>
                            <span>Fee: <?php echo $service['fee'] > 0 ? '₱' . number_format($service['fee'], 2) : 'Free'; ?></span>
                        </div>
                    </div>
                    
                    <?php if ($service['requirements']): ?>
                    <div class="service-requirements">
                        <i class="fas fa-clipboard-list"></i>
                        <strong>Requirements:</strong><br>
                        <?php echo nl2br(htmlspecialchars(substr($service['requirements'], 0, 100))); ?>
                        <?php if (strlen($service['requirements']) > 100): ?>...<?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="service-footer">
                    <button onclick="toggleStatus(<?php echo $service['id']; ?>, <?php echo $service['is_active']; ?>)" 
                            class="btn-toggle <?php echo $service['is_active'] ? 'active' : 'inactive'; ?>">
                        <i class="fas <?php echo $service['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                        <?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                    <button onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" class="btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <?php if (isMainAdmin()): ?>
                    <button onclick="deleteService(<?php echo $service['id']; ?>)" class="btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Service</h2>
            <button class="close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Service Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="service_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea class="form-control" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Requirements</label>
                    <textarea class="form-control" name="requirements" rows="3" placeholder="List all requirements..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Fee (₱)</label>
                    <input type="number" class="form-control" name="fee" step="0.01" min="0" value="0">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="add_is_active" checked>
                    <label for="add_is_active">Active (Service is available to residents)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="add_service" class="btn-save">Add Service</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal - With Scrollbar -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Service</h2>
            <button class="close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="service_id" id="edit_service_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Service Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="edit_service_name" name="service_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Requirements</label>
                    <textarea class="form-control" id="edit_requirements" name="requirements" rows="6" placeholder="List all requirements...&#10;&#10;Example:&#10;1. Valid ID&#10;2. Barangay Clearance&#10;3. Application Form&#10;4. 2x2 Picture&#10;5. Proof of Residency"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Fee (₱)</label>
                    <input type="number" class="form-control" id="edit_fee" name="fee" step="0.01" min="0">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="edit_is_active">
                    <label for="edit_is_active">Active (Service is available to residents)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_service" class="btn-save">Update Service</button>
            </div>
        </form>
    </div>
</div>

<form id="toggleForm" method="POST" style="display: none;">
    <input type="hidden" name="service_id" id="toggle_service_id">
    <input type="hidden" name="current_status" id="toggle_current_status">
    <input type="hidden" name="toggle_status" value="1">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="service_id" id="delete_service_id">
    <input type="hidden" name="delete_service" value="1">
</form>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        // Store current scroll position
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
        
        // Ensure modal body gets proper height for scrolling
        setTimeout(() => {
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody) {
                modalBody.style.maxHeight = 'calc(85vh - 140px)';
            }
        }, 10);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        // Restore scroll position
        const scrollY = document.body.style.top;
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        window.scrollTo(0, parseInt(scrollY || '0') * -1);
    }
}

function editService(service) {
    document.getElementById('edit_service_id').value = service.id;
    document.getElementById('edit_service_name').value = service.service_name;
    document.getElementById('edit_description').value = service.description;
    document.getElementById('edit_requirements').value = service.requirements || '';
    document.getElementById('edit_fee').value = service.fee || 0;
    document.getElementById('edit_is_active').checked = service.is_active == 1;
    openModal('editModal');
}

function toggleStatus(id, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this service?')) {
        document.getElementById('toggle_service_id').value = id;
        document.getElementById('toggle_current_status').value = currentStatus;
        document.getElementById('toggleForm').submit();
    }
}

function deleteService(id) {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
        document.getElementById('delete_service_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        openModals.forEach(modal => {
            closeModal(modal.id);
        });
    }
});
</script>

<?php include '../includes/admin_footer.php'; ?>