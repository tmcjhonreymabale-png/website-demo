<?php
// auth/forgot_password.php

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

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Process forgot password request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if email exists
            $query = "SELECT id, username, first_name, last_name, email FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                
                try {
                    // Check if password_resets table exists
                    $tableCheck = $db->query("SHOW TABLES LIKE 'password_resets'");
                    if ($tableCheck->rowCount() == 0) {
                        // Create table if it doesn't exist
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
                    }
                    
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour
                    
                    // Delete any existing unused reset tokens for this user
                    $deleteQuery = "DELETE FROM password_resets WHERE user_id = :user_id AND used = 0";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->bindParam(':user_id', $user['id']);
                    $deleteStmt->execute();
                    
                    // Insert new token
                    $insertQuery = "INSERT INTO password_resets (user_id, token, expires_at, used) 
                                   VALUES (:user_id, :token, :expires, 0)";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':user_id', $user['id']);
                    $insertStmt->bindParam(':token', $token);
                    $insertStmt->bindParam(':expires', $expires);
                    $insertStmt->execute();
                    
                    // Generate reset link
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $reset_link = $protocol . $_SERVER['HTTP_HOST'] . '/testweb/auth/reset_password.php?token=' . $token;
                    
                    // For production, uncomment email sending
                    /*
                    $to = $user['email'];
                    $subject = "Password Reset Request - Barangay System";
                    $message = "Hello " . $user['first_name'] . ",\n\n";
                    $message .= "You requested to reset your password. Click the link below to reset it:\n\n";
                    $message .= $reset_link . "\n\n";
                    $message .= "This link will expire in 1 hour.\n\n";
                    $message .= "If you didn't request this, please ignore this email.\n\n";
                    $message .= "Regards,\nBarangay System";
                    $headers = "From: noreply@barangay.com";
                    
                    mail($to, $subject, $message, $headers);
                    */
                    
                    $success = "Password reset instructions have been sent to your email address.";
                    
                    // For development - show reset link
                    $success .= "<br><br><div style='background: #f1f5f9; padding: 10px; border-radius: 6px; margin-top: 10px;'>
                        <strong>Development Mode - Click this link to reset your password:</strong><br>
                        <a href='$reset_link' target='_blank'>$reset_link</a>
                    </div>";
                    
                } catch (Exception $e) {
                    $error = "Unable to process request. Please try again later.";
                    error_log("Password reset error: " . $e->getMessage());
                }
                
            } else {
                // Don't reveal if email exists or not for security
                $success = "If the email address exists in our system, password reset instructions will be sent.";
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
    <title>Forgot Password - Barangay System</title>
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
        
        a {
            color: #667eea;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .info-text {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Forgot Password</h1>
        <div class="subtitle">Enter your email to reset your password</div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your registered email">
                <div class="info-text">We'll send you a link to reset your password.</div>
            </div>
            
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <?php endif; ?>
        
        <div class="links">
            <p><a href="login.php">Back to Login</a></p>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>