<?php
// admin/qr/scan_request.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Ensure tables have required columns
try {
    // Check for qr_token column
    $check_column = "SHOW COLUMNS FROM resident_requests LIKE 'qr_token'";
    $col_check = $db->query($check_column);
    if ($col_check->rowCount() == 0) {
        $add_column = "ALTER TABLE resident_requests ADD COLUMN qr_token VARCHAR(100) NULL";
        $db->exec($add_column);
    }
    
    // Check for document_data column
    $check_doc = "SHOW COLUMNS FROM resident_requests LIKE 'document_data'";
    $doc_check = $db->query($check_doc);
    if ($doc_check->rowCount() == 0) {
        $add_doc = "ALTER TABLE resident_requests ADD COLUMN document_data TEXT NULL";
        $db->exec($add_doc);
    }
    
    // Check for processed_by column
    $check_processed = "SHOW COLUMNS FROM resident_requests LIKE 'processed_by'";
    $processed_check = $db->query($check_processed);
    if ($processed_check->rowCount() == 0) {
        $add_processed = "ALTER TABLE resident_requests ADD COLUMN processed_by INT NULL";
        $db->exec($add_processed);
    }
    
    // Check for processed_date column
    $check_processed_date = "SHOW COLUMNS FROM resident_requests LIKE 'processed_date'";
    $date_check = $db->query($check_processed_date);
    if ($date_check->rowCount() == 0) {
        $add_date = "ALTER TABLE resident_requests ADD COLUMN processed_date DATETIME NULL";
        $db->exec($add_date);
    }
} catch (Exception $e) {
    // Columns might already exist
}

$scan_result = null;
$error = '';
$success = '';
$update_message = '';
$document_html = '';

