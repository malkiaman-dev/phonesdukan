<?php
if (!function_exists('getProjectRootPath')) {
    function getProjectRootPath()
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('getBasePath')) {
    function getBasePath()
    {
        static $basePath = null;
        if ($basePath !== null) {
            return $basePath;
        }

        if (defined('BASE_PATH')) {
            $basePath = rtrim((string) BASE_PATH, '/');
            return $basePath;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptFilename = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
        $projectRoot = realpath(getProjectRootPath());

        if ($scriptName !== '' && $scriptFilename && $projectRoot && stripos($scriptFilename, $projectRoot) === 0) {
            $scriptDirUrl = trim(str_replace('\\', '/', dirname($scriptName)), '/');
            $relativeDirFs = trim(str_replace('\\', '/', substr(dirname($scriptFilename), strlen($projectRoot))), '/');

            if ($relativeDirFs !== '') {
                $suffix = '/' . $relativeDirFs;
                if (substr($scriptDirUrl, -strlen($suffix)) === $suffix) {
                    $scriptDirUrl = substr($scriptDirUrl, 0, -strlen($suffix));
                }
            }

            $basePath = $scriptDirUrl === '' ? '' : '/' . trim($scriptDirUrl, '/');
            return $basePath;
        }

        $segments = array_values(array_filter(explode('/', trim($scriptName, '/'))));
        $basePath = isset($segments[0]) ? '/' . $segments[0] : '';
        return $basePath;
    }
}

if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }

        $basePath = getBasePath();
        return ($basePath === '' ? '/' : $basePath . '/');
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        $path = (string) $path;
        if ($path === '') {
            return getBaseURL();
        }

        if (preg_match('#^https?://#i', $path) || strpos($path, '//') === 0) {
            return $path;
        }

        return getBaseURL() . ltrim($path, '/');
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo($path, $status = 302)
    {
        $target = url($path);
        header('Location: ' . $target, true, (int) $status);
        exit;
    }
}

if (!function_exists('getRequestPath')) {
    function getRequestPath()
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $basePath = getBasePath();

        if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
            $requestPath = substr($requestPath, strlen($basePath));
        }

        $requestPath = '/' . ltrim($requestPath, '/');
        return $requestPath === '//' ? '/' : $requestPath;
    }
}

if (!function_exists('getCurrentPage')) {
    function getCurrentPage()
    {
        $path = trim(getRequestPath(), '/');
        if ($path === '') {
            return 'index';
        }

        return basename($path, '.php');
    }
}

if (!function_exists('assetFilePath')) {
    function assetFilePath($relativePath)
    {
        return getProjectRootPath() . '/' . ltrim($relativePath, '/');
    }
}

if (!function_exists('emitCss')) {
    function emitCss($relativePath)
    {
        $fullPath = assetFilePath($relativePath);
        if (!file_exists($fullPath)) {
            return;
        }

        echo '<link rel="stylesheet" href="' . url($relativePath) . '?v=' . filemtime($fullPath) . '">';
    }
}

if (!function_exists('emitJs')) {
    function emitJs($relativePath)
    {
        $fullPath = assetFilePath($relativePath);
        if (!file_exists($fullPath)) {
            return;
        }

        echo '<script src="' . url($relativePath) . '?v=' . filemtime($fullPath) . '"></script>';
    }
}

