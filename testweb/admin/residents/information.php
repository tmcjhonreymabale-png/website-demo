<?php
// admin/residents/information.php

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

// First, check and add missing columns to users table
try {
    // Check if profile_pic column exists
    $check_profile = "SHOW COLUMNS FROM users LIKE 'profile_pic'";
    $check_stmt = $db->prepare($check_profile);
    $check_stmt->execute();
    if ($check_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER email");
    }
    
    // Check if last_name column exists
    $check_lastname = "SHOW COLUMNS FROM users LIKE 'last_name'";
    $check_lastname_stmt = $db->prepare($check_lastname);
    $check_lastname_stmt->execute();
    if ($check_lastname_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name");
    }
    
    // Check if address column exists in users table
    $check_address = "SHOW COLUMNS FROM users LIKE 'address'";
    $check_address_stmt = $db->prepare($check_address);
    $check_address_stmt->execute();
    if ($check_address_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN address TEXT NULL");
    }
    
    // Check if contact_number column exists in users table
    $check_contact = "SHOW COLUMNS FROM users LIKE 'contact_number'";
    $check_contact_stmt = $db->prepare($check_contact);
    $check_contact_stmt->execute();
    if ($check_contact_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN contact_number VARCHAR(20) NULL");
    }
} catch (Exception $e) {
    // Silently continue
}

// Ensure resident_info table has all required columns
try {
    $check_table = "SHOW TABLES LIKE 'resident_info'";
    $table_check = $db->prepare($check_table);
    $table_check->execute();
    $table_exists = $table_check->rowCount() > 0;
    
    if (!$table_exists) {
        // Create full table with all columns
        $create_table = "CREATE TABLE resident_info (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            middle_name VARCHAR(100),
            suffix VARCHAR(20),
            birth_date DATE,
            birth_place VARCHAR(255),
            age INT,
            gender ENUM('male', 'female', 'other') DEFAULT NULL,
            civil_status ENUM('single', 'married', 'widowed', 'separated') DEFAULT NULL,
            citizenship VARCHAR(100) DEFAULT 'Filipino',
            occupation VARCHAR(255),
            contact_number VARCHAR(20),
            address VARCHAR(255),
            barangay VARCHAR(100),
            city VARCHAR(100),
            province VARCHAR(100),
            zip_code VARCHAR(10),
            emergency_contact_name VARCHAR(255),
            emergency_contact_number VARCHAR(20),
            emergency_contact_relation VARCHAR(100),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $db->exec($create_table);
    } else {
        // Check and add missing columns
        $required_columns = [
            'middle_name' => 'VARCHAR(100)',
            'suffix' => 'VARCHAR(20)',
            'birth_date' => 'DATE',
            'birth_place' => 'VARCHAR(255)',
            'age' => 'INT',
            'gender' => "ENUM('male', 'female', 'other') DEFAULT NULL",
            'civil_status' => "ENUM('single', 'married', 'widowed', 'separated') DEFAULT NULL",
            'citizenship' => "VARCHAR(100) DEFAULT 'Filipino'",
            'occupation' => 'VARCHAR(255)',
            'contact_number' => 'VARCHAR(20)',
            'address' => 'VARCHAR(255)',
            'barangay' => 'VARCHAR(100)',
            'city' => 'VARCHAR(100)',
            'province' => 'VARCHAR(100)',
            'zip_code' => 'VARCHAR(10)',
            'emergency_contact_name' => 'VARCHAR(255)',
            'emergency_contact_number' => 'VARCHAR(20)',
            'emergency_contact_relation' => 'VARCHAR(100)'
        ];
        
        foreach ($required_columns as $column => $definition) {
            try {
                $check_col = $db->prepare("SHOW COLUMNS FROM resident_info LIKE :column");
                $check_col->bindParam(':column', $column);
                $check_col->execute();
                if ($check_col->rowCount() == 0) {
                    $db->exec("ALTER TABLE resident_info ADD COLUMN $column $definition");
                }
            } catch (Exception $e) {
                // Column might already exist
            }
        }
    }
} catch (Exception $e) {
    // Table creation failed
}

