<?php
// resident/profile.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// First, check and add missing columns to users table
try {
    // Check if profile_pic column exists
    $check_column = "SHOW COLUMNS FROM users LIKE 'profile_pic'";
    $check_stmt = $db->prepare($check_column);
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
        $db->exec("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER contact_number");
    }
    
    // Check if contact_number column exists in users table
    $check_contact = "SHOW COLUMNS FROM users LIKE 'contact_number'";
    $check_contact_stmt = $db->prepare($check_contact);
    $check_contact_stmt->execute();
    if ($check_contact_stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN contact_number VARCHAR(20) NULL AFTER address");
    }
} catch (Exception $e) {
    // Silently continue
}

// Create or update resident_info table with all required columns
try {
    // First, check if table exists
    $check_table = "SHOW TABLES LIKE 'resident_info'";
    $table_check = $db->prepare($check_table);
    $table_check->execute();
    $table_exists = $table_check->rowCount() > 0;
    
    if (!$table_exists) {
        // Create full table with all columns
        $create_table = "CREATE TABLE resident_info (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            middle_name VARCHAR(100),
            suffix VARCHAR(20),
            birth_date DATE,
            birth_place VARCHAR(255),
            age INT,
            gender ENUM('male', 'female', 'other') DEFAULT 'male',
            civil_status ENUM('single', 'married', 'widowed', 'separated') DEFAULT 'single',
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
        // Check and add missing columns one by one
        $required_columns = [
            'middle_name' => 'VARCHAR(100)',
            'suffix' => 'VARCHAR(20)',
            'birth_date' => 'DATE',
            'birth_place' => 'VARCHAR(255)',
            'age' => 'INT',
            'gender' => "ENUM('male', 'female', 'other') DEFAULT 'male'",
            'civil_status' => "ENUM('single', 'married', 'widowed', 'separated') DEFAULT 'single'",
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
                // Column might already exist or can't be added
            }
        }
    }
} catch (Exception $e) {
    // Table creation failed, but we'll continue
}

// Get user data from users table
$user_query = "SELECT id, username, first_name, last_name, email, profile_pic, created_at, contact_number, address 
               FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
}

// If last_name is NULL, set it to empty string
if (!isset($user['last_name']) || $user['last_name'] === null) {
    $user['last_name'] = '';
}

// Get resident info from resident_info table
$info_query = "SELECT * FROM resident_info WHERE user_id = :user_id";
$info_stmt = $db->prepare($info_query);
$info_stmt->bindParam(':user_id', $_SESSION['user_id']);
$info_stmt->execute();
$resident_info = $info_stmt->fetch(PDO::FETCH_ASSOC);

// Initialize resident_info array if empty
if (!$resident_info) {
    $resident_info = [];
}

// Calculate age from birth date if available
$calculated_age = null;
if (!empty($resident_info['birth_date']) && $resident_info['birth_date'] != '0000-00-00') {
    $birth = new DateTime($resident_info['birth_date']);
    $today = new DateTime('today');
    $calculated_age = $birth->diff($today)->y;
}

// Use stored age or calculated age
$age = !empty($resident_info['age']) ? $resident_info['age'] : $calculated_age;

// Merge data - prioritize resident_info over users table
$contact_number = !empty($resident_info['contact_number']) ? $resident_info['contact_number'] : ($user['contact_number'] ?? '');
$address = !empty($resident_info['address']) ? $resident_info['address'] : ($user['address'] ?? '');

// Build full address
$full_address = $address;
$address_parts = [];
if (!empty($address)) $address_parts[] = $address;
if (!empty($resident_info['barangay'])) $address_parts[] = $resident_info['barangay'];
if (!empty($resident_info['city'])) $address_parts[] = $resident_info['city'];
if (!empty($resident_info['province'])) $address_parts[] = $resident_info['province'];
if (!empty($resident_info['zip_code'])) $address_parts[] = $resident_info['zip_code'];
$full_address = implode(', ', $address_parts);

// Define required fields for profile completion
$required_fields = [
    'birth_date' => 'Birth Date',
    'gender' => 'Gender',
    'civil_status' => 'Civil Status',
    'address' => 'Address',
    'contact_number' => 'Contact Number'
];

// Check which fields are missing
$missing_fields = [];
foreach ($required_fields as $field => $label) {
    $value = '';
    if ($field == 'address') {
        $value = $address;
    } elseif ($field == 'contact_number') {
        $value = $contact_number;
    } else {
        $value = $resident_info[$field] ?? '';
    }
    if (empty($value)) {
        $missing_fields[] = $label;
    }
}

