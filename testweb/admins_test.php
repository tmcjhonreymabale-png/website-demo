<?php
require_once '../../config/database.php';
require_once '../includes/permissions.php';

echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'not set') . "<br>";
echo "can('manage_admins'): " . (can('manage_admins') ? 'TRUE' : 'FALSE') . "<br>";
echo "can('manage_roles'): " . (can('manage_roles') ? 'TRUE' : 'FALSE') . "<br>";

if (!can('manage_admins') && !can('manage_roles')) {
    echo "No permission, would redirect to dashboard.";
} else {
    echo "Permission OK, page would load.";
}