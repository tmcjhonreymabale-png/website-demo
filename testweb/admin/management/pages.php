<?php
// admin/management/pages.php

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

// First, ensure pages table has all required columns
try {
    // Check and add meta_description column
    $check_meta = "SHOW COLUMNS FROM pages LIKE 'meta_description'";
    $check_meta_stmt = $db->prepare($check_meta);
    $check_meta_stmt->execute();
    if ($check_meta_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pages ADD COLUMN meta_description TEXT AFTER content");
    }
    
    // Check and add featured_image column
    $check_image = "SHOW COLUMNS FROM pages LIKE 'featured_image'";
    $check_image_stmt = $db->prepare($check_image);
    $check_image_stmt->execute();
    if ($check_image_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pages ADD COLUMN featured_image VARCHAR(255) AFTER meta_description");
    }
    
    // Check and add updated_by column
    $check_updated = "SHOW COLUMNS FROM pages LIKE 'updated_by'";
    $check_updated_stmt = $db->prepare($check_updated);
    $check_updated_stmt->execute();
    if ($check_updated_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pages ADD COLUMN updated_by INT AFTER featured_image");
    }
    
    // Check and add last_updated column
    $check_last = "SHOW COLUMNS FROM pages LIKE 'last_updated'";
    $check_last_stmt = $db->prepare($check_last);
    $check_last_stmt->execute();
    if ($check_last_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pages ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by");
    }
} catch (Exception $e) {
    // Silently continue
}

// Create about_sections table with correct structure for frontend
try {
    $check_about_table = "SHOW TABLES LIKE 'about_sections'";
    $table_check = $db->prepare($check_about_table);
    $table_check->execute();
    $table_exists = $table_check->rowCount() > 0;
    
    if (!$table_exists) {
        $create_about = "CREATE TABLE about_sections (
            id INT PRIMARY KEY AUTO_INCREMENT,
            page_id INT,
            section_type ENUM('history', 'mission', 'vision', 'officials', 'contact', 'general') DEFAULT 'general',
            section_title VARCHAR(255),
            section_content TEXT,
            display_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )";
        $db->exec($create_about);
    } else {
        // Check and add missing columns to about_sections
        $required_columns = [
            'page_id' => 'INT',
            'section_type' => "ENUM('history', 'mission', 'vision', 'officials', 'contact', 'general') DEFAULT 'general'",
            'section_title' => 'VARCHAR(255)',
            'section_content' => 'TEXT',
            'display_order' => 'INT DEFAULT 0',
            'is_active' => 'BOOLEAN DEFAULT TRUE'
        ];
        
        foreach ($required_columns as $column => $definition) {
            try {
                $check_col = $db->prepare("SHOW COLUMNS FROM about_sections LIKE :column");
                $check_col->bindParam(':column', $column);
                $check_col->execute();
                if ($check_col->rowCount() == 0) {
                    $db->exec("ALTER TABLE about_sections ADD COLUMN $column $definition");
                }
            } catch (Exception $e) {
                // Column might already exist
            }
        }
    }
} catch (Exception $e) {
    // Table creation failed
}

// Ensure all required pages exist
$required_pages = ['home', 'about', 'announcements', 'services'];
$default_titles = [
    'home' => 'Welcome to Barangay System',
    'about' => 'About Us',
    'announcements' => 'Barangay Announcements',
    'services' => 'Barangay Services'
];
$default_contents = [
    'home' => '<h1>Welcome to Barangay Cabuco</h1><p>This is the official website of Barangay Cabuco. We are committed to serving our community with excellence and transparency.</p><p>Our barangay is dedicated to providing quality public service, promoting the welfare of our residents, and fostering a safe, healthy, and progressive community.</p>',
    'about' => '<p>Learn more about Barangay Cabuco, its history, mission, and vision. Our barangay is committed to serving the community with dedication and integrity.</p>',
    'announcements' => '<p>Stay updated with the latest news and announcements from your Barangay. We regularly post important information, events, and updates for our residents.</p>',
    'services' => '<p>We offer various services to cater to the needs of our residents. Browse through our available services and request them online for your convenience.</p>'
];

