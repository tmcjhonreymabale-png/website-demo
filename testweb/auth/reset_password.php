<?php
// auth/reset_password.php

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    // ini_set('session.cookie_secure', 1); // Uncomment if using HTTPS
    ini_set('session.use_strict_mode', 1);
    session_start();
}

require_once '../config/database.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Debug mode - set to false in production
$debug = true;

if ($debug && !empty($token)) {
    error_log("Reset token received: " . $token);
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Validate token and get user data
$reset_data = null;

if (empty($token)) {
    $error = 'No reset token provided. Please request a new password reset link.';
} else {
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if password_resets table exists, if not create it
        $tableCheck = $db->query("SHOW TABLES LIKE 'password_resets'");
        if ($tableCheck->rowCount() == 0) {
            // Create password_resets table
            $createTable = "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expires (expires_at)
            )";
            $db->exec($createTable);
            
            if ($debug) {
                error_log("Created password_resets table");
            }
        }
        
        // First, try to find the token directly
        $query = "SELECT * FROM password_resets WHERE token = :token";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $token_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debug && $token_record) {
            error_log("Token found in database");
            error_log("Token user_id: " . $token_record['user_id']);
            error_log("Token used: " . $token_record['used']);
            error_log("Token expires: " . $token_record['expires_at']);
            error_log("Current time: " . date('Y-m-d H:i:s'));
        }
        
        if (!$token_record) {
            if ($debug) {
                error_log("Token not found in database");
            }
            $error = 'Invalid password reset link. The token does not exist.';
        } elseif ($token_record['used'] == 1) {
            if ($debug) {
                error_log("Token has already been used");
            }
            $error = 'This password reset link has already been used. Please request a new one.';
        } elseif (strtotime($token_record['expires_at']) < time()) {
            if ($debug) {
                error_log("Token expired at: " . $token_record['expires_at']);
                error_log("Current time: " . date('Y-m-d H:i:s'));
            }
            $error = 'This password reset link has expired. Please request a new one.';
        } else {
            // Token is valid, get user data
            $userQuery = "SELECT id, username, email, first_name, last_name FROM users WHERE id = :user_id";
            $userStmt = $db->prepare($userQuery);
            $userStmt->bindParam(':user_id', $token_record['user_id']);
            $userStmt->execute();
            
            $reset_data = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset_data) {
                $error = 'User not found. Please request a new password reset link.';
            } else {
                if ($debug) {
                    error_log("Valid token found for user: " . $reset_data['email']);
                }
                // Store token_id for later use
                $reset_data['token_id'] = $token_record['id'];
                $reset_data['token'] = $token;
            }
        }
        
    } catch (Exception $e) {
        $error = "System error. Please try again later.";
        error_log("Password reset error: " . $e->getMessage());
        if ($debug) {
            $error .= " Debug: " . $e->getMessage();
        }
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password']) && !$error && $reset_data) {
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($password)) {
            $error = 'Please enter a password.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                // Start transaction
                $db->beginTransaction();
                
                // Hash the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user's password
                $updateQuery = "UPDATE users SET password = :password WHERE id = :user_id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':password', $hashed_password);
                $updateStmt->bindParam(':user_id', $reset_data['id']);
                $updateStmt->execute();
                
                // Mark token as used
                $markUsedQuery = "UPDATE password_resets SET used = 1 WHERE token = :token";
                $markUsedStmt = $db->prepare($markUsedQuery);
                $markUsedStmt->bindParam(':token', $reset_data['token']);
                $markUsedStmt->execute();
                
                // Commit transaction
                $db->commit();
                
                $success = "Password has been reset successfully! You can now login with your new password.";
                
                // Clear any existing sessions
                session_destroy();
                
                // Redirect to login after 3 seconds with JavaScript
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>";
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Unable to reset password. Please try again later.";
                error_log("Password reset error: " . $e->getMessage());
                if ($debug) {
                    $error .= " Debug: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Barangay System</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-size: 1.8rem;
        }
        
        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.1);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-secondary {
            background: #64748b;
            margin-top: 1rem;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
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
        }
        
        .links p {
            margin: 0.5rem 0;
        }
        
        a {
            color: #667eea;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .password-field {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
        }
        
        .info-text {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.5rem;
        }
        
        .requirements {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.5rem;
            padding-left: 1rem;
        }
        
        .requirements li {
            margin-bottom: 0.25rem;
        }
        
        .debug-info {
            background: #f1f5f9;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            margin-top: 1rem;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Reset Password</h1>
        <div class="subtitle">Create a new password for your account</div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php if (strpos($error, 'expired') !== false || strpos($error, 'Invalid') !== false): ?>
            <div class="links">
                <p><a href="forgot_password.php">Request a new reset link</a></p>
                <p><a href="login.php">Back to Login</a></p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div class="links">
                <p><a href="login.php">Click here to login</a></p>
            </div>
        <?php endif; ?>
        
        <?php if (!$error && !$success && $reset_data): ?>
        <form method="POST" action="" id="resetForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="reset_password" value="1">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($reset_data['email']); ?>" disabled>
                <div class="info-text">Resetting password for: <?php echo htmlspecialchars($reset_data['email']); ?></div>
            </div>
            
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required minlength="8">
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                <ul class="requirements">
                    <li id="req-length">✓ At least 8 characters long</li>
                    <li id="req-letter">✓ At least one letter</li>
                    <li id="req-number">✓ At least one number</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                <div id="match-message" class="info-text"></div>
            </div>
            
            <button type="submit" class="btn">Reset Password</button>
        </form>
        
        <div class="links">
            <p><a href="login.php">Back to Login</a></p>
        </div>
        <?php endif; ?>
        
        <?php if ($debug && !empty($token) && $error && strpos($error, 'token does not exist') !== false): ?>
        <div class="debug-info">
            <strong>Debug Information:</strong><br>
            Token received: <?php echo htmlspecialchars($token); ?><br>
            Token length: <?php echo strlen($token); ?> characters<br>
            <small>Note: Make sure the token in the URL matches exactly what was generated.</small>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
        
        // Password strength validation
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const matchMessage = document.getElementById('match-message');
        
        function validatePassword() {
            const password = passwordInput.value;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasLetter = /[a-zA-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            document.getElementById('req-length').style.color = hasLength ? '#22c55e' : '#64748b';
            document.getElementById('req-letter').style.color = hasLetter ? '#22c55e' : '#64748b';
            document.getElementById('req-number').style.color = hasNumber ? '#22c55e' : '#64748b';
            
            return hasLength && hasLetter && hasNumber;
        }
        
        function validateConfirm() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm === '') {
                matchMessage.textContent = '';
                matchMessage.style.color = '#64748b';
            } else if (password === confirm) {
                matchMessage.textContent = '✓ Passwords match';
                matchMessage.style.color = '#22c55e';
            } else {
                matchMessage.textContent = '✗ Passwords do not match';
                matchMessage.style.color = '#ef4444';
            }
        }
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                validatePassword();
                validateConfirm();
            });
        }
        
        if (confirmInput) {
            confirmInput.addEventListener('input', validateConfirm);
        }
        
        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
                alert('Please meet all password requirements before submitting.');
                return false;
            }
            
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                return false;
            }
        });
    </script>
</body>
</html>