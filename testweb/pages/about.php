<?php
// pages/about.php

// Set page title
$page_title = "About Us | " . ($barangay_name ?? 'Barangay System');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once dirname(__DIR__) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch page content from pages table for about page
$page_query = "SELECT * FROM pages WHERE page_name = 'about'";
$page_stmt = $db->prepare($page_query);
$page_stmt->execute();
$about_page = $page_stmt->fetch(PDO::FETCH_ASSOC);

// Use fetched content or default
$page_title_text = $about_page['title'] ?? 'About Us';
$page_description = $about_page['content'] ?? 'Learn about our history, mission, and the team serving the community';

// Ensure team_members table exists
$create_team_table = "CREATE TABLE IF NOT EXISTS team_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    position_category ENUM('barangay_official', 'sk_official', 'staff', 'volunteer') DEFAULT 'barangay_official',
    biography TEXT,
    contact_info VARCHAR(255),
    profile_image VARCHAR(255),
    display_order INT DEFAULT 0,
    term_start DATE,
    term_end DATE,
    committee VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
try {
    $db->exec($create_team_table);
} catch (Exception $e) {
    // Table might already exist
}

// Create about_sections table if not exists with correct structure
$create_about_table = "CREATE TABLE IF NOT EXISTS about_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT,
    section_type ENUM('history', 'mission', 'vision', 'officials', 'contact', 'general') DEFAULT 'general',
    section_title VARCHAR(255),
    section_content TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
)";
try {
    $db->exec($create_about_table);
} catch (Exception $e) {
    // Table might already exist
}