foreach ($required_pages as $page_name) {
    $check_query = "SELECT id FROM pages WHERE page_name = :page_name";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':page_name', $page_name);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        $insert_query = "INSERT INTO pages (page_name, title, content, meta_description, created_at, last_updated) 
                         VALUES (:page_name, :title, :content, :meta, NOW(), NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':page_name', $page_name);
        $insert_stmt->bindParam(':title', $default_titles[$page_name]);
        $insert_stmt->bindParam(':content', $default_contents[$page_name]);
        $meta = "View the official " . $page_name . " page of Barangay Cabuco";
        $insert_stmt->bindParam(':meta', $meta);
        $insert_stmt->execute();
    }
}

// Handle add announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'] ?? 'active';
    
    try {
        $query = "INSERT INTO announcements (title, content, posted_by, status) 
                  VALUES (:title, :content, :posted_by, :status)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':posted_by', $_SESSION['admin_id']);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Announcement added successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to add announcement: " . $e->getMessage();
    }
    header('Location: pages.php#announcements');
    exit();
}

// Handle update announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'] ?? 'active';
    
    try {
        $query = "UPDATE announcements SET 
                  title = :title, 
                  content = :content, 
                  status = :status 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $announcement_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Announcement updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update announcement: " . $e->getMessage();
    }
    header('Location: pages.php#announcements');
    exit();
}

// Handle delete announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_announcement'])) {
    if (($_SESSION['admin_role'] ?? '') == 'Main Admin') {
        $announcement_id = $_POST['announcement_id'];
        
        try {
            $query = "DELETE FROM announcements WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $announcement_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Announcement deleted successfully";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to delete announcement";
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete announcements";
    }
    header('Location: pages.php#announcements');
    exit();
}

// Handle add about section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_about_section'])) {
    $section_title = trim($_POST['section_title']);
    $section_content = trim($_POST['section_content']);
    $section_type = trim($_POST['section_type']);
    $display_order = intval($_POST['display_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        // Get the about page ID
        $page_query = "SELECT id FROM pages WHERE page_name = 'about'";
        $page_stmt = $db->query($page_query);
        $page = $page_stmt->fetch(PDO::FETCH_ASSOC);
        $page_id = $page['id'];
        
        $query = "INSERT INTO about_sections (page_id, section_title, section_content, section_type, display_order, is_active) 
                  VALUES (:page_id, :title, :content, :type, :order, :active)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':page_id', $page_id);
        $stmt->bindParam(':title', $section_title);
        $stmt->bindParam(':content', $section_content);
        $stmt->bindParam(':type', $section_type);
        $stmt->bindParam(':order', $display_order);
        $stmt->bindParam(':active', $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "About section added successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to add about section: " . $e->getMessage();
    }
    header('Location: pages.php#about');
    exit();
}

// Handle update about section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_about_section'])) {
    $section_id = $_POST['section_id'];
    $section_title = trim($_POST['section_title']);
    $section_content = trim($_POST['section_content']);
    $section_type = trim($_POST['section_type']);
    $display_order = intval($_POST['display_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "UPDATE about_sections SET 
                  section_title = :title, 
                  section_content = :content, 
                  section_type = :type,
                  display_order = :order,
                  is_active = :active
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $section_title);
        $stmt->bindParam(':content', $section_content);
        $stmt->bindParam(':type', $section_type);
        $stmt->bindParam(':order', $display_order);
        $stmt->bindParam(':active', $is_active);
        $stmt->bindParam(':id', $section_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "About section updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update about section: " . $e->getMessage();
    }
    header('Location: pages.php#about');
    exit();
}

// Handle delete about section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_about_section'])) {
    if (($_SESSION['admin_role'] ?? '') == 'Main Admin') {
        $section_id = $_POST['section_id'];
        
        try {
            $query = "DELETE FROM about_sections WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $section_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "About section deleted successfully";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to delete about section";
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete about sections";
    }
    header('Location: pages.php#about');
    exit();
}

