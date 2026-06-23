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
$isPdApp             = (!empty($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'PhonesDukanApp') !== false)
    || (isset($_GET['pd_app']) && (string) $_GET['pd_app'] === '1');
?>
<!DOCTYPE html>
<html lang="en"<?= $isPdApp ? ' data-pd-app="1"' : '' ?>>
<head>
    <!-- Required Meta -->
<meta charset="UTF-8">
<script>
(function () {
    var ua = navigator.userAgent || "";
    var qs = typeof location !== "undefined" ? location.search : "";
    var stored = false;
    try { stored = localStorage.getItem("pd_app") === "1"; } catch (e) {}
    var nativeApp = false;
    try {
        nativeApp = !!(window.PhonesDukanNative && window.PhonesDukanNative.isApp && window.PhonesDukanNative.isApp());
    } catch (e) {}
    if (/PhonesDukanApp/i.test(ua) || /[?&]pd_app=1(?:&|$)/.test(qs) || stored || nativeApp) {
        document.documentElement.setAttribute("data-pd-app", "1");
        try { localStorage.setItem("pd_app", "1"); } catch (e) {}
    }
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<style id="pd-safe-top-fix">
:root {
    --pd-chrome-pad-top: 0px;
    --announcement-height: 36px;
    --header-height: 72px;
    --pd-chrome-offset: calc(var(--pd-chrome-pad-top) + var(--announcement-height) + var(--header-height));
}
@media (max-width: 992px) {
    :root {
        --header-height: 58px;
        --announcement-height: 36px;
        --pd-chrome-pad-top: 0px;
        --pd-chrome-offset: calc(var(--pd-chrome-pad-top) + var(--announcement-height) + var(--header-height));
    }
    html[data-pd-app="1"] #pd-site-chrome {
        top: 0 !important;
    }
    html[data-pd-app="1"] #pd-site-chrome .pd-top-bars {
        display: block !important;
        height: auto !important;
        overflow: hidden !important;
    }
    html[data-pd-app="1"] #pd-site-chrome .pd-announcement-bar {
        display: block !important;
        position: relative !important;
        top: auto !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    html[data-pd-app="1"] #pd-site-chrome .pd-announcement-track {
        display: flex !important;
        flex-wrap: nowrap !important;
        background: linear-gradient(90deg, #ffe65a 0%, #f7d117 40%, #d4af00 100%) !important;
        min-height: var(--announcement-height) !important;
        will-change: transform !important;
    }
    html[data-pd-app="1"] #pd-site-chrome .pd-announcement-track.pd-marquee-active {
        animation: pdTicker var(--pd-ticker-duration, 24s) linear infinite !important;
    }
    html[data-pd-app="1"] #pd-install-app-btn,
    html[data-pd-app="1"] #pd-install-app-panel {
        display: none !important;
        visibility: hidden !important;
    }
    #pd-site-chrome {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 10001 !important;
        padding-top: 0 !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        background: var(--mi-soft-black);
    }
    #pd-site-chrome .pd-chrome-safe-fill {
        display: none !important;
        height: 0 !important;
        min-height: 0 !important;
    }
    html[data-pd-app="1"] #pd-site-chrome .pd-chrome-safe-fill {
        display: block !important;
        width: 100% !important;
        height: var(--pd-chrome-pad-top) !important;
        min-height: var(--pd-chrome-pad-top) !important;
        background: linear-gradient(90deg, #ffe65a 0%, #f7d117 40%, #d4af00 100%) !important;
        flex-shrink: 0 !important;
    }
    #pd-site-chrome .pd-status-bar-slot {
        display: none !important;
        height: 0 !important;
        visibility: hidden !important;
    }
    #pd-site-chrome .pd-safe-area-top {
        display: none !important;
    }
    #pd-site-chrome .pd-top-bars {
        height: auto !important;
        overflow: hidden !important;
    }
    #pd-site-chrome .pd-announcement-bar,
    #pd-site-chrome .pd-header-stack {
        position: relative !important;
        top: auto !important;
        left: auto !important;
        right: auto !important;
        width: 100% !important;
    }
    html[data-pd-app="1"] #pd-install-app-btn,
    html[data-pd-app="1"] #pd-install-app-panel {
        display: none !important;
    }
}
@media (max-width: 575px) {
    :root {
        --announcement-height: 34px;
    }
}
@media (min-width: 993px) {
    #pd-site-chrome {
        display: contents;
    }
    .pd-status-bar-slot {
        display: none;
    }
}
.pd-announcement-track {
    background: linear-gradient(90deg, #ffe65a 0%, #f7d117 40%, #d4af00 100%);
}
html[data-pd-app="1"] #pd-install-app-btn,
html[data-pd-app="1"] #pd-install-app-panel {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: absolute !important;
    left: -9999px !important;
    width: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
}
</style>
<script>
(function () {
    var MOBILE_MIN = 36;
    var MOBILE_QUERY = "(max-width: 992px)";
    function isMobile() {
        return window.matchMedia && window.matchMedia(MOBILE_QUERY).matches;
    }
    function isTouchMobile() {
        return isMobile() || ((navigator.maxTouchPoints || 0) > 0 && innerWidth <= 992);
    }
    function isApp() {
        var ua = navigator.userAgent || "";
        if (/PhonesDukanApp/i.test(ua)) return true;
        if (/[?&]pd_app=1(?:&|$)/.test(location.search || "")) return true;
        try {
            if (localStorage.getItem("pd_app") === "1") return true;
        } catch (e) {}
        try {
            if (window.PhonesDukanNative && window.PhonesDukanNative.isApp && window.PhonesDukanNative.isApp()) return true;
        } catch (e) {}
        return document.documentElement.getAttribute("data-pd-app") === "1";
    }
    function readNativeInset() {
        try {
            if (window.PhonesDukanNative && typeof window.PhonesDukanNative.getStatusBarHeight === "function") {
                var nativePx = parseFloat(window.PhonesDukanNative.getStatusBarHeight());
                if (nativePx > 0) return nativePx;
            }
        } catch (e) {}
        return 0;
    }
    function measureEnvInset() {
        var root = document.documentElement;
        var probe = document.createElement("div");
        probe.style.cssText = "position:fixed;padding-top:env(safe-area-inset-top);visibility:hidden;pointer-events:none;";
        root.appendChild(probe);
        var inset = probe.offsetHeight || parseFloat(getComputedStyle(probe).paddingTop) || 0;
        probe.remove();
        return inset;
    }
    function estimateInset() {
        var nativeInset = readNativeInset();
        if (nativeInset > 0) return nativeInset;
        var ua = navigator.userAgent || "";
        var dpr = devicePixelRatio || 1;
        if (/Android/i.test(ua)) {
            if (dpr >= 3) return 36;
            if (dpr >= 2.5) return 34;
            return MOBILE_MIN;
        }
        if (/iPhone|iPod|iPad/i.test(ua)) {
            var longSide = Math.max(screen.width, screen.height);
            var portrait = innerHeight >= innerWidth;
            if (longSide >= 812) return portrait ? 47 : 21;
            return portrait ? 20 : MOBILE_MIN;
        }
        return MOBILE_MIN;
    }
    function applyChromeInset() {
        if (window.PDSafeArea && window.PDSafeArea.apply !== applyChromeInset) {
            window.PDSafeArea.apply();
            return;
        }
        var root = document.documentElement;
        var chrome = document.getElementById("pd-site-chrome");
        var slot = chrome ? chrome.querySelector(".pd-status-bar-slot") : null;
        if (isApp()) {
            root.dataset.pdApp = "1";
            root.setAttribute("data-pd-app", "1");
            try { localStorage.setItem("pd_app", "1"); } catch (e) {}
        } else {
            root.removeAttribute("data-pd-app");
        }

        var inset = 0;
        if (isApp()) {
            inset = Math.max(readNativeInset(), estimateInset());
        }

        root.style.setProperty("--pd-chrome-pad-top", inset + "px");
        root.style.setProperty("--safe-area-top", inset + "px");
        var safeFill = chrome ? chrome.querySelector(".pd-chrome-safe-fill") : null;
        if (chrome) chrome.style.paddingTop = "0px";
        if (safeFill) {
            if (isApp() && inset > 0) {
                safeFill.style.display = "block";
                safeFill.style.height = inset + "px";
                safeFill.style.minHeight = inset + "px";
            } else {
                safeFill.style.display = "none";
                safeFill.style.height = "0px";
                safeFill.style.minHeight = "0px";
            }
        }
        if (slot) {
            slot.style.display = "none";
            slot.style.height = "0px";
        }
        root.dataset.pdSafeArea = String(inset);
    }
    applyChromeInset();
    window.PDSafeArea = window.PDSafeArea || { apply: applyChromeInset, measure: applyChromeInset };
    window.addEventListener("resize", applyChromeInset, { passive: true });
    window.addEventListener("orientationchange", function () { setTimeout(applyChromeInset, 120); }, { passive: true });
    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", applyChromeInset, { passive: true });
    }
})();
</script>
<?php
$pdSafeAreaJs = __DIR__ . '/../public/assets/js/safe-area.js';
?>
<script src="<?= url('public/assets/js/safe-area.js') ?>?v=<?= file_exists($pdSafeAreaJs) ? filemtime($pdSafeAreaJs) : time() ?>"></script>
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
<link rel="apple-touch-icon" href="<?= getBaseURL(); ?>public/assets/images/phonesdukan_logo.png">

