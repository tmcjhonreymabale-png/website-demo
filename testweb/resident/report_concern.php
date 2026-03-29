<?php
// resident/report_concern.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user info for incomplete profile check
$user_query = "SELECT id, first_name, last_name FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Check if profile is complete
$info_query = "SELECT * FROM resident_info WHERE user_id = :user_id";
$info_stmt = $db->prepare($info_query);
$info_stmt->bindParam(':user_id', $_SESSION['user_id']);
$info_stmt->execute();
$resident_info = $info_stmt->fetch(PDO::FETCH_ASSOC);

$required_fields = ['middle_name', 'birth_date', 'address', 'contact_number'];
$has_incomplete_profile = false;
if ($resident_info) {
    foreach ($required_fields as $field) {
        if (empty($resident_info[$field])) {
            $has_incomplete_profile = true;
            break;
        }
    }
} else {
    $has_incomplete_profile = true;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_type = $_POST['report_type'] ?? 'concern';
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'application/pdf'];
        $file_type = $_FILES['attachment']['type'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['attachment']['size'] > $max_size) {
            $error_message = "File size must be less than 5MB";
        } elseif (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/reports/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $file_name = 'report_' . time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $file_name)) {
                $attachment = $file_name;
            } else {
                $error_message = "Failed to upload file";
            }
        } else {
            $error_message = "Invalid file type. Allowed: JPG, PNG, GIF, PDF";
        }
    }
    
    if (empty($subject)) {
        $error_message = "Subject is required";
    } elseif (empty($description)) {
        $error_message = "Description is required";
    } else {
        try {
            $query = "INSERT INTO resident_reports (user_id, report_type, subject, description, location, attachment, priority) 
                      VALUES (:user_id, :type, :subject, :description, :location, :attachment, :priority)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':type', $report_type);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':attachment', $attachment);
            $stmt->bindParam(':priority', $priority);
            
            if ($stmt->execute()) {
                $success_message = "Your report has been submitted successfully! Our team will review it shortly.";
            } else {
                $error_message = "Failed to submit report. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Report a Concern | Barangay System";
include '../includes/header.php';
?>

<style>
    .report-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    
    .form-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f6;
    }
    
    .form-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .form-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .form-header p {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
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
    
    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border-left: 4px solid #f59e0b;
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
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.9rem;
        transition: all 0.2s;
        font-family: inherit;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    select.form-control {
        cursor: pointer;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
    }
    
    .btn-submit:hover {
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
    }
    
    .success-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    
    .file-info {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.3rem;
    }
    
    @media (max-width: 768px) {
        .report-container {
            padding: 0 1rem;
        }
        
        .form-card {
            padding: 1.5rem;
        }
        
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
</style>

<div class="report-container">
    <div class="form-card">
        <div class="form-header">
            <h1>Report a Concern</h1>
            <p>Share your feedback, report issues, or suggest improvements</p>
        </div>
        
        <?php if ($has_incomplete_profile): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Please <a href="profile.php" style="color: #92400e; font-weight: 600;">complete your profile</a> before submitting a report.</span>
        </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <div class="success-actions">
                <a href="../index.php?page=home" class="btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        <?php elseif ($has_incomplete_profile): ?>
            <div class="success-actions">
                <a href="profile.php" class="btn-submit">
                    <i class="fas fa-user-edit"></i> Complete Profile
                </a>
            </div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Report Type <span class="required">*</span></label>
                        <select name="report_type" class="form-control" required>
                            <option value="complaint">📢 Complaint</option>
                            <option value="concern">❓ Concern</option>
                            <option value="feedback">💬 Feedback</option>
                            <option value="suggestion">💡 Suggestion</option>
                            <option value="compliment">👍 Compliment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Priority Level</label>
                        <select name="priority" class="form-control">
                            <option value="low">🟢 Low - Non-urgent</option>
                            <option value="medium" selected>🟡 Medium - Normal</option>
                            <option value="high">🟠 High - Important</option>
                            <option value="urgent">🔴 Urgent - Needs immediate attention</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Subject <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-control" required placeholder="Brief summary of your report">
                </div>
                
                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea name="description" class="form-control" required placeholder="Please provide detailed information about your concern..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Location (Optional)</label>
                    <input type="text" name="location" class="form-control" placeholder="Where did this happen? (e.g., Purok 1, near the basketball court)">
                </div>
                
                <div class="form-group">
                    <label>Attachment (Optional)</label>
                    <input type="file" name="attachment" class="form-control" accept="image/*,application/pdf">
                    <div class="file-info">
                        <i class="fas fa-info-circle"></i> Max size: 5MB. Allowed: JPG, PNG, GIF, PDF
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>