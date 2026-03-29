<?php
// resident/view_report.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM resident_reports WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $report_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header('Location: my_reports.php');
    exit();
}

$page_title = "Report Details | Barangay System";
include '../includes/header.php';
?>

<style>
    .report-detail {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    
    .detail-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f6;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }
    
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eef2f6;
    }
    
    .report-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .badge {
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-in-progress { background: #dbeafe; color: #1e40af; }
    .badge-resolved { background: #dcfce7; color: #166534; }
    .badge-closed { background: #f1f5f9; color: #475569; }
    
    .info-section {
        margin-bottom: 1.5rem;
    }
    
    .info-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        color: #1e293b;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .remarks-section {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1rem;
        margin-top: 1rem;
        border-left: 3px solid #667eea;
    }
    
    .attachment-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f1f5f9;
        border-radius: 8px;
        text-decoration: none;
        color: #475569;
        font-size: 0.85rem;
    }
    
    @media (max-width: 768px) {
        .report-detail {
            padding: 0 1rem;
        }
        
        .detail-card {
            padding: 1.5rem;
        }
    }
</style>

<div class="report-detail">
    <a href="my_reports.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to My Reports
    </a>
    
    <div class="detail-card">
        <div class="report-header">
            <div class="report-title"><?php echo htmlspecialchars($report['subject']); ?></div>
            <div class="badge badge-<?php echo $report['status']; ?>">
                <?php echo ucfirst(str_replace('-', ' ', $report['status'])); ?>
            </div>
        </div>
        
        <div class="info-section">
            <div class="info-label">Report Type</div>
            <div class="info-value"><?php echo ucfirst($report['report_type']); ?></div>
        </div>
        
        <div class="info-section">
            <div class="info-label">Priority Level</div>
            <div class="info-value"><?php echo ucfirst($report['priority']); ?></div>
        </div>
        
        <div class="info-section">
            <div class="info-label">Submitted</div>
            <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($report['reported_date'])); ?></div>
        </div>
        
        <?php if ($report['location']): ?>
        <div class="info-section">
            <div class="info-label">Location</div>
            <div class="info-value"><?php echo htmlspecialchars($report['location']); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-section">
            <div class="info-label">Description</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($report['description'])); ?></div>
        </div>
        
        <?php if ($report['attachment']): ?>
        <div class="info-section">
            <div class="info-label">Attachment</div>
            <div class="info-value">
                <a href="../uploads/reports/<?php echo $report['attachment']; ?>" target="_blank" class="attachment-link">
                    <i class="fas fa-paperclip"></i> View Attachment
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($report['admin_remarks']): ?>
        <div class="remarks-section">
            <div class="info-label" style="color: #667eea;">Admin Response</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($report['admin_remarks'])); ?></div>
            <?php if ($report['resolved_date']): ?>
            <div class="info-label" style="margin-top: 0.5rem;">Resolved on</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($report['resolved_date'])); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>