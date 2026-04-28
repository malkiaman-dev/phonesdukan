<?php
if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

        return ($basePath === '' ? '/' : $basePath . '/');
    }
}

if (!function_exists('escapeHtml')) {
    function escapeHtml($string)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getCurrentURL')) {
    function getCurrentURL()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $requestUri;
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($price, $currency = 'PKR')
    {
        return number_format((float) $price) . ' ' . $currency;
    }
}

