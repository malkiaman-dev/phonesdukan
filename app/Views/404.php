<?php
// Ensure a real HTTP 404 is always sent regardless of where this view is included from
if (http_response_code() === 200) {
    http_response_code(404);
}

$pageTitle       = '404 - Page Not Found | Phones Dukan';
$metaDescription = 'The page you are looking for could not be found. Browse our latest mobile phones, smart watches, and accessories at Phones Dukan.';
$metaRobots      = 'noindex, follow';

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.pd-404-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 60px 20px;
    text-align: center;
}
.pd-404-code {
    font-size: clamp(80px, 15vw, 160px);
    font-weight: 800;
    color: #f7d117;
    line-height: 1;
    margin: 0 0 8px;
    font-family: 'Poppins', sans-serif;
}
.pd-404-title {
    font-size: clamp(20px, 4vw, 32px);
    font-weight: 700;
    color: #111;
    margin: 0 0 12px;
}
.pd-404-text {
    font-size: 16px;
    color: #555;
    max-width: 480px;
    margin: 0 auto 32px;
    line-height: 1.6;
}
.pd-404-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}
.pd-404-btn {
    display: inline-block;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: background .2s, color .2s;
}
.pd-404-btn-primary {
    background: #f7d117;
    color: #111;
}
.pd-404-btn-primary:hover { background: #ffe033; }
.pd-404-btn-secondary {
    background: #f4f4f4;
    color: #111;
    border: 1px solid #e0e0e0;
}
.pd-404-btn-secondary:hover { background: #e8e8e8; }
.pd-404-links {
    margin-top: 40px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
}
.pd-404-links a {
    color: #555;
    font-size: 14px;
    text-decoration: none;
    border-bottom: 1px solid #ccc;
    padding-bottom: 2px;
    transition: color .2s, border-color .2s;
}
.pd-404-links a:hover { color: #111; border-color: #111; }
</style>

<div class="pd-404-wrap" role="main">
    <p class="pd-404-code" aria-hidden="true">404</p>
    <h1 class="pd-404-title">Page Not Found</h1>
    <p class="pd-404-text">
        The page you are looking for may have been moved, removed, or never existed.
        Try browsing our latest products below.
    </p>
    <div class="pd-404-actions">
        <a href="<?= url() ?>" class="pd-404-btn pd-404-btn-primary">Go to Homepage</a>
        <a href="<?= url('mobiles/') ?>" class="pd-404-btn pd-404-btn-secondary">Browse Mobiles</a>
    </div>
    <nav class="pd-404-links" aria-label="Quick links">
        <a href="<?= url('smart-watches/') ?>">Smart Watches</a>
        <a href="<?= url('wireless-earbuds/') ?>">Earbuds</a>
        <a href="<?= url('mobile-accessories/') ?>">Accessories</a>
        <a href="<?= url('contact-us/') ?>">Contact Us</a>
    </nav>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
