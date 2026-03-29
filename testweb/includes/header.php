<?php
// includes/header.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Determine the correct base path
$base_path = dirname(__DIR__);

// Include database connection
require_once $base_path . '/config/database.php';

// Default barangay name
$barangay_name = "Barangay System";

// Get user data if logged in
$user_data = null;
$has_incomplete_profile = false;
$missing_fields_list = [];

if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, first_name, last_name, email, profile_pic FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            $_SESSION['user_email'] = $user_data['email'];
        }
        
        // Check if resident_info table exists and fetch profile completion status
        $check_table = "SHOW TABLES LIKE 'resident_info'";
        $table_check = $db->prepare($check_table);
        $table_check->execute();
        
        if ($table_check->rowCount() > 0) {
            $info_query = "SELECT * FROM resident_info WHERE user_id = :user_id";
            $info_stmt = $db->prepare($info_query);
            $info_stmt->bindParam(':user_id', $_SESSION['user_id']);
            $info_stmt->execute();
            $resident_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Define required fields for profile completion
            $required_fields = [
                'birth_date' => 'Birth Date',
                'gender' => 'Gender',
                'civil_status' => 'Civil Status',
                'address' => 'Address',
                'contact_number' => 'Contact Number'
            ];
            
            if ($resident_info) {
                foreach ($required_fields as $field => $label) {
                    if (empty($resident_info[$field])) {
                        $missing_fields_list[] = $label;
                    }
                }
            } else {
                $missing_fields_list = array_values($required_fields);
            }
            
            $has_incomplete_profile = !empty($missing_fields_list);
        }
    } catch (Exception $e) {
        // Silently fail if database error
    }
}

// Get current page for active state
$current_file = basename($_SERVER['PHP_SELF']);
$current_page = $_GET['page'] ?? 'home';

// Set page title based on current page
$page_title = $barangay_name;
$page_description = "Official website of " . $barangay_name;

