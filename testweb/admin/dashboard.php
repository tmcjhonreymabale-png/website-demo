<?php
// admin/dashboard.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Include database
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Initialize stats array with defaults
$stats = [
    'total_residents' => 0,
    'online_residents' => 0,
    'pending_requests' => 0,
    'pending_reports' => 0,
    'total_services' => 0,
    'total_announcements' => 0,
    'total_requests' => 0,
    'total_reports' => 0
];

// Get statistics with error handling
try {
    // Check if tables exist
    $tables_check = $db->query("SHOW TABLES");
    $existing_tables = [];
    while ($row = $tables_check->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    // Total residents
    if (in_array('users', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'resident'";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_residents'] = $result['total'] ?? 0;
    }

    // Online residents
    if (in_array('users', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM users WHERE is_online = 1 AND last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['online_residents'] = $result['total'] ?? 0;
    }

    // Pending requests
    if (in_array('resident_requests', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM resident_requests WHERE status = 'pending'";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pending_requests'] = $result['total'] ?? 0;
    }

    // Pending reports
    if (in_array('resident_reports', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM resident_reports WHERE status = 'pending'";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pending_reports'] = $result['total'] ?? 0;
    }

    // Total services
    if (in_array('services', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM services WHERE is_active = 1";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_services'] = $result['total'] ?? 0;
    }

    // Total announcements
    if (in_array('announcements', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM announcements WHERE status = 'active'";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_announcements'] = $result['total'] ?? 0;
    }

    // Total requests
    if (in_array('resident_requests', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM resident_requests";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_requests'] = $result['total'] ?? 0;
    }

    // Total reports
    if (in_array('resident_reports', $existing_tables)) {
        $query = "SELECT COUNT(*) as total FROM resident_reports";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_reports'] = $result['total'] ?? 0;
    }

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Gender statistics (only Male and Female)
$gender_stats = ['male' => 0, 'female' => 0];
try {
    if (in_array('resident_info', $existing_tables ?? [])) {
        $query = "SELECT gender, COUNT(*) as count FROM resident_info WHERE gender IN ('male', 'female') GROUP BY gender";
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($gender_stats[$row['gender']])) {
                $gender_stats[$row['gender']] = $row['count'];
            }
        }
    }
} catch (Exception $e) {
    // ignore
}

// Get recent requests
$recent_requests = [];
try {
    if (in_array('resident_requests', $existing_tables ?? []) && in_array('users', $existing_tables ?? []) && in_array('services', $existing_tables ?? [])) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.username, s.service_name 
                  FROM resident_requests r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN services s ON r.service_id = s.id
                  ORDER BY r.request_date DESC LIMIT 5";
        $stmt = $db->query($query);
        $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $recent_requests = [];
}

// Get recent reports
$recent_reports = [];
try {
    if (in_array('resident_reports', $existing_tables ?? []) && in_array('users', $existing_tables ?? [])) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.username 
                  FROM resident_reports r
                  LEFT JOIN users u ON r.user_id = u.id
                  ORDER BY r.reported_date DESC LIMIT 5";
        $stmt = $db->query($query);
        $recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $recent_reports = [];
}

// Get request statistics by status
$request_stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
try {
    if (in_array('resident_requests', $existing_tables ?? [])) {
        $query = "SELECT status, COUNT(*) as count FROM resident_requests GROUP BY status";
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($request_stats[$row['status']])) {
                $request_stats[$row['status']] = $row['count'];
            }
        }
    }
} catch (Exception $e) {
    // keep defaults
}

// Get report statistics by status
$report_stats = ['pending' => 0, 'in-progress' => 0, 'resolved' => 0, 'closed' => 0];
try {
    if (in_array('resident_reports', $existing_tables ?? [])) {
        $query = "SELECT status, COUNT(*) as count FROM resident_reports GROUP BY status";
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['status'];
            if ($status == 'in-progress') {
                $report_stats['in-progress'] = $row['count'];
            } elseif (isset($report_stats[$status])) {
                $report_stats[$status] = $row['count'];
            }
        }
    }
} catch (Exception $e) {
    // keep defaults
}

include 'includes/admin_header.php';
?>

<style>
/* Dashboard Specific Styles */
.dashboard-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}

.dashboard-stat-card {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.dashboard-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}

.dashboard-stat-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dashboard-stat-icon .material-icons {
    font-size: 1.75rem;
    color: white;
}

.dashboard-stat-details {
    flex: 1;
}

.dashboard-stat-details h3 {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.dashboard-stat-details p {
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.dashboard-charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}

.dashboard-chart-container {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.dashboard-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.dashboard-chart-header h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dashboard-chart-header h3 .material-icons {
    font-size: 1.1rem;
    color: #2563eb;
}

.dashboard-badge {
    background: #f1f5f9;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    color: #475569;
    font-weight: 500;
}

.dashboard-chart-body {
    height: 200px;
    position: relative;
}

.dashboard-tables-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}

.dashboard-table-container {
    background: white;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.dashboard-table-header {
    padding: 1rem 1.25rem;
    background: #fafcff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-table-header h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dashboard-table-header h3 .material-icons {
    font-size: 1.1rem;
    color: #2563eb;
}

.dashboard-view-all {
    color: #2563eb;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 500;
    transition: color 0.2s;
}

.dashboard-view-all:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.dashboard-table-responsive {
    overflow-x: auto;
}

.dashboard-data-table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-data-table th {
    text-align: left;
    padding: 0.75rem 1.25rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #475569;
    background: #fafafa;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dashboard-data-table td {
    padding: 0.75rem 1.25rem;
    font-size: 0.8rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}

.dashboard-data-table tr:last-child td {
    border-bottom: none;
}

.dashboard-data-table tr:hover td {
    background: #f8fafc;
}

.dashboard-status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.dashboard-status-pending { background: #fef3c7; color: #b45309; }
.dashboard-status-approved { background: #dcfce7; color: #166534; }
.dashboard-status-rejected { background: #fee2e2; color: #991b1b; }
.dashboard-status-completed { background: #d1fae5; color: #065f46; }
.dashboard-status-in-progress { background: #dbeafe; color: #1e40af; }
.dashboard-status-resolved { background: #dcfce7; color: #166534; }
.dashboard-status-closed { background: #f1f5f9; color: #334155; }

.dashboard-btn-icon {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
    display: inline-flex;
    align-items: center;
}

.dashboard-btn-icon:hover {
    color: #2563eb;
}

.dashboard-quick-actions {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
}

.dashboard-quick-actions h3 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #1e293b;
}

.dashboard-quick-actions h3 .material-icons {
    font-size: 1.1rem;
    color: #f59e0b;
}

.dashboard-action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
    gap: 0.75rem;
}

.dashboard-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 16px;
    text-decoration: none;
    color: #1e293b;
    transition: all 0.2s ease;
    gap: 0.5rem;
    border: 1px solid #e2e8f0;
}

.dashboard-action-btn:hover {
    background: #2563eb;
    color: white;
    transform: translateY(-2px);
    border-color: #2563eb;
}

.dashboard-action-btn .material-icons {
    font-size: 1.25rem;
}

.dashboard-action-btn span:last-child {
    font-size: 0.7rem;
    font-weight: 500;
}

.dashboard-welcome-banner {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px;
    margin-bottom: 1.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.2);
}

.dashboard-welcome-banner h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.dashboard-welcome-banner p {
    font-size: 0.85rem;
    opacity: 0.9;
}

.dashboard-date-display {
    background: rgba(255, 255, 255, 0.15);
    padding: 0.5rem 1.25rem;
    border-radius: 40px;
    font-size: 0.8rem;
    backdrop-filter: blur(4px);
}

@media (max-width: 768px) {
    .dashboard-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    .dashboard-charts-row {
        grid-template-columns: 1fr;
    }
    .dashboard-tables-row {
        grid-template-columns: 1fr;
    }
    .dashboard-welcome-banner {
        flex-direction: column;
        text-align: center;
    }
    .dashboard-stat-details h3 {
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .dashboard-stats-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-action-buttons {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="content-wrapper">
    <!-- Welcome Banner -->
    <div class="dashboard-welcome-banner">
        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>! 👋</h1>
            <p>Here's what's happening in your barangay today.</p>
        </div>
        <div class="dashboard-date-display">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-stats-grid">
        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e40af);"><span class="material-icons">people</span></div>
            <div class="dashboard-stat-details">
                <h3><?php echo number_format($stats['total_residents']); ?></h3>
                <p>Total Residents</p>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><span class="material-icons">pending_actions</span></div>
            <div class="dashboard-stat-details">
                <h3><?php echo $stats['pending_requests']; ?></h3>
                <p>Pending Requests</p>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"><span class="material-icons">flag</span></div>
            <div class="dashboard-stat-details">
                <h3><?php echo $stats['pending_reports']; ?></h3>
                <p>Pending Reports</p>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><span class="material-icons">miscellaneous_services</span></div>
            <div class="dashboard-stat-details">
                <h3><?php echo $stats['total_services']; ?></h3>
                <p>Active Services</p>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><span class="material-icons">campaign</span></div>
            <div class="dashboard-stat-details">
                <h3><?php echo $stats['total_announcements']; ?></h3>
                <p>Announcements</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="dashboard-charts-row">
        <div class="dashboard-chart-container">
            <div class="dashboard-chart-header">
                <h3><span class="material-icons">bar_chart</span> Request Status Overview</h3>
                <span class="dashboard-badge">Total: <?php echo $stats['total_requests']; ?></span>
            </div>
            <div class="dashboard-chart-body">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>
        <div class="dashboard-chart-container">
            <div class="dashboard-chart-header">
                <h3><span class="material-icons">bar_chart</span> Report Status Overview</h3>
                <span class="dashboard-badge">Total: <?php echo $stats['total_reports']; ?></span>
            </div>
            <div class="dashboard-chart-body">
                <canvas id="reportsChart"></canvas>
            </div>
        </div>
        <div class="dashboard-chart-container">
            <div class="dashboard-chart-header">
                <h3><span class="material-icons">people</span> Residents by Gender</h3>
                <span class="dashboard-badge">Total: <?php echo $stats['total_residents']; ?></span>
            </div>
            <div class="dashboard-chart-body">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Data Tables -->
    <div class="dashboard-tables-row">
        <div class="dashboard-table-container">
            <div class="dashboard-table-header">
                <h3><span class="material-icons">assignment</span> Recent Requests</h3>
                <a href="residents/requests.php" class="dashboard-view-all">View All →</a>
            </div>
            <div class="dashboard-table-responsive">
                <table class="dashboard-data-table">
                    <thead>
                        <tr><th>ID</th><th>Resident</th><th>Service</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_requests)): ?>
                            <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:2rem;">No recent requests</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $req): ?>
                            <tr>
                                <td>#<?php echo str_pad($req['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars(($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($req['service_name'] ?? 'N/A'); ?></td>
                                <td><span class="dashboard-status-badge dashboard-status-<?php echo $req['status'] ?? 'pending'; ?>"><?php echo ucfirst($req['status'] ?? 'pending'); ?></span></td>
                                <td><a href="residents/view_request.php?id=<?php echo $req['id']; ?>" class="dashboard-btn-icon"><span class="material-icons">visibility</span></a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dashboard-table-container">
            <div class="dashboard-table-header">
                <h3><span class="material-icons">flag</span> Recent Reports</h3>
                <a href="residents/reports.php" class="dashboard-view-all">View All →</a>
            </div>
            <div class="dashboard-table-responsive">
                <table class="dashboard-data-table">
                    <thead>
                        <tr><th>ID</th><th>Resident</th><th>Subject</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_reports)): ?>
                            <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:2rem;">No recent reports</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_reports as $rep): ?>
                            <tr>
                                <td>#<?php echo str_pad($rep['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars(($rep['first_name'] ?? '') . ' ' . ($rep['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars(substr($rep['subject'] ?? '', 0, 30)) . (strlen($rep['subject'] ?? '') > 30 ? '...' : ''); ?></td>
                                <td><span class="dashboard-status-badge dashboard-status-<?php echo str_replace('-', '', $rep['status'] ?? 'pending'); ?>"><?php echo ucfirst($rep['status'] ?? 'pending'); ?></span></td>
                                <td><a href="residents/view_report.php?id=<?php echo $rep['id']; ?>" class="dashboard-btn-icon"><span class="material-icons">visibility</span></a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-quick-actions">
        <h3><span class="material-icons">bolt</span> Quick Actions</h3>
        <div class="dashboard-action-buttons">
            <a href="residents/requests.php" class="dashboard-action-btn"><span class="material-icons">assignment</span><span>Manage Requests</span></a>
            <a href="residents/reports.php" class="dashboard-action-btn"><span class="material-icons">flag</span><span>Manage Reports</span></a>
            <a href="residents/information.php" class="dashboard-action-btn"><span class="material-icons">person_add</span><span>Add Resident</span></a>
            <a href="management/pages.php" class="dashboard-action-btn"><span class="material-icons">campaign</span><span>Post Announcement</span></a>
            <a href="management/services.php" class="dashboard-action-btn"><span class="material-icons">build</span><span>Update Services</span></a>
            <a href="management/carousel.php" class="dashboard-action-btn"><span class="material-icons">slideshow</span><span>Manage Carousel</span></a>
            <a href="qr/scan.php" class="dashboard-action-btn"><span class="material-icons">qr_code_scanner</span><span>Scan QR</span></a>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Charts
    const requestsCtx = document.getElementById('requestsChart');
    if (requestsCtx) {
        new Chart(requestsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected', 'Completed'],
                datasets: [{
                    data: [<?php echo $request_stats['pending']; ?>, <?php echo $request_stats['approved']; ?>, <?php echo $request_stats['rejected']; ?>, <?php echo $request_stats['completed']; ?>],
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#3b82f6'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 10 } 
                    } 
                }
            }
        });
    }
    
    const reportsCtx = document.getElementById('reportsChart');
    if (reportsCtx) {
        new Chart(reportsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    data: [<?php echo $report_stats['pending']; ?>, <?php echo $report_stats['in-progress']; ?>, <?php echo $report_stats['resolved']; ?>, <?php echo $report_stats['closed']; ?>],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#6b7280'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 10 } 
                    } 
                }
            }
        });
    }
    
    const genderCtx = document.getElementById('genderChart');
    if (genderCtx) {
        new Chart(genderCtx, {
            type: 'bar',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    label: 'Number of Residents',
                    data: [<?php echo $gender_stats['male']; ?>, <?php echo $gender_stats['female']; ?>],
                    backgroundColor: ['#3b82f6', '#ec4899'],
                    borderRadius: 8,
                    barPercentage: 0.65
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1, precision: 0, font: { size: 10 } },
                        grid: { color: '#e2e8f0' }
                    },
                    x: { 
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: { bodyFont: { size: 11 } }
                }
            }
        });
    }
</script>

<?php include 'includes/admin_footer.php'; ?>