<!-- Author & Theme -->
<meta name="author" content="Phones Dukan">
<meta name="theme-color" content="#111111">
<meta name="format-detection" content="telephone=no">

<!-- Pakistan geo targeting -->
<meta name="geo.region" content="PK-IS">
<meta name="geo.placename" content="Islamabad, Pakistan">
<meta name="geo.position" content="33.6682924;72.9984135">
<meta name="ICBM" content="33.6682924, 72.9984135">

<!-- Resource hints — preconnect to critical third-party origins -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="//www.googletagmanager.com">
<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">
<link rel="dns-prefetch" href="//cdn.onesignal.com">
<link rel="dns-prefetch" href="//www.clarity.ms">

<script>
window.__PD_BASE_PATH__ = <?= json_encode(rtrim(getBaseURL(), '/')) ?>;
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
<body>

    <!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="site-wrapper">

    <div id="pd-site-chrome" class="pd-site-chrome">
    <div class="pd-chrome-safe-fill" aria-hidden="true"></div>
    <div class="pd-status-bar-slot" aria-hidden="true"></div>

    <!-- Announcement bar (fixed below safe area via CSS) -->
    <div class="pd-top-bars">
        <div class="pd-safe-area-top" aria-hidden="true"></div>
        <div class="pd-announcement-bar" role="region" aria-label="Store announcements">
            <div class="pd-announcement-viewport">
            <div class="pd-announcement-track">
                <span><strong>Mobile Island</strong> Official Store • We Believe in Satisfaction</span>
                <span>Free delivery across Pakistan on selected products</span>
                <span>Call / WhatsApp: <strong>+92 311 6600031</strong></span>
                <span><strong>Mobile Island</strong> Official Store • We Believe in Satisfaction</span>
                <span>Free delivery across Pakistan on selected products</span>
                <span>Call / WhatsApp: <strong>+92 311 6600031</strong></span>
            </div>
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