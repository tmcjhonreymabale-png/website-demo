-- Create team_members table
CREATE TABLE `team_members` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `position` VARCHAR(100) NOT NULL,
    `position_category` ENUM('barangay_official', 'sk_official', 'staff', 'volunteer') DEFAULT 'barangay_official',
    `biography` TEXT,
    `contact_info` VARCHAR(255),
    `profile_image` VARCHAR(255),
    `display_order` INT DEFAULT 0,
    `term_start` DATE,
    `term_end` DATE,
    `committee` VARCHAR(100),
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert a few sample records
INSERT INTO `team_members` (`full_name`, `position`, `position_category`) VALUES
('Juan Dela Cruz', 'Barangay Captain', 'barangay_official'),
('Maria Santos', 'Barangay Kagawad', 'barangay_official'),
('Kevin Mercado', 'SK Chairman', 'sk_official'),
('Luzviminda Cruz', 'Administrative Aide', 'staff'),
('Rico Mercado', 'Barangay Tanod', 'volunteer');