$has_missing_info = !empty($missing_fields);
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['upload_photo'])) {
    // Get data from form
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $birth_place = trim($_POST['birth_place'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $citizenship = trim($_POST['citizenship'] ?? 'Filipino');
    $occupation = trim($_POST['occupation'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
    $emergency_contact_relation = trim($_POST['emergency_contact_relation'] ?? '');
    
    // Allow empty values
    if ($middle_name === '') {
        $middle_name = null;
    }
    if ($suffix === '') {
        $suffix = null;
    }
    if ($occupation === '') {
        $occupation = null;
    }
    if ($birth_place === '') {
        $birth_place = null;
    }
    if ($citizenship === '') {
        $citizenship = 'Filipino';
    }
    
    // Calculate age from birth date
    $age = null;
    if (!empty($birth_date)) {
        $birth = new DateTime($birth_date);
        $today = new DateTime('today');
        $age = $birth->diff($today)->y;
    }
    
    try {
        $db->beginTransaction();
        
        // Update users table
        $update_user = "UPDATE users SET contact_number = :contact_number, address = :address WHERE id = :user_id";
        $user_update = $db->prepare($update_user);
        $user_update->bindParam(':contact_number', $contact_number);
        $user_update->bindParam(':address', $address);
        $user_update->bindParam(':user_id', $_SESSION['user_id']);
        $user_update->execute();
        
        // Check if record exists in resident_info
        $check_exists = $db->prepare("SELECT id FROM resident_info WHERE user_id = :user_id");
        $check_exists->bindParam(':user_id', $_SESSION['user_id']);
        $check_exists->execute();
        $exists = $check_exists->fetch();
        
        if ($exists) {
            // Update existing record
            $query = "UPDATE resident_info SET 
                      middle_name = :middle_name,
                      suffix = :suffix,
                      birth_date = :birth_date,
                      birth_place = :birth_place,
                      age = :age,
                      gender = :gender,
                      civil_status = :civil_status,
                      citizenship = :citizenship,
                      occupation = :occupation,
                      contact_number = :contact_number,
                      address = :address,
                      barangay = :barangay,
                      city = :city,
                      province = :province,
                      zip_code = :zip_code,
                      emergency_contact_name = :emergency_contact_name,
                      emergency_contact_number = :emergency_contact_number,
                      emergency_contact_relation = :emergency_contact_relation
                      WHERE user_id = :user_id";
        } else {
            // Insert new record
            $query = "INSERT INTO resident_info (
                      user_id, middle_name, suffix, birth_date, birth_place, age,
                      gender, civil_status, citizenship, occupation, contact_number,
                      address, barangay, city, province, zip_code,
                      emergency_contact_name, emergency_contact_number, emergency_contact_relation
                      ) VALUES (
                      :user_id, :middle_name, :suffix, :birth_date, :birth_place, :age,
                      :gender, :civil_status, :citizenship, :occupation, :contact_number,
                      :address, :barangay, :city, :province, :zip_code,
                      :emergency_contact_name, :emergency_contact_number, :emergency_contact_relation
                      )";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':middle_name', $middle_name);
        $stmt->bindParam(':suffix', $suffix);
        $stmt->bindParam(':birth_date', $birth_date);
        $stmt->bindParam(':birth_place', $birth_place);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':civil_status', $civil_status);
        $stmt->bindParam(':citizenship', $citizenship);
        $stmt->bindParam(':occupation', $occupation);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':barangay', $barangay);
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':province', $province);
        $stmt->bindParam(':zip_code', $zip_code);
        $stmt->bindParam(':emergency_contact_name', $emergency_contact_name);
        $stmt->bindParam(':emergency_contact_number', $emergency_contact_number);
        $stmt->bindParam(':emergency_contact_relation', $emergency_contact_relation);
        
        $stmt->execute();
        
        $db->commit();
        $success_message = "Your profile has been updated successfully!";
        
        // Refresh user data
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Refresh resident info
        $info_stmt->execute();
        $resident_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resident_info) $resident_info = [];
        
        // Re-calculate age
        if (!empty($birth_date)) {
            $birth = new DateTime($birth_date);
            $today = new DateTime('today');
            $age = $birth->diff($today)->y;
        }
        
        // Update contact and address
        $contact_number = $contact_number;
        $address = $address;
        
        // Re-check missing fields
        $missing_fields = [];
        foreach ($required_fields as $field => $label) {
            $value = '';
            if ($field == 'address') {
                $value = $address;
            } elseif ($field == 'contact_number') {
                $value = $contact_number;
            } else {
                $value = $resident_info[$field] ?? '';
            }
            if (empty($value)) {
                $missing_fields[] = $label;
            }
        }
        $has_missing_info = !empty($missing_fields);
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['profile_pic']['type'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if ($_FILES['profile_pic']['size'] > $max_size) {
            $error_message = "Image size must be less than 2MB";
        } elseif (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $file_name = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $file_name)) {
                // Delete old profile picture if exists
                if (!empty($user['profile_pic']) && file_exists($upload_dir . $user['profile_pic'])) {
                    unlink($upload_dir . $user['profile_pic']);
                }
                
                // Update database
                $update_query = "UPDATE users SET profile_pic = :profile_pic WHERE id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':profile_pic', $file_name);
                $update_stmt->bindParam(':user_id', $_SESSION['user_id']);
                $update_stmt->execute();
                
                $success_message = "Profile picture updated successfully!";
                // Refresh user data
                $user_stmt->execute();
                $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Failed to upload image";
            }
        } else {
            $error_message = "Invalid file type. Allowed: JPG, PNG, GIF";
        }
    } else {
        $error_message = "Please select an image to upload";
    }
}

