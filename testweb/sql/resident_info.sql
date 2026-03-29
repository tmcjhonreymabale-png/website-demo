-- Create resident_info table if it doesn't exist
CREATE TABLE IF NOT EXISTS resident_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    birth_date DATE,
    age INT,
    gender ENUM('male', 'female', 'other'),
    civil_status ENUM('single', 'married', 'widowed', 'separated'),
    occupation VARCHAR(100),
    monthly_income DECIMAL(10,2),
    household_count INT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create qr_codes table if it doesn't exist
CREATE TABLE IF NOT EXISTS qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    qr_code_data TEXT,
    qr_code_image VARCHAR(255),
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);