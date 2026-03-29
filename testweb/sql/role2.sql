-- Add any missing columns to roles table
ALTER TABLE `roles` 
ADD COLUMN IF NOT EXISTS `is_default` BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create permissions table if not exists
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create role_permissions junction table if not exists
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert permissions (ignore duplicates)
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
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

-- Now update the admins table to use role_id (same as above)
ALTER TABLE `admins` 
ADD COLUMN IF NOT EXISTS `role_id` INT AFTER `password`,
ADD COLUMN IF NOT EXISTS `is_active` BOOLEAN DEFAULT TRUE AFTER `role_id`,
ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;