if (!function_exists('loadCSS')) {
    function loadCSS()
    {
        $uri = rtrim(getRequestPath(), '/');
        $uri = $uri === '' ? '/' : $uri;

        emitCss('public/assets/css/style.css');

        if ($uri === '/' || $uri === '/index.php') {
            emitCss('public/assets/css/frontend/index.css');
        }

        emitCss('public/assets/css/frontend/ui-controls.css');

        $cssMap = [
            '/admin/' => 'public/assets/css/admin/admin.css',
            '/mobiles' => 'public/assets/css/frontend/mobiles.css',
            '/power-banks' => 'public/assets/css/frontend/power-banks.css',
            '/smart-watches' => 'public/assets/css/frontend/smart-watches.css',
            '/wireless-earbuds' => 'public/assets/css/frontend/wireless-earbuds.css',
            '/bluetooth-speakers' => 'public/assets/css/frontend/bluetooth-speakers.css',
            '/mobile-accessories' => 'public/assets/css/frontend/mobile-accessories.css',
            '/coming-soon-products' => 'public/assets/css/frontend/coming-soon.css',
            '/register' => 'public/assets/css/frontend/register.css',
            '/login' => 'public/assets/css/frontend/login.css',
            '/verify' => 'public/assets/css/frontend/verify.css',
            '/cart' => 'public/assets/css/frontend/cart.css',
            '/checkout' => 'public/assets/css/frontend/checkout.css',
            '/thankyou' => 'public/assets/css/frontend/order_confirmation.css',
            '/return-policy' => 'public/assets/css/frontend/return-policy.css',
            '/about-us' => 'public/assets/css/frontend/about-us.css',
            '/write-for-us' => 'public/assets/css/frontend/write-us.css',
            '/search' => 'public/assets/css/frontend/search.css',
            '/blog-search' => 'public/assets/css/frontend/blog-search.css',
            '/blog' => 'public/assets/css/frontend/blog.css',
            '/contact-us' => 'public/assets/css/frontend/contact.css',
            '/shop' => 'public/assets/css/frontend/shop.css',
            '/wholesale' => 'public/assets/css/frontend/wholesale.css',
        ];

        foreach ($cssMap as $pathPrefix => $cssFile) {
            if (strpos($uri, rtrim($pathPrefix, '/')) === 0) {
                emitCss($cssFile);
            }
        }

        if (preg_match('#^/mobiles/[^/]+$#', $uri)) {
            emitCss('public/assets/css/frontend/mobile_brands.css');
        }

        // Product detail pages: /{category}/{brand}/{product}
        if (substr_count(trim($uri, '/'), '/') === 2) {
            emitCss('public/assets/css/frontend/product.css');
        }

        if (strpos($uri, 'mobiles-price-list/best-mobiles-under-') !== false) {
            emitCss('public/assets/css/frontend/best-mobiles-page.css');
        }

        $pageCss = 'public/assets/css/frontend/' . getCurrentPage() . '.css';
        emitCss($pageCss);
    }
}

if (!function_exists('loadJS')) {
    function loadJS()
    {
        $uri = getRequestPath();

        emitJs('public/assets/js/common.js');
        emitJs('public/assets/js/faqs.js');

        if ($uri === '/' || strpos($uri, '/index') !== false) {
            emitJs('public/assets/js/frontend/index.js');
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (strpos($uri, '/wholesale') === 0) {
            emitJs('public/assets/js/jquery-3.6.0.min.js');
            emitJs('public/assets/js/sweetalert2.all.min.js');
        }

        if (substr_count(trim($uri, '/'), '/') === 2) {
            emitJs('public/assets/js/jquery-3.6.0.min.js');
            emitJs('public/assets/js/sweetalert2.all.min.js');
            emitJs('public/assets/js/frontend/product.js');
        }

        if (strpos($uri, '/admin/') === 0) {
            emitJs('public/assets/js/admin/manage-reviews.js');
            emitJs('public/assets/js/admin/media-library.js');
            emitJs('public/assets/js/admin/update_image_metadata.js');
            emitJs('public/assets/js/admin/upload_image.js');
        }

        if (strpos($uri, '/shop') !== false) {
            emitJs('public/assets/js/frontend/shop-filter.js');
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (strpos($uri, '/mobiles-price-list') !== false) {
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (strpos($uri, '/checkout') !== false) {
            emitJs('public/assets/js/frontend/checkout.js');
        }

        if (strpos($uri, '/cart') !== false) {
            emitJs('public/assets/js/frontend/cart.js');
            emitJs('public/assets/js/sweetalert2.all.min.js');
        }

        emitJs('public/assets/js/frontend/' . getCurrentPage() . '.js');
    }
}