$barangay_name = $barangay_name ?? 'Barangay System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Profile | <?php echo htmlspecialchars($barangay_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            padding-top: 80px;
            min-height: 100vh;
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 12px;
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
        
        .profile-header {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            border: 1px solid #eef2f6;
        }
        
        .profile-avatar {
            position: relative;
            flex-shrink: 0;
        }
        
        .avatar-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .upload-btn {
            position: absolute;
            bottom: 0px;
            right: 0px;
            background: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
            color: #667eea;
        }
        
        .upload-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.05);
        }
        
        .upload-btn i {
            font-size: 0.8rem;
        }
        
        .profile-info h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .profile-info p {
            color: #64748b;
            font-size: 0.8rem;
            margin-bottom: 0.2rem;
        }
        
        .member-since {
            margin-top: 0.25rem;
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .missing-info-notice {
            background: #fef3c7;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            border-left: 4px solid #f59e0b;
        }
        
        .notice-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .notice-content i {
            font-size: 1.2rem;
            color: #f59e0b;
        }
        
        .notice-text h3 {
            font-size: 0.85rem;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 0.25rem;
        }
        
        .notice-text p {
            font-size: 0.75rem;
            color: #b45309;
        }
        
        .missing-fields {
            background: white;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 500;
            color: #92400e;
        }
        
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #eef2f6;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eef2f6;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.25rem;
        }
        
        .form-group label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }
        
        .form-group label .optional-note {
            color: #10b981;
            font-size: 0.6rem;
            font-weight: normal;
            margin-left: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.55rem 0.7rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.8rem;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control.missing {
            border-color: #f59e0b;
            background: #fef9e6;
        }
        
        .form-control[disabled] {
            background: #f1f5f9;
            cursor: not-allowed;
        }
        
        select.form-control {
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eef2f6;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.55rem 1.2rem;
            border: none;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            padding: 0.55rem 1.2rem;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        #photoUpload {
            display: none;
        }
        
        .help-text {
            font-size: 0.6rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        
        /* ===== RESPONSIVE BREAKPOINTS ===== */
        
        /* Tablet (max-width: 768px) */
        @media (max-width: 768px) {
            .profile-container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .profile-header {
                padding: 1.2rem;
                gap: 1rem;
            }
            
            .avatar-image, .avatar-placeholder {
                width: 70px;
                height: 70px;
            }
            
            .avatar-placeholder i {
                font-size: 1.8rem;
            }
            
            .profile-info h1 {
                font-size: 1.2rem;
            }
            
            .profile-info p {
                font-size: 0.75rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }
            
            .missing-info-notice {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .notice-content {
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
            
            .form-section {
                padding: 1.2rem;
            }
        }
        
        /* Mobile Large (max-width: 576px) */
        @media (max-width: 576px) {
            .profile-container {
                padding: 0 0.75rem;
                margin: 0.75rem auto;
            }
            
            .profile-header {
                padding: 1rem;
                border-radius: 16px;
            }
            
            .avatar-image, .avatar-placeholder {
                width: 60px;
                height: 60px;
            }
            
            .avatar-placeholder i {
                font-size: 1.5rem;
            }
            
            .upload-btn {
                width: 24px;
                height: 24px;
            }
            
            .upload-btn i {
                font-size: 0.7rem;
            }
            
            .profile-info h1 {
                font-size: 1rem;
            }
            
            .profile-info p {
                font-size: 0.7rem;
            }
            
            .member-since {
                font-size: 0.6rem;
            }
            
            .form-section {
                padding: 1rem;
                border-radius: 16px;
            }
            
            .section-title {
                font-size: 0.85rem;
                margin-bottom: 1rem;
            }
            
            .form-group label {
                font-size: 0.65rem;
            }
            
            .form-control {
                padding: 0.5rem 0.6rem;
                font-size: 0.75rem;
                border-radius: 8px;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }
            
            .alert {
                padding: 0.7rem;
                font-size: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .missing-info-notice {
                padding: 0.8rem;
            }
            
            .notice-content i {
                font-size: 1rem;
            }
            
            .notice-text h3 {
                font-size: 0.75rem;
            }
            
            .notice-text p {
                font-size: 0.65rem;
            }
            
            .missing-fields {
                font-size: 0.6rem;
                padding: 0.3rem 0.6rem;
            }
        }
        
        /* Mobile Small (max-width: 380px) */
        @media (max-width: 380px) {
            .profile-container {
                padding: 0 0.5rem;
            }
            
            .profile-header {
                padding: 0.8rem;
            }
            
            .avatar-image, .avatar-placeholder {
                width: 50px;
                height: 50px;
            }
            
            .avatar-placeholder i {
                font-size: 1.2rem;
            }
            
            .upload-btn {
                width: 20px;
                height: 20px;
            }
            
            .upload-btn i {
                font-size: 0.6rem;
            }
            
            .profile-info h1 {
                font-size: 0.9rem;
            }
            
            .profile-info p {
                font-size: 0.65rem;
            }
            
            .form-section {
                padding: 0.8rem;
            }
            
            .section-title {
                font-size: 0.75rem;
            }
            
            .form-group label {
                font-size: 0.6rem;
            }
            
            .form-control {
                padding: 0.4rem 0.5rem;
                font-size: 0.7rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.4rem 0.8rem;
                font-size: 0.7rem;
            }
        }
        
        /* Touch-friendly adjustments */
        @media (hover: none) and (pointer: coarse) {
            .btn-primary, 
            .btn-secondary,
            .upload-btn {
                cursor: default;
            }
            
            .btn-primary:active,
            .btn-secondary:active {
                transform: scale(0.98);
            }
            
            .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="profile-container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($has_missing_info): ?>
            <div class="missing-info-notice">
                <div class="notice-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="notice-text">
                        <h3>Complete Your Profile</h3>
                        <p>Please fill out the missing information below to complete your profile.</p>
                    </div>
                </div>
                <div class="missing-fields">
                    <i class="fas fa-list"></i> Missing: <?php echo implode(', ', array_slice($missing_fields, 0, 5)); ?>
                    <?php if (count($missing_fields) > 5): ?> +<?php echo count($missing_fields) - 5; ?> more<?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_pic']) && file_exists('../uploads/profiles/' . $user['profile_pic'])): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="avatar-image" alt="Profile Picture">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <button class="upload-btn" onclick="document.getElementById('photoUpload').click()">
                    <i class="fas fa-camera"></i>
                </button>
                <form method="POST" enctype="multipart/form-data" id="photoForm" style="display: none;">
                    <input type="file" name="profile_pic" id="photoUpload" accept="image/*" onchange="this.form.submit()">
                    <input type="hidden" name="upload_photo" value="1">
                </form>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><i class="fas fa-user"></i> @<?php echo htmlspecialchars($user['username']); ?></p>
                <?php if ($age): ?>
                <p><i class="fas fa-birthday-cake"></i> <?php echo $age; ?> years old</p>
                <?php endif; ?>
                <div class="member-since">
                    <i class="far fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Profile Form -->
        <form method="POST" action="">
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-user-circle"></i>
                    Personal Information
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Middle Name <span class="optional-note">(Optional - enter "N/A" if none)</span></label>
                        <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($resident_info['middle_name'] ?? ''); ?>" placeholder="Enter your middle name or N/A">
                    </div>
                    <div class="form-group">
                        <label>Suffix <span class="optional-note">(Optional - enter "N/A" if none)</span></label>
                        <input type="text" class="form-control" name="suffix" value="<?php echo htmlspecialchars($resident_info['suffix'] ?? ''); ?>" placeholder="e.g., Jr., Sr., III, or N/A">
                    </div>
                    <div class="form-group">
                        <label>Birth Date <?php if (empty($resident_info['birth_date'] ?? '')): ?><span class="required">*</span><?php endif; ?></label>
                        <input type="date" class="form-control <?php echo empty($resident_info['birth_date'] ?? '') ? 'missing' : ''; ?>" name="birth_date" value="<?php echo htmlspecialchars($resident_info['birth_date'] ?? ''); ?>" onchange="calculateAge(this.value)">
                        <small id="ageDisplay" style="display: block; margin-top: 4px; font-size: 0.65rem; color: #6b7280;">
                            <?php if ($age): ?>Age: <?php echo $age; ?> years old<?php endif; ?>
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Birth Place <span class="optional-note">(Optional)</span></label>
                        <input type="text" class="form-control" name="birth_place" value="<?php echo htmlspecialchars($resident_info['birth_place'] ?? ''); ?>" placeholder="City, Province">
                    </div>
                    <div class="form-group">
                        <label>Gender <?php if (empty($resident_info['gender'] ?? '')): ?><span class="required">*</span><?php endif; ?></label>
                        <select class="form-control <?php echo empty($resident_info['gender'] ?? '') ? 'missing' : ''; ?>" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($resident_info['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($resident_info['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($resident_info['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status <?php if (empty($resident_info['civil_status'] ?? '')): ?><span class="required">*</span><?php endif; ?></label>
                        <select class="form-control <?php echo empty($resident_info['civil_status'] ?? '') ? 'missing' : ''; ?>" name="civil_status">
                            <option value="">Select Civil Status</option>
                            <option value="single" <?php echo ($resident_info['civil_status'] ?? '') == 'single' ? 'selected' : ''; ?>>Single</option>
                            <option value="married" <?php echo ($resident_info['civil_status'] ?? '') == 'married' ? 'selected' : ''; ?>>Married</option>
                            <option value="widowed" <?php echo ($resident_info['civil_status'] ?? '') == 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                            <option value="separated" <?php echo ($resident_info['civil_status'] ?? '') == 'separated' ? 'selected' : ''; ?>>Separated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Citizenship</label>
                        <input type="text" class="form-control" name="citizenship" value="<?php echo htmlspecialchars($resident_info['citizenship'] ?? 'Filipino'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Occupation <span class="optional-note">(Optional - enter "N/A" if none)</span></label>
                        <input type="text" class="form-control" name="occupation" value="<?php echo htmlspecialchars($resident_info['occupation'] ?? ''); ?>" placeholder="e.g., Teacher, Engineer, or N/A">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-address-card"></i>
                    Contact Information
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Contact Number <?php if (empty($contact_number)): ?><span class="required">*</span><?php endif; ?></label>
                        <input type="tel" class="form-control <?php echo empty($contact_number) ? 'missing' : ''; ?>" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" placeholder="0912 345 6789">
                    </div>
                    <div class="form-group">
                        <label>Email (Registered)</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Address Information
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Street Address <?php if (empty($address)): ?><span class="required">*</span><?php endif; ?></label>
                        <input type="text" class="form-control <?php echo empty($address) ? 'missing' : ''; ?>" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="House/Unit No., Street">
                    </div>
                    <div class="form-group">
                        <label>Barangay</label>
                        <input type="text" class="form-control" name="barangay" value="<?php echo htmlspecialchars($resident_info['barangay'] ?? ''); ?>" placeholder="Your barangay">
                    </div>
                    <div class="form-group">
                        <label>City/Municipality</label>
                        <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($resident_info['city'] ?? ''); ?>" placeholder="City or Municipality">
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($resident_info['province'] ?? ''); ?>" placeholder="Province">
                    </div>
                    <div class="form-group">
                        <label>ZIP Code</label>
                        <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($resident_info['zip_code'] ?? ''); ?>" placeholder="ZIP Code">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-ambulance"></i>
                    Emergency Contact
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Emergency Contact Name <span class="optional-note">(Optional)</span></label>
                        <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo htmlspecialchars($resident_info['emergency_contact_name'] ?? ''); ?>" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Number <span class="optional-note">(Optional)</span></label>
                        <input type="tel" class="form-control" name="emergency_contact_number" value="<?php echo htmlspecialchars($resident_info['emergency_contact_number'] ?? ''); ?>" placeholder="Contact number">
                    </div>
                    <div class="form-group">
                        <label>Relationship <span class="optional-note">(Optional)</span></label>
                        <input type="text" class="form-control" name="emergency_contact_relation" value="<?php echo htmlspecialchars($resident_info['emergency_contact_relation'] ?? ''); ?>" placeholder="e.g., Parent, Spouse, Sibling">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="../index.php?page=home" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Calculate age from birth date
        function calculateAge(birthDate) {
            if (!birthDate) return;
            
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            const ageDisplay = document.getElementById('ageDisplay');
            if (ageDisplay) {
                if (age > 0) {
                    ageDisplay.innerHTML = 'Age: ' + age + ' years old';
                } else {
                    ageDisplay.innerHTML = '';
                }
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>