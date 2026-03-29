-- Check if table exists and has correct structure
DESCRIBE resident_reports;

-- If table doesn't exist or has issues, recreate it:
DROP TABLE IF EXISTS resident_reports;

CREATE TABLE resident_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    report_type VARCHAR(50),
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    attachment VARCHAR(255),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in-progress', 'resolved', 'closed') DEFAULT 'pending',
    admin_remarks TEXT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_date TIMESTAMP NULL,
    resolved_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_reports_status ON resident_reports(status);
CREATE INDEX idx_reports_priority ON resident_reports(priority);
CREATE INDEX idx_reports_date ON resident_reports(reported_date);