// Handle page update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_page'])) {
    $page_id = $_POST['page_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    try {
        $query = "UPDATE pages SET 
                  title = :title, 
                  content = :content, 
                  meta_description = :meta_description,
                  updated_by = :admin_id, 
                  last_updated = NOW() 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':meta_description', $meta_description);
        $stmt->bindParam(':admin_id', $_SESSION['admin_id']);
        $stmt->bindParam(':id', $page_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Page updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update page: " . $e->getMessage();
    }
    header('Location: pages.php#pages');
    exit();
}

// Fetch all pages
$pages_query = "SELECT p.*, CONCAT(a.first_name, ' ', a.last_name) as updated_by_name 
                FROM pages p 
                LEFT JOIN admins a ON p.updated_by = a.id
                ORDER BY FIELD(p.page_name, 'home', 'about', 'announcements', 'services'), p.page_name";
$pages_stmt = $db->query($pages_query);
$pages = $pages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all announcements
$announcements_query = "SELECT a.*, CONCAT(adm.first_name, ' ', adm.last_name) as posted_by_name 
                        FROM announcements a 
                        LEFT JOIN admins adm ON a.posted_by = adm.id 
                        ORDER BY a.created_at DESC";
$announcements_stmt = $db->query($announcements_query);
$announcements = $announcements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch about sections
$about_sections = [];
try {
    $about_query = "SELECT * FROM about_sections ORDER BY display_order, id";
    $about_stmt = $db->query($about_query);
    $about_sections = $about_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $about_sections = [];
}

include '../includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ===== PAGE & ANNOUNCEMENT MANAGEMENT STYLES ===== */
.pages-module {
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

/* Tab Navigation */
.tab-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 0.6rem 1.5rem;
    background: none;
    border: none;
    border-radius: 6px 6px 0 0;
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: #2563eb;
    background: #f8fafc;
}

.tab-btn.active {
    color: #2563eb;
    border-bottom: 3px solid #2563eb;
    background: #f8fafc;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Alerts */
.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 6px;
    animation: slideIn 0.3s ease;
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

/* Add Button */
.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: #22c55e;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 1.5rem;
}

.btn-add:hover {
    background: #16a34a;
    transform: translateY(-1px);
}

/* Pages Grid */
.pages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.page-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}

.page-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.page-header {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    text-transform: capitalize;
}

.page-badge {
    background: #2563eb;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.page-content {
    padding: 1.5rem;
    flex: 1;
}

.page-preview {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #e2e8f0;
}

