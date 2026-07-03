<?php
ob_start(); // Start output buffering
require_once dirname(__DIR__, 1) . '/app/Models/CartModel.php';
require_once dirname(__DIR__, 1) . '/app/config/session.php';
require_once dirname(__DIR__, 1) . '/app/config/wholesale_config.php';
require_once dirname(__DIR__, 1) . '/includes/functions.php';
$wholesaleAccessGranted = wholesaleHasAccess();
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

// Fetch logged-in user's avatar data for header
$headerUserPhoto    = '';
$headerUserInitials = '';
if ($userId) {
    try {
        require_once dirname(__DIR__, 1) . '/database/db.php';
        $_hdb   = (new Database())->getConnection();
        $_hstmt = $_hdb->prepare('SELECT full_name, profile_photo FROM users WHERE user_id = :id');
        $_hstmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $_hstmt->execute();
        $_hu = $_hstmt->fetch(PDO::FETCH_ASSOC);
        if ($_hu) {
            $headerUserPhoto = $_hu['profile_photo'] ?? '';
            $name = trim($_hu['full_name'] ?? '');
            foreach (explode(' ', $name) as $p) {
                $headerUserInitials .= strtoupper(mb_substr($p, 0, 1));
            }
            $headerUserInitials = mb_substr($headerUserInitials, 0, 2);
            $headerUserFirstName = explode(' ', $name)[0] ?? 'Account';
        }
    } catch (Exception $_e) {}
}
?>
<?php
// Compute product price/availability for OG/Twitter meta (used on product pages only)
if (isset($product) && is_array($product)) {
    $salePrice    = isset($product['sale_price'])    && is_numeric($product['sale_price'])    ? (float)$product['sale_price']    : 0;
    $regularPrice = isset($product['regular_price']) && is_numeric($product['regular_price']) ? (float)$product['regular_price'] : 0;
    $productPrice = ($salePrice > 0 && $salePrice < $regularPrice) ? $salePrice : $regularPrice;
} else {
    $productPrice = null;
}
if (!isset($formattedProductPrice)) {
    $formattedProductPrice = $productPrice > 0 ? number_format((float)$productPrice, 2) : '0.00';
}
$productCurrency     = 'PKR';
$stockQty            = isset($product['stock_quantity']) && is_numeric($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
$productAvailability = (isset($product['product_status']) && (int)$product['product_status'] === 1 && $stockQty > 0) ? 'instock' : 'outofstock';
$productImage        = isset($images[0]['image_url']) ? getBaseURL() . ltrim($images[0]['image_url'], '/') : $ogImage;
$productImageAlt     = isset($product['product_name']) ? $product['product_name'] : 'Phones Dukan';
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
<?php if (!empty($lcpPreload)): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($lcpPreload) ?>" fetchpriority="high">
<?php endif; ?>

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
<link rel="apple-touch-icon" href="<?= getBaseURL(); ?>public/assets/images/Phones_dukan_favicon.png">

<!-- Author & Theme -->
<meta name="author" content="Phones Dukan">
<meta name="theme-color" content="#F7D117">
<meta name="format-detection" content="telephone=no">

<!-- Pakistan geo targeting -->
<meta name="geo.region" content="PK-IS">
<meta name="geo.placename" content="Islamabad, Pakistan">
<meta name="geo.position" content="33.6682924;72.9984135">
<meta name="ICBM" content="33.6682924, 72.9984135">

<!-- Resource hints — preconnect to critical third-party origins -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if (!isPhonesDukanApp()) : ?>
<link rel="dns-prefetch" href="//www.googletagmanager.com">
<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">
<link rel="dns-prefetch" href="//cdn.onesignal.com">
<link rel="dns-prefetch" href="//www.clarity.ms">
<?php endif; ?>

<script>
window.__PD_BASE_PATH__ = <?= json_encode(rtrim(getBaseURL(), '/')) ?>;
window.__PD_WHOLESALE_ACCESS__ = <?= $wholesaleAccessGranted ? 'true' : 'false' ?>;
</script>

<!-- ── Global Schema.org JSON-LD ─────────────────────────────────────── -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "@id": "https://www.phonesdukan.com/#website",
  "name": "Phones Dukan",
  "alternateName": "PhonesDukan",
  "url": "https://www.phonesdukan.com/",
  "description": "Pakistan’s trusted online mobile store for PTA-approved smartphones, smart watches, earbuds, and accessories.",
  "inLanguage": "en-PK",
  "publisher": { "@id": "https://www.phonesdukan.com/#organization" },
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://www.phonesdukan.com/shop?query={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://www.phonesdukan.com/#organization",
  "name": "Phones Dukan",
  "alternateName": ["PhonesDukan", "Mobile Island"],
  "url": "https://www.phonesdukan.com/",
  "logo": {
    "@type": "ImageObject",
    "@id": "https://www.phonesdukan.com/#logo",
    "url": "https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp",
    "contentUrl": "https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp",
    "width": 512,
    "height": 120,
    "caption": "Phones Dukan"
  },
  "image": { "@id": "https://www.phonesdukan.com/#logo" },
  "description": "Phones Dukan (Mobile Island) is Pakistan’s trusted online store for PTA-approved smartphones, smart watches, wireless earbuds, mobile accessories, power banks, and Bluetooth speakers.",
  "email": "info@phonesdukan.com",
  "telephone": "+92-311-6600031",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Al-Ghaffar Shopping Mall, Shop 13B, G-11 Markaz",
    "addressLocality": "Islamabad",
    "addressRegion": "Islamabad Capital Territory",
    "postalCode": "44000",
    "addressCountry": "PK"
  },
  "sameAs": [
    "https://www.facebook.com/mobileisland01/",
    "https://www.youtube.com/@mobileisland",
    "https://www.instagram.com/mobile_island01/",
    "https://www.tiktok.com/@mobile_island_g11_isb"
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": ["Store", "MobilePhoneStore", "LocalBusiness"],
  "@id": "https://www.phonesdukan.com/#localbusiness",
  "name": "Phones Dukan — Mobile Island",
  "image": "https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp",
  "url": "https://www.phonesdukan.com/",
  "telephone": "+92-311-6600031",
  "email": "info@phonesdukan.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Al-Ghaffar Shopping Mall, Shop 13B, G-11 Markaz",
    "addressLocality": "Islamabad",
    "addressRegion": "Islamabad Capital Territory",
    "postalCode": "44000",
    "addressCountry": "PK"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 33.6682924,
    "longitude": 72.9984135
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
      "opens": "10:00",
      "closes": "21:00"
    }
  ],
  "priceRange": "PKR",
  "currenciesAccepted": "PKR",
  "paymentAccepted": "Cash, Credit Card, Debit Card, JazzCash, EasyPaisa",
  "areaServed": { "@type": "Country", "name": "Pakistan" },
  "hasMap": "https://www.google.com/maps/dir//Al-ghaffar+shoping+mall,+G-11+Markaz+G+11+Markaz+G-11,+Islamabad,+Islamabad+Capital+Territory+44000",
  "parentOrganization": { "@id": "https://www.phonesdukan.com/#organization" }
}
</script>
<?php if (isset($breadcrumbs) && is_array($breadcrumbs) && count($breadcrumbs) > 0): ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => array_map(function($crumb, $idx) {
        $item = [
            '@type'    => 'ListItem',
            'position' => $idx + 1,
            'name'     => $crumb['name'],
        ];
        if (!empty($crumb['url'])) {
            $item['item'] = $crumb['url'];
        }
        return $item;
    }, $breadcrumbs, array_keys($breadcrumbs))
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>

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
<link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/frontend/header.css">
<link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/frontend/footer.css">
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet"></noscript>

