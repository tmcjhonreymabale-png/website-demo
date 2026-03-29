<?php
// index.php - Main routing file

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', __DIR__);

// Include header
require_once BASE_PATH . '/includes/header.php';

// Get the page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define allowed pages
$allowed_pages = ['home', 'announcements', 'services', 'about'];

// Check if page is allowed and exists
if (in_array($page, $allowed_pages)) {
    $page_file = BASE_PATH . '/pages/' . $page . '.php';
    if (file_exists($page_file)) {
        include $page_file;
    } else {
        include BASE_PATH . '/pages/home.php';
    }
} else {
    include BASE_PATH . '/pages/home.php';
}

// Include footer
require_once BASE_PATH . '/includes/footer.php';
?>