.page-preview h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.page-preview p {
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 0;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.page-meta {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.last-updated {
    font-size: 0.75rem;
    color: #64748b;
}

.btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-edit:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* ===== MODAL STYLES - WITH SCROLLBAR ===== */
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

/* Always show scrollbar when content overflows */
.modal-body {
    overflow-y: auto;
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

/* Form Styles - EXACTLY LIKE RESIDENT INFORMATION */
.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 12px;
}

.form-row .form-group {
    flex: 1;
}

.form-group {
    margin-bottom: 12px;
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
    min-height: 150px;
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

/* Buttons - EXACTLY LIKE RESIDENT INFORMATION */
.btn-save {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-save:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-cancel {
    background: #e5e7eb;
    color: #374151;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-cancel:hover {
    background: #d1d5db;
}

/* Announcements & About Cards */
.announcements-header, .about-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.announcements-header h2, .about-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
}

.announcements-grid, .about-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.announcement-card, .about-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}

.announcement-card:hover, .about-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.announcement-card.inactive, .about-card.inactive {
    opacity: 0.7;
    background: #f8fafc;
}

.announcement-header, .about-header-card {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.announcement-header h3, .about-header-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.section-type-badge {
    background: #8b5cf6;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.announcement-status {
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

.announcement-content, .about-content {
    padding: 1.5rem;
    flex: 1;
}

.announcement-text, .about-text {
    margin-bottom: 1rem;
    line-height: 1.6;
    color: #475569;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
}

.announcement-meta, .about-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    flex-wrap: wrap;
}

.announcement-footer, .about-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn-delete {
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-delete:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    padding: 3rem;
    text-align: center;
    color: #94a3b8;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

/* Responsive */
@media (max-width: 1024px) {
    .announcements-grid, .about-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .tab-nav {
        flex-wrap: wrap;
    }
    
    .pages-grid,
    .announcements-grid,
    .about-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header,
    .announcement-header,
    .about-header-card {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-footer,
    .announcement-footer,
    .about-footer {
        flex-direction: column;
    }
    
    .btn-edit, .btn-delete {
        width: 100%;
        justify-content: center;
    }
    
    .announcement-footer button,
    .about-footer button {
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
</style>

<div class="pages-module">
    <!-- Header -->
    <div class="module-header">
        <div class="module-title">
            <h1>Content Management</h1>
            <p>Manage website pages, announcements, and about sections</p>
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

    <!-- Tab Navigation -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab(event, 'pages')">
            <i class="fas fa-file-alt"></i> Pages
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'announcements')">
            <i class="fas fa-bullhorn"></i> Announcements
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'about')">
            <i class="fas fa-info-circle"></i> About Us
        </button>
    </div>

    <!-- Pages Tab -->
    <div id="pages-tab" class="tab-content active">
        <div class="pages-grid">
            <?php if (empty($pages)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>No pages found</p>
                </div>
            <?php else: ?>
                <?php foreach ($pages as $page): ?>
                <div class="page-card">
                    <div class="page-header">
                        <h3><?php echo ucfirst($page['page_name']); ?> Page</h3>
                        <span class="page-badge"><?php echo $page['page_name']; ?></span>
                    </div>
                    
                    <div class="page-content">
                        <div class="page-preview">
                            <h4><?php echo htmlspecialchars($page['title'] ?: 'No title'); ?></h4>
                            <p><?php echo htmlspecialchars(substr(strip_tags($page['content']), 0, 150)); ?><?php echo strlen(strip_tags($page['content'])) > 150 ? '...' : ''; ?></p>
                        </div>
                        
                        <?php if (!empty($page['meta_description'])): ?>
                        <div class="page-meta">
                            <small><strong>Meta:</strong> <?php echo htmlspecialchars(substr($page['meta_description'], 0, 100)); ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="page-footer">
                        <span class="last-updated">
                            <i class="fas fa-clock"></i>
                            <?php echo date('M j, Y g:i A', strtotime($page['last_updated'])); ?>
                            <?php if ($page['updated_by_name']): ?><br><i class="fas fa-user"></i> by <?php echo htmlspecialchars($page['updated_by_name']); ?><?php endif; ?>
                        </span>
                        <button onclick="editPage(<?php echo htmlspecialchars(json_encode($page)); ?>)" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit Page
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcements Tab -->
    <div id="announcements-tab" class="tab-content">
        <div class="announcements-header">
            <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
            <button onclick="showAddAnnouncementModal()" class="btn-add">
                <i class="fas fa-plus"></i> New Announcement
            </button>
        </div>

        <div class="announcements-grid">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn"></i>
                    <p>No announcements yet</p>
                    <button onclick="showAddAnnouncementModal()" class="btn-add" style="margin-top: 1rem;">
                        Create Your First Announcement
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card <?php echo $announcement['status'] == 'inactive' ? 'inactive' : ''; ?>">
                    <div class="announcement-header">
                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <div class="announcement-status">
                            <span class="status-badge status-<?php echo $announcement['status']; ?>">
                                <?php echo ucfirst($announcement['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="announcement-content">
                        <div class="announcement-text">
                            <?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 200))); ?><?php echo strlen($announcement['content']) > 200 ? '...' : ''; ?>
                        </div>
                        
                        <div class="announcement-meta">
                            <span>
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($announcement['posted_by_name'] ?: 'Unknown'); ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="announcement-footer">
                        <button onclick="editAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if (($_SESSION['admin_role'] ?? '') == 'Main Admin'): ?>
                        <button onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)" class="btn-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- About Us Tab -->
    <div id="about-tab" class="tab-content">
        <div class="about-header">
            <h2><i class="fas fa-info-circle"></i> About Us Sections</h2>
            <button onclick="showAddAboutModal()" class="btn-add">
                <i class="fas fa-plus"></i> Add Section
            </button>
        </div>

        <div class="about-grid">
            <?php if (empty($about_sections)): ?>
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p>No about sections yet</p>
                    <button onclick="showAddAboutModal()" class="btn-add" style="margin-top: 1rem;">
                        Add Your First Section
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($about_sections as $section): ?>
                <div class="about-card <?php echo !$section['is_active'] ? 'inactive' : ''; ?>">
                    <div class="about-header-card">
                        <h3><?php echo htmlspecialchars($section['section_title']); ?></h3>
                        <span class="section-type-badge"><?php echo ucfirst($section['section_type']); ?></span>
                    </div>
                    
                    <div class="about-content">
                        <div class="about-text">
                            <?php echo nl2br(htmlspecialchars(substr($section['section_content'], 0, 200))); ?><?php echo strlen($section['section_content']) > 200 ? '...' : ''; ?>
                        </div>
                        
                        <div class="about-meta">
                            <span>
                                <i class="fas fa-sort"></i>
                                Order: <?php echo $section['display_order']; ?>
                            </span>
                            <span>
                                <i class="fas fa-eye"></i>
                                <?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="about-footer">
                        <button onclick="editAboutSection(<?php echo htmlspecialchars(json_encode($section)); ?>)" class="btn-edit" style="background: #8b5cf6;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if (($_SESSION['admin_role'] ?? '') == 'Main Admin'): ?>
                        <button onclick="deleteAboutSection(<?php echo $section['id']; ?>)" class="btn-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Page Modal -->
<div id="editPageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Page</h2>
            <button class="close" onclick="closeModal('editPageModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="page_id" id="page_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Page Name</label>
                        <input type="text" class="form-control" id="pageNameDisplay" style="background: #f1f5f9;" readonly>
                    </div>
                    <div class="form-group">
                        <label>Page Title <span class="required">*</span></label>
                        <input type="text" class="form-control" id="page_title" name="title" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea class="form-control" id="page_meta" name="meta_description" rows="2" placeholder="SEO description for search engines"></textarea>
                    <small style="color: #6c757d; font-size: 0.7rem;">Optional: A brief description for search engines</small>
                </div>
                
                <div class="form-group">
                    <label>Page Content <span class="required">*</span></label>
                    <textarea class="form-control" id="page_content" name="content" rows="12" required></textarea>
                    <small style="color: #6c757d; font-size: 0.7rem;">You can use HTML tags: &lt;h1&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, etc.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editPageModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_page" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Announcement Modal -->
<div id="addAnnouncementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> New Announcement</h2>
            <button class="close" onclick="closeModal('addAnnouncementModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" class="form-control" id="announcement_title" name="title" required placeholder="Enter announcement title">
                </div>
                
                <div class="form-group">
                    <label>Content <span class="required">*</span></label>
                    <textarea class="form-control" id="announcement_content" name="content" rows="8" required placeholder="Write your announcement here..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" id="announcement_status" name="status">
                        <option value="active">Active (Visible on website)</option>
                        <option value="inactive">Inactive (Hidden)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addAnnouncementModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="add_announcement" class="btn-save">Post Announcement</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Announcement</h2>
            <button class="close" onclick="closeModal('editAnnouncementModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="announcement_id" id="edit_announcement_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" class="form-control" id="edit_announcement_title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Content <span class="required">*</span></label>
                    <textarea class="form-control" id="edit_announcement_content" name="content" rows="8" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" id="edit_announcement_status" name="status">
                        <option value="active">Active (Visible on website)</option>
                        <option value="inactive">Inactive (Hidden)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editAnnouncementModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_announcement" class="btn-save">Update Announcement</button>
            </div>
        </form>
    </div>
</div>

<!-- Add About Section Modal -->
<div id="addAboutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add About Section</h2>
            <button class="close" onclick="closeModal('addAboutModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Section Title <span class="required">*</span></label>
                        <input type="text" class="form-control" id="about_section_title" name="section_title" required placeholder="e.g., Our History, Mission & Vision">
                    </div>
                    
                    <div class="form-group">
                        <label>Section Type</label>
                        <select class="form-control" id="about_section_type" name="section_type">
                            <option value="history">History</option>
                            <option value="mission">Mission</option>
                            <option value="vision">Vision</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" class="form-control" id="about_display_order" name="display_order" value="0" min="0">
                        <small style="color: #6c757d;">Lower numbers appear first</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_active" checked> Active (Visible)
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Content <span class="required">*</span></label>
                    <textarea class="form-control" id="about_section_content" name="section_content" rows="8" required placeholder="Write the section content here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addAboutModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="add_about_section" class="btn-save">Add Section</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit About Section Modal -->
<div id="editAboutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit About Section</h2>
            <button class="close" onclick="closeModal('editAboutModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="section_id" id="edit_about_section_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Section Title <span class="required">*</span></label>
                        <input type="text" class="form-control" id="edit_about_section_title" name="section_title" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Section Type</label>
                        <select class="form-control" id="edit_about_section_type" name="section_type">
                            <option value="history">History</option>
                            <option value="mission">Mission</option>
                            <option value="vision">Vision</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" class="form-control" id="edit_about_display_order" name="display_order" min="0">
                        <small style="color: #6c757d;">Lower numbers appear first</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_active" id="edit_about_is_active"> Active (Visible)
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Content <span class="required">*</span></label>
                    <textarea class="form-control" id="edit_about_section_content" name="section_content" rows="8" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editAboutModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_about_section" class="btn-save">Update Section</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Forms -->
<form id="deleteAnnouncementForm" method="POST" style="display: none;">
    <input type="hidden" name="announcement_id" id="delete_announcement_id">
    <input type="hidden" name="delete_announcement" value="1">
</form>

<form id="deleteAboutForm" method="POST" style="display: none;">
    <input type="hidden" name="section_id" id="delete_about_id">
    <input type="hidden" name="delete_about_section" value="1">
</form>

<script>
// Tab switching
function switchTab(event, tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');
    
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');
}

// Page functions
function editPage(page) {
    document.getElementById('page_id').value = page.id;
    document.getElementById('pageNameDisplay').value = page.page_name.charAt(0).toUpperCase() + page.page_name.slice(1);
    document.getElementById('page_title').value = page.title || '';
    document.getElementById('page_meta').value = page.meta_description || '';
    document.getElementById('page_content').value = page.content || '';
    
    openModal('editPageModal');
}

// Announcement functions
function showAddAnnouncementModal() {
    openModal('addAnnouncementModal');
}

function editAnnouncement(announcement) {
    document.getElementById('edit_announcement_id').value = announcement.id;
    document.getElementById('edit_announcement_title').value = announcement.title;
    document.getElementById('edit_announcement_content').value = announcement.content;
    document.getElementById('edit_announcement_status').value = announcement.status;
    
    openModal('editAnnouncementModal');
}

function deleteAnnouncement(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        document.getElementById('delete_announcement_id').value = id;
        document.getElementById('deleteAnnouncementForm').submit();
    }
}

// About section functions
function showAddAboutModal() {
    openModal('addAboutModal');
}

function editAboutSection(section) {
    document.getElementById('edit_about_section_id').value = section.id;
    document.getElementById('edit_about_section_title').value = section.section_title;
    document.getElementById('edit_about_section_type').value = section.section_type;
    document.getElementById('edit_about_section_content').value = section.section_content;
    document.getElementById('edit_about_display_order').value = section.display_order;
    document.getElementById('edit_about_is_active').checked = section.is_active == 1;
    
    openModal('editAboutModal');
}

function deleteAboutSection(id) {
    if (confirm('Are you sure you want to delete this about section?')) {
        document.getElementById('delete_about_id').value = id;
        document.getElementById('deleteAboutForm').submit();
    }
}

// Modal functions - With scrollbar
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