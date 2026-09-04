<?php
// Shopkeeper access code for the B2B / wholesale portal.
// Override in wholesale_config.local.php (gitignored) or WHOLESALE_SHOPKEEPER_CODE env var.

$wholesaleLocalConfig = __DIR__ . '/wholesale_config.local.php';
if (is_readable($wholesaleLocalConfig)) {
    require_once $wholesaleLocalConfig;
}

if (!defined('WHOLESALE_SHOPKEEPER_CODE')) {
    define('WHOLESALE_SHOPKEEPER_CODE', getenv('WHOLESALE_SHOPKEEPER_CODE') ?: 'PDK-SHOP-786');
}

// Load the access helper if present; otherwise fall back to inline definitions
// so a missing helper file never causes a fatal error on the live site.
$wholesaleAccessHelper = dirname(__DIR__) . '/helpers/WholesaleAccess.php';
if (is_readable($wholesaleAccessHelper)) {
    require_once $wholesaleAccessHelper;
}

if (!function_exists('wholesaleHasAccess')) {
    function wholesaleHasAccess(): bool
    {
        return !empty($_SESSION['wholesale_access_granted']);
    }
}

if (!function_exists('wholesaleGrantAccess')) {
    function wholesaleGrantAccess(): void
    {
        $_SESSION['wholesale_access_granted'] = true;
    }
}

if (!function_exists('wholesaleRevokeAccess')) {
    function wholesaleRevokeAccess(): void
    {
        unset($_SESSION['wholesale_access_granted']);
    }
}

if (!function_exists('wholesaleVerifyCode')) {
    function wholesaleVerifyCode(string $code): bool
    {
        $expected = defined('WHOLESALE_SHOPKEEPER_CODE') ? (string) WHOLESALE_SHOPKEEPER_CODE : '';
        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, trim($code));
    }
}

if (!function_exists('wholesaleRequireAccess')) {
    function wholesaleRequireAccess(): void
    {
        if (!wholesaleHasAccess()) {
            http_response_code(403);
            throw new Exception('Wholesale access denied. Valid shopkeeper code required.');
        }
    }
}
