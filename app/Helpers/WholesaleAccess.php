<?php

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
