CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('site_name', 'Barangay System'),
('barangay_name', 'Barangay San Jose'),
('barangay_address', '123 Main Street, City, Province'),
('barangay_contact', '(123) 456-7890'),
('barangay_email', 'info@barangay.gov.ph'),
('system_version', '1.0.0');