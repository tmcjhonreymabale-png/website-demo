<?php
// resident/my_requests.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch user's requests
$query = "SELECT r.*, s.service_name 
          FROM resident_requests r
          JOIN services s ON r.service_id = s.id
          WHERE r.user_id = :user_id
          ORDER BY r.request_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "My Requests | Barangay System";
include '../includes/header.php';
?>

<style>
    /* Make the entire page flex column to push footer down */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
        padding-top: 80px; /* Account for fixed header */
    }
    
    .main-content {
        flex: 1;
    }
    
    .requests-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    
    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .page-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .btn-request {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-request:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    /* Requests Table */
    .requests-table-wrapper {
        background: white;
        border-radius: 20px;
        border: 1px solid #eef2f6;
        overflow-x: auto;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    .requests-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 500px;
    }
    
    .requests-table th {
        text-align: left;
        padding: 1rem 1.2rem;
        background: #f8fafc;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .requests-table td {
        padding: 1rem 1.2rem;
        font-size: 0.85rem;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .requests-table tr:hover td {
        background: #fafcff;
    }
    
    .requests-table tr:last-child td {
        border-bottom: none;
    }
    
    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
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
    
    /* Request Type Badge */
    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 500;
    }
    
    .type-online {
        background: #e0f2fe;
        color: #0369a1;
    }
    
    .type-walk-in {
        background: #fef9c3;
        color: #854d0e;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-view, .btn-qr {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-view {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-view:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .btn-qr {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-qr:hover {
        background: #10b981;
        color: white;
        border-color: #10b981;
        transform: translateY(-2px);
    }
    
    .btn-view i, .btn-qr i {
        font-size: 0.8rem;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 20px;
        border: 1px solid #eef2f6;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .requests-container {
            padding: 0 1rem;
            margin: 1rem auto;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
        }
        
        .requests-table th,
        .requests-table td {
            padding: 0.75rem 1rem;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 0.4rem;
        }
        
        .btn-view, .btn-qr {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="main-content">
    <div class="requests-container">
        <div class="page-header">
            <h1>My Service Requests</h1>
            <a href="../index.php?page=services" class="btn-request">
                <i class="fas fa-plus"></i> New Request
            </a>
        </div>
        
        <?php if (count($requests) > 0): ?>
            <div class="requests-table-wrapper">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['service_name']); ?></td>
                            <td>
                                <span class="type-badge type-<?php echo $request['request_type']; ?>">
                                    <i class="fas fa-<?php echo $request['request_type'] == 'online' ? 'laptop' : 'walking'; ?>"></i>
                                    <?php echo ucfirst($request['request_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <i class="fas fa-<?php 
                                        echo $request['status'] == 'pending' ? 'clock' : 
                                            ($request['status'] == 'approved' ? 'check-circle' : 
                                            ($request['status'] == 'rejected' ? 'times-circle' : 'check-double')); 
                                    ?>"></i>
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <a href="view_request_qr.php?id=<?php echo $request['id']; ?>" class="btn-qr">
                                        <i class="fas fa-qrcode"></i> QR Code
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>You haven't made any service requests yet.</p>
                <a href="../index.php?page=services" class="btn-request" style="display: inline-flex;">
                    <i class="fas fa-concierge-bell"></i> Browse Available Services
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>