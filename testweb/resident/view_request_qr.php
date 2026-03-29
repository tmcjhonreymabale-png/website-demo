<?php
// resident/view_request_qr.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch user details - using correct column names
$user_query = "SELECT * FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Determine user's full name based on available columns
$user_full_name = 'N/A';
if (!empty($user['name'])) {
    $user_full_name = $user['name'];
} elseif (!empty($user['fullname'])) {
    $user_full_name = $user['fullname'];
} elseif (!empty($user['first_name']) && !empty($user['last_name'])) {
    $user_full_name = $user['first_name'] . ' ' . $user['last_name'];
} elseif (!empty($user['firstname']) && !empty($user['lastname'])) {
    $user_full_name = $user['firstname'] . ' ' . $user['lastname'];
} elseif (!empty($user['username'])) {
    $user_full_name = $user['username'];
}

// Get user contact and email
$user_contact = $user['contact_number'] ?? ($user['contact'] ?? ($user['phone'] ?? 'N/A'));
$user_email = $user['email'] ?? 'N/A';

// Fetch request and ensure it belongs to the logged-in user
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

// If no QR token yet, generate one (for backward compatibility)
if (empty($request['qr_token'])) {
    $token = 'REQ-' . str_pad($request['id'], 6, '0', STR_PAD_LEFT) . '-' . bin2hex(random_bytes(4));
    $update = "UPDATE resident_requests SET qr_token = :token WHERE id = :id";
    $update_stmt = $db->prepare($update);
    $update_stmt->bindParam(':token', $token);
    $update_stmt->bindParam(':id', $request['id']);
    $update_stmt->execute();
    $request['qr_token'] = $token;
}

// Format appointment date and time
$appointment_info = '';
if (!empty($request['preferred_date'])) {
    $appointment_info = date('F j, Y', strtotime($request['preferred_date']));
    if (!empty($request['preferred_time'])) {
        $appointment_info .= ' at ' . htmlspecialchars($request['preferred_time']);
    }
} elseif (!empty($request['preferred_day']) && !empty($request['preferred_time'])) {
    $appointment_info = htmlspecialchars($request['preferred_day']) . ' at ' . htmlspecialchars($request['preferred_time']);
} else {
    $appointment_info = 'To be scheduled';
}

// Format fee display
$fee_display = $request['fee'] > 0 ? '₱' . number_format($request['fee'], 2) : 'Free';

$page_title = "Request QR Code | Barangay System";
include '../includes/header.php';
?>