// Handle manual entry or simulated scan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['scan_token'])) {
        $token = trim($_POST['token']);
        
        if (empty($token)) {
            $error = "Please enter a token or request ID";
        } else {
            // Try to find by QR token first - using actual column names from your database
            $query = "SELECT r.*, 
                             u.first_name, u.last_name, u.username, u.email, u.contact_number, u.address,
                             s.service_name, s.description as service_description, s.fee, s.requirements
                      FROM resident_requests r
                      JOIN users u ON r.user_id = u.id
                      JOIN services s ON r.service_id = s.id
                      WHERE r.qr_token = :token OR r.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':id', $token);
            $stmt->execute();
            $scan_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found by token or ID, try to find by request ID pattern
            if (!$scan_result && preg_match('/REQ-(\d+)/', $token, $matches)) {
                $request_id = intval($matches[1]);
                $query2 = "SELECT r.*, 
                                  u.first_name, u.last_name, u.username, u.email, u.contact_number, u.address,
                                  s.service_name, s.description as service_description, s.fee, s.requirements
                           FROM resident_requests r
                           JOIN users u ON r.user_id = u.id
                           JOIN services s ON r.service_id = s.id
                           WHERE r.id = :id";
                $stmt2 = $db->prepare($query2);
                $stmt2->bindParam(':id', $request_id);
                $stmt2->execute();
                $scan_result = $stmt2->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($scan_result) {
                // Load existing document data if any
                $document_data = json_decode($scan_result['document_data'] ?? '{}', true);
                $document_html = generateDocumentForm($scan_result, $document_data);
                
                // Log the scan
                try {
                    $log_query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                                  VALUES (:admin_id, 'SCAN_REQUEST_QR', :desc, :ip)";
                    $log_stmt = $db->prepare($log_query);
                    $desc = "Scanned QR for request #" . str_pad($scan_result['id'], 6, '0', STR_PAD_LEFT);
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $log_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                    $log_stmt->bindParam(':desc', $desc);
                    $log_stmt->bindParam(':ip', $ip);
                    $log_stmt->execute();
                } catch (Exception $e) {
                    // Log table might not exist
                }
                $success = "Request found! Please fill out the document form below.";
            } else {
                $error = "No request found with that token or ID";
            }
        }
    } elseif (isset($_POST['save_document'])) {
        // Handle document form submission
        $request_id = $_POST['request_id'];
        $status = $_POST['status'];
        $document_data = [];
        
        // Collect all form fields
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'doc_') === 0) {
                $field_name = substr($key, 4);
                $document_data[$field_name] = trim($value);
            }
        }
        
        $remarks = trim($_POST['remarks'] ?? '');
        $amount_paid = $_POST['amount_paid'] ?? 0;
        
        try {
            $update_query = "UPDATE resident_requests 
                            SET status = :status, 
                                admin_remarks = :remarks, 
                                amount_paid = :amount_paid,
                                document_data = :document_data,
                                processed_date = NOW(),
                                processed_by = :admin_id
                            WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':status', $status);
            $update_stmt->bindParam(':remarks', $remarks);
            $update_stmt->bindParam(':amount_paid', $amount_paid);
            $document_json = json_encode($document_data);
            $update_stmt->bindParam(':document_data', $document_json);
            $update_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
            $update_stmt->bindParam(':id', $request_id);
            
            if ($update_stmt->execute()) {
                $update_message = "Document saved and request updated successfully!";
                // Refresh the scan result
                $refresh_query = "SELECT r.*, u.first_name, u.last_name, u.username, u.email, u.contact_number, u.address,
                                         s.service_name, s.description as service_description, s.fee, s.requirements
                                  FROM resident_requests r
                                  JOIN users u ON r.user_id = u.id
                                  JOIN services s ON r.service_id = s.id
                                  WHERE r.id = :id";
                $refresh_stmt = $db->prepare($refresh_query);
                $refresh_stmt->bindParam(':id', $request_id);
                $refresh_stmt->execute();
                $scan_result = $refresh_stmt->fetch(PDO::FETCH_ASSOC);
                $document_data = json_decode($scan_result['document_data'] ?? '{}', true);
                $document_html = generateDocumentForm($scan_result, $document_data);
            } else {
                $error = "Failed to save document";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Function to generate document form based on service type
function generateDocumentForm($request, $existing_data = []) {
    $service_name = strtolower($request['service_name'] ?? '');
    $resident_name = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
    $address = $request['address'] ?? '';
    $contact = $request['contact_number'] ?? '';
    $purpose = $request['details'] ?? '';
    
    // Determine document type
    $document_type = '';
    if (strpos($service_name, 'clearance') !== false) {
        $document_type = 'Barangay Clearance';
    } elseif (strpos($service_name, 'certificate') !== false) {
        $document_type = 'Certificate';
    } elseif (strpos($service_name, 'indigency') !== false) {
        $document_type = 'Certificate of Indigency';
    } elseif (strpos($service_name, 'residency') !== false) {
        $document_type = 'Certificate of Residency';
    } elseif (strpos($service_name, 'business') !== false) {
        $document_type = 'Business Permit';
    } else {
        $document_type = 'Barangay Document';
    }
    
    $html = '
    <div class="document-form-container">
        <div class="document-header">
            <div class="barangay-logo">
                <i class="material-icons" style="font-size: 48px;">account_balance</i>
            </div>
            <div class="barangay-title">
                <h2>REPUBLIC OF THE PHILIPPINES</h2>
                <h3>BARANGAY ' . strtoupper($request['barangay_name'] ?? 'MANAGEMENT SYSTEM') . '</h3>
                <p>Office of the Barangay Captain</p>
            </div>
        </div>
        
        <div class="document-title">
            <h1>' . strtoupper($document_type) . '</h1>
            <p>Request ID: #' . str_pad($request['id'], 6, '0', STR_PAD_LEFT) . '</p>
        </div>
        
        <form method="POST" id="documentForm">
            <input type="hidden" name="request_id" value="' . $request['id'] . '">
            
            <div class="form-section">
                <h4><i class="material-icons">person</i> RESIDENT INFORMATION</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="doc_full_name" class="form-control" value="' . htmlspecialchars($existing_data['full_name'] ?? $resident_name) . '" required>
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="doc_age" class="form-control" value="' . htmlspecialchars($existing_data['age'] ?? '') . '">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="doc_gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" ' . (($existing_data['gender'] ?? '') == 'Male' ? 'selected' : '') . '>Male</option>
                            <option value="Female" ' . (($existing_data['gender'] ?? '') == 'Female' ? 'selected' : '') . '>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="doc_contact" class="form-control" value="' . htmlspecialchars($existing_data['contact'] ?? $contact) . '">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Complete Address</label>
                    <textarea name="doc_address" class="form-control" rows="2">' . htmlspecialchars($existing_data['address'] ?? $address) . '</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Civil Status</label>
                        <select name="doc_civil_status" class="form-control">
                            <option value="">Select Status</option>
                            <option value="Single" ' . (($existing_data['civil_status'] ?? '') == 'Single' ? 'selected' : '') . '>Single</option>
                            <option value="Married" ' . (($existing_data['civil_status'] ?? '') == 'Married' ? 'selected' : '') . '>Married</option>
                            <option value="Widowed" ' . (($existing_data['civil_status'] ?? '') == 'Widowed' ? 'selected' : '') . '>Widowed</option>
                            <option value="Separated" ' . (($existing_data['civil_status'] ?? '') == 'Separated' ? 'selected' : '') . '>Separated</option>
                        </select>
                    </div>
                </div>
            </div>';
    
    // Additional fields based on service type
    if (strpos($service_name, 'clearance') !== false) {
        $html .= '
        <div class="form-section">
            <h4><i class="material-icons">gavel</i> CLEARANCE DETAILS</h4>
            <div class="form-group">
                <label>Purpose of Clearance</label>
                <textarea name="doc_purpose" class="form-control" rows="2" required>' . htmlspecialchars($existing_data['purpose'] ?? $purpose) . '</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Years in Barangay</label>
                    <input type="text" name="doc_years_residing" class="form-control" value="' . htmlspecialchars($existing_data['years_residing'] ?? '') . '">
                </div>
                <div class="form-group">
                    <label>Business/Employment</label>
                    <input type="text" name="doc_employment" class="form-control" value="' . htmlspecialchars($existing_data['employment'] ?? '') . '">
                </div>
            </div>
        </div>';
    } elseif (strpos($service_name, 'indigency') !== false) {
        $html .= '
        <div class="form-section">
            <h4><i class="material-icons">help</i> INDIGENCY DETAILS</h4>
            <div class="form-group">
                <label>Purpose of Certification</label>
                <textarea name="doc_purpose" class="form-control" rows="2" required>' . htmlspecialchars($existing_data['purpose'] ?? $purpose) . '</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Monthly Income</label>
                    <input type="text" name="doc_monthly_income" class="form-control" value="' . htmlspecialchars($existing_data['monthly_income'] ?? '') . '">
                </div>
                <div class="form-group">
                    <label>Source of Income</label>
                    <input type="text" name="doc_income_source" class="form-control" value="' . htmlspecialchars($existing_data['income_source'] ?? '') . '">
                </div>
            </div>
            <div class="form-group">
                <label>Number of Dependents</label>
                <input type="number" name="doc_dependents" class="form-control" value="' . htmlspecialchars($existing_data['dependents'] ?? '') . '">
            </div>
        </div>';
    } elseif (strpos($service_name, 'business') !== false) {
        $html .= '
        <div class="form-section">
            <h4><i class="material-icons">store</i> BUSINESS DETAILS</h4>
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" name="doc_business_name" class="form-control" value="' . htmlspecialchars($existing_data['business_name'] ?? '') . '" required>
            </div>
            <div class="form-group">
                <label>Business Address</label>
                <textarea name="doc_business_address" class="form-control" rows="2" required>' . htmlspecialchars($existing_data['business_address'] ?? '') . '</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Business Type</label>
                    <input type="text" name="doc_business_type" class="form-control" value="' . htmlspecialchars($existing_data['business_type'] ?? '') . '">
                </div>
                <div class="form-group">
                    <label>Years in Operation</label>
                    <input type="text" name="doc_years_operation" class="form-control" value="' . htmlspecialchars($existing_data['years_operation'] ?? '') . '">
                </div>
            </div>
        </div>';
    } else {
        $html .= '
        <div class="form-section">
            <h4><i class="material-icons">description</i> CERTIFICATE DETAILS</h4>
            <div class="form-group">
                <label>Purpose/Reason</label>
                <textarea name="doc_purpose" class="form-control" rows="3" required>' . htmlspecialchars($existing_data['purpose'] ?? $purpose) . '</textarea>
            </div>
        </div>';
    }
    
    $html .= '
        <div class="form-section">
            <h4><i class="material-icons">receipt</i> OFFICIAL USE</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>OR Number</label>
                    <input type="text" name="doc_or_number" class="form-control" value="' . htmlspecialchars($existing_data['or_number'] ?? '') . '">
                </div>
                <div class="form-group">
                    <label>Amount Paid</label>
                    <input type="number" name="amount_paid" class="form-control" step="0.01" value="' . ($existing_data['amount_paid'] ?? $request['fee'] ?? 0) . '">
                </div>
            </div>
            <div class="form-group">
                <label>Admin Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">' . htmlspecialchars($request['admin_remarks'] ?? '') . '</textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h4><i class="material-icons">assignment_turned_in</i> REQUEST STATUS</h4>
            <select name="status" class="form-control" required>
                <option value="pending" ' . (($request['status'] ?? '') == 'pending' ? 'selected' : '') . '>Pending</option>
                <option value="approved" ' . (($request['status'] ?? '') == 'approved' ? 'selected' : '') . '>Approved</option>
                <option value="in-progress" ' . (($request['status'] ?? '') == 'in-progress' ? 'selected' : '') . '>In Progress</option>
                <option value="completed" ' . (($request['status'] ?? '') == 'completed' ? 'selected' : '') . '>Completed</option>
                <option value="rejected" ' . (($request['status'] ?? '') == 'rejected' ? 'selected' : '') . '>Rejected</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="save_document" class="btn-save">
                <i class="material-icons">save</i> Save Document & Update Request
            </button>
            <button type="button" onclick="window.print()" class="btn-print">
                <i class="material-icons">print</i> Print Document
            </button>
        </div>
        </form>
    </div>';
    
    return $html;
}

include '../includes/admin_header.php';
?>

<style>
/* Document Form Styles */
.document-form-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.document-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.barangay-logo {
    margin-bottom: 1rem;
}

.barangay-logo i {
    font-size: 48px;
    color: #ffd700;
}

.barangay-title h2 {
    font-size: 1rem;
    font-weight: 500;
    letter-spacing: 1px;
    margin: 0;
}

.barangay-title h3 {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0.5rem 0;
}

.barangay-title p {
    font-size: 0.85rem;
    opacity: 0.9;
    margin: 0;
}

.document-title {
    text-align: center;
    padding: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
    background: #f8fafc;
}

.document-title h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e3c72;
    margin: 0;
    text-transform: uppercase;
}