// Fetch about sections from database
$sections_query = "SELECT * FROM about_sections WHERE is_active = 1 ORDER BY display_order, id";
$sections_stmt = $db->prepare($sections_query);
$sections_stmt->execute();
$sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no sections exist, insert default ones
if (empty($sections)) {
    $page_query = "SELECT id FROM pages WHERE page_name = 'about'";
    $page_stmt = $db->prepare($page_query);
    $page_stmt->execute();
    $about_page_data = $page_stmt->fetch(PDO::FETCH_ASSOC);
    $page_id = $about_page_data['id'] ?? null;
    
    $default_sections = [
        ['history', 'Our History', 'Our barangay was established with a vision to serve the community. Over the years, we have grown and developed into a progressive community that values unity, cooperation, and development.', 1],
        ['mission', 'Our Mission', 'To provide efficient and effective public service, promote the welfare of our residents, and foster a safe, healthy, and progressive community.', 2],
        ['vision', 'Our Vision', 'A model barangay known for its good governance, empowered citizens, and sustainable development that serves as an inspiration to others.', 3]
    ];
    
    $insert_about = "INSERT INTO about_sections (page_id, section_type, section_title, section_content, display_order, is_active) 
                     VALUES (:page_id, :type, :title, :content, :order, 1)";
    $insert_stmt = $db->prepare($insert_about);
    
    foreach ($default_sections as $section) {
        $insert_stmt->bindParam(':page_id', $page_id);
        $insert_stmt->bindParam(':type', $section[0]);
        $insert_stmt->bindParam(':title', $section[1]);
        $insert_stmt->bindParam(':content', $section[2]);
        $insert_stmt->bindParam(':order', $section[3]);
        $insert_stmt->execute();
    }
    
    $sections_stmt->execute();
    $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch team members
$team_query = "SELECT * FROM team_members WHERE is_active = 1 ORDER BY 
                FIELD(position_category, 'barangay_official', 'sk_official', 'staff', 'volunteer'),
                display_order, full_name";
$team_stmt = $db->prepare($team_query);
$team_stmt->execute();
$team_members = $team_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate sections by type
$history_content = '';
$mission_content = '';
$vision_content = '';
$general_sections = [];

foreach ($sections as $section) {
    switch ($section['section_type']) {
        case 'history':
            $history_content = $section['section_content'];
            break;
        case 'mission':
            $mission_content = $section['section_content'];
            break;
        case 'vision':
            $vision_content = $section['section_content'];
            break;
        default:
            $general_sections[] = $section;
    }
}

// Group team members by category
$grouped_members = [
    'barangay_official' => [],
    'sk_official' => [],
    'staff' => [],
    'volunteer' => []
];

foreach ($team_members as $member) {
    $category = $member['position_category'];
    if (isset($grouped_members[$category])) {
        $grouped_members[$category][] = $member;
    }
}

$category_names = [
    'barangay_official' => ['name' => 'Barangay Officials', 'icon' => 'fas fa-landmark'],
    'sk_official' => ['name' => 'SK Officials', 'icon' => 'fas fa-graduation-cap'],
    'staff' => ['name' => 'Barangay Staff', 'icon' => 'fas fa-users'],
    'volunteer' => ['name' => 'Volunteers', 'icon' => 'fas fa-heart']
];

// Barangay location
$barangay_lat = 14.2791696;
$barangay_lng = 120.8446077;
$barangay_name_display = "Cabuco Barangay Hall";
$barangay_address_full = "Purok 4, Cabuco, Trece Martires City, Cavite";
$barangay_address_display = $barangay_address ?? $barangay_address_full;
$barangay_name = $barangay_name ?? 'Barangay System';
?>

<style>
    .about-page {
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
    
    /* History Section */
    .history-section {
        background: #f8fafc;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid #eef2f6;
    }
    
    .history-section h2 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: inline-block;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #667eea;
    }
    
    .history-section p {
        color: #475569;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    
    /* Mission Vision Grid */
    .mv-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .mv-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid #eef2f6;
        transition: all 0.3s;
    }
    
    .mv-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    }
    
    .mv-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }
    
    .mv-icon i {
        font-size: 1.3rem;
        color: white;
    }
    
    .mv-card h2 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .mv-card p {
        color: #64748b;
        line-height: 1.5;
        font-size: 0.85rem;
    }
    
    /* General Sections */
    .general-section {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #eef2f6;
    }
    
    .general-section h2 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.75rem;
        padding-left: 0.75rem;
        border-left: 3px solid #667eea;
    }
    
    .general-section p {
        color: #475569;
        line-height: 1.5;
        font-size: 0.9rem;
    }
    
    /* Team Section */
    .team-section {
        margin-top: 2rem;
        margin-bottom: 2rem;
    }
    
    .team-section h2 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .team-category {
        margin-bottom: 2rem;
    }
    
    .team-category h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .team-category h3 i {
        color: #667eea;
        font-size: 1rem;
    }
    
    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.2rem;
    }
    
    .team-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s;
        border: 1px solid #eef2f6;
    }
    
    .team-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .team-image {
        width: 100%;
        height: 220px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    
    .team-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .default-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .default-avatar i {
        font-size: 3rem;
        color: white;
    }
    
    .team-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        background: rgba(0, 0, 0, 0.6);
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 500;
        color: white;
    }
    
    .team-info {
        padding: 1rem;
        text-align: center;
    }
    
    .team-info h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.2rem;
    }
    
    .team-position {
        font-size: 0.75rem;
        color: #667eea;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .team-committee {
        display: inline-block;
        background: #f1f5f9;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.65rem;
        color: #475569;
        margin-bottom: 0.5rem;
    }
    
    .team-bio {
        color: #64748b;
        font-size: 0.75rem;
        line-height: 1.4;
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .team-contact {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        color: #94a3b8;
        padding-top: 0.5rem;
        border-top: 1px solid #eef2f6;
    }
    
    .team-contact i {
        color: #667eea;
        font-size: 0.7rem;
    }
    
    /* Empty State */
    .empty-team {
        text-align: center;
        padding: 2rem;
        background: #f8fafc;
        border-radius: 16px;
        border: 1px solid #eef2f6;
    }
    
    .empty-team i {
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 0.5rem;
    }
    
    .empty-team p {
        color: #64748b;
        font-size: 0.85rem;
    }
    
    /* Map Section */
    .map-section {
        background: white;
        border-radius: 24px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid #eef2f6;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .map-section h2 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .map-section h2 i {
        color: #667eea;
    }
    
    .map-container {
        width: 100%;
        height: 450px;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 1rem;
        background: #1a2a3a;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    #map {
        width: 100%;
        height: 100%;
    }
    
    .map-info {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1rem 1.2rem;
        border-radius: 16px;
        margin-top: 1rem;
        border: 1px solid #eef2f6;
    }
    
    .map-info p {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #475569;
        font-size: 0.9rem;
        margin: 0.5rem 0;
        word-break: break-word;
    }
    
    .map-info i {
        color: #667eea;
        width: 24px;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    
    .map-directions {
        display: flex;
        justify-content: center;
        margin-top: 1.2rem;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .btn-directions {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.7rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
    }
    
    .btn-directions:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        gap: 0.8rem;
    }
    
    .btn-streetview {
        background: white;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }
    
    .btn-streetview:hover {
        background: #f8fafc;
        border-color: #667eea;
        color: #667eea;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* Contact Section */
    .contact-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
        margin-top: 1rem;
    }
    
    .contact-section h2 {
        font-size: 1.3rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .contact-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .contact-item {
        text-align: center;
        padding: 0.5rem;
    }
    
    .contact-item i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .contact-item h3 {
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .contact-item p {
        font-size: 0.7rem;
        opacity: 0.9;
        line-height: 1.4;
    }
    
    /* ===== RESPONSIVE BREAKPOINTS ===== */
    
    /* Tablet Landscape (max-width: 1024px) */
    @media (max-width: 1024px) {
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    /* Tablet Portrait (max-width: 768px) */
    @media (max-width: 768px) {
        .about-page {
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
        
        .mv-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .history-section {
            padding: 1.5rem;
        }
        
        .history-section h2 {
            font-size: 1.2rem;
        }
        
        .history-section p {
            font-size: 0.9rem;
        }
        
        .contact-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .team-image {
            height: 200px;
        }
        
        .map-container {
            height: 350px;
        }
        
        .map-section h2 {
            font-size: 1.2rem;
        }
        
        .map-info p {
            font-size: 0.85rem;
        }
        
        .contact-section {
            padding: 1.5rem;
        }
        
        .contact-section h2 {
            font-size: 1.2rem;
        }
    }
    
    /* Mobile Large (max-width: 576px) */
    @media (max-width: 576px) {
        .about-page {
            padding: 1rem 0.75rem;
        }
        
        .page-header h1 {
            font-size: 1.4rem;
        }
        
        .page-header .page-description {
            font-size: 0.85rem;
        }
        
        .history-section {
            padding: 1rem;
            border-radius: 16px;
        }
        
        .history-section h2 {
            font-size: 1.1rem;
        }
        
        .history-section p {
            font-size: 0.85rem;
        }
        
        .mv-card {
            padding: 1rem;
        }
        
        .mv-icon {
            width: 45px;
            height: 45px;
        }
        
        .mv-icon i {
            font-size: 1.1rem;
        }
        
        .mv-card h2 {
            font-size: 1.1rem;
        }
        
        .mv-card p {
            font-size: 0.8rem;
        }
        
        .general-section {
            padding: 1rem;
        }
        
        .general-section h2 {
            font-size: 1rem;
        }
        
        .general-section p {
            font-size: 0.85rem;
        }
        
        .team-section h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .team-category h3 {
            font-size: 0.9rem;
        }
        
        .team-grid {
            grid-template-columns: 1fr;
        }
        
        .team-image {
            height: 200px;
        }
        
        .team-info h3 {
            font-size: 0.95rem;
        }
        
        .team-position {
            font-size: 0.7rem;
        }
        
        .team-bio {
            font-size: 0.7rem;
        }
        
        .map-container {
            height: 280px;
        }
        
        .map-directions {
            flex-direction: column;
        }
        
        .btn-directions {
            justify-content: center;
            width: 100%;
        }
        
        .map-info p {
            font-size: 0.75rem;
        }
        
        .map-info i {
            width: 20px;
            font-size: 0.9rem;
        }
        
        .contact-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .contact-item {
            padding: 0.75rem;
        }
        
        .contact-item i {
            font-size: 1.3rem;
        }
        
        .contact-item h3 {
            font-size: 0.8rem;
        }
        
        .contact-item p {
            font-size: 0.7rem;
        }
    }
    
    /* Mobile Small (max-width: 380px) */
    @media (max-width: 380px) {
        .about-page {
            padding: 0.75rem 0.5rem;
        }
        
        .page-header h1 {
            font-size: 1.2rem;
        }
        
        .page-header .page-description {
            font-size: 0.75rem;
        }
        
        .history-section h2 {
            font-size: 1rem;
        }
        
        .history-section p {
            font-size: 0.8rem;
        }
        
        .mv-card h2 {
            font-size: 1rem;
        }
        
        .mv-card p {
            font-size: 0.75rem;
        }
        
        .team-section h2 {
            font-size: 1rem;
        }
        
        .team-image {
            height: 180px;
        }
        
        .team-info {
            padding: 0.75rem;
        }
        
        .team-info h3 {
            font-size: 0.85rem;
        }
        
        .map-container {
            height: 250px;
        }
        
        .map-section {
            padding: 1rem;
        }
        
        .map-section h2 {
            font-size: 1rem;
        }
        
        .map-info {
            padding: 0.75rem;
        }
        
        .map-info p {
            font-size: 0.7rem;
        }
    }
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="about-page">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title_text); ?></h1>
        <div class="page-description">
            <?php 
            $plain_description = strip_tags($page_description);
            echo htmlspecialchars($plain_description);
            ?>
        </div>
    </div>
    
    <?php if (!empty($history_content)): ?>
    <div class="history-section">
        <h2>Our History</h2>
        <p><?php echo nl2br(htmlspecialchars($history_content)); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="mv-grid">
        <?php if (!empty($mission_content)): ?>
        <div class="mv-card">
            <div class="mv-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <h2>Our Mission</h2>
            <p><?php echo nl2br(htmlspecialchars($mission_content)); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($vision_content)): ?>
        <div class="mv-card">
            <div class="mv-icon">
                <i class="fas fa-eye"></i>
            </div>
            <h2>Our Vision</h2>
            <p><?php echo nl2br(htmlspecialchars($vision_content)); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- General Sections -->
    <?php if (!empty($general_sections)): ?>
        <?php foreach ($general_sections as $section): ?>
        <div class="general-section">
            <h2><?php echo htmlspecialchars($section['section_title']); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($section['section_content'])); ?></p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($team_members)): ?>
    <div class="team-section">
        <h2>Meet Our Team</h2>
        
        <?php foreach ($grouped_members as $category => $members): ?>
            <?php if (!empty($members)): ?>
            <div class="team-category">
                <h3>
                    <i class="<?php echo $category_names[$category]['icon']; ?>"></i>
                    <?php echo $category_names[$category]['name']; ?>
                </h3>
                <div class="team-grid">
                    <?php foreach ($members as $member): ?>
                    <div class="team-card">
                        <div class="team-image">
                            <?php 
                            $image_path = '';
                            if (!empty($member['profile_image'])) {
                                $full_image_path = dirname(__DIR__) . '/assets/uploads/team/' . $member['profile_image'];
                                if (file_exists($full_image_path)) {
                                    $image_path = '/testweb/assets/uploads/team/' . $member['profile_image'];
                                }
                            }
                            
                            if (!empty($image_path) && file_exists($full_image_path)): 
                            ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($member['full_name']); ?>">
                            <?php else: ?>
                                <div class="default-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                            <?php endif; ?>
                            <div class="team-badge">
                                <?php 
                                switch($category) {
                                    case 'barangay_official':
                                        echo 'Official';
                                        break;
                                    case 'sk_official':
                                        echo 'SK Official';
                                        break;
                                    case 'staff':
                                        echo 'Staff';
                                        break;
                                    default:
                                        echo 'Volunteer';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="team-info">
                            <h3><?php echo htmlspecialchars($member['full_name']); ?></h3>
                            <div class="team-position"><?php echo htmlspecialchars($member['position']); ?></div>
                            <?php if (!empty($member['committee'])): ?>
                            <div class="team-committee">
                                <i class="fas fa-users"></i> <?php echo htmlspecialchars($member['committee']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($member['biography'])): ?>
                            <div class="team-bio">
                                <?php echo htmlspecialchars(substr($member['biography'], 0, 80)); ?>
                                <?php if (strlen($member['biography']) > 80): ?>...<?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($member['contact_info'])): ?>
                            <div class="team-contact">
                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($member['contact_info']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-team">
        <i class="fas fa-users"></i>
        <p>Team information coming soon</p>
    </div>
    <?php endif; ?>

    <!-- Satellite Map Section -->
    <div class="map-section">
        <h2>
            <i class="fas fa-map-marked-alt"></i>
            Find Us Here
        </h2>
        <div class="map-container">
            <div id="map"></div>
        </div>
        <div class="map-info">
            <p><i class="fas fa-map-pin"></i> <strong><?php echo htmlspecialchars($barangay_name_display); ?></strong></p>
            <p><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($barangay_address_full); ?></p>
        </div>
        <div class="map-directions">
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($barangay_address_full); ?>" target="_blank" class="btn-directions">
                <i class="fas fa-directions"></i> Get Directions
            </a>
            <a href="https://www.google.com/maps?q=<?php echo urlencode($barangay_address_full); ?>" target="_blank" class="btn-directions btn-streetview">
                <i class="fas fa-street-view"></i> Street View
            </a>
        </div>
    </div>
    
    <div class="contact-section">
        <h2>Contact Us</h2>
        <div class="contact-grid">
            <div class="contact-item">
                <i class="fas fa-phone-alt"></i>
                <h3>Phone</h3>
                <p><?php echo htmlspecialchars($barangay_contact ?? '0912 345 6789'); ?></p>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>info@cabuco.gov.ph</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <h3>Office Hours</h3>
                <p>Mon-Sat: 8AM-5PM<br>Sunday: Closed</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Satellite Map with Google Maps Style Marker
    document.addEventListener('DOMContentLoaded', function() {
        const barangayLat = 14.2791696;
        const barangayLng = 120.8446077;
        const barangayName = "Cabuco Barangay Hall";
        const barangayAddress = "Purok 4, Cabuco, Trece Martires City, Cavite";
        
        const customIcon = L.divIcon({
            html: `
                <div style="position: relative;">
                    <div style="background: #ea4335; width: 36px; height: 36px; border-radius: 50% 50% 50% 0; position: relative; transform: rotate(-45deg); left: 50%; top: 50%; margin: -18px 0 0 -18px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(45deg);">
                            <i class="fas fa-landmark" style="color: white; font-size: 16px;"></i>
                        </div>
                    </div>
                    <div style="position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); white-space: nowrap; background: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #ea4335; box-shadow: 0 2px 8px rgba(0,0,0,0.15); pointer-events: none;">
                        ${barangayName}
                    </div>
                </div>
            `,
            className: 'custom-marker',
            iconSize: [36, 48],
            iconAnchor: [18, 48],
            popupAnchor: [0, -48]
        });
        
        const map = L.map('map').setView([barangayLat, barangayLng], 18);
        
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            minZoom: 3,
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        }).addTo(map);
        
        map.zoomControl.setPosition('bottomright');
        
        L.control.scale({
            metric: true,
            imperial: false,
            position: 'bottomleft'
        }).addTo(map);
        
        const marker = L.marker([barangayLat, barangayLng], { icon: customIcon }).addTo(map);
        
        const popupContent = `
            <div style="text-align: center; padding: 12px; min-width: 260px;">
                <div style="background: linear-gradient(135deg, #ea4335 0%, #c5221f 100%); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                    <i class="fas fa-landmark" style="color: white; font-size: 1.4rem;"></i>
                </div>
                <strong style="color: #ea4335; font-size: 1rem; display: block;">${barangayName}</strong>
                <span style="color: #5f6368; font-size: 0.8rem; display: block; margin: 5px 0;">${barangayAddress}</span>
                <hr style="margin: 10px 0;">
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(barangayAddress)}" target="_blank" style="color: #ea4335; text-decoration: none; font-size: 0.75rem;">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                    <a href="https://www.google.com/maps?q=${encodeURIComponent(barangayAddress)}" target="_blank" style="color: #ea4335; text-decoration: none; font-size: 0.75rem;">
                        <i class="fas fa-street-view"></i> Street View
                    </a>
                </div>
            </div>
        `;
        
        marker.bindPopup(popupContent).openPopup();
        
        L.circle([barangayLat, barangayLng], {
            color: '#ea4335',
            fillColor: '#ea4335',
            fillOpacity: 0.2,
            radius: 80,
            weight: 2
        }).addTo(map);
        
        window.addEventListener('resize', function() {
            setTimeout(() => {
                map.invalidateSize();
            }, 100);
        });
        
        setTimeout(() => {
            map.invalidateSize();
        }, 500);
    });
</script>