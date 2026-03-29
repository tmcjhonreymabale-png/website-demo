<?php
// pages/home.php

// Set page title
$page_title = "Home | " . ($barangay_name ?? 'Barangay System');

// Database connection is already established in header.php
// Get database connection if needed for additional queries
$database = new Database();
$db = $database->getConnection();

// Fetch page content from pages table
$page_query = "SELECT * FROM pages WHERE page_name = 'home'";
$page_stmt = $db->prepare($page_query);
$page_stmt->execute();
$home_page = $page_stmt->fetch(PDO::FETCH_ASSOC);

// Use fetched content or default
$home_title = $home_page['title'] ?? 'Welcome to Barangay System';
$home_content = $home_page['content'] ?? '<h1>Welcome to Our Barangay</h1><p>This is the official website of Barangay Cabuco. We are committed to serving our community with excellence and transparency.</p>';
$home_meta = $home_page['meta_description'] ?? '';

// Fetch recent announcements
$query = "SELECT * FROM announcements WHERE status = 'active' ORDER BY created_at DESC LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total households
$household_query = "SELECT COUNT(DISTINCT address) as total FROM users WHERE user_type = 'resident' AND address IS NOT NULL AND address != ''";
$household_stmt = $db->prepare($household_query);
$household_stmt->execute();
$total_households = $household_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// If no households found, set default or use residents count as fallback
if ($total_households == 0) {
    $resident_count_query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'resident'";
    $resident_count_stmt = $db->prepare($resident_count_query);
    $resident_count_stmt->execute();
    $total_residents_count = $resident_count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_households = $total_residents_count;
}

// Total residents - custom number
$total_residents = 38621;

