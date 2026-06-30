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

require_once dirname(__DIR__) . '/helpers/WholesaleAccess.php';
