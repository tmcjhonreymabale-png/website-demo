<?php
// auth/change_password.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user info
$user_query = "SELECT id, username, email, first_name, last_name FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$error_message = '';
$success_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Get current password from database
    $pass_query = "SELECT password FROM users WHERE id = :user_id";
    $pass_stmt = $db->prepare($pass_query);
    $pass_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $pass_stmt->execute();
    $user_data = $pass_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Validation
    if (empty($current_password)) {
        $error_message = "Current password is required";
    } elseif (empty($new_password)) {
        $error_message = "New password is required";
    } elseif (empty($confirm_password)) {
        $error_message = "Please confirm your new password";
    } elseif (!password_verify($current_password, $user_data['password'])) {
        // This is where the current password is verified
        $error_message = "Current password is incorrect";
    } elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error_message = "Password must contain both letters and numbers";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':password', $hashed_password);
        $update_stmt->bindParam(':user_id', $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $success_message = "Your password has been changed successfully!";
            // Clear any remember tokens
            $clear_tokens = "DELETE FROM remember_tokens WHERE user_id = :user_id";
            $clear_stmt = $db->prepare($clear_tokens);
            $clear_stmt->bindParam(':user_id', $_SESSION['user_id']);
            $clear_stmt->execute();
        } else {
            $error_message = "Failed to change password. Please try again.";
        }
    }
}

$page_title = "Change Password | Barangay System";
include '../includes/header.php';
?>

<style>
    .change-password-container {
        max-width: 500px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    
    .password-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f6;
    }
    
    .card-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .card-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .card-header p {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .user-info {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: center;
        border: 1px solid #eef2f6;
    }
    
    .user-info i {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 0.5rem;
    }
    
    .user-info h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }
    
    .user-info p {
        font-size: 0.8rem;
        color: #64748b;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
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
    
    .alert-success {
        background: #dcfce7;
        color: #166534;
        border-left: 4px solid #22c55e;
    }
    
    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
    
    .form-group {
        margin-bottom: 1.2rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #1e293b;
        font-size: 0.85rem;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 0.25rem;
    }
    
    .password-wrapper {
        position: relative;
    }
    
    .password-wrapper input {
        width: 100%;
        padding: 0.75rem;
        padding-right: 2.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.9rem;
        transition: all 0.2s;
        font-family: inherit;
    }
    
    .password-wrapper input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #94a3b8;
        transition: color 0.2s;
    }
    
    .toggle-password:hover {
        color: #667eea;
    }
    
    .password-strength {
        margin-top: 0.5rem;
        font-size: 0.7rem;
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
    
    .btn-change {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem;
        border: none;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
    }
    
    .btn-change:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-secondary:hover {
        background: #e2e8f0;
    }
    
    .success-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    
    .password-requirements {
        background: #f8fafc;
        border-radius: 12px;
        padding: 0.75rem;
        margin-top: 1rem;
        font-size: 0.7rem;
        color: #64748b;
    }
    
    .password-requirements ul {
        margin-left: 1.2rem;
        margin-top: 0.3rem;
    }
    
    .password-requirements li {
        margin-bottom: 0.2rem;
    }
    
    .requirement-check {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .requirement-check i {
        font-size: 0.7rem;
    }
    
    .requirement-check .valid {
        color: #10b981;
    }
    
    .requirement-check .invalid {
        color: #94a3b8;
    }
    
    @media (max-width: 768px) {
        .change-password-container {
            padding: 0 1rem;
        }
        
        .password-card {
            padding: 1.5rem;
        }
        
        .card-header h1 {
            font-size: 1.5rem;
        }
        
        .success-actions {
            flex-direction: column;
        }
        
        .btn-secondary {
            justify-content: center;
        }
    }
</style>

<div class="change-password-container">
    <div class="password-card">
        <div class="card-header">
            <h1>Change Password</h1>
            <p>Keep your account secure with a strong password</p>
        </div>
        
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <div class="success-actions">
                <a href="../index.php?page=home" class="btn-secondary">
                    <i class="fas fa-home"></i> Go to Home
                </a>
                <a href="../resident/profile.php" class="btn-secondary">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="" id="passwordForm">
                <div class="form-group">
                    <label>Current Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="current_password" name="current_password" required 
                               placeholder="Enter your current password">
                        <i class="toggle-password fas fa-eye-slash" onclick="togglePassword('current_password')"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>New Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" required>
                        <i class="toggle-password fas fa-eye-slash" onclick="togglePassword('new_password')"></i>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthFill"></div>
                        </div>
                        <small id="strengthText">Password strength</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="toggle-password fas fa-eye-slash" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    <small id="matchMessage" style="color: #64748b;"></small>
                </div>
                
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li id="req-length" class="requirement-check">
                            <i class="fas fa-circle"></i> At least 8 characters
                        </li>
                        <li id="req-letter" class="requirement-check">
                            <i class="fas fa-circle"></i> Contains letters (A-Z, a-z)
                        </li>
                        <li id="req-number" class="requirement-check">
                            <i class="fas fa-circle"></i> Contains numbers (0-9)
                        </li>
                    </ul>
                </div>
                
                <button type="submit" class="btn-change">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    // Toggle password visibility
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
    const newPasswordInput = document.getElementById('new_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    // Requirement elements
    const reqLength = document.getElementById('req-length');
    const reqLetter = document.getElementById('req-letter');
    const reqNumber = document.getElementById('req-number');
    
    function checkStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[A-Za-z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        return strength;
    }
    
    function updateRequirements(password) {
        // Length requirement
        if (password.length >= 8) {
            reqLength.innerHTML = '<i class="fas fa-check-circle valid"></i> At least 8 characters';
        } else {
            reqLength.innerHTML = '<i class="fas fa-circle invalid"></i> At least 8 characters';
        }
        
        // Letter requirement
        if (password.match(/[A-Za-z]/)) {
            reqLetter.innerHTML = '<i class="fas fa-check-circle valid"></i> Contains letters (A-Z, a-z)';
        } else {
            reqLetter.innerHTML = '<i class="fas fa-circle invalid"></i> Contains letters (A-Z, a-z)';
        }
        
        // Number requirement
        if (password.match(/[0-9]/)) {
            reqNumber.innerHTML = '<i class="fas fa-check-circle valid"></i> Contains numbers (0-9)';
        } else {
            reqNumber.innerHTML = '<i class="fas fa-circle invalid"></i> Contains numbers (0-9)';
        }
    }
    
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkStrength(password);
            
            strengthFill.className = 'strength-bar-fill';
            updateRequirements(password);
            
            if (password.length === 0) {
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
        const newPassword = newPasswordInput ? newPasswordInput.value : '';
        const confirm = confirmInput ? confirmInput.value : '';
        
        if (confirm === '') {
            matchMessage.textContent = '';
        } else if (newPassword === confirm) {
            matchMessage.textContent = '✓ Passwords match';
            matchMessage.style.color = '#10b981';
        } else {
            matchMessage.textContent = '✗ Passwords do not match';
            matchMessage.style.color = '#ef4444';
        }
    }
    
    if (newPasswordInput && confirmInput) {
        newPasswordInput.addEventListener('input', checkMatch);
        confirmInput.addEventListener('input', checkMatch);
    }
    
    // Form validation
    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
        const newPassword = newPasswordInput ? newPasswordInput.value : '';
        const confirm = confirmInput ? confirmInput.value : '';
        
        if (newPassword !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
        
        if (!newPassword.match(/[A-Za-z]/) || !newPassword.match(/[0-9]/)) {
            e.preventDefault();
            alert('Password must contain both letters and numbers!');
            return false;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>