<style>
    .qr-container {
        max-width: 500px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        text-align: center;
        border: 1px solid #eef2f6;
    }
    
    .qr-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .qr-subtitle {
        color: #64748b;
        margin-bottom: 2rem;
        font-size: 0.85rem;
    }
    
    .qr-code {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 2rem 0;
        padding: 1.5rem;
        background: white;
        border: 2px solid #eef2f6;
        border-radius: 20px;
        background: #fafcff;
        min-height: 260px;
    }
    
    .qr-code canvas,
    .qr-code img {
        max-width: 100%;
        height: auto;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
    }
    
    .qr-details {
        background: #f8fafc;
        padding: 1.2rem;
        border-radius: 16px;
        margin: 1.5rem 0;
        text-align: left;
        border: 1px solid #eef2f6;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eef2f6;
    }
    
    .detail-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .detail-label {
        font-weight: 600;
        color: #475569;
        width: 110px;
        font-size: 0.8rem;
    }
    
    .detail-value {
        color: #1e293b;
        flex: 1;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .qr-token {
        background: #f1f5f9;
        padding: 0.75rem;
        border-radius: 12px;
        font-family: monospace;
        font-size: 0.75rem;
        word-break: break-all;
        margin: 1rem 0;
        color: #475569;
        border: 1px solid #eef2f6;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        font-family: inherit;
    }
    
    .btn-back {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-back:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .btn-download {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        gap: 0.5rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
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
    
    /* Receipt Styling for Download */
    .receipt-download-card {
        width: 450px;
        background: white;
        border-radius: 20px;
        padding: 1.8rem;
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        border: 1px solid #e2e8f0;
        position: fixed;
        left: -9999px;
        top: 0;
        z-index: -1;
    }
    
    .receipt-header {
        text-align: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px dashed #e2e8f0;
    }
    
    .receipt-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.5px;
    }
    
    .receipt-subtitle {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
    
    .receipt-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.2rem 0.8rem;
        border-radius: 20px;
        font-size: 0.65rem;
        display: inline-block;
        margin-top: 0.5rem;
        font-weight: 500;
    }
    
    .receipt-section {
        margin: 1rem 0;
        padding: 0.8rem;
        background: #f8fafc;
        border-radius: 12px;
    }
    
    .section-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #667eea;
        margin-bottom: 0.6rem;
        letter-spacing: 0.5px;
    }
    
    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.75rem;
        padding: 0.2rem 0;
    }
    
    .receipt-label {
        font-weight: 600;
        color: #475569;
    }
    
    .receipt-value {
        color: #1e293b;
        text-align: right;
        font-weight: 500;
    }
    
    .qr-code-receipt {
        text-align: center;
        margin: 1rem 0;
        padding: 0.8rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .qr-code-receipt canvas,
    .qr-code-receipt img {
        width: 140px;
        height: 140px;
        display: block;
        margin: 0 auto;
    }
    
    .token-section-receipt {
        background: #fef3c7;
        border-radius: 10px;
        padding: 0.6rem;
        margin: 0.8rem 0;
        text-align: center;
        font-family: monospace;
        font-size: 0.7rem;
        word-break: break-all;
        color: #92400e;
        border-left: 3px solid #f59e0b;
    }
    
    .receipt-footer {
        text-align: center;
        margin-top: 1rem;
        padding-top: 0.8rem;
        border-top: 1px dashed #e2e8f0;
        font-size: 0.6rem;
        color: #94a3b8;
    }
    
    .requirements-note {
        background: #fef9e6;
        border-radius: 8px;
        padding: 0.6rem;
        margin-top: 0.6rem;
        font-size: 0.65rem;
        color: #92400e;
        border-left: 2px solid #f59e0b;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .qr-container {
            margin: 1rem;
            padding: 1.5rem;
        }
        
        .qr-title {
            font-size: 1.3rem;
        }
        
        .detail-label {
            width: 90px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="qr-container">
    <h2 class="qr-title">Your Service Request QR Code</h2>
    <p class="qr-subtitle">Show this QR code to the barangay staff to process your request</p>
    
    <!-- QR Code -->
    <div id="qrcode" class="qr-code">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="qr-details">
        <div class="detail-row">
            <span class="detail-label">Service:</span>
            <span class="detail-value"><?php echo htmlspecialchars($request['service_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Appointment:</span>
            <span class="detail-value">
                <i class="fas fa-calendar-alt"></i> <?php echo $appointment_info; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
                <span class="status-badge status-<?php echo $request['status']; ?>">
                    <i class="fas fa-<?php 
                        echo $request['status'] == 'pending' ? 'clock' : 
                            ($request['status'] == 'approved' ? 'check-circle' : 
                            ($request['status'] == 'rejected' ? 'times-circle' : 'check-double')); 
                    ?>"></i>
                    <?php echo ucfirst($request['status']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Submitted:</span>
            <span class="detail-value"><?php echo date('F j, Y', strtotime($request['request_date'])); ?></span>
        </div>
    </div>
    
    <div class="qr-token">
        <i class="fas fa-key"></i> Token: <?php echo $request['qr_token']; ?>
    </div>
    
    <div class="action-buttons no-print">
        <button id="downloadQRBtn" class="btn btn-download">
            <i class="fas fa-download"></i> Download Receipt & QR
        </button>
        <a href="my_requests.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to My Requests
        </a>
    </div>
</div>

<!-- Receipt Download Card -->
<div id="receiptDownloadCard" class="receipt-download-card">
    <div class="receipt-header">
        <div class="receipt-title">BARANGAY SERVICE REQUEST</div>
        <div class="receipt-subtitle">Official Request Receipt</div>
        <div class="receipt-badge"><?php echo strtoupper($request['status']); ?></div>
    </div>
    
    <div class="receipt-section">
        <div class="section-title">📋 REQUEST INFORMATION</div>
        <div class="receipt-row">
            <span class="receipt-label">Request ID:</span>
            <span class="receipt-value">#<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Service Type:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($request['service_name']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Type:</span>
            <span class="receipt-value"><?php echo ucfirst($request['request_type']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Date Submitted:</span>
            <span class="receipt-value"><?php echo date('F j, Y g:i A', strtotime($request['request_date'])); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Appointment:</span>
            <span class="receipt-value"><?php echo $appointment_info; ?></span>
        </div>
    </div>
    
    <div class="receipt-section">
        <div class="section-title">👤 RESIDENT INFORMATION</div>
        <div class="receipt-row">
            <span class="receipt-label">Full Name:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($user_full_name); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Contact Number:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($user_contact); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Email:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($user_email); ?></span>
        </div>
    </div>
    
    <div class="receipt-section">
        <div class="section-title">💰 PAYMENT DETAILS</div>
        <div class="receipt-row">
            <span class="receipt-label">Processing Fee:</span>
            <span class="receipt-value"><?php echo $fee_display; ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Payment Status:</span>
            <span class="receipt-value"><?php echo $request['fee'] > 0 ? 'To be paid at barangay hall' : 'No payment required'; ?></span>
        </div>
    </div>
    
    <div id="qrCodeReceipt" class="qr-code-receipt">
        <div style="font-size: 0.6rem; color: #64748b; margin-bottom: 0.3rem;">SCAN TO VERIFY</div>
        <div class="loading-spinner"></div>
    </div>
    
    <div class="token-section-receipt">
        <i class="fas fa-qrcode"></i> Verification Token: <?php echo $request['qr_token']; ?>
    </div>
    
    <?php if (!empty($request['requirements'])): ?>
    <div class="requirements-note">
        <strong><i class="fas fa-clipboard-list"></i> Requirements to Bring:</strong><br>
        <?php echo nl2br(htmlspecialchars($request['requirements'])); ?>
    </div>
    <?php endif; ?>
    
    <div class="receipt-footer">
        This is a system-generated receipt. Please present this QR code along with the required documents.<br>
        For inquiries, contact the Barangay Hall.
    </div>
</div>

<!-- QR Code generation library using canvas-based approach -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    const qrcodeToken = <?php echo json_encode($request['qr_token']); ?>;
    let mainQRImage = null;
    
    // Function to generate QR code as canvas
    function generateQRCodeAsCanvas(text, size, cellSize = 4) {
        return new Promise((resolve, reject) => {
            try {
                // Create QR code instance
                const qr = qrcode(0, 'M');
                qr.addData(text);
                qr.make();
                
                // Get module count
                const moduleCount = qr.getModuleCount();
                
                // Calculate canvas dimensions
                const canvasSize = moduleCount * cellSize;
                const canvas = document.createElement('canvas');
                canvas.width = canvasSize;
                canvas.height = canvasSize;
                const ctx = canvas.getContext('2d');
                
                // Draw QR code
                for (let row = 0; row < moduleCount; row++) {
                    for (let col = 0; col < moduleCount; col++) {
                        const isDark = qr.isDark(row, col);
                        ctx.fillStyle = isDark ? '#1e293b' : '#ffffff';
                        ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                    }
                }
                
                // Resize to desired size
                const resizedCanvas = document.createElement('canvas');
                resizedCanvas.width = size;
                resizedCanvas.height = size;
                const resizedCtx = resizedCanvas.getContext('2d');
                resizedCtx.drawImage(canvas, 0, 0, canvasSize, canvasSize, 0, 0, size, size);
                
                resolve(resizedCanvas);
            } catch (error) {
                reject(error);
            }
        });
    }
    
    // Generate main QR code on page load
    document.addEventListener('DOMContentLoaded', async function() {
        const qrcodeDiv = document.getElementById("qrcode");
        
        try {
            qrcodeDiv.innerHTML = '';
            const canvas = await generateQRCodeAsCanvas(qrcodeToken, 220, 5);
            qrcodeDiv.appendChild(canvas);
            mainQRImage = canvas;
        } catch (error) {
            console.error('Error generating QR code:', error);
            qrcodeDiv.innerHTML = '<div style="color: red; padding: 1rem;">Error generating QR code. Please refresh the page.</div>';
        }
    });
    
    // Download Receipt with QR Code
    document.getElementById('downloadQRBtn').addEventListener('click', async function() {
        const receiptCard = document.getElementById('receiptDownloadCard');
        const qrReceiptContainer = document.getElementById('qrCodeReceipt');
        const downloadBtn = this;
        
        // Show loading state
        const originalText = downloadBtn.innerHTML;
        downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        downloadBtn.disabled = true;
        
        try {
            // Clear previous QR code from receipt
            qrReceiptContainer.innerHTML = '<div style="font-size: 0.6rem; color: #64748b; margin-bottom: 0.3rem;">SCAN TO VERIFY</div>';
            
            // Generate QR code for receipt
            const receiptQRCanvas = await generateQRCodeAsCanvas(qrcodeToken, 140, 4);
            qrReceiptContainer.appendChild(receiptQRCanvas);
            
            // Wait for DOM to update
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Capture the receipt card as an image
            const canvas = await html2canvas(receiptCard, {
                scale: 3,
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: false,
                allowTaint: false,
                windowWidth: receiptCard.scrollWidth,
                windowHeight: receiptCard.scrollHeight
            });
            
            // Create download link
            const link = document.createElement('a');
            link.download = 'barangay_receipt_<?php echo $request['qr_token']; ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            
        } catch (error) {
            console.error('Error generating receipt image:', error);
            alert('Unable to download receipt. Please try again.');
        } finally {
            // Reset button
            downloadBtn.innerHTML = originalText;
            downloadBtn.disabled = false;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>