</head>
<body class="<?= isPhonesDukanApp() ? 'pd-in-app' : '' ?>">

    <!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="site-wrapper">

    <div id="pd-site-chrome" class="pd-site-chrome">
        <div class="pd-chrome-safe-fill" aria-hidden="true"></div>
        <div class="pd-status-bar-slot" aria-hidden="true"></div>

    <!-- Scrollable bars — NOT sticky, scroll away naturally -->
    <div class="pd-top-bars">
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

        <!-- stripe Section  -->

        <!-- <div class="pd-trust-strip" role="region" aria-label="Store trust highlights">
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
        </div> -->
    </div>

    <!-- Sticky navbar only -->
    <div class="pd-header-stack">
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
                        <button id="desktop-search-btn" class="pd-search-btn" type="button" aria-label="Search">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </button>
                        <ul id="search-results" aria-live="polite"></ul>
                    </div>
                </div>

                <div class="pd-header-right">
                    <div class="icons">
                        <a href="<?= url('my-account/'); ?>" class="icon pd-action-icon <?= $userId ? 'pd-account-loggedin' : '' ?>" aria-label="My Account">
                            <?php if ($userId): ?>
                                <span class="pd-header-avatar">
                                    <?php if ($headerUserPhoto): ?>
                                        <img src="<?= url(htmlspecialchars($headerUserPhoto)) ?>" alt="<?= htmlspecialchars($headerUserInitials) ?>">
                                    <?php else: ?>
                                        <span><?= htmlspecialchars($headerUserInitials) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <img src="<?= url('public/assets/images/my-account.svg'); ?>" alt="My Account">
                            <?php endif; ?>
                            <span class="pd-action-label"><?= $userId ? htmlspecialchars($headerUserFirstName) : 'Account' ?></span>
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
                    <a href="<?= url('my-account/'); ?>" class="mobile-icon icon <?= $userId ? 'pd-account-loggedin' : '' ?>" aria-label="My Account">
                        <?php if ($userId): ?>
                            <span class="pd-header-avatar pd-header-avatar--sm">
                                <?php if ($headerUserPhoto): ?>
                                    <img src="<?= url(htmlspecialchars($headerUserPhoto)) ?>" alt="<?= htmlspecialchars($headerUserInitials) ?>">
                                <?php else: ?>
                                    <span><?= htmlspecialchars($headerUserInitials) ?></span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <img src="<?= url('public/assets/images/my-account-mobile.svg'); ?>" alt="My Account">
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <div class="mobile-live-search-container" style="display: none;">
                <button id="mobile-close-search" class="mobile-close-search" aria-label="Close search">✕</button>
                <input type="text" id="mobile-search-input" placeholder="Search products..." autocomplete="off">
                <div id="mobile-search-results" class="search-results"></div>
            </div>
        </header>

    </div>
    </div><!-- /#pd-site-chrome -->

    <div class="pd-chrome-spacer" aria-hidden="true"></div>

    <main class="content">