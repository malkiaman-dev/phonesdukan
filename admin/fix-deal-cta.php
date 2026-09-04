<?php
/**
 * One-click fix: set Deal "Shop This Deal" to the L-210 product page.
 * Open while logged into admin: /admin/fix-deal-cta.php
 */
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
ensureSiteSettingsSchema($conn);

$settings = getSiteSettings($conn);
$image = trim((string) ($settings['deal_image'] ?? ''));

$productPath = '';
if ($image !== '' && function_exists('findDealProductPathByImage')) {
    $productPath = findDealProductPathByImage($image, $conn);
}

// Prefer the live-known L-210 permalink when image/title matches that deal.
$imageBase = strtolower(basename($image));
$title = strtolower((string) ($settings['deal_title'] ?? ''));
if ($productPath === '' && (strpos($imageBase, 'l-210') !== false || strpos($title, 'earbuds') !== false)) {
    $productPath = 'login/wireless-earbuds/Login-L-210-Earbuds';
}

if ($productPath === '') {
    $productPath = 'login/wireless-earbuds/Login-L-210-Earbuds';
}

$cta = rtrim($productPath, '/') . '/';
$conn->prepare('UPDATE site_settings SET deal_cta_url = ?')->execute([$cta]);

$resolved = resolveDealCtaUrl($cta, $image, $conn);

$_SESSION['deal_settings_flash'] = [
    'message' => 'Deal button link fixed to: ' . $cta,
    'type' => 'success',
];

header('Location: deal-settings.php');
exit;