// Fetch total active services
$services_query = "SELECT COUNT(*) as total FROM services WHERE is_active = 1";
$services_stmt = $db->prepare($services_query);
$services_stmt->execute();
$total_services = $services_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Fetch total announcements
$announcements_count_query = "SELECT COUNT(*) as total FROM announcements WHERE status = 'active'";
$announcements_count_stmt = $db->prepare($announcements_count_query);
$announcements_count_stmt->execute();
$total_announcements = $announcements_count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Fetch carousel images from database
$carousel_images = [];
try {
    $table_check = $db->query("SHOW TABLES LIKE 'carousel_images'");
    if ($table_check->rowCount() > 0) {
        $carousel_query = "SELECT * FROM carousel_images WHERE is_active = 1 ORDER BY display_order, id ASC";
        $carousel_stmt = $db->prepare($carousel_query);
        $carousel_stmt->execute();
        $carousel_images = $carousel_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $carousel_images = [];
}

if (empty($carousel_images)) {
    $carousel_images = [
        ['image_path' => '', 'title' => $home_title, 'caption' => 'Your trusted partner in community development'],
    ];
}
?>

<style>
    .home-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }
    
    /* Hero Section - Carousel */
    .hero-section {
        margin-bottom: 3rem;
        border-radius: 24px;
        overflow: hidden;
        position: relative;
    }
    
    .carousel-container {
        position: relative;
        width: 100%;
        overflow: hidden;
        border-radius: 24px;
    }
    
    .carousel-slides {
        display: flex;
        transition: transform 0.5s ease-in-out;
    }
    
    .carousel-slide {
        min-width: 100%;
        position: relative;
    }
    
    .carousel-slide img {
        width: 100%;
        height: 400px;
        object-fit: cover;
    }
    
    .carousel-caption {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    
    .carousel-caption h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
        opacity: 0.9;
    }
    
    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.5);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        z-index: 10;
    }
    
    .carousel-btn:hover {
        background: rgba(0,0,0,0.8);
    }
    
    .carousel-btn.prev {
        left: 20px;
    }
    
    .carousel-btn.next {
        right: 20px;
    }
    
    .carousel-dots {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 10;
    }
    
    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255,255,255,0.5);
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .dot.active {
        background: white;
        width: 25px;
        border-radius: 5px;
    }
    
    /* Welcome Section */
    .welcome-section {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        margin-bottom: 3rem;
        text-align: center;
        border: 1px solid #eef2f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .welcome-section h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
    }
    
    .welcome-section .content {
        color: #475569;
        line-height: 1.6;
        max-width: 800px;
        margin: 0 auto;
    }
    
    /* Stats Grid - 2 cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 3rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s;
        border: 1px solid #eef2f6;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        background: #f0f4ff;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    
    .stat-icon i {
        font-size: 1.5rem;
        color: #667eea;
    }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    /* Section Title */
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 1.5rem;
        color: #1e293b;
    }
    
    /* Announcements Grid */
    .announcements-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .announcement-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s;
        cursor: pointer;
        border: 1px solid #eef2f6;
    }
    
    .announcement-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .announcement-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0.4rem 1rem;
        display: inline-block;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .announcement-content {
        padding: 1.2rem;
    }
    
    .announcement-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .announcement-meta {
        margin-bottom: 0.75rem;
        color: #94a3b8;
        font-size: 0.7rem;
    }
    
    .announcement-card p {
        color: #475569;
        line-height: 1.5;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .read-more {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .read-more:hover {
        gap: 0.5rem;
    }
    
    /* Quick Actions */
    .quick-actions {
        background: #f8fafc;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        border: 1px solid #eef2f6;
    }
    
    .quick-actions h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .quick-actions p {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 1.2rem;
    }
    
    .actions-grid {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .action-btn {
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
        background: white;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-size: 0.85rem;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        border-color: #667eea;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    /* Empty States */
    .empty-state {
        text-align: center;
        padding: 2rem;
        background: white;
        border-radius: 20px;
        border: 1px solid #eef2f6;
    }
    
    .empty-state i {
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #64748b;
        font-size: 0.85rem;
    }
    
    /* ===== RESPONSIVE BREAKPOINTS ===== */
    
    /* Tablet Landscape (1024px) */
    @media (max-width: 1024px) {
        .announcements-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .carousel-slide img {
            height: 350px;
        }
        
        .carousel-caption h2 {
            font-size: 1.5rem;
        }
    }
    
    /* Tablet Portrait (768px) */
    @media (max-width: 768px) {
        .home-page {
            padding: 1.5rem 1rem;
        }
        
        .hero-section {
            margin-bottom: 2rem;
        }
        
        .carousel-slide img {
            height: 280px;
        }
        
        .carousel-caption {
            padding: 1rem;
        }
        
        .carousel-caption h2 {
            font-size: 1.3rem;
        }
        
        .carousel-caption p {
            font-size: 0.85rem;
        }
        
        .carousel-btn {
            width: 32px;
            height: 32px;
        }
        
        .welcome-section {
            padding: 1.5rem;
        }
        
        .welcome-section h1 {
            font-size: 1.5rem;
        }
        
        .welcome-section .content {
            font-size: 0.9rem;
        }
        
        .stats-grid {
            gap: 1rem;
            max-width: 500px;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
        }
        
        .stat-icon i {
            font-size: 1.3rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 1.2rem;
        }
        
        .announcements-grid {
            gap: 1rem;
        }
        
        .announcement-content {
            padding: 1rem;
        }
        
        .announcement-card h3 {
            font-size: 1rem;
        }
        
        .quick-actions {
            padding: 1.5rem;
        }
        
        .quick-actions h3 {
            font-size: 1.1rem;
        }
        
        .actions-grid {
            gap: 0.75rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
    }
    
    /* Mobile Large (576px) */
    @media (max-width: 576px) {
        .home-page {
            padding: 1rem;
        }
        
        .hero-section {
            border-radius: 16px;
        }
        
        .carousel-slide img {
            height: 220px;
        }
        
        .carousel-caption h2 {
            font-size: 1.1rem;
        }
        
        .carousel-caption p {
            font-size: 0.75rem;
        }
        
        .carousel-btn {
            width: 28px;
            height: 28px;
        }
        
        .carousel-btn i {
            font-size: 0.8rem;
        }
        
        .carousel-btn.prev {
            left: 10px;
        }
        
        .carousel-btn.next {
            right: 10px;
        }
        
        .welcome-section {
            padding: 1rem;
            border-radius: 16px;
        }
        
        .welcome-section h1 {
            font-size: 1.3rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            max-width: 280px;
        }
        
        .stat-card {
            padding: 0.9rem;
        }
        
        .stat-number {
            font-size: 1.3rem;
        }
        
        .section-title {
            font-size: 1.2rem;
        }
        
        .announcements-grid {
            grid-template-columns: 1fr;
        }
        
        .announcement-card {
            border-radius: 16px;
        }
        
        .actions-grid {
            flex-direction: column;
            align-items: stretch;
        }
        
        .action-btn {
            justify-content: center;
            width: 100%;
        }
        
        .quick-actions {
            padding: 1rem;
            border-radius: 16px;
        }
        
        .quick-actions h3 {
            font-size: 1rem;
        }
        
        .quick-actions p {
            font-size: 0.8rem;
        }
    }
    
    /* Mobile Small (380px) */
    @media (max-width: 380px) {
        .home-page {
            padding: 0.75rem;
        }
        
        .carousel-slide img {
            height: 180px;
        }
        
        .carousel-caption h2 {
            font-size: 0.9rem;
        }
        
        .carousel-caption p {
            font-size: 0.65rem;
        }
        
        .welcome-section h1 {
            font-size: 1.1rem;
        }
        
        .welcome-section .content {
            font-size: 0.8rem;
        }
        
        .stat-number {
            font-size: 1.1rem;
        }
        
        .section-title {
            font-size: 1rem;
        }
        
        .announcement-card h3 {
            font-size: 0.9rem;
        }
        
        .announcement-card p {
            font-size: 0.75rem;
        }
        
        .read-more {
            font-size: 0.7rem;
        }
    }
</style>

<div class="home-page">
    <!-- Hero Carousel Section -->
    <div class="hero-section">
        <div class="carousel-container">
            <div class="carousel-slides" id="carouselSlides">
                <?php foreach ($carousel_images as $index => $slide): ?>
                <div class="carousel-slide">
                    <?php 
                    $image_path = $slide['image_path'] ?? '';
                    $image_title = $slide['title'] ?? $home_title;
                    $image_caption = $slide['caption'] ?? '';
                    
                    if (!empty($image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)): 
                    ?>
                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($image_title); ?>" loading="lazy">
                    <?php else: ?>
                        <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                            <div style="text-align: center; color: white; padding: 1rem;">
                                <i class="fas fa-landmark" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                                <h2><?php echo htmlspecialchars($image_title); ?></h2>
                                <p><?php echo htmlspecialchars($image_caption); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="carousel-caption">
                        <h2><?php echo htmlspecialchars($image_title); ?></h2>
                        <p><?php echo htmlspecialchars($image_caption); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($carousel_images) > 1): ?>
            <button class="carousel-btn prev" id="prevBtn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-btn next" id="nextBtn">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="carousel-dots" id="carouselDots">
                <?php foreach ($carousel_images as $index => $slide): ?>
                <div class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Welcome Section - Dynamic from Page Management -->
    <div class="welcome-section">
        <?php echo $home_content; ?>
    </div>
    
    <!-- Statistics - Only Residents and Households -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo number_format($total_residents); ?></div>
            <div class="stat-label">Total Residents</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-number"><?php echo number_format($total_households); ?></div>
            <div class="stat-label">Households</div>
        </div>
    </div>
    
    <!-- Latest Announcements -->
    <h2 class="section-title">Latest Updates</h2>
    <div class="announcements-grid">
        <?php if (count($announcements) > 0): ?>
            <?php foreach (array_slice($announcements, 0, 3) as $announcement): ?>
                <div class="announcement-card" onclick="window.location.href='index.php?page=announcements&id=<?php echo $announcement['id']; ?>'">
                    <div class="announcement-badge">
                        <i class="fas fa-bullhorn"></i> Announcement
                    </div>
                    <div class="announcement-content">
                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <div class="announcement-meta">
                            <i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                        </div>
                        <p><?php echo substr(htmlspecialchars($announcement['content']), 0, 100); ?>...</p>
                        <a href="index.php?page=announcements&id=<?php echo $announcement['id']; ?>" class="read-more">
                            Read more <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-newspaper"></i>
                <p>No announcements yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Need help?</h3>
        <p>We're here to assist you with your concerns</p>
        <div class="actions-grid">
            <a href="/testweb/index.php?page=services" class="action-btn">
                <i class="fas fa-concierge-bell"></i> Browse Services
            </a>
            <a href="/testweb/index.php?page=announcements" class="action-btn">
                <i class="fas fa-bullhorn"></i> View Updates
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/testweb/resident/my_requests.php" class="action-btn">
                    <i class="fas fa-file-alt"></i> My Requests
                </a>
                <a href="/testweb/resident/profile.php" class="action-btn">
                    <i class="fas fa-user"></i> My Profile
                </a>
            <?php else: ?>
                <a href="/testweb/auth/register.php" class="action-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <a href="/testweb/auth/login.php" class="action-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Carousel functionality
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const slidesContainer = document.getElementById('carouselSlides');
    let autoSlideInterval;
    
    if (slides.length > 1) {
        function updateCarousel() {
            if (slidesContainer) {
                slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
            }
            
            dots.forEach((dot, index) => {
                if (index === currentSlide) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            updateCarousel();
            resetAutoSlide();
        }
        
        function prevSlide() {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateCarousel();
            resetAutoSlide();
        }
        
        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
            resetAutoSlide();
        }
        
        function startAutoSlide() {
            autoSlideInterval = setInterval(nextSlide, 5000);
        }
        
        function resetAutoSlide() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }
        
        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);
        
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const index = parseInt(dot.getAttribute('data-index'));
                goToSlide(index);
            });
        });
        
        const carousel = document.querySelector('.carousel-container');
        if (carousel) {
            carousel.addEventListener('mouseenter', () => {
                clearInterval(autoSlideInterval);
            });
            
            carousel.addEventListener('mouseleave', () => {
                startAutoSlide();
            });
            
            // Touch support for mobile
            let touchStartX = 0;
            let touchEndX = 0;
            
            carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            carousel.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchEndX < touchStartX - 50) {
                    nextSlide();
                }
                if (touchEndX > touchStartX + 50) {
                    prevSlide();
                }
            });
        }
        
        startAutoSlide();
    }
</script>