-- Create roles table if not exists
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `permissions` JSON NOT NULL,
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default roles (if missing)
INSERT IGNORE INTO `roles` (`name`, `description`, `permissions`, `is_default`) VALUES
('Main Admin', 'Full access to all features', '["*"]', 1),
('Staff Admin', 'Manage residents, requests, reports', '["view_residents","add_resident","edit_resident","delete_resident","view_requests","update_request_status","view_reports","update_report_status","view_services","manage_own_profile"]', 1),
('Sub Admin', 'View‑only access', '["view_residents","view_requests","view_reports","view_services"]', 1);

-- Make sure your admin users have a role_id
-- Replace 'your_username' with your actual admin username
UPDATE admins SET role_id = (SELECT id FROM roles WHERE name = 'Main Admin') WHERE username = 'mainadmin';
UPDATE admins SET role_id = (SELECT id FROM roles WHERE name = 'Staff Admin') WHERE username = 'staffadmin';
UPDATE admins SET role_id = (SELECT id FROM roles WHERE name = 'Sub Admin') WHERE username = 'subadmin';