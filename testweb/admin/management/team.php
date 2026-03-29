<?php
// admin/management/team.php

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

// Only Main Admin can manage team - Use isMainAdmin() function
if (!isMainAdmin()) {
    $_SESSION['error'] = "You don't have permission to manage team members";
    header('Location: ../dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Ensure the table exists with correct structure
$create_table = "CREATE TABLE IF NOT EXISTS team_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    position_category ENUM('barangay_official', 'sk_official', 'staff', 'volunteer') DEFAULT 'barangay_official',
    biography TEXT,
    contact_info VARCHAR(255),
    profile_image VARCHAR(255),
    display_order INT DEFAULT 0,
    term_start DATE,
    term_end DATE,
    committee VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$db->exec($create_table);

// Handle add team member
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $position_category = $_POST['position_category'] ?? 'barangay_official';
    $biography = trim($_POST['biography'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $term_start = !empty($_POST['term_start']) ? $_POST['term_start'] : null;
    $term_end = !empty($_POST['term_end']) ? $_POST['term_end'] : null;
    $committee = trim($_POST['committee'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../../assets/uploads/team/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'team_' . time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $file_name)) {
                $profile_image = $file_name;
            }
        }
    }

    try {
        $query = "INSERT INTO team_members (full_name, position, position_category, biography, contact_info, profile_image, display_order, term_start, term_end, committee, is_active) 
                  VALUES (:full_name, :position, :category, :biography, :contact, :image, :order, :term_start, :term_end, :committee, :active)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':category', $position_category);
        $stmt->bindParam(':biography', $biography);
        $stmt->bindParam(':contact', $contact_info);
        $stmt->bindParam(':image', $profile_image);
        $stmt->bindParam(':order', $display_order);
        $stmt->bindParam(':term_start', $term_start);
        $stmt->bindParam(':term_end', $term_end);
        $stmt->bindParam(':committee', $committee);
        $stmt->bindParam(':active', $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Team member added successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to add team member: " . $e->getMessage();
    }
    header('Location: team.php');
    exit();
}

// Handle update team member
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_member'])) {
    $member_id = $_POST['member_id'];
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $position_category = $_POST['position_category'] ?? 'barangay_official';
    $biography = trim($_POST['biography'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $term_start = !empty($_POST['term_start']) ? $_POST['term_start'] : null;
    $term_end = !empty($_POST['term_end']) ? $_POST['term_end'] : null;
    $committee = trim($_POST['committee'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Get current image
    $img_query = "SELECT profile_image FROM team_members WHERE id = :id";
    $img_stmt = $db->prepare($img_query);
    $img_stmt->bindParam(':id', $member_id);
    $img_stmt->execute();
    $current = $img_stmt->fetch(PDO::FETCH_ASSOC);
    $profile_image = $current['profile_image'];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../../assets/uploads/team/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            if ($profile_image && file_exists($upload_dir . $profile_image)) {
                unlink($upload_dir . $profile_image);
            }
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'team_' . time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $file_name)) {
                $profile_image = $file_name;
            }
        }
    }

    try {
        $query = "UPDATE team_members SET 
                  full_name = :full_name, 
                  position = :position, 
                  position_category = :category,
                  biography = :biography, 
                  contact_info = :contact, 
                  profile_image = :image,
                  display_order = :order, 
                  term_start = :term_start, 
                  term_end = :term_end,
                  committee = :committee, 
                  is_active = :active 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':category', $position_category);
        $stmt->bindParam(':biography', $biography);
        $stmt->bindParam(':contact', $contact_info);
        $stmt->bindParam(':image', $profile_image);
        $stmt->bindParam(':order', $display_order);
        $stmt->bindParam(':term_start', $term_start);
        $stmt->bindParam(':term_end', $term_end);
        $stmt->bindParam(':committee', $committee);
        $stmt->bindParam(':active', $is_active);
        $stmt->bindParam(':id', $member_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Team member updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update team member: " . $e->getMessage();
    }
    header('Location: team.php');
    exit();
}

// Handle delete team member
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_member'])) {
    $member_id = $_POST['member_id'];
    try {
        $info_query = "SELECT profile_image, full_name FROM team_members WHERE id = :id";
        $info_stmt = $db->prepare($info_query);
        $info_stmt->bindParam(':id', $member_id);
        $info_stmt->execute();
        $member = $info_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($member && $member['profile_image']) {
            $upload_dir = '../../assets/uploads/team/';
            if (file_exists($upload_dir . $member['profile_image'])) {
                unlink($upload_dir . $member['profile_image']);
            }
        }
        
        $query = "DELETE FROM team_members WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $member_id);
        $stmt->execute();
        
        $_SESSION['success'] = "Team member deleted successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete team member";
    }
    header('Location: team.php');
    exit();
}

