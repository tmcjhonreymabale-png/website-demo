-- Create database
CREATE DATABASE IF NOT EXISTS barangay_system;
USE barangay_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    address TEXT,
    contact_number VARCHAR(20),
    user_type ENUM('resident', 'admin') DEFAULT 'resident',
    is_online BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('Main Admin', 'Staff Admin', 'Sub Admin') NOT NULL,
    profile_image VARCHAR(255),
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements table
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    posted_by INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES admins(id)
);

-- Services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    processing_time VARCHAR(100),
    fee DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Resident requests table
CREATE TABLE resident_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    service_id INT,
    request_type VARCHAR(50),
    details TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_remarks TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    processed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (processed_by) REFERENCES admins(id)
);

-- Resident reports table
CREATE TABLE resident_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    report_type VARCHAR(50),
    subject VARCHAR(200),
    description TEXT,
    attachment VARCHAR(255),
    status ENUM('pending', 'in-progress', 'resolved', 'closed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_date TIMESTAMP NULL,
    resolved_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES admins(id)
);

-- Resident information table
CREATE TABLE resident_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
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
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Page management table
CREATE TABLE pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_name VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200),
    content TEXT,
    meta_description TEXT,
    featured_image VARCHAR(255),
    updated_by INT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admins(id)
);

-- QR codes table
CREATE TABLE qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    qr_code_data TEXT,
    qr_code_image VARCHAR(255),
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Team members table
CREATE TABLE team_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    position VARCHAR(100),
    biography TEXT,
    contact_info VARCHAR(255),
    profile_image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Admin history logs table
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Insert default admin accounts
INSERT INTO admins (username, password, email, first_name, last_name, role) VALUES
('mainadmin', '$2y$10$YourHashedPasswordHere', 'mainadmin@barangay.com', 'Main', 'Admin', 'Main Admin'),
('staffadmin', '$2y$10$YourHashedPasswordHere', 'staff@barangay.com', 'Staff', 'Admin', 'Staff Admin'),
('subadmin', '$2y$10$YourHashedPasswordHere', 'sub@barangay.com', 'Sub', 'Admin', 'Sub Admin');

-- Insert default pages
INSERT INTO pages (page_name, title, content) VALUES
('home', 'Welcome to Barangay [Name]', '<h1>Welcome to Our Barangay</h1><p>This is the official website of Barangay [Name]. We are committed to serving our community with excellence and transparency.</p>'),
('announcements', 'Barangay Announcements', '<p>Stay updated with the latest news and announcements from your Barangay.</p>'),
('services', 'Barangay Services', '<p>We offer various services to cater to the needs of our residents.</p>'),
('about', 'About Us', '<p>Learn more about Barangay [Name], its history, mission, and vision.</p>');

-- Insert default services
INSERT INTO services (service_name, description, processing_time, fee) VALUES
('Barangay Clearance', 'Official document certifying a person''s residency and good moral character', '30 minutes', 50.00),
('Certificate of Indigency', 'Certificate for residents belonging to low-income families', '30 minutes', 30.00),
('Business Clearance', 'Clearance for business permit application', '1 hour', 100.00),
('Residency Certificate', 'Proof of residency in the barangay', '30 minutes', 50.00);