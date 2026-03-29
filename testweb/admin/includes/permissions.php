<?php
// admin/includes/permissions.php

// Include database.php for helper functions
require_once dirname(__DIR__, 2) . '/config/database.php';

/**
 * Check if current admin is Main Admin
 * @return bool
 */
function isMainAdmin() {
    return isset($_SESSION['admin_type']) && $_SESSION['admin_type'] == 'main_admin';
}

/**
 * Check if current admin is Staff Admin
 * @return bool
 */
function isStaffAdmin() {
    return isset($_SESSION['admin_type']) && $_SESSION['admin_type'] == 'staff_admin';
}

/**
 * Get current admin type
 * @return string|null
 */
function getAdminType() {
    return $_SESSION['admin_type'] ?? null;
}

/**
 * Check if current admin has a specific permission
 * @param string $permission
 * @return bool
 */
function can($permission) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    if (!isset($_SESSION['admin_type'])) {
        return false;
    }
    
    // Main Admin has full access
    if ($_SESSION['admin_type'] == 'main_admin') {
        return true;
    }
    
    // Staff Admin - define what they can access
    if ($_SESSION['admin_type'] == 'staff_admin') {
        $forbidden = [
            'admin_settings', 
            'history_logs', 
            'manage_admins', 
            'manage_roles', 
            'delete_resident', 
            'delete_request', 
            'delete_report', 
            'delete_service', 
            'delete_announcement', 
            'delete_about_section',
            'manage_team'
        ];
        
        if (in_array($permission, $forbidden)) {
            return false;
        }
        return true;
    }
    
    return false;
}

/**
 * Check permission and redirect if not allowed
 * @param string $permission
 * @param string $redirect_url
 */
function requirePermission($permission, $redirect_url = '../dashboard.php') {
    if (!can($permission)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        redirect($redirect_url);
    }
}

/**
 * Check if admin is logged in, redirect if not
 */
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        redirect('../login.php');
    }
}
?>