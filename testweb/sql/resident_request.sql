-- Check if table exists and has correct structure
DESCRIBE resident_requests;

-- If table doesn't exist or has issues, recreate it:
DROP TABLE IF EXISTS resident_requests;

CREATE TABLE resident_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    request_type VARCHAR(50) DEFAULT 'online',
    details TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_remarks TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    processed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_requests_status ON resident_requests(status);
CREATE INDEX idx_requests_date ON resident_requests(request_date);
CREATE INDEX idx_requests_user ON resident_requests(user_id);
CREATE INDEX idx_requests_service ON resident_requests(service_id);