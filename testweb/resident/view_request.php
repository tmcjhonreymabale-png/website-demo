<?php
// resident/view_request.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT r.*, s.service_name, s.description, s.fee, s.requirements
          FROM resident_requests r
          JOIN services s ON r.service_id = s.id
          WHERE r.id = :id AND r.user_id = :user_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $request_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    $_SESSION['error'] = "Request not found.";
    header('Location: my_requests.php');
    exit();
}

$page_title = "Request Details | Barangay System";
include '../includes/header.php';
?>

<style>
    .request-detail {
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
        transition: gap 0.2s;
    }
    
    .back-link:hover {
        gap: 0.8rem;
    }
    
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eef2f6;
    }
    
    .request-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-approved {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-completed {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .info-section {
        margin-bottom: 1.5rem;
    }
    
    .info-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 0.25rem;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        color: #1e293b;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .service-details {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.2rem;
        margin-top: 1rem;
        border: 1px solid #eef2f6;
    }
    
    .service-details h3 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .service-details h3 i {
        color: #667eea;
    }
    
    .service-details p {
        color: #475569;
        font-size: 0.85rem;
        line-height: 1.5;
        margin-bottom: 0.75rem;
    }
    
    .requirements-section {
        background: #fef9e6;
        border-radius: 12px;
        padding: 0.8rem;
        margin-top: 0.5rem;
        border-left: 3px solid #f59e0b;
    }
    
    .requirements-section h4 {
        font-size: 0.75rem;
        font-weight: 600;
        color: #b45309;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .requirements-section p {
        font-size: 0.75rem;
        color: #92400e;
        margin: 0;
    }
    
    .remarks-section {
        background: #f0f9ff;
        border-radius: 16px;
        padding: 1rem;
        margin-top: 1rem;
        border-left: 3px solid #667eea;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eef2f6;
    }
    
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    
    .btn-back:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .btn-qr {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    
    .btn-qr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        gap: 0.8rem;
    }
    
    .fee-amount {
        font-weight: 700;
        color: #10b981;
    }
    
    @media (max-width: 768px) {
        .request-detail {
            padding: 0 1rem;
        }
        
        .detail-card {
            padding: 1.5rem;
        }
        
        .request-title {
            font-size: 1.1rem;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn-back, .btn-qr {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="request-detail">
    
    <div class="detail-card">
        <div class="request-header">
            <div class="request-title"><?php echo htmlspecialchars($request['service_name']); ?></div>
            <div class="status-badge status-<?php echo $request['status']; ?>">
                <i class="fas fa-<?php 
                    echo $request['status'] == 'pending' ? 'clock' : 
                        ($request['status'] == 'approved' ? 'check-circle' : 
                        ($request['status'] == 'rejected' ? 'times-circle' : 'check-double')); 
                ?>"></i>
                <?php echo ucfirst($request['status']); ?>
            </div>
        </div>
        
        <div class="info-section">
            <div class="info-label">Type</div>
            <div class="info-value">
                <i class="fas fa-<?php echo $request['request_type'] == 'online' ? 'laptop' : 'walking'; ?>"></i>
                <?php echo ucfirst($request['request_type']); ?> Request
            </div>
        </div>
        
        <div class="info-section">
            <div class="info-label">Preferred Schedule</div>
            <div class="info-value">
                <?php 
                if (!empty($request['preferred_date'])) {
                    echo '<i class="fas fa-calendar-day"></i> ' . date('F j, Y', strtotime($request['preferred_date']));
                    if (!empty($request['preferred_time'])) {
                        echo ' at <i class="fas fa-clock"></i> ' . htmlspecialchars($request['preferred_time']);
                    }
                } elseif (!empty($request['preferred_day']) && !empty($request['preferred_time'])) {
                    echo '<i class="fas fa-calendar-week"></i> ' . htmlspecialchars($request['preferred_day']) . ' at <i class="fas fa-clock"></i> ' . htmlspecialchars($request['preferred_time']);
                } else {
                    echo 'Not specified';
                }
                ?>
            </div>
        </div>
        
        <div class="info-section">
            <div class="info-label">Date Submitted</div>
            <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($request['request_date'])); ?></div>
        </div>
        
        <div class="service-details">
            <h3><i class="fas fa-info-circle"></i> Service Information</h3>
            <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
            <div class="info-section" style="margin-bottom: 0;">
                <div class="info-label">Fee</div>
                <div class="info-value">
                    <span class="fee-amount">
                        <?php echo $request['fee'] > 0 ? '₱' . number_format($request['fee'], 2) : 'Free'; ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($request['requirements'])): ?>
            <div class="requirements-section">
                <h4><i class="fas fa-clipboard-list"></i> Requirements</h4>
                <p><?php echo nl2br(htmlspecialchars($request['requirements'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($request['admin_remarks'])): ?>
        <div class="remarks-section">
            <div class="info-label" style="color: #667eea;">Admin Remarks</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($request['admin_remarks'])); ?></div>
            <?php if ($request['processed_date']): ?>
            <div class="info-label" style="margin-top: 0.5rem;">Processed on</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($request['processed_date'])); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="view_request_qr.php?id=<?php echo $request['id']; ?>" class="btn-qr">
                <i class="fas fa-qrcode"></i> Show QR Code
            </a>
            <a href="my_requests.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>