<?php
// admin/login.php
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// IMPORTANT: Clear any existing session data to prevent conflicts
// But only if we're not already logged in
if (isset($_SESSION['admin_id']) && !isset($_POST['username'])) {
    // Already logged in, redirect to dashboard
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $query = "SELECT * FROM admins WHERE username = :username AND is_active = 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $admin['password'])) {
                    // Clear any old session data
                    $_SESSION = array();
                    
                    // Set new session data
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                    $_SESSION['admin_type'] = $admin['admin_type'];
                    
                    // Update last login
                    $update = "UPDATE admins SET last_login = NOW() WHERE id = :id";
                    $updateStmt = $db->prepare($update);
                    $updateStmt->bindParam(':id', $admin['id']);
                    $updateStmt->execute();
                    
                    // Log the action
                    logAdminAction($admin['id'], 'LOGIN', 'Admin logged in', $db);
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Barangay System</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Your existing login CSS */
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
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
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
        
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            border-left: 3px solid #ef4444;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
            font-size: 0.9rem;
        }
        
        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            transition: all 0.2s;
        }
        
        .input-group:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .input-group .material-icons {
            padding: 0.75rem 0 0.75rem 0.75rem;
            color: #94a3b8;
            font-size: 1.2rem;
        }
        
        .input-group input {
            flex: 1;
            padding: 0.75rem 0.75rem 0.75rem 0.5rem;
            border: none;
            background: transparent;
            font-size: 0.95rem;
            outline: none;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.85rem;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Barangay Admin</h1>
            <p>Sign in to manage your barangay system</p>
        </div>
        
        <div class="login-card">
            <?php if ($error): ?>
                <div class="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-group">
                        <span class="material-icons">person</span>
                        <input type="text" name="username" placeholder="Enter your username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <span class="material-icons">lock</span>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php">← Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>