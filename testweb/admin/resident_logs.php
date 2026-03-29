<?php
// admin/resident_logs.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get admin role from database
$role_check = $db->prepare("SELECT admin_type FROM admins WHERE id = ?");
$role_check->execute([$_SESSION['admin_id']]);
$admin_data = $role_check->fetch(PDO::FETCH_ASSOC);
$user_role = $admin_data['admin_type'] ?? '';

// Only Main Admin can access logs
if ($user_role != 'main_admin') {
    $_SESSION['error'] = "You don't have permission to access this page";
    header('Location: dashboard.php');
    exit();
}

// Set the current page variable for the header
$current_page = 'resident_logs';

// Get filter parameters
$resident_filter = isset($_GET['resident']) ? $_GET['resident'] : '';
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';

// Build query for resident logs
$query = "SELECT rl.*, u.first_name, u.last_name, u.username 
          FROM resident_logs rl
          LEFT JOIN users u ON rl.user_id = u.id
          WHERE 1=1";

$params = array();

if (!empty($resident_filter)) {
    $query .= " AND rl.user_id = :resident_id";
    $params[':resident_id'] = $resident_filter;
}

if (!empty($action_filter)) {
    $query .= " AND rl.action LIKE :action";
    $params[':action'] = "%$action_filter%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(rl.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(rl.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY rl.created_at DESC";

$logs = [];
try {
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

// Get distinct actions for filter dropdown
$actions = [];
try {
    $actions_query = "SELECT DISTINCT action FROM resident_logs ORDER BY action";
    $actions_stmt = $db->query($actions_query);
    $actions = $actions_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $actions = [];
}

// Get residents for filter dropdown
$residents = [];
try {
    $residents_query = "SELECT id, username, first_name, last_name FROM users WHERE user_type = 'resident' OR user_type IS NULL ORDER BY first_name";
    $residents_stmt = $db->query($residents_query);
    $residents = $residents_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $residents = [];
}

// Export function
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="resident_logs_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Date & Time', 'Resident', 'Username', 'Action', 'Description', 'IP Address']);
    
    foreach ($logs as $log) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($log['created_at'])),
            ($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''),
            $log['username'] ?? '',
            $log['action'] ?? '',
            $log['description'] ?? '',
            $log['ip_address'] ?? ''
        ]);
    }
    fclose($output);
    exit();
}

// Include header
include dirname(__FILE__) . '/includes/admin_header.php';
?>

<style>
/* Admin Management Styles - Consistent with Admin Accounts */
.admin-management {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-header p {
    color: #64748b;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

/* Tabs Styles */
.tabs-container {
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
}

.tabs {
    display: flex;
    gap: 0.5rem;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    background: none;
    border: none;
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    margin-bottom: -2px;
    text-decoration: none;
}

.tab-btn:hover {
    color: #2563eb;
}

.tab-btn.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideIn 0.3s ease;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
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

/* Filter Section */
.filter-section {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.filter-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 180px;
}

.filter-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    transition: all 0.2s;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.1);
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-filter, .btn-clear, .btn-export {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-filter {
    background: #2563eb;
    color: white;
}

.btn-filter:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.btn-clear {
    background: #e2e8f0;
    color: #475569;
}

.btn-clear:hover {
    background: #cbd5e1;
    transform: translateY(-1px);
}

.btn-export {
    background: #059669;
    color: white;
}

.btn-export:hover {
    background: #047857;
    transform: translateY(-1px);
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.table-header {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.table-header h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.table-header .badge {
    background: #2563eb;
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

.data-table th {
    text-align: left;
    padding: 1rem;
    background: #fafcff;
    color: #475569;
    font-weight: 600;
    font-size: 0.75rem;
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Fixed column widths for consistent alignment */
.data-table th:nth-child(1), .data-table td:nth-child(1) { width: 15%; } /* Date & Time */
.data-table th:nth-child(2), .data-table td:nth-child(2) { width: 18%; } /* Resident */
.data-table th:nth-child(3), .data-table td:nth-child(3) { width: 12%; } /* Username */
.data-table th:nth-child(4), .data-table td:nth-child(4) { width: 12%; } /* Action */
.data-table th:nth-child(5), .data-table td:nth-child(5) { width: 35%; } /* Description */
.data-table th:nth-child(6), .data-table td:nth-child(6) { width: 8%; } /* IP Address */

.data-table tbody tr:hover {
    background: #f8fafc;
}

/* Badge Styles */
.action-badge {
    background: #f1f5f9;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.7rem;
    font-family: monospace;
    font-weight: 600;
    color: #475569;
    display: inline-block;
}

.ip-address {
    font-family: monospace;
    font-size: 0.75rem;
    color: #2563eb;
    background: #eff6ff;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.resident-name {
    font-weight: 600;
    color: #1e293b;
}

.resident-username {
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 0.2rem;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
}

.empty-state .material-icons {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    color: #cbd5e1;
}

.empty-state p {
    margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .tabs {
        flex-direction: column;
        border-bottom: none;
    }
    
    .tab-btn {
        border-bottom: 1px solid #e2e8f0;
        justify-content: center;
    }
    
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .btn-filter, .btn-clear, .btn-export {
        width: 100%;
        justify-content: center;
    }
    
    .table-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header h1 {
        font-size: 1.4rem;
    }
    
    .data-table {
        min-width: 750px;
    }
}

@media (max-width: 480px) {
    .data-table th,
    .data-table td {
        padding: 0.75rem;
    }
    
    .data-table {
        min-width: 650px;
    }
}
</style>

<div class="admin-management">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <span class="material-icons">history</span>
            Resident History Logs
        </h1>
        <p>Track all resident activities in the system</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <a href="logs.php" class="tab-btn">
                <span class="material-icons">admin_panel_settings</span>
                Admin Logs
            </a>
            <a href="resident_logs.php" class="tab-btn active">
                <span class="material-icons">people</span>
                Resident Logs
            </a>
        </div>
    </div>

    <!-- Error message if any -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <span class="material-icons">error</span>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Filters Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>👤 RESIDENT</label>
                <select name="resident">
                    <option value="">All Residents</option>
                    <?php foreach ($residents as $resident): ?>
                    <option value="<?php echo $resident['id']; ?>" <?php echo $resident_filter == $resident['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>⚡ ACTION</label>
                <select name="action">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                    <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter == $action ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($action); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>📅 FROM DATE</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label>📅 TO DATE</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <span class="material-icons">search</span> Apply Filters
                </button>
                <a href="resident_logs.php" class="btn-clear">
                    <span class="material-icons">refresh</span> Reset
                </a>
                <a href="?export=csv&resident=<?php echo urlencode($resident_filter); ?>&action=<?php echo urlencode($action_filter); ?>&from=<?php echo urlencode($date_from); ?>&to=<?php echo urlencode($date_to); ?>" class="btn-export">
                    <span class="material-icons">download</span> Export CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="table-card">
        <div class="table-header">
            <h2>
                <span class="material-icons">history</span>
                Resident Activity Logs
            </h2>
            <span class="badge"><?php echo count($logs); ?> entries</span>
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Resident</th>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr class="empty-state">
                        <td colspan="6" class="empty-state">
                            <span class="material-icons">history</span>
                            <p>No resident logs found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('M j, Y g:i:s A', strtotime($log['created_at'])); ?></td>
                            <td>
                                <div class="resident-name"><?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?></div>
                                <div class="resident-username">@<?php echo htmlspecialchars($log['username'] ?? ''); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($log['username'] ?? ''); ?></td>
                            <td><span class="action-badge"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><span class="ip-address"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include dirname(__FILE__) . '/includes/admin_footer.php'; ?>