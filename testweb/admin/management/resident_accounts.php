<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Resident Accounts | Admin Panel</title>
    <!-- Google Material Icons & Fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f7f9fc;
            color: #0f172a;
        }

        /* main container */
        .resident-management {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 1.8rem;
        }

        /* Back button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 1.8rem;
            padding: 0.4rem 0;
            transition: all 0.2s ease;
            background: transparent;
            border-radius: 40px;
        }
        .back-button:hover {
            color: #1e40af;
            transform: translateX(-4px);
        }
        .back-button .material-icons {
            font-size: 1.2rem;
        }

        /* Page header */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 0 0.25rem 0;
        }
        .page-header h1 .material-icons {
            font-size: 2rem;
            color: #3b82f6;
        }
        .page-header p {
            color: #475569;
            font-size: 0.9rem;
            margin-top: 0.35rem;
        }

        /* Alert styles */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: 16px;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .alert-success {
            background: #e6f7e6;
            border-left: 5px solid #22c55e;
            color: #14532d;
        }
        .alert-danger {
            background: #fee9e9;
            border-left: 5px solid #ef4444;
            color: #991b1b;
        }
        .alert .material-icons {
            font-size: 1.3rem;
        }

        /* Filter section */
        .filter-section {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e9eef3;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .filter-form {
            display: flex;
            gap: 1.2rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 190px;
        }
        .filter-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.85rem;
            background: #ffffff;
            transition: 0.2s;
            font-family: 'Inter', monospace;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .filter-actions {
            display: flex;
            gap: 0.8rem;
        }
        .btn-filter, .btn-reset {
            padding: 0.6rem 1.4rem;
            border: none;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn-filter {
            background: #3b82f6;
            color: white;
        }
        .btn-filter:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .btn-reset {
            background: #f1f5f9;
            color: #1e293b;
            text-decoration: none;
        }
        .btn-reset:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Table card - FIXED ALIGNMENT for Registered & Actions */
        .table-card {
            background: white;
            border-radius: 28px;
            border: 1px solid #edf2f7;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            margin-bottom: 2rem;
        }
        .table-header {
            padding: 1.2rem 1.8rem;
            background: #ffffff;
            border-bottom: 1px solid #ecf3fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .table-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .badge {
            background: #eef2ff;
            color: #1e40af;
            padding: 0.3rem 0.9rem;
            border-radius: 60px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .table-responsive {
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
            table-layout: fixed;
        }
        /* Column width distribution for perfect alignment */
        .data-table th:nth-child(1), .data-table td:nth-child(1) { width: 6%; }  /* ID */
        .data-table th:nth-child(2), .data-table td:nth-child(2) { width: 18%; } /* Name */
        .data-table th:nth-child(3), .data-table td:nth-child(3) { width: 14%; } /* Username */
        .data-table th:nth-child(4), .data-table td:nth-child(4) { width: 22%; } /* Email */
        .data-table th:nth-child(5), .data-table td:nth-child(5) { width: 10%; } /* Status */
        .data-table th:nth-child(6), .data-table td:nth-child(6) { width: 12%; } /* Registered */
        .data-table th:nth-child(7), .data-table td:nth-child(7) { width: 18%; } /* Actions */
        
        .data-table th {
            padding: 1rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            color: #475569;
            background: #fafcff;
            border-bottom: 1px solid #e9edf2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid #f0f4f9;
            font-size: 0.85rem;
            color: #1e293b;
            vertical-align: middle;
        }
        
        /* Registered date - perfect alignment, no wrap */
        .data-table td:nth-child(6) {
            white-space: nowrap;
            font-feature-settings: 'tnum';
            font-variant-numeric: tabular-nums;
            font-weight: 500;
            color: #334155;
        }
        
        /* Actions cell - ensure buttons align horizontally and vertically centered */
        .data-table td:nth-child(7) {
            vertical-align: middle;
        }
        
        /* Action buttons container - FIXED: nowrap, centered, consistent spacing */
        .action-buttons {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            flex-wrap: nowrap;
        }
        
        /* Button base style - consistent size */
        .btn-icon {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: #64748b;
        }
        
        .btn-icon .material-icons {
            font-size: 1.2rem;
        }
        
        /* Edit button */
        .btn-icon.edit:hover {
            background: #eef2ff;
            color: #3b82f6;
        }
        
        /* Toggle/Status button styles - different colors for active/inactive state */
        .btn-icon.toggle-active {
            color: #64748b;
        }
        .btn-icon.toggle-active:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-icon.toggle-inactive {
            color: #64748b;
        }
        .btn-icon.toggle-inactive:hover {
            background: #dcfce7;
            color: #16a34a;
        }
        
        /* Delete button - enhanced styling with danger indication */
        .btn-icon.delete {
            color: #94a3b8;
        }
        .btn-icon.delete:hover {
            background: #fee2e2;
            color: #dc2626;
            transform: scale(1.02);
        }
        
        /* Status badge - enhanced with icon and better styling */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            border-radius: 60px;
            font-size: 0.7rem;
            font-weight: 600;
            width: fit-content;
            white-space: nowrap;
        }
        
        .status-badge.active {
            background: #dcfce7;
            color: #15803d;
        }
        
        .status-badge.inactive {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .status-badge .material-icons {
            font-size: 0.8rem;
        }
        
        /* Disabled account row styling - visual indication for inactive accounts */
        .data-table tr.inactive-row {
            background: #fef9f9;
            opacity: 0.85;
        }
        
        .data-table tr.inactive-row td {
            color: #6c6f78;
        }
        
        .data-table tr.inactive-row td:first-child,
        .data-table tr.inactive-row td:nth-child(2) {
            font-weight: 500;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #94a3b8;
        }
        .empty-state .material-icons {
            font-size: 2.8rem;
            color: #cbd5e1;
            margin-bottom: 0.6rem;
        }

        /* MODAL styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 32px;
            max-width: 620px;
            width: 90%;
            margin: 1.5rem auto;
            box-shadow: 0 25px 45px rgba(0,0,0,0.2);
            animation: modalSlideUp 0.25s ease;
            overflow: hidden;
        }
        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid #edf2f7;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #0f172a;
        }
        .modal-header .close {
            background: #f1f5f9;
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 60px;
            font-size: 1.4rem;
            cursor: pointer;
            color: #5b6e8c;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .modal-header .close:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .modal-body {
            padding: 1.6rem;
            background: #ffffff;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 0;
            flex-wrap: wrap;
        }
        .form-group {
            margin-bottom: 1.2rem;
            flex: 1;
        }
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .required {
            color: #e11d48;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            font-size: 0.85rem;
            background: #ffffff;
            transition: 0.2s;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        small {
            color: #5c6f8c;
            font-size: 0.7rem;
            margin-top: 0.3rem;
            display: inline-block;
        }
        .modal-footer {
            padding: 1rem 1.6rem;
            background: #fbfdff;
            border-top: 1px solid #eef3fc;
            display: flex;
            gap: 0.8rem;
            justify-content: flex-end;
        }
        .btn-save {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
        }
        .btn-save:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .btn-cancel-modal {
            background: #f1f5f9;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 40px;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-cancel-modal:hover {
            background: #e2e8f0;
        }

        /* Confirm modal */
        .confirm-modal {
            display: none;
            position: fixed;
            z-index: 10010;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .confirm-modal.show {
            display: flex;
        }
        .confirm-modal-content {
            background: white;
            border-radius: 36px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            overflow: hidden;
            box-shadow: 0 25px 40px rgba(0,0,0,0.25);
            animation: modalPop 0.2s ease;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        .confirm-modal-icon {
            padding-top: 1.8rem;
        }
        .confirm-modal-icon .material-icons {
            font-size: 3.4rem;
        }
        .confirm-modal-icon.warning .material-icons { color: #f97316; }
        .confirm-modal-icon.success .material-icons { color: #22c55e; }
        .confirm-modal-icon.danger .material-icons { color: #ef4444; }
        .confirm-modal-header h3 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0.5rem 0 0.25rem;
            color: #0f172a;
        }
        .confirm-modal-body {
            padding: 0.2rem 1.5rem 1rem;
        }
        .resident-name {
            background: #eef2ff;
            padding: 0.5rem 1rem;
            border-radius: 60px;
            display: inline-block;
            font-weight: 600;
            color: #1e3a8a;
            margin-top: 0.8rem;
            font-size: 0.85rem;
        }
        .warning-text {
            background: #ffefef;
            margin-top: 1rem;
            padding: 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            color: #b91c1c;
        }
        .confirm-modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            border-top: 1px solid #eff3f8;
            background: #fefefe;
        }
        .btn-cancel {
            background: #eef2ff;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 40px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-confirm {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-confirm.activate {
            background: #22c55e;
        }
        .btn-confirm.activate:hover {
            background: #16a34a;
        }
        .btn-confirm:hover {
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .resident-management {
                padding: 1rem;
            }
            .data-table {
                min-width: 850px;
            }
            .action-buttons {
                gap: 0.4rem;
            }
        }
        @media (max-width: 700px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                width: 100%;
            }
            .filter-actions {
                width: 100%;
            }
            .btn-filter, .btn-reset {
                flex: 1;
                justify-content: center;
            }
            .modal-content {
                width: 95%;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="resident-management">
    <!-- Back Button -->
    <a href="admin_settings.php" class="back-button">
        <span class="material-icons">arrow_back</span>
        Back to Settings
    </a>

    <!-- Header -->
    <div class="page-header">
        <h1>
            <span class="material-icons">people_alt</span>
            Resident Accounts
        </h1>
        <p>Manage resident accounts, edit profiles, reset passwords, and control account status</p>
    </div>

    <!-- Alerts from session (simulated for demo, but functional with PHP) -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><span class="material-icons">check_circle</span><span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><span class="material-icons">error</span><span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span></div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form" action="">
            <div class="filter-group">
                <label>🔍 SEARCH</label>
                <input type="text" name="search" placeholder="Name, username or email..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="filter-group">
                <label>📊 STATUS</label>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="1" <?php echo (($_GET['status'] ?? '') === '1') ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo (($_GET['status'] ?? '') === '0') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter"><span class="material-icons">search</span> Search</button>
                <a href="resident_accounts.php" class="btn-reset"><span class="material-icons">refresh</span> Reset</a>
            </div>
        </form>
    </div>

    <!-- Residents Table -->
    <div class="table-card">
        <div class="table-header">
            <h2><span class="material-icons">group</span> Resident Accounts</h2>
            <span class="badge" id="residentCount">3 residents</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="residentsTableBody">
                    <!-- Sample data matching your screenshot but with enhanced styling -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Resident Modal -->
<div id="editResidentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><span class="material-icons">edit_square</span> Edit Resident</h2>
            <button class="close" onclick="closeModal('editResidentModal')">&times;</button>
        </div>
        <form method="POST" id="editResidentForm">
            <input type="hidden" name="resident_id" id="edit_resident_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" id="edit_first_name" class="form-control" required></div>
                    <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" id="edit_last_name" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Username <span class="required">*</span></label><input type="text" name="username" id="edit_username" class="form-control" required></div>
                    <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                </div>
                <div class="form-group"><label>New Password</label><input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current password"><small>Minimum 6 characters - only fill to change password</small></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeModal('editResidentModal')">Cancel</button>
                <button type="submit" name="update_resident" class="btn-save">Update Resident</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <div class="confirm-modal-icon" id="modalIcon"><span class="material-icons">warning</span></div>
        <div class="confirm-modal-header"><h3 id="modalTitle">Deactivate Account</h3></div>
        <div class="confirm-modal-body">
            <p id="modalMessage">Are you sure you want to deactivate this resident account?</p>
            <div id="residentNameDisplay" class="resident-name"></div>
            <div id="warningText" class="warning-text"><span class="material-icons">info</span><span>They will not be able to log in.</span></div>
        </div>
        <div class="confirm-modal-footer">
            <button class="btn-cancel" onclick="closeConfirmModal()">Cancel</button>
            <button id="confirmBtn" class="btn-confirm">Deactivate</button>
        </div>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="deleteResidentForm" method="POST" style="display: none;"><input type="hidden" name="resident_id" id="delete_resident_id"><input type="hidden" name="delete_resident" value="1"></form>
<form id="toggleResidentForm" method="POST" style="display: none;"><input type="hidden" name="resident_id" id="toggle_resident_id"><input type="hidden" name="new_status" id="toggle_new_status"><input type="hidden" name="toggle_status" value="1"></form>

<script>
    function escapeHtml(str) { 
        if(!str) return ''; 
        return str.replace(/[&<>]/g, function(m){
            if(m==='&') return '&amp;'; 
            if(m==='<') return '&lt;'; 
            if(m==='>') return '&gt;'; 
            return m;
        }); 
    }
    
    // Resident data matching your screenshot with proper status flags
    const residentsData = [
        {id: 4, first_name: "Arlyn", last_name: "Mabale", username: "arlynmabale", email: "arlynmabale@gmail.com", is_active: 1, created_at: "2026-03-28"},
        {id: 3, first_name: "Mary Grace", last_name: "Bacares", username: "marygrace", email: "marygracebacares@gmail.com", is_active: 1, created_at: "2026-03-28"},
        {id: 1, first_name: "John Rey", last_name: "Mabale", username: "john rey", email: "johnyrelyfrenandez41@gmail.com", is_active: 1, created_at: "2026-03-13"}
    ];
    
    // Additional inactive demo to show disabled styling
    // You can uncomment to test inactive styling, but keeping original 3 active for now
    // residentsData.push({id: 5, first_name: "Test", last_name: "Disabled", username: "testdisabled", email: "disabled@example.com", is_active: 0, created_at: "2026-02-01"});
    
    function renderResidentsTable(residents) {
        const tbody = document.getElementById('residentsTableBody');
        if (!tbody) return;
        
        if (!residents || residents.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-state"><span class="material-icons">people_outline</span><p>No resident accounts found</p></td></tr>`;
            document.getElementById('residentCount').innerText = `0 residents`;
            return;
        }
        
        let html = '';
        residents.forEach(r => {
            const isActive = (r.is_active == 1);
            const statusClass = isActive ? 'active' : 'inactive';
            const statusText = isActive ? 'Active' : 'Inactive';
            const statusIcon = isActive ? 'check_circle' : 'cancel';
            
            // Format date - perfect alignment
            let formattedDate = 'N/A';
            if (r.created_at) {
                const dateObj = new Date(r.created_at);
                if (!isNaN(dateObj.getTime())) {
                    formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            }
            
            // Row styling for inactive accounts
            const rowClass = !isActive ? 'inactive-row' : '';
            
            // Determine toggle button style based on current status
            const toggleBtnClass = isActive ? 'toggle-active' : 'toggle-inactive';
            const toggleIcon = isActive ? 'block' : 'check_circle';
            const toggleTitle = isActive ? 'Deactivate Account' : 'Activate Account';
            const toggleAction = isActive ? 'deactivate' : 'activate';
            
            html += `<tr class="${rowClass}">
                        <td style="font-weight:600; color:#2563eb;">#${r.id}</td>
                        <td><strong>${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</strong></td>
                        <td>${escapeHtml(r.username)}</td>
                        <td>${escapeHtml(r.email)}</td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                <span class="material-icons">${statusIcon}</span>
                                ${statusText}
                            </span>
                        </td>
                        <td>${formattedDate}</td>
                        <td>
                            <div class="action-buttons">
                                <button onclick='editResident(${JSON.stringify(r).replace(/'/g, "&#39;")})' class="btn-icon edit" title="Edit Account">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button onclick="showConfirmModal(${r.id}, '${escapeHtml(r.first_name + ' ' + r.last_name)}', '${toggleAction}')" class="btn-icon ${toggleBtnClass}" title="${toggleTitle}">
                                    <span class="material-icons">${toggleIcon}</span>
                                </button>
                                <button onclick="deleteResident(${r.id})" class="btn-icon delete" title="Delete Account (Permanent)">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>`;
        });
        
        tbody.innerHTML = html;
        document.getElementById('residentCount').innerText = `${residents.length} residents`;
    }
    
    // Initialize table on page load
    window.addEventListener('DOMContentLoaded', () => {
        renderResidentsTable(residentsData);
    });
    
    // Global functions for actions
    let pendingToggleId = null;
    let pendingToggleStatus = null;
    
    window.editResident = function(resident) {
        document.getElementById('edit_resident_id').value = resident.id;
        document.getElementById('edit_first_name').value = resident.first_name;
        document.getElementById('edit_last_name').value = resident.last_name;
        document.getElementById('edit_username').value = resident.username;
        document.getElementById('edit_email').value = resident.email;
        document.getElementById('editResidentModal').style.display = 'flex';
        document.body.classList.add('modal-open');
    };
    
    window.deleteResident = function(id) {
        if (confirm('⚠️ WARNING: This will permanently delete this resident account and all associated data. This action cannot be undone!\n\nAre you sure you want to proceed?')) {
            document.getElementById('delete_resident_id').value = id;
            document.getElementById('deleteResidentForm').submit();
        }
    };
    
    window.showConfirmModal = function(id, name, action) {
        pendingToggleId = id;
        pendingToggleStatus = action;
        const modal = document.getElementById('confirmModal');
        const modalIconDiv = document.getElementById('modalIcon');
        const title = document.getElementById('modalTitle');
        const message = document.getElementById('modalMessage');
        const residentDisplay = document.getElementById('residentNameDisplay');
        const warningDiv = document.getElementById('warningText');
        const confirmBtn = document.getElementById('confirmBtn');
        
        if (action === 'deactivate') {
            modalIconDiv.className = 'confirm-modal-icon warning';
            modalIconDiv.innerHTML = '<span class="material-icons">warning</span>';
            title.innerText = 'Deactivate Account';
            message.innerText = 'Are you sure you want to deactivate this resident account?';
            residentDisplay.innerText = name;
            warningDiv.innerHTML = '<span class="material-icons">info</span><span>They will not be able to log in. The account will be disabled.</span>';
            confirmBtn.innerText = 'Deactivate';
            confirmBtn.className = 'btn-confirm';
        } else {
            modalIconDiv.className = 'confirm-modal-icon success';
            modalIconDiv.innerHTML = '<span class="material-icons">check_circle</span>';
            title.innerText = 'Activate Account';
            message.innerText = 'Are you sure you want to activate this resident account?';
            residentDisplay.innerText = name;
            warningDiv.innerHTML = '<span class="material-icons">check</span><span>They will be able to log in again.</span>';
            confirmBtn.innerText = 'Activate';
            confirmBtn.className = 'btn-confirm activate';
        }
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    };
    
    window.closeConfirmModal = function() {
        document.getElementById('confirmModal').classList.remove('show');
        document.body.classList.remove('modal-open');
        pendingToggleId = null;
        pendingToggleStatus = null;
    };
    
    window.confirmAction = function() {
        if (pendingToggleId && pendingToggleStatus) {
            const newStatus = pendingToggleStatus === 'activate' ? 1 : 0;
            document.getElementById('toggle_resident_id').value = pendingToggleId;
            document.getElementById('toggle_new_status').value = newStatus;
            document.getElementById('toggleResidentForm').submit();
        }
        closeConfirmModal();
    };
    
    window.closeModal = function(modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
    };
    
    document.getElementById('confirmBtn').onclick = confirmAction;
    
    window.onclick = function(event) {
        if (event.target.classList && event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
        if (event.target.classList && event.target.classList.contains('confirm-modal')) {
            closeConfirmModal();
        }
    };
</script>
</body>
</html>