<?php
if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
        if ($basePath === '/index.php') {
            $basePath = '';
        }
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

if (!function_exists('buildProductPath')) {
    function buildProductPath(
        string $brandSlug,
        string $categorySlug,
        string $productSlug,
        ?string $subcategorySlug = null
    ): string {
        $segments = [
            rawurlencode(trim($brandSlug, '/')),
            rawurlencode(trim($categorySlug, '/')),
        ];
        if ($subcategorySlug !== null && trim($subcategorySlug) !== '') {
            $segments[] = rawurlencode(trim($subcategorySlug, '/'));
        }
        $segments[] = rawurlencode(preg_replace('/\s+/', '-', trim($productSlug, '/')));
        return '/' . implode('/', $segments);
    }
}

if (!function_exists('buildProductPathFromRow')) {
    function buildProductPathFromRow(array $product): string
    {
        $subcategorySlug = !empty($product['subcategory_slug']) ? (string) $product['subcategory_slug'] : null;
        return buildProductPath(
            (string) ($product['brand_slug'] ?? ''),
            (string) ($product['category_slug'] ?? ''),
            (string) ($product['product_slug'] ?? ''),
            $subcategorySlug
        );
    }
}

if (!function_exists('encodeUrlPath')) {
    function encodeUrlPath(string $path): string
    {
        $hasLeadingSlash = isset($path[0]) && $path[0] === '/';
        $segments = explode('/', trim($path, '/'));
        $encoded = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            $encoded[] = rawurlencode($segment);
        }
        $joined = implode('/', $encoded);
        return $hasLeadingSlash ? '/' . $joined : $joined;
    }
}

if (!function_exists('buildBrandCategoryPath')) {
    function buildBrandCategoryPath(string $brandSlug, string $categorySlug): string
    {
        return '/' . rawurlencode(trim($brandSlug, '/')) . '/' . rawurlencode(trim($categorySlug, '/'));
    }
}

