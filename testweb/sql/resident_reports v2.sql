-- Update resident_reports table to add missing columns and modify existing ones
ALTER TABLE resident_reports
ADD COLUMN IF NOT EXISTS report_type ENUM('complaint', 'concern', 'feedback', 'suggestion', 'compliment') AFTER user_id,
MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
ADD COLUMN IF NOT EXISTS location VARCHAR(255) AFTER description,
ADD COLUMN IF NOT EXISTS resolved_by INT AFTER resolved_date,
ADD INDEX idx_reports_type (report_type);

-- Create resident_feedback table if not exists
CREATE TABLE IF NOT EXISTS resident_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    feedback_type ENUM('service', 'staff', 'facility', 'general') DEFAULT 'general',
    rating INT CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(255) NOT NULL,
    feedback TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'reviewed', 'responded') DEFAULT 'pending',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_feedback_status (status),
    INDEX idx_feedback_rating (rating)
);