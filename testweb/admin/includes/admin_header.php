<?php
// admin/includes/admin_header.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once dirname(__DIR__, 2) . '/config/database.php';

// Include permissions helper
require_once dirname(__DIR__) . '/includes/permissions.php';

// IMPORTANT: Check if we're on login page to avoid infinite redirect
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Allow these pages without login check
$allowed_pages = ['login.php', 'logout.php'];
$allowed_dirs = [];

// If we're on login.php or logout.php, skip login check
if (in_array($current_page, $allowed_pages)) {
    // Don't redirect, just include the rest
} else {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Make db available globally
$GLOBALS['db'] = $db;

// Get admin info if logged in
$admin_info = null;
if (isset($_SESSION['admin_id'])) {
    try {
        $query = "SELECT * FROM admins WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['admin_id']);
        $stmt->execute();
        $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Handle error gracefully
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Panel - Barangay System</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            overflow-x: hidden;
        }

        /* Admin Layout */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Modern Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            color: #e2e8f0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #1e293b;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 4px;
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 1.75rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 1rem;
        }

        .sidebar-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .sidebar-header p {
            font-size: 0.7rem;
            opacity: 0.6;
            color: #94a3b8;
            margin-top: 0.35rem;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 0.5rem 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #94a3b8;
            text-decoration: none;
            gap: 0.85rem;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0.2rem 0;
            cursor: pointer;
        }

        .sidebar-menu a:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border-left-color: #3b82f6;
        }

        .sidebar-menu a.active {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border-left-color: #3b82f6;
        }

        .sidebar-menu a .material-icons {
            font-size: 1.25rem;
            width: 28px;
        }

        .sidebar-menu .menu-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
            margin: 0.75rem 1.5rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            background: #f8fafc;
        }

        /* Modern Top Bar */
        .top-bar {
            background: white;
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 99;
            border-bottom: 1px solid #e2e8f0;
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            color: #334155;
            padding: 0.5rem;
            transition: color 0.2s;
        }

        .menu-toggle:hover {
            color: #3b82f6;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-title .material-icons {
            color: #3b82f6;
            font-size: 1.5rem;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 1rem;
            background: #f1f5f9;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .admin-info:hover {
            background: #e2e8f0;
        }

        .admin-info .material-icons {
            color: #3b82f6;
            font-size: 1.2rem;
        }

        .admin-info span {
            font-size: 0.85rem;
            font-weight: 500;
            color: #1e293b;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: #fee2e2;
            border-radius: 40px;
            color: #dc2626;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        .logout-btn .material-icons {
            font-size: 1rem;
        }

        /* Content Wrapper */
        .content-wrapper {
            padding: 1.75rem;
            min-height: calc(100vh - 70px);
        }

        /* Modern Alert Styles */
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            animation: slideIn 0.3s ease;
            border-left: 4px solid;
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
            background: #f0fdf4;
            color: #166534;
            border-left-color: #22c55e;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: #ef4444;
        }

        .alert-warning {
            background: #fffbeb;
            color: #b45309;
            border-left-color: #f59e0b;
        }

        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border-left-color: #3b82f6;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 380px;
            margin: 20vh auto;
            border-radius: 24px;
            padding: 2rem 1.5rem;
            text-align: center;
            animation: modalFade 0.2s ease;
        }

        @keyframes modalFade {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-content .material-icons {
            font-size: 3.5rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }

        .modal-content h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

        .modal-content p {
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 1.75rem;
        }

        .modal-buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .btn-cancel, .btn-logout {
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #334155;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .btn-logout {
            background: #3b82f6;
            color: white;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .page-title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }
            .top-bar {
                padding: 0.5rem 1rem;
            }
            .admin-info span {
                display: none;
            }
            .logout-btn span:last-child {
                display: none;
            }
            .logout-btn {
                padding: 0.4rem 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                max-width: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Barangay Admin</h2>
                <p>Management System</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="../../../testweb/admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span> Dashboard
                </a></li>
                
                <li><a href="../../../testweb/admin/residents/requests.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'requests.php') !== false && strpos($_SERVER['PHP_SELF'], 'scan_request') === false) ? 'active' : ''; ?>">
                    <span class="material-icons">assignment</span> Resident Requests
                </a></li>
                
                <li><a href="../../../testweb/admin/residents/reports.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">flag</span> Resident Reports
                </a></li>
                
                <li><a href="../../../testweb/admin/residents/information.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'information.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">people</span> Resident Information
                </a></li>
                
                <li class="menu-divider"></li>
                
                <li><a href="../../../testweb/admin/management/pages.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'pages.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">description</span> Page Management
                </a></li>
                
                <li><a href="../../../testweb/admin/management/services.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'services.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">miscellaneous_services</span> Services Management
                </a></li>
                
                <li><a href="../../../testweb/admin/management/team.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'team.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">groups</span> Team Management
                </a></li>
                
                <li><a href="../../../testweb/admin/qr/scan_request.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'scan_request.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">assignment_turned_in</span> Scan Request QR
                </a></li>
                
                <?php if (isset($_SESSION['admin_type']) && $_SESSION['admin_type'] == 'main_admin'): ?>
                <li><a href="../../../testweb/admin/logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                    <span class="material-icons">history</span> History Logs
                </a></li>
                <?php endif; ?>

                <?php if (isset($_SESSION['admin_type']) && $_SESSION['admin_type'] == 'main_admin'): ?>
                <li class="menu-divider"></li>
                <li><a href="../../../testweb/admin/management/admins.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admins.php') !== false ? 'active' : ''; ?>">
                    <span class="material-icons">admin_panel_settings</span> Admin Settings
                </a></li>
                <?php endif; ?>
                
                
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="menu-toggle" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </div>
                
                <div class="page-title" id="page-title">
                    <span class="material-icons">dashboard</span>
                    <span>Dashboard</span>
                </div>
                
                <div class="top-bar-right">
                    <div class="admin-info">
                        <span class="material-icons">account_circle</span>
                        <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                    </div>
                    <a href="#" onclick="showLogoutModal(); return false;" class="logout-btn">
                        <span class="material-icons">logout</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
            
            <div class="content-wrapper">