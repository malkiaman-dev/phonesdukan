<?php
ob_start(); // Start output buffering
require_once dirname(__DIR__, 1) . '/app/Models/CartModel.php';
require_once dirname(__DIR__, 1) . '/app/config/session.php';
require_once dirname(__DIR__, 1) . '/includes/functions.php';
if (!isset($pageTitle)) {
    $pageTitle = null;
}
if (!isset($metaDescription)) {
    $metaDescription = null;
}
if (!isset($metaKeywords)) {
    $metaKeywords = null;
}
if (!isset($metaRobots)) {
    $metaRobots = null;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$pageUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . url(ltrim(getRequestPath(), '/'));
$ogImage = getBaseURL() . "public/assets/images/Phones_dukan_favicon.png";

// Initialize CartModel and fetch cart items
$cartModel = new CartModel();
$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;
$cartItems = $cartModel->fetchCartItems($sessionId, $userId);
$cartCount = 0;
foreach ($cartItems as $item) {
    $cartCount += (int)$item['total_quantity'];
}
?>
<?php
// These should ideally be passed from controller
// Initialize default values in case the product is not set
$productPrice = isset($product['product_price']) ? $product['product_price'] : null;
$productCurrency = 'PKR';
$productAvailability = isset($product['product_status']) && $product['product_status'] == 1 ? 'instock' : 'outofstock';
$productImage = isset($images[0]['image_url']) ? getBaseURL() . ltrim($images[0]['image_url'], '/') : $ogImage;
$productImageAlt = isset($product['product_name']) ? $product['product_name'] : 'Phones Dukan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required Meta -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<meta name="description" content="<?= $metaDescription ?>">
<?php if ($metaKeywords): ?>
<meta name="keywords" content="<?= $metaKeywords ?>">
<?php endif; ?>
<meta name="robots" content="<?= $metaRobots ?>">
<link rel="canonical" href="<?= $pageUrl ?>">

<!-- Open Graph Tags -->
<meta property="og:title" content="<?= $pageTitle ?>">
<meta property="og:description" content="<?= $metaDescription ?>">
<meta property="og:image" content="<?= htmlspecialchars($productImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/webp"> <!-- Adjust if using WebP -->
<meta property="og:url" content="<?= $pageUrl ?>">
<meta property="og:type" content="<?= isset($product) && is_array($product) ? 'product' : 'website' ?>">
<meta property="og:site_name" content="Phones Dukan">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $pageTitle ?>">
<meta name="twitter:description" content="<?= $metaDescription ?>">
<meta name="twitter:image" content="<?= isset($product) && is_array($product) ? $productImage : $ogImage ?>">
<meta name="twitter:image:width" content="1200">
<meta name="twitter:image:height" content="630">
<meta name="twitter:image:type" content="image/webp"> <!-- Adjust if using WebP -->
<meta name="twitter:site" content="@phonesdukan">

<!-- Favicon -->
<link rel="icon" href="<?= getBaseURL(); ?>public/assets/images/Phones_dukan_favicon.png" type="image/x-icon">

<!-- Author & Theme -->
<meta name="author" content="Phones Dukan">
<meta name="theme-color" content="#F7D117">

<?php if (isset($product) && is_array($product)): ?>
<!-- Enhanced Open Graph -->
<meta property="og:image:alt" content="<?= htmlspecialchars($productImageAlt) ?>">
<meta property="og:image:width" content="500">
<meta property="og:image:height" content="500">
<meta property="og:image:type" content="image/webp">

<!-- Product Structured Meta -->
<meta property="product:price:amount" content="<?= $formattedProductPrice ?>">
<meta property="product:price:currency" content="<?= $productCurrency ?>">
<meta property="product:availability" content="<?= $productAvailability ?>">

<!-- Twitter Enhanced Tags -->
<meta name="twitter:label1" content="Price">
<meta name="twitter:data1" content="₨ <?= $formattedProductPrice ?>">
<meta name="twitter:label2" content="Availability">
<meta name="twitter:data2" content="<?= ucfirst($productAvailability == 'instock' ? 'In stock' : 'Out of stock') ?>">
<?php endif; ?>

<!-- Load Styles -->
<?php loadCSS(); ?>
<link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/style.css">
<link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/frontend/header.css">
<link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/frontend/footer.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">

</head>
<body>
    <!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="site-wrapper">
    <div class="pd-header-stack">
        <div class="pd-announcement-bar" role="region" aria-label="Store announcements">
            <div class="pd-announcement-track">
                <span><strong>Mobile Island</strong> Official Store • We Believe in Satisfaction</span>
                <span>Free delivery across Pakistan on selected products</span>
                <span>Call / WhatsApp: <strong>+92 311 6600031</strong></span>
                <span><strong>Mobile Island</strong> Official Store • We Believe in Satisfaction</span>
                <span>Free delivery across Pakistan on selected products</span>
                <span>Call / WhatsApp: <strong>+92 311 6600031</strong></span>
            </div>
        </div>

        <!-- Primary Header -->
        <header id="main-header" class="pd-main-header">
            <div class="pd-header-container">
                <div class="pd-header-left">
                    <button id="hamburger-icon" class="hamburger-icon pd-icon-btn" type="button" aria-label="Open menu">
                        <img src="<?= url('public/assets/images/hamburger-icon.svg'); ?>" alt="Open Menu">
                    </button>
                    <div class="logo">
                        <a href="<?= url(); ?>" aria-label="Visit Phones Dukan - Your go-to store for mobile phones">
                            <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan Logo">
                            <span class="sr-only">Visit Phones Dukan</span>
                        </a>
                    </div>
                </div>

                <div class="pd-header-center">
                    <div class="live-search-container">
                        <input type="text" id="search-input" placeholder="Search mobiles, accessories, earbuds..." autocomplete="off" aria-label="Search products">
                        <button id="desktop-close-results" type="button" aria-label="Clear search">✕</button>
                        <ul id="search-results" aria-live="polite"></ul>
                    </div>
                </div>

                <div class="pd-header-right">
                    <div class="icons">
                        <a href="<?= url('my-account/'); ?>" class="icon pd-action-icon" aria-label="My Account">
                            <img src="<?= url('public/assets/images/my-account.svg'); ?>" alt="My Account">
                            <span class="pd-action-label">Account</span>
                        </a>
                        <a href="<?= url('cart'); ?>" class="icon pd-action-icon" aria-label="View Cart">
                            <img src="<?= url('public/assets/images/cart_icon.svg'); ?>" alt="Cart">
                            <span class="pd-action-label">Cart</span>
                            <span class="cart-count"><?= $cartCount ?></span>
                        </a>
                        <a href="<?= url('contact-us/'); ?>" class="icon pd-action-icon" aria-label="Support">
                            <img src="<?= url('public/assets/images/customer.svg'); ?>" alt="Support">
                            <span class="pd-action-label">Support</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Header -->
        <header id="mobile-header" class="pd-mobile-header">
            <div class="pd-mobile-container">
                <div class="pd-mobile-left">
                    <button id="mobile-menu-toggle" class="hamburger-icon pd-icon-btn" type="button" aria-label="Toggle mobile menu">
                        <img src="<?= url('public/assets/images/menu_icon.svg'); ?>" alt="Menu">
                    </button>

                    <div class="m-logo">
                        <a href="<?= url(); ?>" aria-label="Visit Phones Dukan - Your go-to store for mobile phones">
                            <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan Logo">
                            <span class="sr-only">Visit Phones Dukan</span>
                        </a>
                    </div>
                </div>

                <div class="mobile-icons">
                    <a href="#" class="icon mobile-icon" id="mobile-search-icon" aria-label="Search">
                        <img src="<?= url('public/assets/images/search_icon.svg'); ?>" alt="Search">
                    </a>
                    <a href="<?= url('cart'); ?>" class="mobile-icon icon" aria-label="View Cart">
                        <img src="<?= url('public/assets/images/cart_icon.svg'); ?>" alt="Cart">
                        <span class="cart-count"><?= $cartCount ?></span>
                    </a>
                    <a href="<?= url('my-account/'); ?>" class="mobile-icon icon" aria-label="My Account">
                        <img src="<?= url('public/assets/images/my-account-mobile.svg'); ?>" alt="My Account">
                    </a>
                </div>
            </div>

            <div class="mobile-live-search-container" style="display: none;">
                <button id="mobile-close-search" class="mobile-close-search" aria-label="Close search">✕</button>
                <input type="text" id="mobile-search-input" placeholder="Search products..." autocomplete="off">
                <div id="mobile-search-results" class="search-results"></div>
            </div>
        </header>

        <div class="pd-trust-strip" role="region" aria-label="Store trust highlights">
            <div class="pd-trust-container">
                <div class="pd-trust-item">
                    <span class="pd-trust-dot" aria-hidden="true">●</span>
                    <p>Fast &amp; Free Delivery Over Order <strong>Rs. 3000/-</strong> Only.</p>
                </div>
                <div class="pd-trust-item">
                    <span class="pd-trust-dot" aria-hidden="true">●</span>
                    <p><strong>30M+</strong> Happy Customers</p>
                </div>
                <div class="pd-trust-item">
                    <span class="pd-trust-dot" aria-hidden="true">●</span>
                    <p><strong>7 Days</strong> Replacement &amp; <strong>1 Year</strong> Warranty</p>
                </div>
            </div>
        </div>
    </div>

    <main class="content">