.document-title p {
    color: #64748b;
    margin: 0.5rem 0 0;
    font-size: 0.85rem;
}

.form-section {
    padding: 1.5rem;
    border-bottom: 1px solid #eef2f6;
}

.form-section h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #667eea;
}

.form-section h4 .material-icons {
    color: #667eea;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #475569;
    font-size: 0.85rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

select.form-control {
    cursor: pointer;
    background: white;
}

textarea.form-control {
    resize: vertical;
}

.form-actions {
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    background: #f8fafc;
}

.btn-save, .btn-print {
    flex: 1;
    padding: 0.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-print {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-print:hover {
    background: #cbd5e1;
    transform: translateY(-2px);
}

/* QR Scanner Section */
.scan-request-module {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

.module-header {
    margin-bottom: 1.5rem;
}

.module-header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.module-header p {
    color: #64748b;
}

.scanner-section {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.scanner-placeholder {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: white;
    padding: 3rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.scanner-placeholder .material-icons {
    font-size: 4rem;
    color: #8b5cf6;
    margin-bottom: 1rem;
}

.manual-form {
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    gap: 1rem;
}

.manual-form input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
}

.manual-form button {
    padding: 0.75rem 1.5rem;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
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

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border-left: 4px solid #3b82f6;
}

@media print {
    .scanner-section, .alert, .form-actions, .module-header {
        display: none;
    }
    .document-form-container {
        box-shadow: none;
        padding: 0;
    }
    .form-control {
        border: none;
        padding: 0;
    }
    .form-group {
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .manual-form {
        flex-direction: column;
    }
    .form-actions {
        flex-direction: column;
    }
}
</style>

<div class="scan-request-module">
    <div class="module-header">
        <h1><i class="material-icons" style="vertical-align: middle;">qr_code_scanner</i> Scan & Process Request</h1>
        <p>Scan QR code to load resident request and fill out barangay document form</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <span class="material-icons">error</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <span class="material-icons">check_circle</span>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($update_message): ?>
        <div class="alert alert-info">
            <span class="material-icons">info</span>
            <span><?php echo htmlspecialchars($update_message); ?></span>
        </div>
    <?php endif; ?>
    
    <div class="scanner-section">
        <div class="scanner-placeholder">
            <span class="material-icons">qr_code_scanner</span>
            <h3>QR Code Scanner</h3>
            <p>Point your camera at the QR code to load the resident's request</p>
        </div>
        
        <button onclick="simulateScan()" class="btn-simulate" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #8b5cf6; color: white; border: none; border-radius: 8px; cursor: pointer; margin-bottom: 1.5rem;">
            <span class="material-icons">qr_code_scanner</span>
            Simulate Scan
        </button>
        
        <form method="POST" class="manual-form">
            <input type="text" name="token" placeholder="Enter QR token, request ID, or REQ-XXXXXX" required>
            <button type="submit" name="scan_token">
                <span class="material-icons">search</span>
                Load Request
            </button>
        </form>
        
        <div class="scanner-note" style="margin-top: 1rem; padding: 0.75rem; background: #f1f5f9; border-radius: 8px;">
            <span class="material-icons">info</span>
            <span>Scan the QR code from the resident's receipt to load their request and fill out the barangay document form</span>
        </div>
    </div>
    
    <?php if ($scan_result): ?>
        <?php echo $document_html; ?>
    <?php else: ?>
    <div class="document-form-container" style="text-align: center; padding: 3rem;">
        <span class="material-icons" style="font-size: 4rem; color: #cbd5e1;">qr_code</span>
        <h3 style="margin-top: 1rem; color: #1e293b;">No Request Loaded</h3>
        <p style="color: #64748b;">Scan a QR code or enter a token to load the resident's request and fill out the barangay document.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function simulateScan() {
    var token = prompt("Enter QR token or request ID:\n\nExamples:\n- REQ-000001\n- 1 (request ID)\n- QR token from request", "REQ-000001");
    if (token && token.trim() !== "") {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'token';
        input.value = token.trim();
        
        var submitInput = document.createElement('input');
        submitInput.type = 'hidden';
        submitInput.name = 'scan_token';
        submitInput.value = '1';
        
        form.appendChild(input);
        form.appendChild(submitInput);
        document.body.appendChild(form);
        form.submit();
    }
}

setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            if (alert.parentNode) alert.remove();
        }, 500);
    });
}, 5000);
</script>

<?php include '../includes/admin_footer.php'; ?>