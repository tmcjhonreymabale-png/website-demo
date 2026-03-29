<?php
// auth/login.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Function to log resident activity
function logResidentActivity($db, $user_id, $action, $description, $ip_address = null) {
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    try {
        $query = "INSERT INTO resident_logs (user_id, action, description, ip_address) 
                  VALUES (:user_id, :action, :description, :ip_address)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $ip_address
        ]);
    } catch (Exception $e) {
        error_log("Failed to log resident activity: " . $e->getMessage());
    }
}

// If already logged in as user, redirect to home
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $query = "SELECT * FROM users WHERE username = :username OR email = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_type'] = 'resident';
                
                $description = "User " . $user['username'] . " logged in";
                logResidentActivity($db, $user['id'], 'LOGIN', $description);
                
                try {
                    $update = "UPDATE users SET last_login = NOW(), is_online = 1, last_activity = NOW() WHERE id = :id";
                    $updateStmt = $db->prepare($update);
                    $updateStmt->bindParam(':id', $user['id']);
                    $updateStmt->execute();
                } catch (Exception $e) {
                    // Column might not exist
                }
                
                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please enter username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Login - Barangay System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 2rem;
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
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }
        
        .input-group input:focus {
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
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            z-index: 10;
        }
        
        .checkbox-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
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
        
        .alert-danger i {
            margin-right: 0.5rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        /* ===== RESPONSIVE BREAKPOINTS ===== */
        
        /* Tablet (max-width: 768px) */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .login-container {
                max-width: 380px;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .login-header p {
                font-size: 0.85rem;
            }
            
            .login-body {
                padding: 1.5rem;
            }
            
            .form-group {
                margin-bottom: 1.2rem;
            }
            
            .form-group label {
                font-size: 0.85rem;
            }
            
            .input-group input {
                padding: 0.7rem 1rem 0.7rem 2.2rem;
                font-size: 0.9rem;
            }
            
            .input-group i {
                left: 0.8rem;
                font-size: 0.9rem;
            }
            
            .checkbox-group label,
            .forgot-link {
                font-size: 0.8rem;
            }
            
            .btn-login {
                padding: 0.7rem;
                font-size: 0.9rem;
            }
            
            .register-link {
                margin-top: 1.2rem;
                padding-top: 1.2rem;
                font-size: 0.85rem;
            }
        }
        
        /* Mobile Large (max-width: 480px) */
        @media (max-width: 480px) {
            body {
                padding: 0.75rem;
            }
            
            .login-container {
                max-width: 100%;
                border-radius: 20px;
            }
            
            .login-header {
                padding: 1.2rem;
            }
            
            .login-header h1 {
                font-size: 1.3rem;
            }
            
            .login-header p {
                font-size: 0.8rem;
            }
            
            .login-body {
                padding: 1.2rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-group label {
                font-size: 0.8rem;
                margin-bottom: 0.3rem;
            }
            
            .input-group input {
                padding: 0.65rem 1rem 0.65rem 2rem;
                font-size: 0.85rem;
                border-radius: 10px;
            }
            
            .input-group i {
                left: 0.7rem;
                font-size: 0.85rem;
            }
            
            .toggle-password {
                right: 0.8rem;
                font-size: 0.85rem;
            }
            
            .checkbox-group {
                margin-bottom: 1.2rem;
            }
            
            .checkbox-group label,
            .forgot-link {
                font-size: 0.75rem;
            }
            
            .btn-login {
                padding: 0.65rem;
                font-size: 0.85rem;
                border-radius: 10px;
            }
            
            .alert {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
                margin-bottom: 1.2rem;
            }
            
            .register-link {
                margin-top: 1rem;
                padding-top: 1rem;
                font-size: 0.8rem;
            }
        }
        
        /* Mobile Small (max-width: 375px) */
        @media (max-width: 375px) {
            .login-header {
                padding: 1rem;
            }
            
            .login-header h1 {
                font-size: 1.2rem;
            }
            
            .login-header p {
                font-size: 0.75rem;
            }
            
            .login-body {
                padding: 1rem;
            }
            
            .form-group label {
                font-size: 0.75rem;
            }
            
            .input-group input {
                padding: 0.6rem 1rem 0.6rem 1.8rem;
                font-size: 0.8rem;
            }
            
            .input-group i {
                left: 0.6rem;
                font-size: 0.8rem;
            }
            
            .checkbox-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .checkbox-group label,
            .forgot-link {
                font-size: 0.7rem;
            }
            
            .btn-login {
                padding: 0.6rem;
                font-size: 0.8rem;
            }
            
            .alert {
                font-size: 0.75rem;
                padding: 0.5rem 0.7rem;
            }
            
            .register-link {
                font-size: 0.75rem;
            }
        }
        
        /* Landscape orientation adjustments */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 0.5rem;
            }
            
            .login-container {
                max-width: 350px;
            }
            
            .login-header {
                padding: 0.8rem;
            }
            
            .login-header h1 {
                font-size: 1.2rem;
            }
            
            .login-header p {
                font-size: 0.7rem;
            }
            
            .login-body {
                padding: 1rem;
            }
            
            .form-group {
                margin-bottom: 0.8rem;
            }
            
            .btn-login {
                padding: 0.5rem;
            }
            
            .register-link {
                margin-top: 0.8rem;
                padding-top: 0.8rem;
            }
        }
        
        /* Touch-friendly adjustments */
        @media (hover: none) and (pointer: coarse) {
            .btn-login, 
            .toggle-password,
            .forgot-link,
            .register-link a,
            .checkbox-group label {
                cursor: default;
            }
            
            .btn-login:active {
                transform: scale(0.98);
            }
            
            .input-group input {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username or Email</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" required placeholder="Enter username or email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required placeholder="Enter password">
                        </div>
                        <i class="fas fa-eye-slash toggle-password" onclick="togglePassword()"></i>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="remember_me"> Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        // Close alert after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>