// Fetch all team members
$team_members = [];
try {
    $query = "SELECT * FROM team_members ORDER BY 
              FIELD(position_category, 'barangay_official', 'sk_official', 'staff', 'volunteer'),
              display_order, full_name";
    $stmt = $db->query($query);
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $team_members = [];
}

// Get statistics
$stats = ['total' => 0, 'barangay_officials' => 0, 'sk_officials' => 0, 'staff' => 0, 'volunteer' => 0, 'active' => 0];
try {
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN position_category = 'barangay_official' THEN 1 ELSE 0 END) as barangay_officials,
                    SUM(CASE WHEN position_category = 'sk_official' THEN 1 ELSE 0 END) as sk_officials,
                    SUM(CASE WHEN position_category = 'staff' THEN 1 ELSE 0 END) as staff,
                    SUM(CASE WHEN position_category = 'volunteer' THEN 1 ELSE 0 END) as volunteer,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                    FROM team_members";
    $stats_stmt = $db->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
} catch (Exception $e) {
    // Keep default stats
}

include '../includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ===== TEAM MANAGEMENT STYLES ===== */
.team-module {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

/* Header */
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
    background: #2563eb;
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
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* Alerts */
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

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Team Grid */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.team-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}

.team-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.team-card.inactive {
    opacity: 0.7;
    background: #f8fafc;
}

.team-image {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.team-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-image i {
    font-size: 4rem;
    color: rgba(255,255,255,0.8);
}

.team-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    background: rgba(0,0,0,0.6);
    color: white;
}

.team-info {
    padding: 1.25rem;
    flex: 1;
}

.team-info h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.team-position {
    font-size: 0.85rem;
    color: #2563eb;
    font-weight: 500;
    margin-bottom: 0.75rem;
}

.team-category {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-bottom: 0.75rem;
}

