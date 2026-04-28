<?php
ob_start(); // Start output buffering
require_once __DIR__ . '/../app/config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetching SEO meta data passed from the controller
$metaTitle = isset($metaTitle) ? htmlspecialchars($metaTitle) : 'Blog | Phones Dukan';
$metaDescription = isset($metaDescription) ? htmlspecialchars($metaDescription) : 'Explore the latest blog posts and tax insights on Phones Dukan.';
$metaKeywords = isset($metaKeywords) ? htmlspecialchars($metaKeywords) : null;
$metaRobots = isset($metaRobots) ? htmlspecialchars($metaRobots) : 'index, follow';

// Get the current page URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$pageUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . url(ltrim(getRequestPath(), '/'));

// Open Graph and Twitter image URL
$ogImage = getBaseURL() . "public/assets/images/Phones_dukan_favicon.png"; // Default image for blog posts
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required Meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Dynamic Meta Title -->
    <title><?= $metaTitle ?></title>

    <!-- Dynamic Meta Description -->
    <meta name="description" content="<?= $metaDescription ?>">

    <!-- Meta Keywords -->
    <?php if ($metaKeywords): ?>
        <meta name="keywords" content="<?= $metaKeywords ?>">
    <?php endif; ?>

    <!-- Robots Meta Tag -->
    <meta name="robots" content="<?= $metaRobots ?>">

    <!-- Canonical Link -->
    <link rel="canonical" href="<?= $pageUrl ?>">

    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?= $metaTitle ?>">
    <meta property="og:description" content="<?= $metaDescription ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:url" content="<?= $pageUrl ?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Phones Dukan">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $metaTitle ?>">
    <meta name="twitter:description" content="<?= $metaDescription ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">
    <meta name="twitter:image:width" content="1200">
    <meta name="twitter:image:height" content="630">
    <meta name="twitter:image:type" content="image/webp">
    <meta name="twitter:site" content="@phonesdukan">

    <!-- Favicon -->
    <link rel="icon" href="<?= getBaseURL(); ?>public/assets/images/Phones_dukan_favicon.png" type="image/x-icon">

    <!-- Author & Theme -->
    <meta name="author" content="Phones Dukan">
    <meta name="theme-color" content="#004080">

    <!-- Load Styles -->
    <?php loadCSS(); ?>
    <link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/frontend/blog_header.css">
    <link rel="stylesheet" href="<?= getBaseURL(); ?>public/assets/css/frontend/blog_footer.css">
</head>
<body>
<header class="rishi-header sticky">
<div class="row-wrapper">
    <div class="logo-col">
        <div class="branding">
            <a href="<?= url('blog'); ?>" class="logo" rel="home">
                <img src="<?= getBaseURL(); ?>public/assets/images/phonesdukan_logo.webp" alt="Phonesdukan Logo">
            </a>
        </div>
    </div>
    <div class="nav-col">
        <nav class="site-nav">
            <button class="menu-close" aria-label="Close Menu">
                <img src="<?= getBaseURL(); ?>public/assets/images/close-icon.svg" alt="Close Menu" class="close-icon">
            </button>
            <ul class="main-categories">
                    <li class="category-item has-submenu">
                        <a href="#">Devices & Accessories</a>
                        <ul class="sub-categories">
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/mobile-reviews">Mobile Reviews</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/hardware-accessories">Hardware & Accessories</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/pta-mobile-tax">PTA Mobile Tax</a></li>
                        </ul>
                    </li>
                    <li class="category-item has-submenu">
                        <a href="#">Apps & Usage</a>
                        <ul class="sub-categories">
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/mobile-apps">Mobile Apps</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/mobile-tips">Mobile Tips</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/mobile-packages">Mobile Packages</a></li>
                        </ul>
                    </li>
                    <li class="category-item has-submenu">
                        <a href="#">Tech Trends</a>
                        <ul class="sub-categories">
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/ai-emerging-tech">AI & Emerging Tech</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/cybersecurity">Cybersecurity</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/startups-tech-business">Startups & Tech Business</a></li>
                        </ul>
                    </li>
                    <li class="category-item has-submenu">
                        <a href="#">Lifestyle & Learning</a>
                        <ul class="sub-categories">
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/gaming-entertainment">Gaming & Entertainment</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/education-tech-learning">Education & Tech Learning</a></li>
                            <li class="category-item"><a href="<?= getBaseURL(); ?>blog/news">News</a></li>
                        </ul>
                    </li>
                </ul>
        </nav>
    </div>
    <div class="buttons-wrapper">
        <div class="search-col">
            <button class="search-btn" aria-label="Search Post and Pages">
                <svg class="icon" width="15" height="15" viewBox="0 0 15 15">
                    <path d="M14.6 13L12 10.5c.7-.8 1.3-2.5 1.3-3.8 0-3.6-3-6.6-6.6-6.6C3 0 0 3.1 0 6.7c0 3.6 3 6.6 6.6 6.6 1.4 0 2.7-.6 3.8-1.2l2.5 2.3c.7.7 1.2.7 1.7.2.5-.5.5-1 0-1.6zm-8-1.4c-2.7 0-4.9-2.2-4.9-4.9s2.2-4.9 4.9-4.9 4.9 2.2 4.9 4.9c0 2.6-2.2 4.9-4.9 4.9z"></path>
                </svg>
            </button>
            <div class="search-modal">
                <form method="get" action="<?= getBaseURL(); ?>blog-search" class="search-form">
                    <label for="search-field">
                        <input type="search" id="search-field" class="b-search-input" placeholder="Search posts..." name="s" title="Search Input" value="<?= htmlspecialchars($search_term ?? '') ?>" autocomplete="off">
                    </label>
                    <input type="submit" class="search-submit" value="Search">
                    <div class="search-suggestions" style="display: none;"></div>
                </form>
                <button id="close-btn" class="close-btn">Close</button>
            </div>
        </div>
        <button class="menu-toggle" aria-label="Toggle Menu">
            <img src="<?= getBaseURL(); ?>public/assets/images/mmenu_icon.svg" alt="Menu Toggle" class="toggle-icon">
        </button>
    </div>
</div>
</header>

<main class="content">
