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
<meta name="theme-color" content="#004080">

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

</head>
<body>
    <!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="site-wrapper">    
    <!-- Primary Header -->
    <header id="main-header">
        <div class="container">
            <div class="header-left">
                <!-- Hamburger Icon -->
                <div id="hamburger-icon" class="hamburger-icon">
                    <img src="<?= url('public/assets/images/hamburger-icon.svg'); ?>" alt="Hamburger Icon">
                </div>
                <div class="logo">
                    <a href="<?= url(); ?>" aria-label="Visit Phones Dukan - Your go-to store for mobile phones">
                        <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan Logo">
                        <span class="sr-only">Visit Phones Dukan</span>
                    </a>
                </div>
            </div>

            <div class="header-center">
                <!-- Live Search Container -->
                <div class="live-search-container">
                    <input type="text" id="search-input" placeholder="Search products..." autocomplete="off">
                    <button id="desktop-close-results" style="display: none;">✖</button>
                    <ul id="search-results"></ul>
                </div>
            </div>

            <div class="header-right">
                <div class="icons">
                    <a href="<?= url('my-account/'); ?>" class="icon">
                        <img src="<?= url('public/assets/images/my-account.svg'); ?>" alt="My Account">
                    </a>
                    <a href="<?= url('cart'); ?>" class="icon">
                        <img src="<?= url('public/assets/images/cart_icon.svg'); ?>" alt="Cart">
                        <span class="cart-count"><?= $cartCount ?></span>
                    </a>
                    <a href="<?= url('contact-us/'); ?>" class="icon">
                        <img src="<?= url('public/assets/images/customer.svg'); ?>" alt="Support">
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Header -->
    <header id="mobile-header">
        <div class="mobile-container">
            <!-- Left Side: Logo -->
            <div class="mobile-logo">
            <div id="hamburger-icon" class="hamburger-icon">
            <button id="mobile-menu-toggle" class="hamburger-icon" aria-label="Toggle mobile menu">
                    <img src="<?= url('public/assets/images/menu_icon.svg'); ?>" alt="Menu">
                </button>                </div>
                <div class="m-logo">
                    <a href="<?= url(); ?>" aria-label="Visit Phones Dukan - Your go-to store for mobile phones">
                        <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan Logo">
                        <span class="sr-only">Visit Phones Dukan</span>
                    </a>
                </div>
            </div>

            <!-- Right Side: Icons -->
            <div class="mobile-icons">
                <a href="#" class="icon mobile-icon" id="mobile-search-icon" aria-label="Search">
                    <img src="<?= url('public/assets/images/search_icon.svg'); ?>" alt="Search">
                </a>
                <a href="<?= url('cart'); ?>" class="mobile-icon" aria-label="View Cart">
                    <img src="<?= url('public/assets/images/cart_icon.svg'); ?>" alt="Cart">
                    <span class="cart-count"><?= $cartCount ?></span>
                </a>
                <a href="<?= url('my-account'); ?>" class="mobile-icon" aria-label="My Account">
                    <img src="<?= url('public/assets/images/my-account-mobile.svg'); ?>" alt="my_account">
                </a>
            </div>
        </div>

        <!-- Mobile Live Search -->
        <div class="mobile-live-search-container" style="display: none;">
            <button id="mobile-close-search" class="mobile-close-search">X</button>
            <input type="text" id="mobile-search-input" placeholder="Search products..." />
            <div id="mobile-search-results" class="search-results"></div>
        </div>
    </header>
    
    <main class="content">