// Determine page title based on current route
if (strpos($_SERVER['REQUEST_URI'], 'resident/') !== false) {
    if (strpos($_SERVER['REQUEST_URI'], 'my_requests.php') !== false) {
        $page_title = "My Requests | " . $barangay_name;
    } elseif (strpos($_SERVER['REQUEST_URI'], 'report_concern.php') !== false) {
        $page_title = "Report a Concern | " . $barangay_name;
    } elseif (strpos($_SERVER['REQUEST_URI'], 'profile.php') !== false) {
        $page_title = "My Profile | " . $barangay_name;
    } elseif (strpos($_SERVER['REQUEST_URI'], 'request_service.php') !== false) {
        $page_title = "Request Service | " . $barangay_name;
    }
} elseif (strpos($_SERVER['REQUEST_URI'], 'auth/') !== false) {
    if (strpos($_SERVER['REQUEST_URI'], 'login.php') !== false) {
        $page_title = "Login | " . $barangay_name;
    } elseif (strpos($_SERVER['REQUEST_URI'], 'register.php') !== false) {
        $page_title = "Register | " . $barangay_name;
    } elseif (strpos($_SERVER['REQUEST_URI'], 'change_password.php') !== false) {
        $page_title = "Change Password | " . $barangay_name;
    }
} else {
    switch ($current_page) {
        case 'home':
            $page_title = $barangay_name . " - Official Website";
            break;
        case 'announcements':
            $page_title = "Announcements | " . $barangay_name;
            break;
        case 'services':
            $page_title = "Services | " . $barangay_name;
            break;
        case 'about':
            $page_title = "About Us | " . $barangay_name;
            break;
        default:
            $page_title = $barangay_name;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding-top: 80px;
            background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Modern Navbar */
        .modern-navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 10000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .modern-navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }
        
        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            flex-shrink: 0;
        }
        
        .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.2);
        }
        
        .logo:hover .logo-icon {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.3);
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .logo-name {
            font-size: 1.35rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
            letter-spacing: -0.3px;
            transition: all 0.3s;
        }
        
        .logo:hover .logo-name {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
        }
        
        .logo-badge {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        
        /* Desktop Navigation */
        .nav-links {
            display: flex;
            gap: 0.25rem;
            list-style: none;
            align-items: center;
            margin: 0;
            padding: 0;
        }
        
        .nav-links li {
            position: relative;
            list-style: none;
        }
        
        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 1.1rem;
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .nav-links li a i {
            font-size: 1rem;
            color: #94a3b8;
            transition: all 0.2s;
        }
        
        .nav-links li a:hover {
            background: #f1f5f9;
            color: #667eea;
        }
        
        .nav-links li a:hover i {
            color: #667eea;
            transform: translateY(-1px);
        }
        
        .nav-links li a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
        }
        
        .nav-links li a.active i {
            color: white;
        }
        
        /* Profile Incomplete Notice */
        .profile-notice {
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: #fef3c7;
            border-bottom: 1px solid #f59e0b;
            padding: 0.75rem 1rem;
            text-align: center;
            z-index: 9999;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-notice-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .profile-notice-content i {
            color: #f59e0b;
            font-size: 1.2rem;
        }
        
        .profile-notice-content span {
            color: #92400e;
            font-size: 0.85rem;
        }
        
        .profile-notice-content a {
            color: #f59e0b;
            background: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.75rem;
            transition: all 0.2s;
            border: 1px solid #f59e0b;
        }
        
        .profile-notice-content a:hover {
            background: #f59e0b;
            color: white;
        }
        
        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .btn-login, 
        .btn-register {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.35);
        }
        
        .btn-register {
            background: transparent;
            color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        
        .btn-register:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }
        
        /* Profile Button */
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.5rem 1rem 0.5rem 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 100px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .profile-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .profile-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-info {
            text-align: left;
        }
        
        .profile-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.85rem;
            line-height: 1.3;
        }
        
        .profile-role {
            font-size: 0.7rem;
            color: #64748b;
        }
        
        .profile-arrow {
            color: #94a3b8;
            font-size: 0.75rem;
            transition: transform 0.2s;
        }
        
        /* Dropdown Menu */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.15);
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            z-index: 1000;
        }
        
        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown.active .profile-arrow {
            transform: rotate(180deg);
        }
        
        .dropdown-header {
            padding: 1.2rem 1.2rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .dropdown-user-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }
        
        .dropdown-user-email {
            font-size: 0.75rem;
            color: #64748b;
            word-break: break-all;
        }
        
        .dropdown-items {
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.7rem 1.2rem;
            color: #334155;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .dropdown-item:hover {
            background: #f8fafc;
            padding-left: 1.5rem;
            color: #667eea;
        }
        
        .dropdown-item i {
            width: 20px;
            font-size: 1rem;
            color: #94a3b8;
            transition: color 0.2s;
        }
        
        .dropdown-item:hover i {
            color: #667eea;
        }
        
        .dropdown-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0.5rem 0;
        }
        
        .dropdown-item.logout {
            color: #ef4444;
        }
        
        .dropdown-item.logout i {
            color: #ef4444;
        }
        
        .dropdown-item.logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        
        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: #475569;
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.2s;
            z-index: 10001;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-menu-btn:hover {
            background: #f1f5f9;
            color: #667eea;
        }
        
        /* Right Section */
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 320px;
            max-width: 85vw;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            z-index: 10001;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .mobile-nav.active {
            right: 0;
        }
        
        .mobile-nav-header {
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
        }
        
        .mobile-barangay-name {
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .mobile-user-info {
            color: white;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .mobile-user-name {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 6px;
            word-break: break-word;
        }
        
        .mobile-user-email {
            font-size: 0.75rem;
            opacity: 0.9;
            word-break: break-all;
        }
        
        .mobile-nav-links {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .mobile-nav-links li {
            list-style: none;
        }
        
        .mobile-nav-links li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0.9rem 1.5rem;
            color: #334155;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
        }
        
        .mobile-nav-links li a:hover {
            background: #f8fafc;
            padding-left: 2rem;
            color: #667eea;
        }
        
        .mobile-nav-links li a i {
            width: 24px;
            color: #94a3b8;
            font-size: 1.1rem;
        }
        
        .mobile-nav-links li a:hover i {
            color: #667eea;
        }
        
        .mobile-nav-links li a.active {
            background: #f1f5f9;
            color: #667eea;
            border-left: 3px solid #667eea;
        }
        
        .mobile-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0.75rem 0;
        }
        
        /* Mobile Auth Buttons */
        .mobile-auth-buttons {
            display: none;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            margin-top: 0.5rem;
        }
        
        .mobile-auth-buttons .btn-login,
        .mobile-auth-buttons .btn-register {
            justify-content: center;
            width: 100%;
        }
        
        /* Overlay */
        .nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 10000;
            display: none;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }
        
        .nav-overlay.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .nav-container {
                padding: 0 1.5rem;
            }
            
            .nav-links li a {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 992px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: flex;
            }
            
            .auth-buttons {
                display: none;
            }
            
            .mobile-auth-buttons {
                display: flex;
            }
            
            body {
                padding-top: 70px;
            }
            
            .profile-notice {
                top: 70px;
            }
            
            .nav-container {
                height: 70px;
                padding: 0 1.2rem;
            }
            
            .logo-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
            
            .logo-name {
                font-size: 1.1rem;
            }
            
            .logo-badge {
                font-size: 0.65rem;
            }
            
            .profile-btn {
                padding: 0.4rem 0.8rem 0.4rem 0.6rem;
            }
            
            .profile-avatar {
                width: 32px;
                height: 32px;
            }
            
            .profile-info {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .nav-container {
                padding: 0 1rem;
            }
            
            .logo-name {
                font-size: 0.95rem;
            }
            
            .logo-badge {
                display: none;
            }
            
            .logo-icon {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .profile-avatar {
                width: 28px;
                height: 28px;
            }
            
            .mobile-nav {
                width: 280px;
            }
            
            .profile-notice-content {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .profile-notice-content span {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 380px) {
            .logo-text {
                display: none;
            }
            
            .logo-icon {
                margin: 0;
            }
        }
        
        /* Touch-friendly adjustments */
        @media (hover: none) and (pointer: coarse) {
            .nav-links li a,
            .dropdown-item,
            .mobile-nav-links li a,
            .btn-login,
            .btn-register,
            .profile-btn {
                cursor: default;
                -webkit-tap-highlight-color: transparent;
            }
            
            .nav-links li a:active,
            .dropdown-item:active,
            .mobile-nav-links li a:active {
                opacity: 0.7;
            }
        }
        
        /* Ensure all clickable elements work */
        .dropdown-item, 
        .mobile-nav-links li a,
        .nav-links li a,
        .btn-login,
        .btn-register,
        .logo,
        .profile-btn {
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        
        button, a {
            touch-action: manipulation;
        }
    </style>
</head>
<body>

<!-- Profile Incomplete Notice -->
<?php if (isset($_SESSION['user_id']) && $has_incomplete_profile): ?>
<div class="profile-notice">
    <div class="profile-notice-content">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Your profile is incomplete! Please complete your profile information.</span>
        <a href="/testweb/resident/profile.php">
            Complete Profile <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Modern Navbar -->
<nav class="modern-navbar" id="navbar">
    <div class="nav-container">
        <!-- Logo -->
        <a href="/testweb/index.php?page=home" class="logo">
            <div class="logo-icon">
                <i class="fas fa-landmark"></i>
            </div>
            <div class="logo-text">
                <span class="logo-name"><?php echo htmlspecialchars($barangay_name); ?></span>
                <span class="logo-badge">Barangay Management System</span>
            </div>
        </a>
        
        <!-- Desktop Navigation -->
        <ul class="nav-links">
            <li><a href="/testweb/index.php?page=home" class="<?php echo $current_page == 'home' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Home</span>
            </a></li>
            <li><a href="/testweb/index.php?page=announcements" class="<?php echo $current_page == 'announcements' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> <span>Announcements</span>
            </a></li>
            <li><a href="/testweb/index.php?page=services" class="<?php echo $current_page == 'services' ? 'active' : ''; ?>">
                <i class="fas fa-concierge-bell"></i> <span>Services</span>
            </a></li>
            <li><a href="/testweb/index.php?page=about" class="<?php echo $current_page == 'about' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> <span>About</span>
            </a></li>
        </ul>
        
        <!-- Right Section -->
        <div class="header-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Desktop Profile Dropdown -->
                <div class="dropdown" id="profileDropdown">
                    <button class="profile-btn" id="profileBtn" type="button" aria-label="Profile menu">
                        <div class="profile-avatar">
                            <?php if (isset($user_data['profile_pic']) && !empty($user_data['profile_pic'])): ?>
                                <img src="/testweb/uploads/profiles/<?php echo htmlspecialchars($user_data['profile_pic']); ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name">
                                <?php 
                                if (isset($user_data['first_name'])) {
                                    echo htmlspecialchars($user_data['first_name']);
                                } elseif (isset($_SESSION['user_name'])) {
                                    echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);
                                } else {
                                    echo 'Resident';
                                }
                                ?>
                            </div>
                            <div class="profile-role">Resident</div>
                        </div>
                        <i class="fas fa-chevron-down profile-arrow"></i>
                    </button>
                    
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <div class="dropdown-user-name">
                                <?php 
                                if (isset($user_data['first_name']) && isset($user_data['last_name'])) {
                                    echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']);
                                } elseif (isset($_SESSION['user_name'])) {
                                    echo htmlspecialchars($_SESSION['user_name']);
                                } else {
                                    echo 'Resident';
                                }
                                ?>
                            </div>
                            <div class="dropdown-user-email">
                                <?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>
                            </div>
                        </div>
                        <div class="dropdown-items">
                            <a href="/testweb/resident/profile.php" class="dropdown-item">
                                <i class="fas fa-user-circle"></i> My Profile
                            </a>
                            <a href="/testweb/resident/report_concern.php" class="dropdown-item">
                                <i class="fas fa-plus-circle"></i> Report a Concern
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/testweb/resident/my_requests.php" class="dropdown-item">
                                <i class="fas fa-file-alt"></i> My Requests
                            </a>
                            <a href="/testweb/auth/change_password.php" class="dropdown-item">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/testweb/auth/logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Desktop Auth Buttons -->
                <div class="auth-buttons">
                    <a href="/testweb/auth/login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="/testweb/auth/register.php" class="btn-register">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Mobile menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Navigation -->
<div class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-header">
        <div class="mobile-barangay-name">
            <i class="fas fa-landmark"></i> <?php echo htmlspecialchars($barangay_name); ?>
        </div>
        <?php if (isset($_SESSION['user_id']) && isset($user_data)): ?>
            <div class="mobile-user-info">
                <div class="mobile-user-name">
                    <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                </div>
                <div class="mobile-user-email">
                    <?php echo htmlspecialchars($user_data['email']); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="mobile-user-info">
                <div class="mobile-user-name">Welcome!</div>
                <div class="mobile-user-email">Sign in to access all features</div>
            </div>
        <?php endif; ?>
    </div>
    
    <ul class="mobile-nav-links">
        <li><a href="/testweb/index.php?page=home">
            <i class="fas fa-home"></i> Home
        </a></li>
        <li><a href="/testweb/index.php?page=announcements">
            <i class="fas fa-bullhorn"></i> Announcements
        </a></li>
        <li><a href="/testweb/index.php?page=services">
            <i class="fas fa-concierge-bell"></i> Services
        </a></li>
        <li><a href="/testweb/index.php?page=about">
            <i class="fas fa-info-circle"></i> About Us
        </a></li>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="mobile-divider"></div>
            <li><a href="/testweb/resident/profile.php">
                <i class="fas fa-user-circle"></i> My Profile
            </a></li>
            <?php if ($has_incomplete_profile): ?>
            <li><a href="/testweb/resident/profile.php" style="background: #fef3c7; color: #92400e; margin: 0 1rem; border-radius: 12px;">
                <i class="fas fa-exclamation-triangle"></i> Complete Profile
            </a></li>
            <?php endif; ?>
            <li><a href="/testweb/resident/report_concern.php">
                <i class="fas fa-plus-circle"></i> Report a Concern
            </a></li>
            <li><a href="/testweb/resident/my_requests.php">
                <i class="fas fa-file-alt"></i> My Requests
            </a></li>
            <li><a href="/testweb/auth/change_password.php">
                <i class="fas fa-key"></i> Change Password
            </a></li>
            <div class="mobile-divider"></div>
            <li><a href="/testweb/auth/logout.php" style="color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a></li>
        <?php endif; ?>
    </ul>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="mobile-auth-buttons">
            <a href="/testweb/auth/login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="/testweb/auth/register.php" class="btn-register">
                <i class="fas fa-user-plus"></i> Register
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Overlay -->
<div class="nav-overlay" id="navOverlay"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileNav = document.getElementById('mobileNav');
        const navOverlay = document.getElementById('navOverlay');
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const navbar = document.getElementById('navbar');
        
        function toggleMobileMenu(e) {
            if (e) e.preventDefault();
            mobileNav.classList.toggle('active');
            navOverlay.classList.toggle('active');
            document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
            
            const icon = mobileMenuBtn.querySelector('i');
            if (mobileNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
        
        function closeMobileMenu() {
            mobileNav.classList.remove('active');
            navOverlay.classList.remove('active');
            document.body.style.overflow = '';
            const icon = mobileMenuBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
        
        function toggleDropdown(e) {
            e.preventDefault();
            e.stopPropagation();
            if (profileDropdown) {
                profileDropdown.classList.toggle('active');
            }
        }
        
        function closeDropdown() {
            if (profileDropdown) {
                profileDropdown.classList.remove('active');
            }
        }
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }
        
        if (navOverlay) {
            navOverlay.addEventListener('click', () => {
                closeMobileMenu();
                closeDropdown();
            });
        }
        
        if (profileBtn) {
            profileBtn.addEventListener('click', toggleDropdown);
        }
        
        document.addEventListener('click', function(event) {
            if (profileDropdown && !profileDropdown.contains(event.target)) {
                closeDropdown();
            }
            if (mobileNav && mobileNav.classList.contains('active') && 
                !mobileNav.contains(event.target) && 
                event.target !== mobileMenuBtn && 
                !mobileMenuBtn.contains(event.target)) {
                closeMobileMenu();
            }
        });
        
        if (mobileNav) {
            const mobileLinks = mobileNav.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', closeMobileMenu);
            });
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMobileMenu();
                closeDropdown();
            }
        });
        
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 992 && mobileNav && mobileNav.classList.contains('active')) {
                    closeMobileMenu();
                }
            }, 250);
        });
        
        if (navbar) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        }
        
        // Set active class based on current page
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || 'home';
        
        document.querySelectorAll('.nav-links a, .mobile-nav-links a').forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.includes(`page=${currentPage}`)) {
                link.classList.add('active');
            }
        });
    });
</script>