.category-barangay_official { background: #dbeafe; color: #1e40af; }
.category-sk_official { background: #fef3c7; color: #b45309; }
.category-staff { background: #dcfce7; color: #166534; }
.category-volunteer { background: #f1f5f9; color: #475569; }

.team-bio {
    color: #475569;
    font-size: 0.85rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.team-details {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.75rem;
    color: #64748b;
}

.team-details span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.team-details i {
    font-size: 0.9rem;
}

.team-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.team-footer {
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn-edit, .btn-delete {
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
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
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

.empty-state i {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    color: #cbd5e1;
}

/* ===== MODAL STYLES - NO DOUBLE SCROLLBAR ===== */
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

/* Form Styles */
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
    line-height: 1.5;
}

select.form-control {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.9rem center;
    background-size: 14px;
    padding-right: 2.2rem;
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

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
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
        grid-template-columns: repeat(2, 1fr);
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .team-footer {
        flex-wrap: wrap;
    }
    
    .btn-edit, .btn-delete {
        flex: 1;
        justify-content: center;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
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

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="team-module">
    <!-- Header -->
    <div class="module-header">
        <div class="module-title">
            <h1>Team Management</h1>
            <p>Manage barangay officials, SK officials, staff, and volunteers</p>
        </div>
        <div class="header-actions">
            <button onclick="openModal('addModal')" class="btn-primary">
                <i class="fas fa-user-plus"></i> Add Team Member
            </button>
        </div>
    </div>

    <!-- Alerts -->
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

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                <p>Total Members</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['active'] ?? 0; ?></h3>
                <p>Active Members</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <i class="fas fa-landmark"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['barangay_officials'] ?? 0; ?></h3>
                <p>Barangay Officials</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['sk_officials'] ?? 0; ?></h3>
                <p>SK Officials</p>
            </div>
        </div>
    </div>

    <!-- Team Grid -->
    <div class="team-grid">
        <?php if (empty($team_members)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No team members found</p>
                <button onclick="openModal('addModal')" class="btn-primary" style="margin-top: 1rem;">
                    Add Your First Team Member
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($team_members as $member): ?>
            <div class="team-card <?php echo !$member['is_active'] ? 'inactive' : ''; ?>">
                <div class="team-image">
                    <?php if ($member['profile_image'] && file_exists('../../assets/uploads/team/' . $member['profile_image'])): ?>
                        <img src="../../assets/uploads/team/<?php echo $member['profile_image']; ?>" alt="<?php echo htmlspecialchars($member['full_name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span class="team-badge"><?php echo str_replace('_', ' ', ucfirst($member['position_category'])); ?></span>
                </div>
                
                <div class="team-info">
                    <h3><?php echo htmlspecialchars($member['full_name']); ?></h3>
                    <div class="team-position"><?php echo htmlspecialchars($member['position']); ?></div>
                    <span class="team-category category-<?php echo $member['position_category']; ?>">
                        <?php echo str_replace('_', ' ', ucfirst($member['position_category'])); ?>
                    </span>
                    
                    <?php if ($member['biography']): ?>
                    <div class="team-bio">
                        <?php echo htmlspecialchars(substr($member['biography'], 0, 120)); ?>
                        <?php if (strlen($member['biography']) > 120): ?>...<?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="team-details">
                        <?php if ($member['committee']): ?>
                        <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($member['committee']); ?></span>
                        <?php endif; ?>
                        <?php if ($member['term_start']): ?>
                        <span><i class="fas fa-calendar"></i> Term: <?php echo date('Y', strtotime($member['term_start'])); ?> - <?php echo $member['term_end'] ? date('Y', strtotime($member['term_end'])) : 'Present'; ?></span>
                        <?php endif; ?>
                        <?php if ($member['contact_info']): ?>
                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['contact_info']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="team-status">
                        <span class="status-badge status-<?php echo $member['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <?php if ($member['display_order'] > 0): ?>
                        <span class="order-badge" style="font-size: 0.7rem; color: #64748b;">Order: <?php echo $member['display_order']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="team-footer">
                    <button onclick="editMember(<?php echo htmlspecialchars(json_encode($member)); ?>)" class="btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="deleteMember(<?php echo $member['id']; ?>)" class="btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Add Team Member</h2>
            <button class="close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Position <span class="required">*</span></label>
                        <input type="text" class="form-control" name="position" required placeholder="e.g., Barangay Captain">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category <span class="required">*</span></label>
                        <select class="form-control" name="position_category" required>
                            <option value="barangay_official">Barangay Official</option>
                            <option value="sk_official">SK Official</option>
                            <option value="staff">Staff</option>
                            <option value="volunteer">Volunteer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" class="form-control" name="display_order" value="0" min="0">
                        <small style="color: #6c757d;">Lower numbers appear first</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Biography</label>
                    <textarea class="form-control" name="biography" rows="3" placeholder="Brief description or bio..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Committee/Department</label>
                        <input type="text" class="form-control" name="committee" placeholder="e.g., Committee on Peace and Order">
                    </div>
                    <div class="form-group">
                        <label>Contact Info</label>
                        <input type="text" class="form-control" name="contact_info" placeholder="Phone or email">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Term Start</label>
                        <input type="date" class="form-control" name="term_start">
                    </div>
                    <div class="form-group">
                        <label>Term End</label>
                        <input type="date" class="form-control" name="term_end">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                    <small style="color: #6c757d;">Accepted formats: JPG, PNG, GIF (Max 2MB)</small>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="add_is_active" checked>
                    <label for="add_is_active">Active (Visible to public)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="add_member" class="btn-save">Add Member</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Team Member</h2>
            <button class="close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="member_id" id="edit_member_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Position <span class="required">*</span></label>
                        <input type="text" class="form-control" id="edit_position" name="position" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category <span class="required">*</span></label>
                        <select class="form-control" id="edit_position_category" name="position_category" required>
                            <option value="barangay_official">Barangay Official</option>
                            <option value="sk_official">SK Official</option>
                            <option value="staff">Staff</option>
                            <option value="volunteer">Volunteer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" class="form-control" id="edit_display_order" name="display_order" min="0">
                        <small style="color: #6c757d;">Lower numbers appear first</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Biography</label>
                    <textarea class="form-control" id="edit_biography" name="biography" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Committee/Department</label>
                        <input type="text" class="form-control" id="edit_committee" name="committee">
                    </div>
                    <div class="form-group">
                        <label>Contact Info</label>
                        <input type="text" class="form-control" id="edit_contact_info" name="contact_info">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Term Start</label>
                        <input type="date" class="form-control" id="edit_term_start" name="term_start">
                    </div>
                    <div class="form-group">
                        <label>Term End</label>
                        <input type="date" class="form-control" id="edit_term_end" name="term_end">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                    <small style="color: #6c757d;">Leave empty to keep current image</small>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="edit_is_active">
                    <label for="edit_is_active">Active (Visible to public)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_member" class="btn-save">Update Member</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="member_id" id="delete_member_id">
    <input type="hidden" name="delete_member" value="1">
</form>

<script>
// Modal functions - No double scrollbar
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

// Edit member
function editMember(member) {
    document.getElementById('edit_member_id').value = member.id;
    document.getElementById('edit_full_name').value = member.full_name;
    document.getElementById('edit_position').value = member.position;
    document.getElementById('edit_position_category').value = member.position_category;
    document.getElementById('edit_display_order').value = member.display_order;
    document.getElementById('edit_biography').value = member.biography || '';
    document.getElementById('edit_committee').value = member.committee || '';
    document.getElementById('edit_contact_info').value = member.contact_info || '';
    document.getElementById('edit_term_start').value = member.term_start || '';
    document.getElementById('edit_term_end').value = member.term_end || '';
    document.getElementById('edit_is_active').checked = member.is_active == 1;
    
    openModal('editModal');
}

// Delete member
function deleteMember(id) {
    if (confirm('Are you sure you want to delete this team member? This action cannot be undone.')) {
        document.getElementById('delete_member_id').value = id;
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