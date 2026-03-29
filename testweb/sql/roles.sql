-- First, drop existing tables in correct order (to avoid foreign key errors)
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

-- Table: roles
CREATE TABLE `roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: permissions (list of all possible permissions)
CREATE TABLE `permissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Junction table: role_permissions (many‑to‑many)
CREATE TABLE `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert all possible permissions
INSERT INTO `permissions` (`name`, `description`) VALUES
('view_residents', 'View resident information'),
('add_resident', 'Add new residents'),
('edit_resident', 'Edit existing residents'),
('delete_resident', 'Delete residents'),
('export_residents', 'Export resident data'),
('generate_qr', 'Generate QR codes for residents'),
('view_requests', 'View service requests'),
('update_request_status', 'Update request status'),
('delete_request', 'Delete requests'),
('view_reports', 'View resident reports'),
('update_report_status', 'Update report status'),
('delete_report', 'Delete reports'),
('view_services', 'View services'),
('add_service', 'Add services'),
('edit_service', 'Edit services'),
('delete_service', 'Delete services'),
('toggle_service', 'Activate/deactivate services'),
('view_pages', 'View content pages'),
('edit_page', 'Edit content pages'),
('manage_announcements', 'Manage announcements'),
('manage_about_sections', 'Manage About Us sections'),
('view_team', 'View team members'),
('add_team_member', 'Add team members'),
('edit_team_member', 'Edit team members'),
('delete_team_member', 'Delete team members'),
('scan_qr', 'Scan QR codes'),
('generate_qr_codes', 'Generate QR codes for requests'),
('view_logs', 'View admin logs'),
('manage_admins', 'Manage admin accounts'),
('manage_roles', 'Manage roles and permissions'),
('manage_own_profile', 'Manage own profile');

-- Insert default roles
INSERT INTO `roles` (`name`, `description`, `is_default`) VALUES
('Main Admin', 'Full access to all features', 1),
('Staff Admin', 'Manage residents, requests, and reports', 1),
('Sub Admin', 'View‑only access', 1);

-- Assign all permissions to Main Admin (role_id = 1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Assign specific permissions to Staff Admin (role_id = 2)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `name` IN (
    'view_residents', 'add_resident', 'edit_resident', 'delete_resident', 'export_residents', 'generate_qr',
    'view_requests', 'update_request_status',
    'view_reports', 'update_report_status',
    'view_services',
    'view_pages',
    'view_team',
    'scan_qr', 'generate_qr_codes',
    'manage_own_profile'
);

-- Assign view‑only permissions to Sub Admin (role_id = 3)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions` WHERE `name` IN (
    'view_residents', 'view_requests', 'view_reports', 'view_services', 'view_pages', 'view_team'
);

-- Now update the admins table to use role_id
-- First, add role_id and is_active columns if they don't exist
ALTER TABLE `admins` 
ADD COLUMN IF NOT EXISTS `role_id` INT AFTER `password`,
ADD COLUMN IF NOT EXISTS `is_active` BOOLEAN DEFAULT TRUE AFTER `role_id`,
ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

-- Update existing admins to link to the correct role
-- (adjust the usernames to match your actual admin accounts)
UPDATE `admins` SET `role_id` = 1 WHERE `username` = 'mainadmin';
UPDATE `admins` SET `role_id` = 2 WHERE `username` = 'staffadmin';
UPDATE `admins` SET `role_id` = 3 WHERE `username` = 'subadmin';