// Handle AJAX update resident
if (isset($_POST['ajax_update']) && $_POST['ajax_update'] == '1') {
    header('Content-Type: application/json');
    
    $user_id = $_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $birth_place = trim($_POST['birth_place'] ?? '');
    $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
    $civil_status = !empty($_POST['civil_status']) ? $_POST['civil_status'] : null;
    $citizenship = trim($_POST['citizenship'] ?? 'Filipino');
    $occupation = trim($_POST['occupation'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $emergency_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_number = trim($_POST['emergency_contact_number'] ?? '');
    $emergency_relation = trim($_POST['emergency_contact_relation'] ?? '');
    
    try {
        $db->beginTransaction();
        
        // Update users table
        $user_query = "UPDATE users SET 
                      first_name = :first_name, 
                      last_name = :last_name, 
                      email = :email, 
                      contact_number = :contact, 
                      address = :address 
                      WHERE id = :id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':first_name', $first_name);
        $user_stmt->bindParam(':last_name', $last_name);
        $user_stmt->bindParam(':email', $email);
        $user_stmt->bindParam(':contact', $contact);
        $user_stmt->bindParam(':address', $address);
        $user_stmt->bindParam(':id', $user_id);
        $user_stmt->execute();
        
        // Calculate age from birth date
        $age = null;
        if (!empty($birth_date) && $birth_date != '0000-00-00') {
            $birth = new DateTime($birth_date);
            $today = new DateTime('today');
            $age = $birth->diff($today)->y;
        }
        
        // Check if resident_info exists
        $check_info = "SELECT id FROM resident_info WHERE user_id = :user_id";
        $check_stmt = $db->prepare($check_info);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $info_query = "UPDATE resident_info SET 
                          middle_name = :middle_name,
                          suffix = :suffix,
                          birth_date = :birth_date,
                          birth_place = :birth_place,
                          age = :age,
                          gender = :gender,
                          civil_status = :civil_status,
                          citizenship = :citizenship,
                          occupation = :occupation,
                          contact_number = :contact,
                          address = :address,
                          barangay = :barangay,
                          city = :city,
                          province = :province,
                          zip_code = :zip_code,
                          emergency_contact_name = :emergency_name,
                          emergency_contact_number = :emergency_number,
                          emergency_contact_relation = :emergency_relation
                          WHERE user_id = :user_id";
        } else {
            $info_query = "INSERT INTO resident_info (user_id, middle_name, suffix, birth_date, birth_place, age, gender, civil_status, citizenship, occupation, contact_number, address, barangay, city, province, zip_code, emergency_contact_name, emergency_contact_number, emergency_contact_relation) 
                          VALUES (:user_id, :middle_name, :suffix, :birth_date, :birth_place, :age, :gender, :civil_status, :citizenship, :occupation, :contact, :address, :barangay, :city, :province, :zip_code, :emergency_name, :emergency_number, :emergency_relation)";
        }
        
        $info_stmt = $db->prepare($info_query);
        $info_stmt->bindParam(':user_id', $user_id);
        $info_stmt->bindParam(':middle_name', $middle_name);
        $info_stmt->bindParam(':suffix', $suffix);
        $info_stmt->bindParam(':birth_date', $birth_date);
        $info_stmt->bindParam(':birth_place', $birth_place);
        $info_stmt->bindParam(':age', $age);
        $info_stmt->bindParam(':gender', $gender);
        $info_stmt->bindParam(':civil_status', $civil_status);
        $info_stmt->bindParam(':citizenship', $citizenship);
        $info_stmt->bindParam(':occupation', $occupation);
        $info_stmt->bindParam(':contact', $contact);
        $info_stmt->bindParam(':address', $address);
        $info_stmt->bindParam(':barangay', $barangay);
        $info_stmt->bindParam(':city', $city);
        $info_stmt->bindParam(':province', $province);
        $info_stmt->bindParam(':zip_code', $zip_code);
        $info_stmt->bindParam(':emergency_name', $emergency_name);
        $info_stmt->bindParam(':emergency_number', $emergency_number);
        $info_stmt->bindParam(':emergency_relation', $emergency_relation);
        $info_stmt->execute();
        
        $db->commit();
        
        // Return success with updated data
        echo json_encode([
            'success' => true,
            'message' => 'Resident updated successfully',
            'data' => [
                'id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'contact' => !empty($contact) ? $contact : 'N/A',
                'address' => !empty($address) ? $address : 'N/A',
                'age' => $age,
                'gender' => $gender,
                'civil_status' => $civil_status,
                'occupation' => $occupation,
                'birth_date' => $birth_date,
                'birth_place' => $birth_place,
                'barangay' => $barangay,
                'city' => $city,
                'province' => $province,
                'zip_code' => $zip_code,
                'emergency_name' => $emergency_name,
                'emergency_number' => $emergency_number,
                'emergency_relation' => $emergency_relation
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update resident: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Get search parameter
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get all residents
$query = "SELECT 
          u.id, 
          u.username, 
          u.email, 
          u.first_name, 
          u.last_name, 
          u.address as user_address, 
          u.contact_number as user_contact,
          u.profile_pic, 
          u.created_at,
          u.user_type,
          ri.middle_name, 
          ri.suffix, 
          ri.birth_date, 
          ri.birth_place,
          ri.age, 
          ri.gender, 
          ri.civil_status, 
          ri.citizenship, 
          ri.occupation,
          ri.contact_number as resident_contact, 
          ri.address as resident_address,
          ri.barangay, 
          ri.city, 
          ri.province, 
          ri.zip_code,
          ri.emergency_contact_name, 
          ri.emergency_contact_number, 
          ri.emergency_contact_relation
          FROM users u
          LEFT JOIN resident_info ri ON u.id = ri.user_id
          WHERE (u.user_type = 'resident' OR u.user_type IS NULL OR u.user_type = '')
          GROUP BY u.id";

$params = array();

if (!empty($search)) {
    $query .= " HAVING (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY u.id DESC, u.last_name, u.first_name";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Auto-fix NULL user_type
try {
    $db->exec("UPDATE users SET user_type = 'resident' WHERE user_type IS NULL OR user_type = ''");
} catch (Exception $e) {
    // Silently continue
}

// Process residents and check for incomplete profiles
$incomplete_count = 0;

foreach ($residents as &$resident) {
    // Calculate age if not set
    if (empty($resident['age']) && !empty($resident['birth_date']) && $resident['birth_date'] != '0000-00-00') {
        $birth = new DateTime($resident['birth_date']);
        $today = new DateTime('today');
        $resident['age'] = $birth->diff($today)->y;
    }
    
    // Build full address
    $address_parts = [];
    if (!empty($resident['resident_address'])) $address_parts[] = $resident['resident_address'];
    elseif (!empty($resident['user_address'])) $address_parts[] = $resident['user_address'];
    if (!empty($resident['barangay'])) $address_parts[] = $resident['barangay'];
    if (!empty($resident['city'])) $address_parts[] = $resident['city'];
    if (!empty($resident['province'])) $address_parts[] = $resident['province'];
    if (!empty($resident['zip_code'])) $address_parts[] = $resident['zip_code'];
    $resident['full_address'] = !empty($address_parts) ? implode(', ', $address_parts) : 'N/A';
    
    // Get contact number
    if (!empty($resident['resident_contact'])) {
        $resident['display_contact'] = $resident['resident_contact'];
    } elseif (!empty($resident['user_contact'])) {
        $resident['display_contact'] = $resident['user_contact'];
    } else {
        $resident['display_contact'] = 'N/A';
    }
    
    // Get address for display
    $resident['display_address'] = $resident['full_address'];
    
    // Check for incomplete profile
    $missing_fields = [];
    
    if ($resident['display_contact'] == 'N/A' || empty($resident['display_contact'])) {
        $missing_fields[] = 'Contact Number';
    }
    
    if ($resident['display_address'] == 'N/A' || empty($resident['display_address'])) {
        $missing_fields[] = 'Address';
    }
    
    if (empty($resident['birth_date']) || $resident['birth_date'] == '0000-00-00') {
        $missing_fields[] = 'Birth Date';
    }
    
    if (empty($resident['gender'])) {
        $missing_fields[] = 'Gender';
    }
    
    if (empty($resident['civil_status'])) {
        $missing_fields[] = 'Civil Status';
    }
    
    if (empty($resident['occupation'])) {
        $missing_fields[] = 'Occupation';
    }
    
    $resident['is_incomplete'] = !empty($missing_fields);
    $resident['missing_fields'] = $missing_fields;
    $resident['missing_count'] = count($missing_fields);
    
    if ($resident['is_incomplete']) {
        $incomplete_count++;
    }
    
    // Get profile picture path
    if (!empty($resident['profile_pic'])) {
        $resident['profile_pic_path'] = '../../uploads/profiles/' . $resident['profile_pic'];
        if (!file_exists($resident['profile_pic_path'])) {
            $resident['profile_pic_path'] = null;
        }
    } else {
        $resident['profile_pic_path'] = null;
    }
    
    // Get request count
    try {
        $req_query = "SELECT COUNT(*) as total FROM resident_requests WHERE user_id = :user_id";
        $req_stmt = $db->prepare($req_query);
        $req_stmt->bindParam(':user_id', $resident['id']);
        $req_stmt->execute();
        $req_result = $req_stmt->fetch(PDO::FETCH_ASSOC);
        $resident['total_requests'] = $req_result['total'] ?? 0;
    } catch (Exception $e) {
        $resident['total_requests'] = 0;
    }
    
    // Get report count
    try {
        $rep_query = "SELECT COUNT(*) as total FROM resident_reports WHERE user_id = :user_id";
        $rep_stmt = $db->prepare($rep_query);
        $rep_stmt->bindParam(':user_id', $resident['id']);
        $rep_stmt->execute();
        $rep_result = $rep_stmt->fetch(PDO::FETCH_ASSOC);
        $resident['total_reports'] = $rep_result['total'] ?? 0;
    } catch (Exception $e) {
        $resident['total_reports'] = 0;
    }
}

// Get statistics
$stats_query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'resident' OR user_type IS NULL OR user_type = ''";
$stats_stmt = $db->query($stats_query);
$stats_total = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$male_query = "SELECT COUNT(DISTINCT u.id) as male FROM users u 
               LEFT JOIN resident_info ri ON u.id = ri.user_id 
               WHERE (u.user_type = 'resident' OR u.user_type IS NULL OR u.user_type = '') AND ri.gender = 'male'";
$male_stmt = $db->query($male_query);
$male_count = $male_stmt->fetch(PDO::FETCH_ASSOC);

$female_query = "SELECT COUNT(DISTINCT u.id) as female FROM users u 
                 LEFT JOIN resident_info ri ON u.id = ri.user_id 
                 WHERE (u.user_type = 'resident' OR u.user_type IS NULL OR u.user_type = '') AND ri.gender = 'female'";
$female_stmt = $db->query($female_query);
$female_count = $female_stmt->fetch(PDO::FETCH_ASSOC);

$stats = [
    'total' => $stats_total['total'] ?? 0,
    'male' => $male_count['male'] ?? 0,
    'female' => $female_count['female'] ?? 0,
    'incomplete' => $incomplete_count
];

include '../includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Main Page Styles */
    .content-wrapper {
        padding: 1.5rem;
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
    
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
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
    
    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border-left: 4px solid #f59e0b;
    }
    
    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border-left: 4px solid #3b82f6;
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid #eef2f6;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }
    
    .stat-card.warning {
        background: linear-gradient(135deg, #fff9e6, #fff);
        border-color: #f59e0b;
    }
    
    .stat-card.warning .stat-icon {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .stat-icon i {
        font-size: 1.8rem;
        color: white;
    }
    
    .stat-details {
        flex: 1;
    }
    
    .stat-details h3 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #1f2937;
        line-height: 1.2;
    }
    
    .stat-details p {
        color: #6b7280;
        font-size: 0.8rem;
        font-weight: 500;
        margin: 0;
        letter-spacing: 0.3px;
    }
    
    /* Incomplete Badge */
    .incomplete-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: #fef3c7;
        color: #92400e;
        padding: 0.2rem 0.5rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .incomplete-badge:hover {
        background: #fde68a;
        transform: scale(1.05);
    }
    
    .incomplete-badge i {
        font-size: 0.65rem;
    }
    
    /* Tooltip */
    .tooltip {
        position: relative;
        display: inline-block;
    }
    
    .tooltip .tooltip-text {
        visibility: hidden;
        width: 200px;
        background-color: #1f2937;
        color: #fff;
        text-align: left;
        border-radius: 6px;
        padding: 0.5rem;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.7rem;
        font-weight: normal;
        pointer-events: none;
    }
    
    .tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
    
    .filters-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #eef2f6;
    }
    
    #filterForm {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex: 1;
        min-width: 250px;
    }
    
    .filter-group label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .search-input-wrapper {
        position: relative;
    }
    
    .search-input-wrapper input {
        width: 100%;
        padding: 0.75rem 0.75rem 0.75rem 2.5rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.875rem;
    }
    
    .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }
    
    .filter-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-filter {
        padding: 0.75rem 1.5rem;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-filter:hover {
        background: #0056b3;
        transform: translateY(-1px);
    }
    
    .btn-clear {
        padding: 0.75rem 1.5rem;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-clear:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }
    
    .table-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #eef2f6;
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }
    
    .data-table th {
        text-align: left;
        padding: 1rem;
        color: #2c3e50;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        font-size: 0.85rem;
    }
    
    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
        font-size: 0.85rem;
    }
    
    .data-table tr:hover {
        background: #f8fafc;
    }
    
    .data-table tr.incomplete-row {
        background: #fffbeb;
    }
    
    .data-table tr.incomplete-row:hover {
        background: #fef3c7;
    }
    
    .user-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .user-avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
    }
    
    .user-avatar {
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
    
    .user-name {
        font-weight: 500;
        color: #2c3e50;
    }
    
    .user-email {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .id-cell {
        font-weight: 600;
        color: #007bff;
        font-family: monospace;
    }
    
    .badge-requests {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 500;
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-reports {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 500;
        background: #fee2e2;
        color: #b91c1c;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
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
    
    /* Modal Styles - With Scrollbar */
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
        margin: 1.5rem auto;
        border-radius: 20px;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.2);
        animation: modalFadeIn 0.3s ease;
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
        padding: 1.2rem 1.8rem;
        border-bottom: 1px solid #e9ecef;
        background: #fff;
        color: white;
        flex-shrink: 0;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 1.3rem;
        color: #1e293b;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    
    .modal-header h2 i {
        color: #2563eb;
    }
    
    .modal-header .close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6c757d;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .modal-header .close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
    
    .modal-body {
        padding: 1.8rem;
        overflow-y: auto !important;
        flex: 1;
        max-height: calc(85vh - 140px);
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f1f1;
    }
    
    /* WebKit scrollbar styling */
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
    
    .modal-footer {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding: 1rem 1.8rem;
        background: #f8fafc;
        border-top: 1px solid #e9ecef;
        flex-shrink: 0;
    }
    
    /* Prevent body scroll when modal is open */
    body.modal-open {
        overflow: hidden !important;
        padding-right: 0 !important;
    }
    
    /* Form styling for modal */
    .form-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    
    .form-row .form-group {
        flex: 1;
        min-width: calc(50% - 0.5rem);
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.3rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 0.25rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.7rem 0.9rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: inherit;
        transition: all 0.2s;
        background: #fff;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-control.missing {
        border-color: #f59e0b;
        background-color: #fffbeb;
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
    
    textarea.form-control {
        resize: vertical;
        min-height: 70px;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.7rem 1.8rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 0.7rem 1.8rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-cancel:hover {
        background: #e2e8f0;
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
    
    .missing-fields-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .missing-field-tag {
        background: #fef3c7;
        color: #92400e;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
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
    
    /* Toast Notification */
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .content-wrapper {
            padding: 1rem;
        }
        
        .stats-grid {
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
        }
        
        .stat-icon i {
            font-size: 1.5rem;
        }
        
        .stat-details h3 {
            font-size: 1.4rem;
        }
        
        .filter-group {
            min-width: 100%;
        }
        
        #filterForm {
            flex-direction: column;
        }
        
        .filter-actions {
            width: 100%;
        }
        
        .btn-filter, .btn-clear {
            flex: 1;
            justify-content: center;
        }
        
        .info-label {
            width: 100%;
            margin-bottom: 0.25rem;
        }
        
        .info-row {
            flex-direction: column;
        }
        
        .toast-notification {
            left: 20px;
            right: 20px;
        }
        
        .modal-content {
            width: 95%;
            margin: 1rem auto;
            max-height: 95vh;
        }
        
        .modal-body {
            padding: 1.2rem;
            max-height: calc(95vh - 120px);
        }
        
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .form-row .form-group {
            min-width: 100%;
        }
        
        .modal-footer {
            flex-direction: column;
        }
        
        .btn-save, .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="content-wrapper">
    <!-- Module Header -->
    <div class="module-header">
        <div class="module-title">
            <h1>Resident Information</h1>
            <p>View and manage all registered residents</p>
        </div>
        <div class="header-actions">
            <button onclick="exportResidents()" class="btn-primary">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer"></div>

    <!-- Incomplete Profiles Notice -->
    <?php if ($incomplete_count > 0): ?>
        <div class="alert alert-warning" id="incompleteNotice">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong><?php echo $incomplete_count; ?> resident(s) have incomplete profiles!</strong><br>
                <small>Click the <i class="fas fa-edit"></i> edit button to help residents complete their missing information.</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3 id="statTotal"><?php echo $stats['total'] ?? 0; ?></h3>
                <p>Total Residents</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #047857);">
                <i class="fas fa-male"></i>
            </div>
            <div class="stat-details">
                <h3 id="statMale"><?php echo $stats['male'] ?? 0; ?></h3>
                <p>Male</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ec4899, #be185d);">
                <i class="fas fa-female"></i>
            </div>
            <div class="stat-details">
                <h3 id="statFemale"><?php echo $stats['female'] ?? 0; ?></h3>
                <p>Female</p>
            </div>
        </div>
        <div class="stat-card <?php echo $incomplete_count > 0 ? 'warning' : ''; ?>">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-details">
                <h3 id="statIncomplete"><?php echo $incomplete_count; ?></h3>
                <p>Incomplete Profiles</p>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="filters-card">
        <form method="GET" id="filterForm">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Search Resident</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" id="searchInput" placeholder="Search by name, email, or username..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="information.php" class="btn-clear">
                    <i class="fas fa-eraser"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Residents Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="data-table" id="residentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Resident</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Age/Gender</th>
                        <th>Status</th>
                        <th>Requests</th>
                        <th>Reports</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="residentsTableBody">
                    <?php if (empty($residents)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #dee2e6;"></i>
                                <p>No residents found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $displayed_ids = [];
                        foreach ($residents as $resident): 
                            if (in_array($resident['id'], $displayed_ids)) continue;
                            $displayed_ids[] = $resident['id'];
                            $row_class = $resident['is_incomplete'] ? 'incomplete-row' : '';
                            $row_id = "resident-row-{$resident['id']}";
                        ?>
                        <tr class="<?php echo $row_class; ?>" id="<?php echo $row_id; ?>" data-resident-id="<?php echo $resident['id']; ?>">
                            <td class="id-cell">#<?php echo str_pad($resident['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="user-cell">
                                    <?php 
                                    $profile_pic_path = $resident['profile_pic_path'] ?? null;
                                    
                                    if (!empty($profile_pic_path) && file_exists($profile_pic_path)): 
                                    ?>
                                        <img src="<?php echo $profile_pic_path; ?>" class="user-avatar-img" alt="Profile">
                                    <?php else: ?>
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($resident['first_name'] ?? '?', 0, 1) . substr($resident['last_name'] ?? '?', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="user-name" id="name-<?php echo $resident['id']; ?>"><?php echo htmlspecialchars(($resident['first_name'] ?? '') . ' ' . ($resident['last_name'] ?? '')); ?></div>
                                        <div class="user-email" id="email-<?php echo $resident['id']; ?>"><?php echo htmlspecialchars($resident['email'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td id="contact-<?php echo $resident['id']; ?>"><?php echo htmlspecialchars($resident['display_contact']); ?></td>
                            <td id="address-<?php echo $resident['id']; ?>"><?php echo htmlspecialchars(substr($resident['display_address'], 0, 40)); ?></td>
                            <td id="age-gender-<?php echo $resident['id']; ?>">
                                <?php 
                                $age = $resident['age'] ?? '';
                                $gender = ucfirst($resident['gender'] ?? '');
                                if (!empty($age) && !empty($gender)) {
                                    echo $age . '<br><small>' . $gender . '</small>';
                                } elseif (!empty($age)) {
                                    echo $age . '<br><small>—</small>';
                                } elseif (!empty($gender)) {
                                    echo '<small>' . $gender . '</small>';
                                } else {
                                    echo '<small>—</small>';
                                }
                                ?>
                            </td>
                            <td>
                                <div id="status-<?php echo $resident['id']; ?>">
                                    <?php if ($resident['is_incomplete']): ?>
                                        <div class="tooltip incomplete-badge" data-missing="<?php echo htmlspecialchars(json_encode($resident['missing_fields'])); ?>">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <?php echo $resident['missing_count']; ?> missing
                                            <div class="tooltip-text">
                                                Missing: <?php echo implode(', ', $resident['missing_fields']); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #10b981;">
                                            <i class="fas fa-check-circle"></i> Complete
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="badge-requests" id="requests-<?php echo $resident['id']; ?>"><?php echo $resident['total_requests'] ?? 0; ?></span></td>
                            <td><span class="badge-reports" id="reports-<?php echo $resident['id']; ?>"><?php echo $resident['total_reports'] ?? 0; ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewResident(<?php echo $resident['id']; ?>)" class="btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($resident)); ?>)" class="btn-icon" title="Edit Resident">
                                        <i class="fas fa-edit"></i>
                                    </button>
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

<!-- Edit Resident Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Resident</h2>
            <button class="close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form id="editForm">
            <input type="hidden" name="ajax_update" value="1">
            <input type="hidden" name="user_id" id="edit-user_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" class="form-control" name="first_name" id="edit-first_name" required></div>
                    <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" class="form-control" name="last_name" id="edit-last_name" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Middle Name</label><input type="text" class="form-control" name="middle_name" id="edit-middle_name"></div>
                    <div class="form-group"><label>Suffix</label><input type="text" class="form-control" name="suffix" id="edit-suffix"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" class="form-control" name="email" id="edit-email" required></div>
                    <div class="form-group"><label>Contact</label><input type="text" class="form-control" name="contact_number" id="edit-contact_number"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Birth Date</label><input type="date" class="form-control" name="birth_date" id="edit-birth_date"></div>
                    <div class="form-group"><label>Birth Place</label><input type="text" class="form-control" name="birth_place" id="edit-birth_place"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Gender</label>
                        <select class="form-control" name="gender" id="edit-gender">
                            <option value="">-- Select Gender --</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Civil Status</label>
                        <select class="form-control" name="civil_status" id="edit-civil_status">
                            <option value="">-- Select Civil Status --</option>
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                            <option value="widowed">Widowed</option>
                            <option value="separated">Separated</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Citizenship</label><input type="text" class="form-control" name="citizenship" id="edit-citizenship" value="Filipino"></div>
                    <div class="form-group"><label>Occupation</label><input type="text" class="form-control" name="occupation" id="edit-occupation"></div>
                </div>
                <div class="form-group"><label>Address</label><textarea class="form-control" name="address" id="edit-address" rows="2"></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>Barangay</label><input type="text" class="form-control" name="barangay" id="edit-barangay"></div>
                    <div class="form-group"><label>City</label><input type="text" class="form-control" name="city" id="edit-city"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Province</label><input type="text" class="form-control" name="province" id="edit-province"></div>
                    <div class="form-group"><label>ZIP Code</label><input type="text" class="form-control" name="zip_code" id="edit-zip_code"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Emergency Contact Name</label><input type="text" class="form-control" name="emergency_contact_name" id="edit-emergency_name"></div>
                    <div class="form-group"><label>Emergency Contact Number</label><input type="text" class="form-control" name="emergency_contact_number" id="edit-emergency_number"></div>
                </div>
                <div class="form-group"><label>Emergency Contact Relation</label><input type="text" class="form-control" name="emergency_contact_relation" id="edit-emergency_relation"></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-save" id="saveBtn">Update Resident</button>
            </div>
        </form>
    </div>
</div>

<!-- View Resident Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2><i class="fas fa-user-circle"></i> Resident Details</h2>
            <button class="close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="residentDetails">
            <div style="text-align: center; padding: 2rem;">
                <div class="spinner"></div>
                <p style="margin-top: 1rem;">Loading resident details...</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentResidentData = null;

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        // Remove body class when all modals are closed
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        if (openModals.length === 0) {
            document.body.classList.remove('modal-open');
        }
    }
}

function openEditModal(resident) {
    currentResidentData = resident;
    
    document.getElementById('edit-user_id').value = resident.id;
    document.getElementById('edit-first_name').value = resident.first_name || '';
    document.getElementById('edit-last_name').value = resident.last_name || '';
    document.getElementById('edit-middle_name').value = resident.middle_name || '';
    document.getElementById('edit-suffix').value = resident.suffix || '';
    document.getElementById('edit-email').value = resident.email || '';
    document.getElementById('edit-contact_number').value = resident.resident_contact || resident.user_contact || '';
    document.getElementById('edit-birth_date').value = resident.birth_date || '';
    document.getElementById('edit-birth_place').value = resident.birth_place || '';
    
    // Fix gender select - ensure it shows the correct value
    const genderSelect = document.getElementById('edit-gender');
    if (resident.gender && resident.gender !== '') {
        genderSelect.value = resident.gender;
    } else {
        genderSelect.value = '';
    }
    
    // Fix civil status select
    const civilStatusSelect = document.getElementById('edit-civil_status');
    if (resident.civil_status && resident.civil_status !== '') {
        civilStatusSelect.value = resident.civil_status;
    } else {
        civilStatusSelect.value = '';
    }
    
    document.getElementById('edit-citizenship').value = resident.citizenship || 'Filipino';
    document.getElementById('edit-occupation').value = resident.occupation || '';
    
    // Handle address - don't show 'N/A' in edit form
    const addressValue = resident.resident_address || resident.user_address || '';
    document.getElementById('edit-address').value = addressValue === 'N/A' ? '' : addressValue;
    
    document.getElementById('edit-barangay').value = resident.barangay || '';
    document.getElementById('edit-city').value = resident.city || '';
    document.getElementById('edit-province').value = resident.province || '';
    document.getElementById('edit-zip_code').value = resident.zip_code || '';
    document.getElementById('edit-emergency_name').value = resident.emergency_contact_name || '';
    document.getElementById('edit-emergency_number').value = resident.emergency_contact_number || '';
    document.getElementById('edit-emergency_relation').value = resident.emergency_contact_relation || '';
    
    openModal('editModal');
}

// Handle AJAX form submission
document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.innerHTML;
    
    // Show loading state
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    try {
        const response = await fetch('information.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update the table row with new data
            updateResidentRow(result.data);
            
            // Show success toast
            showToast('success', result.message);
            
            // Close modal
            closeModal('editModal');
            
            // Update statistics if needed
            updateStatistics();
        } else {
            showToast('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'An error occurred while updating');
    } finally {
        // Restore button
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
});

function updateResidentRow(data) {
    const row = document.getElementById(`resident-row-${data.id}`);
    if (!row) return;
    
    // Update name and email
    document.getElementById(`name-${data.id}`).innerHTML = `${data.first_name} ${data.last_name}`;
    document.getElementById(`email-${data.id}`).innerHTML = data.email;
    
    // Update contact
    const contactElement = document.getElementById(`contact-${data.id}`);
    if (contactElement) contactElement.innerHTML = data.contact || 'N/A';
    
    // Update address
    const addressElement = document.getElementById(`address-${data.id}`);
    if (addressElement) {
        let fullAddress = '';
        if (data.address) fullAddress += data.address;
        if (data.barangay) fullAddress += (fullAddress ? ', ' : '') + data.barangay;
        if (data.city) fullAddress += (fullAddress ? ', ' : '') + data.city;
        if (data.province) fullAddress += (fullAddress ? ', ' : '') + data.province;
        if (data.zip_code) fullAddress += (fullAddress ? ', ' : '') + data.zip_code;
        addressElement.innerHTML = fullAddress.substring(0, 40) || 'N/A';
    }
    
    // Update age/gender
    const ageGenderElement = document.getElementById(`age-gender-${data.id}`);
    if (ageGenderElement) {
        let html = '';
        if (data.age) html += data.age;
        if (data.gender) html += (html ? '<br><small>' : '') + data.gender.charAt(0).toUpperCase() + data.gender.slice(1) + (html ? '</small>' : '');
        if (!html) html = '<small>—</small>';
        ageGenderElement.innerHTML = html;
    }
    
    // Update status (check if profile is now complete)
    const missingFields = [];
    if (!data.contact || data.contact === 'N/A') missingFields.push('Contact Number');
    if (!data.address) missingFields.push('Address');
    if (!data.birth_date) missingFields.push('Birth Date');
    if (!data.gender) missingFields.push('Gender');
    if (!data.civil_status) missingFields.push('Civil Status');
    
    const statusElement = document.getElementById(`status-${data.id}`);
    if (statusElement) {
        if (missingFields.length > 0) {
            statusElement.innerHTML = `
                <div class="tooltip incomplete-badge" data-missing='${JSON.stringify(missingFields)}'>
                    <i class="fas fa-exclamation-triangle"></i> 
                    ${missingFields.length} missing
                    <div class="tooltip-text">
                        Missing: ${missingFields.join(', ')}
                    </div>
                </div>
            `;
            if (!row.classList.contains('incomplete-row')) {
                row.classList.add('incomplete-row');
            }
        } else {
            statusElement.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Complete</span>';
            row.classList.remove('incomplete-row');
        }
    }
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} toast-notification`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function updateStatistics() {
    // Count visible rows that are not "no residents" row
    const rows = document.querySelectorAll('#residentsTableBody tr');
    let total = 0;
    let incomplete = 0;
    
    rows.forEach(row => {
        if (row.cells.length > 1 && !row.innerHTML.includes('No residents found')) {
            total++;
            const statusCell = row.cells[5];
            if (statusCell && statusCell.innerHTML.includes('incomplete-badge')) {
                incomplete++;
            }
        }
    });
    
    // Update stats display
    const statTotal = document.getElementById('statTotal');
    const statIncomplete = document.getElementById('statIncomplete');
    
    if (statTotal) statTotal.textContent = total;
    if (statIncomplete) {
        statIncomplete.textContent = incomplete;
        const statCard = document.querySelector('.stat-card.warning');
        if (statCard && incomplete === 0) {
            statCard.classList.remove('warning');
        } else if (statCard && incomplete > 0) {
            statCard.classList.add('warning');
        }
    }
    
    // Update or remove incomplete notice
    let notice = document.getElementById('incompleteNotice');
    if (incomplete > 0) {
        if (!notice) {
            const statsGrid = document.querySelector('.stats-grid');
            const newNotice = document.createElement('div');
            newNotice.id = 'incompleteNotice';
            newNotice.className = 'alert alert-warning';
            newNotice.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>${incomplete} resident(s) have incomplete profiles!</strong><br>
                    <small>Click the <i class="fas fa-edit"></i> edit button to help residents complete their missing information.</small>
                </div>
            `;
            statsGrid.insertAdjacentElement('afterend', newNotice);
        } else {
            notice.querySelector('strong').innerHTML = `${incomplete} resident(s) have incomplete profiles!`;
        }
    } else if (notice) {
        notice.remove();
    }
}

function viewResident(id) {
    openModal('viewModal');
    const detailsDiv = document.getElementById('residentDetails');
    
    detailsDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner"></div><p>Loading resident details...</p></div>';
    
    fetch('get_resident.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            html += '<div class="info-row"><div class="info-label">Resident ID</div><div class="info-value"><strong>#' + String(data.id).padStart(5, '0') + '</strong></div></div>';
            html += '<div class="info-row"><div class="info-label">Full Name</div><div class="info-value">' + (data.full_name || data.first_name + ' ' + data.last_name) + '</div></div>';
            if (data.middle_name) html += '<div class="info-row"><div class="info-label">Middle Name</div><div class="info-value">' + data.middle_name + '</div></div>';
            if (data.suffix) html += '<div class="info-row"><div class="info-label">Suffix</div><div class="info-value">' + data.suffix + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Username</div><div class="info-value">' + (data.username || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Email</div><div class="info-value">' + (data.email || 'N/A') + '</div></div>';
            html += '<div class="info-row"><div class="info-label">Contact Number</div><div class="info-value">' + (data.contact_number || 'N/A') + '</div></div>';
            
            if (data.birth_date) html += '<div class="info-row"><div class="info-label">Birth Date</div><div class="info-value">' + data.birth_date + '</div></div>';
            if (data.birth_place) html += '<div class="info-row"><div class="info-label">Birth Place</div><div class="info-value">' + data.birth_place + '</div></div>';
            if (data.age) html += '<div class="info-row"><div class="info-label">Age</div><div class="info-value">' + data.age + ' years old</div></div>';
            if (data.gender) html += '<div class="info-row"><div class="info-label">Gender</div><div class="info-value">' + (data.gender.charAt(0).toUpperCase() + data.gender.slice(1)) + '</div></div>';
            if (data.civil_status) html += '<div class="info-row"><div class="info-label">Civil Status</div><div class="info-value">' + (data.civil_status.charAt(0).toUpperCase() + data.civil_status.slice(1)) + '</div></div>';
            if (data.citizenship) html += '<div class="info-row"><div class="info-label">Citizenship</div><div class="info-value">' + data.citizenship + '</div></div>';
            if (data.occupation) html += '<div class="info-row"><div class="info-label">Occupation</div><div class="info-value">' + data.occupation + '</div></div>';
            
            if (data.full_address && data.full_address !== 'N/A') {
                html += '<div class="info-row"><div class="info-label">Full Address</div><div class="info-value">' + data.full_address + '</div></div>';
            } else {
                if (data.address && data.address !== 'N/A') html += '<div class="info-row"><div class="info-label">Address</div><div class="info-value">' + data.address + '</div></div>';
                if (data.barangay) html += '<div class="info-row"><div class="info-label">Barangay</div><div class="info-value">' + data.barangay + '</div></div>';
                if (data.city) html += '<div class="info-row"><div class="info-label">City</div><div class="info-value">' + data.city + '</div></div>';
                if (data.province) html += '<div class="info-row"><div class="info-label">Province</div><div class="info-value">' + data.province + '</div></div>';
                if (data.zip_code) html += '<div class="info-row"><div class="info-label">ZIP Code</div><div class="info-value">' + data.zip_code + '</div></div>';
            }
            
            if (data.emergency_contact_name || data.emergency_contact_number || data.emergency_contact_relation) {
                if (data.emergency_contact_name) html += '<div class="info-row"><div class="info-label">Emergency Contact</div><div class="info-value">' + data.emergency_contact_name + '</div></div>';
                if (data.emergency_contact_number) html += '<div class="info-row"><div class="info-label">Emergency Number</div><div class="info-value">' + data.emergency_contact_number + '</div></div>';
                if (data.emergency_contact_relation) html += '<div class="info-row"><div class="info-label">Relation</div><div class="info-value">' + data.emergency_contact_relation + '</div></div>';
            }
            
            if (data.created_at) {
                html += '<div class="info-row"><div class="info-label">Member Since</div><div class="info-value">' + new Date(data.created_at).toLocaleDateString() + '</div></div>';
            }
            
            // Show missing fields warning if any
            if (data.missing_fields && data.missing_fields.length > 0) {
                html += '<div class="alert alert-warning" style="margin-top: 1rem;">';
                html += '<i class="fas fa-exclamation-triangle"></i> ';
                html += '<strong>Incomplete Profile:</strong> Missing ' + data.missing_fields.join(', ');
                html += '</div>';
            }
            
            detailsDiv.innerHTML = html;
        })
        .catch(error => {
            detailsDiv.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i><p>Error loading resident details</p><button onclick="viewResident(' + id + ')" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Retry</button></div>';
        });
}

function exportResidents() { 
    const f = document.getElementById('filterForm'); 
    const p = new URLSearchParams(new FormData(f)).toString(); 
    window.location.href = 'export_residents.php?' + p; 
}

window.onclick = function(e) { 
    if(e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    } 
}

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