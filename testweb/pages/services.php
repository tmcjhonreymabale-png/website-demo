<?php
// pages/services.php

// Set page title
$page_title = "Services | " . ($barangay_name ?? 'Barangay System');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once dirname(__DIR__) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch page content from pages table for services page
$page_query = "SELECT * FROM pages WHERE page_name = 'services'";
$page_stmt = $db->prepare($page_query);
$page_stmt->execute();
$services_page = $page_stmt->fetch(PDO::FETCH_ASSOC);

// Use fetched content or default
$page_title_text = $services_page['title'] ?? ($barangay_name ?? 'Barangay') . ' Services';
$page_description = $services_page['content'] ?? '<p>We offer various services to cater to the needs of our residents. Browse through our available services and request them online for your convenience.</p>';

// Fetch all active services
$query = "SELECT * FROM services WHERE is_active = 1 ORDER BY service_name";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Main Container */
    .services-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }
    
    /* Page Header */
    .page-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    
    .page-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #1e293b;
    }
    
    .page-header .page-description {
        color: #64748b;
        font-size: 1rem;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* Search Section */
    .search-section {
        margin-bottom: 2rem;
    }
    
    .search-box {
        max-width: 400px;
        margin: 0 auto;
    }
    
    .search-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        font-size: 0.9rem;
        transition: all 0.2s;
        background: white;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Stats Badge */
    .stats-badge {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .stats-badge span {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #f1f5f9;
        padding: 0.4rem 1rem;
        border-radius: 50px;
        color: #475569;
        font-size: 0.85rem;
    }
    
    /* Services Grid */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .service-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s;
        border: 1px solid #eef2f6;
        display: flex;
        flex-direction: column;
    }
    
    .service-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .service-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1.5rem;
        text-align: center;
        position: relative;
    }
    
    .service-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
    }
    
    .service-icon i {
        font-size: 1.8rem;
        color: white;
    }
    
    .service-header h3 {
        color: white;
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
    }
    
    .service-content {
        padding: 1.2rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .service-description {
        color: #475569;
        line-height: 1.5;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .requirements-section {
        background: #fef9e6;
        border-radius: 12px;
        padding: 0.75rem;
        margin-bottom: 1rem;
        border-left: 3px solid #f59e0b;
    }
    
    .requirements-section h4 {
        font-size: 0.75rem;
        font-weight: 700;
        color: #b45309;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .requirements-section h4 i {
        font-size: 0.7rem;
    }
    
    .requirements-section p {
        font-size: 0.75rem;
        color: #92400e;
        margin: 0;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .service-meta {
        margin-bottom: 1rem;
        padding-top: 0.5rem;
        border-top: 1px solid #eef2f6;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }
    
    .meta-label {
        font-size: 0.75rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .meta-label i {
        color: #667eea;
        font-size: 0.7rem;
    }
    
    .meta-value {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.85rem;
    }
    
    .btn-service {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.7rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        font-family: inherit;
        margin-top: auto;
    }
    
    .btn-request {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-request:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        gap: 0.7rem;
    }
    
    .btn-login {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-login:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        background: white;
        border-radius: 20px;
        border: 1px solid #eef2f6;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-size: 1.2rem;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #64748b;
        font-size: 0.85rem;
    }
    
    /* No Results Message */
    .no-results {
        text-align: center;
        padding: 3rem 2rem;
        background: white;
        border-radius: 20px;
        border: 1px solid #eef2f6;
        grid-column: 1 / -1;
    }
    
    .no-results i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .no-results h3 {
        font-size: 1.2rem;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .no-results p {
        color: #64748b;
        font-size: 0.85rem;
    }
    
    /* ===== RESPONSIVE BREAKPOINTS ===== */
    
    /* Tablet Landscape (max-width: 1024px) */
    @media (max-width: 1024px) {
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1.2rem;
        }
    }
    
    /* Tablet Portrait (max-width: 768px) */
    @media (max-width: 768px) {
        .services-page {
            padding: 1.5rem 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 1.6rem;
        }
        
        .page-header .page-description {
            font-size: 0.9rem;
        }
        
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .service-header {
            padding: 1.2rem;
        }
        
        .service-icon {
            width: 50px;
            height: 50px;
        }
        
        .service-icon i {
            font-size: 1.5rem;
        }
        
        .service-header h3 {
            font-size: 1rem;
        }
        
        .service-content {
            padding: 1rem;
        }
        
        .stats-badge span {
            font-size: 0.75rem;
            padding: 0.3rem 0.8rem;
        }
        
        .search-input {
            font-size: 0.85rem;
            padding: 0.6rem 0.9rem;
        }
    }
    
    /* Mobile Large (max-width: 576px) */
    @media (max-width: 576px) {
        .services-page {
            padding: 1rem 0.75rem;
        }
        
        .page-header h1 {
            font-size: 1.4rem;
        }
        
        .page-header .page-description {
            font-size: 0.85rem;
        }
        
        .services-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .service-header {
            padding: 1rem;
        }
        
        .service-icon {
            width: 45px;
            height: 45px;
        }
        
        .service-icon i {
            font-size: 1.3rem;
        }
        
        .service-header h3 {
            font-size: 0.95rem;
        }
        
        .service-content {
            padding: 0.9rem;
        }
        
        .service-description {
            font-size: 0.8rem;
        }
        
        .requirements-section p {
            font-size: 0.7rem;
        }
        
        .meta-label, .meta-value {
            font-size: 0.7rem;
        }
        
        .btn-service {
            padding: 0.6rem;
            font-size: 0.8rem;
        }
        
        .stats-badge span {
            font-size: 0.7rem;
        }
        
        .search-box {
            max-width: 100%;
        }
    }
    
    /* Mobile Small (max-width: 380px) */
    @media (max-width: 380px) {
        .services-page {
            padding: 0.75rem 0.5rem;
        }
        
        .page-header h1 {
            font-size: 1.2rem;
        }
        
        .page-header .page-description {
            font-size: 0.75rem;
        }
        
        .service-header {
            padding: 0.8rem;
        }
        
        .service-icon {
            width: 40px;
            height: 40px;
        }
        
        .service-icon i {
            font-size: 1.1rem;
        }
        
        .service-header h3 {
            font-size: 0.85rem;
        }
        
        .service-content {
            padding: 0.75rem;
        }
        
        .service-description {
            font-size: 0.75rem;
        }
        
        .requirements-section h4 {
            font-size: 0.7rem;
        }
        
        .requirements-section p {
            font-size: 0.65rem;
        }
        
        .meta-label, .meta-value {
            font-size: 0.65rem;
        }
        
        .btn-service {
            padding: 0.5rem;
            font-size: 0.75rem;
        }
    }
</style>

<div class="services-page">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title_text); ?></h1>
        <div class="page-description">
            <?php 
            // Remove HTML tags for the description display
            $plain_description = strip_tags($page_description);
            echo htmlspecialchars($plain_description);
            ?>
        </div>
    </div>
    
    <?php if (count($services) > 0): ?>
        <div class="search-section">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search services..." onkeyup="filterServices()">
            </div>
        </div>
        
        <div class="stats-badge">
            <span id="servicesCount">
                <i class="fas fa-concierge-bell"></i>
                <?php echo count($services); ?> service<?php echo count($services) > 1 ? 's' : ''; ?> available
            </span>
        </div>
        
        <div class="services-grid" id="servicesGrid">
            <?php foreach ($services as $service): ?>
                <div class="service-card" data-name="<?php echo strtolower(htmlspecialchars($service['service_name'])); ?>">
                    <div class="service-header">
                        <div class="service-icon">
                            <?php
                            // Dynamic icon based on service name
                            $icon = 'fas fa-file-alt';
                            $service_name_lower = strtolower($service['service_name']);
                            if (strpos($service_name_lower, 'clearance') !== false) {
                                $icon = 'fas fa-id-card';
                            } elseif (strpos($service_name_lower, 'certificate') !== false) {
                                $icon = 'fas fa-certificate';
                            } elseif (strpos($service_name_lower, 'business') !== false) {
                                $icon = 'fas fa-store';
                            } elseif (strpos($service_name_lower, 'indigency') !== false) {
                                $icon = 'fas fa-hand-holding-heart';
                            } elseif (strpos($service_name_lower, 'residency') !== false) {
                                $icon = 'fas fa-home';
                            }
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                    </div>
                    <div class="service-content">
                        <div class="service-description">
                            <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                        </div>
                        
                        <?php if (!empty($service['requirements'])): ?>
                        <div class="requirements-section">
                            <h4><i class="fas fa-clipboard-list"></i> Requirements</h4>
                            <p><?php echo nl2br(htmlspecialchars($service['requirements'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="service-meta">
                            <div class="meta-item">
                                <span class="meta-label"><i class="fas fa-tag"></i> Processing Fee</span>
                                <span class="meta-value">
                                    <?php 
                                    if ($service['fee'] > 0) {
                                        echo '₱' . number_format($service['fee'], 2);
                                    } else {
                                        echo '<span style="color: #10b981;">Free</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/testweb/resident/request_service.php?service_id=<?php echo $service['id']; ?>" class="btn-service btn-request">
                                Request Service <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="/testweb/auth/login.php" class="btn-service btn-login">
                                <i class="fas fa-sign-in-alt"></i> Login to Request
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-concierge-bell"></i>
            <h3>No services available</h3>
            <p>Check back later for available services</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function filterServices() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const cards = document.querySelectorAll('.service-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const serviceName = card.getAttribute('data-name');
            const matchesSearch = serviceName.includes(searchTerm);
            
            if (matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        const countElement = document.getElementById('servicesCount');
        if (countElement) {
            countElement.innerHTML = `<i class="fas fa-concierge-bell"></i> ${visibleCount} service${visibleCount !== 1 ? 's' : ''} available`;
        }
        
        // Show empty state message if no services match
        const grid = document.getElementById('servicesGrid');
        let noResultsMsg = document.getElementById('noResultsMessage');
        
        if (visibleCount === 0 && cards.length > 0) {
            if (!noResultsMsg) {
                const msg = document.createElement('div');
                msg.id = 'noResultsMessage';
                msg.className = 'no-results';
                msg.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h3>No services found</h3>
                    <p>Try searching with different keywords</p>
                `;
                grid.appendChild(msg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', filterServices);
        }
    });
</script>