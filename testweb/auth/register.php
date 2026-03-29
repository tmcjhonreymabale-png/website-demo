<?php
// auth/register.php

session_start();
require_once '../config/database.php';

$error = '';
$success = '';

// Generate CSRF token if not exists or regenerate for new page load
if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
        // Regenerate token for next attempt
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token'];
    } else {
        // Get form data
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms = isset($_POST['terms']);
        
        // Validation
        $errors = [];
        
        // Required fields
        if (empty($first_name)) $errors[] = 'First name is required';
        if (empty($last_name)) $errors[] = 'Last name is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($password)) $errors[] = 'Password is required';
        
        // Email validation
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Username validation
        if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = 'Username must be 3-20 characters (letters, numbers, underscore)';
        }
        
        // Password validation
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            }
            if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain both letters and numbers';
            }
            if ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }
        }
        
        // Terms agreement
        if (!$terms) {
            $errors[] = 'You must agree to the Terms and Conditions and Privacy Policy';
        }
        
        // Only check database if there are no other errors
        if (empty($errors)) {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if email exists
            $checkEmail = "SELECT id FROM users WHERE email = :email";
            $stmt = $db->prepare($checkEmail);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered';
            }
            
            // Check if username exists
            $checkUsername = "SELECT id FROM users WHERE username = :username";
            $stmt = $db->prepare($checkUsername);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = 'Username already taken';
            }
        }
        
        // If no errors, create account
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = bin2hex(random_bytes(32));
                
                // First, check what columns exist in the users table
                $columns_query = "SHOW COLUMNS FROM users";
                $columns_stmt = $db->prepare($columns_query);
                $columns_stmt->execute();
                $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Build query based on existing columns
                $fields = ['first_name', 'last_name', 'email', 'username', 'password', 'user_type', 'status', 'created_at'];
                $placeholders = [':first_name', ':last_name', ':email', ':username', ':password', ':user_type', ':status', ':created_at'];
                $values = [
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':email' => $email,
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':user_type' => 'resident',
                    ':status' => 'active',
                    ':created_at' => date('Y-m-d H:i:s')
                ];
                
                // Add verification_token if column exists
                if (in_array('verification_token', $columns)) {
                    $fields[] = 'verification_token';
                    $placeholders[] = ':verification_token';
                    $values[':verification_token'] = $verification_token;
                    $values[':status'] = 'pending';
                }
                
                // Add profile_pic if column exists
                if (in_array('profile_pic', $columns)) {
                    $fields[] = 'profile_pic';
                    $placeholders[] = ':profile_pic';
                    $values[':profile_pic'] = null;
                }
                
                // Add contact_number if column exists
                if (in_array('contact_number', $columns)) {
                    $fields[] = 'contact_number';
                    $placeholders[] = ':contact_number';
                    $values[':contact_number'] = null;
                }
                
                // Add address if column exists
                if (in_array('address', $columns)) {
                    $fields[] = 'address';
                    $placeholders[] = ':address';
                    $values[':address'] = null;
                }
                
                // Build and execute query
                $query = "INSERT INTO users (" . implode(', ', $fields) . ") 
                          VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $db->prepare($query);
                
                // Bind all parameters
                foreach ($values as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                
                if ($stmt->execute()) {
                    $user_id = $db->lastInsertId();
                    
                    // If verification is enabled, send email
                    if (in_array('verification_token', $columns)) {
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'];
                        $verification_link = $protocol . $host . "/testweb/auth/verify.php?token=" . $verification_token;
                        
                        $success = "Registration successful! Please check your email to verify your account.";
                        $success .= "<br><small><a href='$verification_link' style='color: #667eea;'>Click here to verify your account</a></small>";
                    } else {
                        $success = "Registration successful! You can now login to your account.";
                    }
                    
                    // Clear form data by generating new CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $csrf_token = $_SESSION['csrf_token'];
                    
                    // Redirect after 3 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php?registered=1';
                        }, 3000);
                    </script>";
                    
                } else {
                    $error = "Registration failed. Please try again.";
                    error_log("Registration insert failed for user: $username");
                }
                
            } catch (Exception $e) {
                $error = "Registration failed. Please try again.";
                error_log("Registration error: " . $e->getMessage());
                // Regenerate token for next attempt
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_token'];
            }
        } else {
            $error = implode('<br>', $errors);
            // Regenerate token for next attempt to prevent reuse
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $csrf_token = $_SESSION['csrf_token'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Register - Barangay System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .form-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .form-body {
            padding: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
            font-size: 0.85rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.2s;
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 2.5rem;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            z-index: 10;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }
        
        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }
        
        .checkbox-group input {
            width: auto;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.4;
        }
        
        .checkbox-group a {
            color: #667eea;
            text-decoration: none;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        .btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        
        .links {
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            margin: 2rem auto;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2);
            animation: modalFade 0.3s;
        }
        
        @keyframes modalFade {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .modal-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-header h2 i {
            color: #667eea;
        }
        
        .modal-header .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-header .close:hover {
            background: #f1f5f9;
            color: #ef4444;
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .modal-body h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .modal-body h3:first-child {
            margin-top: 0;
        }
        
        .modal-body p {
            color: #475569;
            line-height: 1.6;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        .modal-body ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .modal-body li {
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 0 0 20px 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn-modal {
            padding: 0.5rem 1.2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
        }
        
        /* ===== RESPONSIVE BREAKPOINTS ===== */
        
        /* Tablet (max-width: 768px) */
        @media (max-width: 768px) {
            body {
                padding: 1.5rem 1rem;
            }
            
            .form-container {
                max-width: 450px;
            }
            
            .form-header {
                padding: 1.5rem;
            }
            
            .form-header h1 {
                font-size: 1.5rem;
            }
            
            .form-header p {
                font-size: 0.85rem;
            }
            
            .form-body {
                padding: 1.5rem;
            }
            
            .form-group input {
                padding: 0.65rem;
                font-size: 0.85rem;
            }
            
            .btn {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }
        
        /* Mobile Large (max-width: 576px) */
        @media (max-width: 576px) {
            body {
                padding: 1rem 0.75rem;
            }
            
            .form-container {
                border-radius: 16px;
            }
            
            .form-header {
                padding: 1.2rem;
            }
            
            .form-header h1 {
                font-size: 1.3rem;
            }
            
            .form-header p {
                font-size: 0.8rem;
            }
            
            .form-body {
                padding: 1.2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
            }
            
            .form-group {
                margin-bottom: 0.8rem;
            }
            
            .form-group label {
                font-size: 0.8rem;
                margin-bottom: 0.3rem;
            }
            
            .form-group input {
                padding: 0.6rem;
                font-size: 0.85rem;
                border-radius: 10px;
            }
            
            .password-wrapper input {
                padding-right: 2.2rem;
            }
            
            .toggle-password {
                right: 10px;
                font-size: 0.85rem;
            }
            
            .checkbox-group {
                margin: 1rem 0;
            }
            
            .checkbox-group label {
                font-size: 0.75rem;
            }
            
            .btn {
                padding: 0.7rem;
                font-size: 0.85rem;
            }
            
            .alert {
                padding: 0.6rem 0.8rem;
                font-size: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .links {
                margin-top: 1rem;
                font-size: 0.75rem;
            }
        }
        
        /* Mobile Small (max-width: 375px) */
        @media (max-width: 375px) {
            body {
                padding: 0.75rem 0.5rem;
            }
            
            .form-header {
                padding: 1rem;
            }
            
            .form-header h1 {
                font-size: 1.2rem;
            }
            
            .form-header p {
                font-size: 0.7rem;
            }
            
            .form-body {
                padding: 1rem;
            }
            
            .form-group label {
                font-size: 0.75rem;
            }
            
            .form-group input {
                padding: 0.55rem;
                font-size: 0.8rem;
            }
            
            .checkbox-group {
                gap: 0.5rem;
            }
            
            .checkbox-group label {
                font-size: 0.7rem;
            }
            
            .btn {
                padding: 0.6rem;
                font-size: 0.8rem;
            }
            
            .modal-header {
                padding: 1rem;
            }
            
            .modal-header h2 {
                font-size: 1rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-body h3 {
                font-size: 0.9rem;
            }
            
            .modal-body p,
            .modal-body li {
                font-size: 0.75rem;
            }
        }
        
        /* Landscape orientation */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 0.5rem;
            }
            
            .form-container {
                max-width: 450px;
            }
            
            .form-header {
                padding: 0.8rem;
            }
            
            .form-header h1 {
                font-size: 1.2rem;
            }
            
            .form-header p {
                font-size: 0.7rem;
            }
            
            .form-body {
                padding: 1rem;
            }
            
            .form-group {
                margin-bottom: 0.5rem;
            }
            
            .btn {
                padding: 0.5rem;
            }
        }
        
        /* Touch-friendly adjustments */
        @media (hover: none) and (pointer: coarse) {
            .btn, 
            .toggle-password,
            .modal-header .close,
            .btn-modal,
            .checkbox-group a {
                cursor: default;
            }
            
            .btn:active {
                transform: scale(0.98);
            }
            
            .form-group input {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>Register Account</h1>
            <p>Join our Barangay Community</p>
        </div>
        
        <div class="form-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required pattern="[A-Za-z0-9_]{3,20}" 
                               title="3-20 characters (letters, numbers, underscore)">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <i class="toggle-password fas fa-eye-slash" onclick="togglePassword('password')"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthFill"></div>
                            </div>
                            <small id="strengthText">Password strength</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="toggle-password fas fa-eye-slash" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        <small id="matchMessage" style="color: #64748b;"></small>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#" onclick="openModal('termsModal'); return false;">Terms and Conditions</a> 
                        and <a href="#" onclick="openModal('privacyModal'); return false;">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">Create Account</button>
            </form>
            <?php endif; ?>
            
            <div class="links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    
    <!-- Terms and Conditions Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-contract"></i> Terms and Conditions</h2>
                <button class="close" onclick="closeModal('termsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h3>1. Acceptance of Terms</h3>
                <p>By registering and using the Barangay System, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use the system.</p>
                
                <h3>2. Account Registration</h3>
                <p>You must provide accurate and complete information during registration. You are responsible for maintaining the confidentiality of your account credentials.</p>
                
                <h3>3. User Responsibilities</h3>
                <ul>
                    <li>Provide truthful and accurate information</li>
                    <li>Keep your account information up to date</li>
                    <li>Not share your account credentials with others</li>
                    <li>Use the system only for lawful purposes</li>
                    <li>Respect the privacy and rights of other users</li>
                </ul>
                
                <h3>4. Prohibited Activities</h3>
                <ul>
                    <li>Submitting false or misleading information</li>
                    <li>Attempting to gain unauthorized access to the system</li>
                    <li>Using the system for illegal activities</li>
                    <li>Harassing or harming other users</li>
                </ul>
                
                <h3>5. Service Availability</h3>
                <p>The barangay reserves the right to modify, suspend, or discontinue any service at any time without prior notice.</p>
                
                <h3>6. Data Accuracy</h3>
                <p>While we strive to maintain accurate information, we do not warrant the completeness or accuracy of the data provided through the system.</p>
                
                <h3>7. Account Termination</h3>
                <p>We reserve the right to suspend or terminate accounts that violate these terms or for any other reason deemed appropriate by the barangay administration.</p>
                
                <h3>8. Amendments</h3>
                <p>These terms may be updated from time to time. Continued use of the system constitutes acceptance of any changes.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-modal" onclick="closeModal('termsModal')">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Privacy Policy Modal -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shield-alt"></i> Privacy Policy</h2>
                <button class="close" onclick="closeModal('privacyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h3>1. Information We Collect</h3>
                <p>We collect personal information such as name, email address, and other relevant details necessary for barangay services.</p>
                
                <h3>2. How We Use Your Information</h3>
                <ul>
                    <li>To process your service requests and inquiries</li>
                    <li>To communicate important announcements and updates</li>
                    <li>To verify your identity as a resident</li>
                    <li>To improve our services and user experience</li>
                    <li>To comply with legal and regulatory requirements</li>
                </ul>
                
                <h3>3. Data Protection</h3>
                <p>We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction.</p>
                
                <h3>4. Data Sharing</h3>
                <p>Your information is only shared with authorized barangay personnel and government agencies as required by law or for legitimate barangay services. We do not sell or rent your personal information to third parties.</p>
                
                <h3>5. Data Retention</h3>
                <p>We retain your information for as long as necessary to fulfill the purposes outlined in this policy, or as required by applicable laws.</p>
                
                <h3>6. Your Rights</h3>
                <ul>
                    <li>Right to access your personal data</li>
                    <li>Right to correct inaccurate information</li>
                    <li>Right to request deletion of your data</li>
                    <li>Right to withdraw consent</li>
                </ul>
                
                <h3>7. Cookies and Tracking</h3>
                <p>We use cookies to enhance your browsing experience and analyze system usage. You can disable cookies in your browser settings.</p>
                
                <h3>8. Contact Us</h3>
                <p>For questions regarding this privacy policy, please contact the Barangay Hall or email: privacy@barangay.gov.ph</p>
                
                <h3>9. Policy Updates</h3>
                <p>We may update this privacy policy from time to time. Any changes will be posted on this page with the updated effective date.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-modal" onclick="closeModal('privacyModal')">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        // Password toggle function
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        function checkStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            return strength;
        }
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const strength = checkStrength(this.value);
                
                strengthFill.className = 'strength-bar-fill';
                
                if (this.value.length === 0) {
                    strengthFill.style.width = '0%';
                    strengthText.textContent = 'Password strength';
                    strengthText.style.color = '#64748b';
                } else if (strength === 1) {
                    strengthFill.classList.add('strength-weak');
                    strengthText.textContent = 'Weak password';
                    strengthText.style.color = '#ef4444';
                } else if (strength === 2) {
                    strengthFill.classList.add('strength-fair');
                    strengthText.textContent = 'Fair password';
                    strengthText.style.color = '#f59e0b';
                } else if (strength === 3) {
                    strengthFill.classList.add('strength-good');
                    strengthText.textContent = 'Good password';
                    strengthText.style.color = '#10b981';
                } else {
                    strengthFill.classList.add('strength-strong');
                    strengthText.textContent = 'Strong password';
                    strengthText.style.color = '#10b981';
                }
            });
        }
        
        // Password confirmation match
        const confirmInput = document.getElementById('confirm_password');
        const matchMessage = document.getElementById('matchMessage');
        
        function checkMatch() {
            if (passwordInput && confirmInput) {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (confirm === '') {
                    matchMessage.textContent = '';
                } else if (password === confirm) {
                    matchMessage.textContent = '✓ Passwords match';
                    matchMessage.style.color = '#10b981';
                } else {
                    matchMessage.textContent = '✗ Passwords do not match';
                    matchMessage.style.color = '#ef4444';
                }
            }
        }
        
        if (passwordInput && confirmInput) {
            passwordInput.addEventListener('input', checkMatch);
            confirmInput.addEventListener('input', checkMatch);
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        // Form validation with disabled button during submission
        const registerForm = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                const password = passwordInput ? passwordInput.value : '';
                const confirm = confirmInput ? confirmInput.value : '';
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
                
                // Disable submit button to prevent double submission
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Creating Account...';
                }
            });
        }
        
        // Auto-hide success message after 5 seconds if no redirect
        setTimeout(function() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    if (successAlert) successAlert.remove();
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>