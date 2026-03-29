<?php
// pages/announcements.php

// Set page title based on whether viewing single announcement or list
if (isset($_GET['id'])) {
    $page_title = "Announcement Details | " . ($barangay_name ?? 'Barangay System');
} else {
    $page_title = "Announcements | " . ($barangay_name ?? 'Barangay System');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once dirname(__DIR__) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch page content from pages table for announcements page
$page_query = "SELECT * FROM pages WHERE page_name = 'announcements'";
$page_stmt = $db->prepare($page_query);
$page_stmt->execute();
$announcements_page = $page_stmt->fetch(PDO::FETCH_ASSOC);

// Use fetched content or default
$page_title_text = $announcements_page['title'] ?? ($barangay_name ?? 'Barangay') . ' Announcements';
$page_description = $announcements_page['content'] ?? '<p>Stay updated with the latest news and events from your Barangay.</p>';

// Get single announcement if ID is provided
$announcement_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($announcement_id) {
    // Fetch single announcement
    $query = "SELECT a.*, CONCAT(adm.first_name, ' ', adm.last_name) as posted_by_name 
              FROM announcements a 
              LEFT JOIN admins adm ON a.posted_by = adm.id 
              WHERE a.id = :id AND a.status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $announcement_id);
    $stmt->execute();
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($announcement) {
        ?>
        <div class="announcement-detail">
            <div class="detail-container">
                <a href="index.php?page=announcements" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Announcements
                </a>
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-bullhorn"></i>
                        <h1><?php echo htmlspecialchars($announcement['title']); ?></h1>
                    </div>
                    <div class="detail-content">
                        <div class="detail-meta">
                            <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($announcement['posted_by_name'] ?? 'Administrator'); ?></span>
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></span>
                        </div>
                        <div class="detail-body">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .announcement-detail {
                max-width: 900px;
                margin: 2rem auto;
                padding: 0 1.5rem;
            }
            
            .detail-container {
                width: 100%;
            }
            
            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
                color: #667eea;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.2s;
            }
            
            .back-link:hover {
                gap: 0.8rem;
                color: #5a67d8;
            }
            
            .detail-card {
                background: white;
                border-radius: 24px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                border: 1px solid #eef2f6;
            }
            
            .detail-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 2.5rem 2rem;
                text-align: center;
            }
            
            .detail-header i {
                font-size: 3rem;
                color: white;
                margin-bottom: 1rem;
            }
            
            .detail-header h1 {
                color: white;
                font-size: 1.8rem;
                margin: 0;
                font-weight: 700;
                word-break: break-word;
            }
            
            .detail-content {
                padding: 2rem;
            }
            
            .detail-meta {
                display: flex;
                gap: 1.5rem;
                align-items: center;
                padding-bottom: 1rem;
                margin-bottom: 1.5rem;
                border-bottom: 1px solid #eef2f6;
                flex-wrap: wrap;
            }
            
            .detail-meta span {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: #64748b;
                font-size: 0.85rem;
            }
            
            .detail-meta i {
                color: #667eea;
                font-size: 0.9rem;
            }
            
            .detail-body {
                line-height: 1.8;
                color: #334155;
                font-size: 1rem;
                word-break: break-word;
            }
            
            /* Responsive Detail Page */
            @media (max-width: 768px) {
                .announcement-detail {
                    padding: 0 1rem;
                    margin: 1rem auto;
                }
                
                .detail-header {
                    padding: 1.8rem 1.5rem;
                }
                
                .detail-header h1 {
                    font-size: 1.4rem;
                }
                
                .detail-header i {
                    font-size: 2rem;
                }
                
                .detail-content {
                    padding: 1.5rem;
                }
                
                .detail-meta {
                    gap: 1rem;
                }
                
                .detail-meta span {
                    font-size: 0.75rem;
                }
                
                .detail-body {
                    font-size: 0.9rem;
                    line-height: 1.6;
                }
            }
            
            @media (max-width: 480px) {
                .announcement-detail {
                    padding: 0 0.75rem;
                }
                
                .detail-header {
                    padding: 1.2rem 1rem;
                }
                
                .detail-header h1 {
                    font-size: 1.2rem;
                }
                
                .detail-header i {
                    font-size: 1.5rem;
                }
                
                .detail-content {
                    padding: 1rem;
                }
                
                .detail-meta {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.5rem;
                }
                
                .back-link {
                    font-size: 0.85rem;
                }
            }
        </style>
        <?php
    } else {
        ?>
        <div class="not-found-container">
            <i class="fas fa-search"></i>
            <h2>Announcement Not Found</h2>
            <p>The announcement you're looking for doesn't exist or has been removed.</p>
            <a href="index.php?page=announcements" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Announcements
            </a>
        </div>
        
        <style>
            .not-found-container {
                max-width: 600px;
                margin: 4rem auto;
                text-align: center;
                padding: 2rem;
                background: white;
                border-radius: 20px;
                border: 1px solid #eef2f6;
            }
            
            .not-found-container i {
                font-size: 3rem;
                color: #cbd5e1;
                margin-bottom: 1rem;
            }
            
            .not-found-container h2 {
                color: #1e293b;
                margin-bottom: 0.5rem;
                font-size: 1.5rem;
            }
            
            .not-found-container p {
                color: #64748b;
                margin-bottom: 1.5rem;
            }
            
            .back-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 0.6rem 1.2rem;
                border-radius: 50px;
                text-decoration: none;
                font-weight: 500;
            }
            
            @media (max-width: 576px) {
                .not-found-container {
                    margin: 2rem 1rem;
                    padding: 1.5rem;
                }
                
                .not-found-container h2 {
                    font-size: 1.2rem;
                }
            }
        </style>
        <?php
    }
} else {
    // List all announcements
    $query = "SELECT a.*, CONCAT(adm.first_name, ' ', adm.last_name) as posted_by_name 
              FROM announcements a 
              LEFT JOIN admins adm ON a.posted_by = adm.id 
              WHERE a.status = 'active' 
              ORDER BY a.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="announcements-page">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($page_title_text); ?></h1>
            <div class="page-description">
                <?php 
                $plain_description = strip_tags($page_description);
                echo htmlspecialchars($plain_description);
                ?>
            </div>
        </div>
        
        <?php if (count($announcements) > 0): ?>
            <div class="announcements-stats">
                <div class="stats-badge">
                    <i class="fas fa-newspaper"></i>
                    <?php echo count($announcements); ?> announcement<?php echo count($announcements) > 1 ? 's' : ''; ?> available
                </div>
            </div>
            
            <div class="announcements-grid">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card" onclick="window.location.href='index.php?page=announcements&id=<?php echo $announcement['id']; ?>'">
                        <div class="card-header">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <div class="card-meta">
                                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($announcement['posted_by_name'] ?? 'Admin'); ?></span>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                            </div>
                            <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 120)); ?><?php echo strlen($announcement['content']) > 120 ? '...' : ''; ?></p>
                            <a href="index.php?page=announcements&id=<?php echo $announcement['id']; ?>" class="read-more" onclick="event.stopPropagation()">
                                Read more <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-newspaper"></i>
                <h3>No announcements yet</h3>
                <p>Check back later for updates from the barangay</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        /* Main Container */
        .announcements-page {
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
        
        /* Stats Badge */
        .announcements-stats {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f1f5f9;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            color: #475569;
            font-size: 0.85rem;
        }
        
        /* Announcements Grid */
        .announcements-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* Announcement Card */
        .announcement-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #eef2f6;
            display: flex;
            flex-direction: column;
        }
        
        .announcement-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            text-align: center;
        }
        
        .card-header i {
            font-size: 2rem;
            color: white;
        }
        
        .card-content {
            padding: 1.2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.75rem;
            color: #94a3b8;
            font-size: 0.7rem;
            flex-wrap: wrap;
        }
        
        .card-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .card-content p {
            color: #475569;
            line-height: 1.5;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .read-more {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.8rem;
            transition: gap 0.2s;
            margin-top: auto;
        }
        
        .read-more:hover {
            gap: 0.5rem;
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
        
        /* ===== RESPONSIVE BREAKPOINTS ===== */
        
        /* Tablet Landscape (max-width: 1024px) */
        @media (max-width: 1024px) {
            .announcements-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.2rem;
            }
        }
        
        /* Tablet Portrait (max-width: 768px) */
        @media (max-width: 768px) {
            .announcements-page {
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
            
            .announcements-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .card-header {
                padding: 1.2rem;
            }
            
            .card-header i {
                font-size: 1.5rem;
            }
            
            .card-content {
                padding: 1rem;
            }
            
            .card-content h3 {
                font-size: 1rem;
            }
            
            .stats-badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.8rem;
            }
        }
        
        /* Mobile Large (max-width: 576px) */
        @media (max-width: 576px) {
            .announcements-page {
                padding: 1rem 0.75rem;
            }
            
            .page-header h1 {
                font-size: 1.4rem;
            }
            
            .page-header .page-description {
                font-size: 0.85rem;
            }
            
            .announcements-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-header i {
                font-size: 1.3rem;
            }
            
            .card-content {
                padding: 0.9rem;
            }
            
            .card-content h3 {
                font-size: 0.95rem;
            }
            
            .card-meta span {
                font-size: 0.65rem;
            }
            
            .card-content p {
                font-size: 0.8rem;
            }
            
            .read-more {
                font-size: 0.75rem;
            }
            
            .stats-badge {
                font-size: 0.7rem;
            }
            
            .empty-state {
                padding: 2rem 1rem;
            }
            
            .empty-state i {
                font-size: 2rem;
            }
            
            .empty-state h3 {
                font-size: 1rem;
            }
        }
        
        /* Mobile Small (max-width: 380px) */
        @media (max-width: 380px) {
            .announcements-page {
                padding: 0.75rem 0.5rem;
            }
            
            .page-header h1 {
                font-size: 1.2rem;
            }
            
            .card-header {
                padding: 0.8rem;
            }
            
            .card-header i {
                font-size: 1.1rem;
            }
            
            .card-content h3 {
                font-size: 0.85rem;
            }
            
            .card-meta {
                gap: 0.5rem;
            }
            
            .card-meta span {
                font-size: 0.6rem;
            }
            
            .card-content p {
                font-size: 0.75rem;
            }
            
            .read-more {
                font-size: 0.7rem;
            }
        }
    </style>
    <?php
}
?>