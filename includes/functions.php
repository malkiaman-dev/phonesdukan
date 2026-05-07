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
        $firstSegment = $segments[0] ?? '';
        $basePath = (strpos($firstSegment, '.php') !== false || $firstSegment === '')
            ? ''
            : '/' . $firstSegment;
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

if (!function_exists('encodeUrlPath')) {
    function encodeUrlPath(string $path): string
    {
        $path = str_replace('�', '-', $path);
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

if (!function_exists('buildProductPath')) {
    function buildProductPath(string $categorySlug, string $brandSlug, string $productSlug): string
    {
        return '/' . implode('/', [
            rawurlencode(trim($categorySlug, '/')),
            rawurlencode(trim($brandSlug, '/')),
            rawurlencode(str_replace('�', '-', trim($productSlug, '/'))),
        ]);
    }
}

if (!function_exists('normalizeMediaUrl')) {
    function normalizeMediaUrl(string $mediaUrl): string
    {
        $mediaUrl = trim(str_replace('�', '-', $mediaUrl));
        if ($mediaUrl === '') {
            return $mediaUrl;
        }

        if (preg_match('#^https?://#i', $mediaUrl)) {
            $parts = parse_url($mediaUrl);
            if (!is_array($parts)) {
                return $mediaUrl;
            }
            $scheme = $parts['scheme'] ?? 'https';
            $host = $parts['host'] ?? '';
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            $path = isset($parts['path']) ? encodeUrlPath($parts['path']) : '';
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
            return $scheme . '://' . $host . $port . $path . $query . $fragment;
        }

        $query = '';
        $fragment = '';
        $pathOnly = $mediaUrl;
        $hashPos = strpos($pathOnly, '#');
        if ($hashPos !== false) {
            $fragment = substr($pathOnly, $hashPos);
            $pathOnly = substr($pathOnly, 0, $hashPos);
        }
        $queryPos = strpos($pathOnly, '?');
        if ($queryPos !== false) {
            $query = substr($pathOnly, $queryPos);
            $pathOnly = substr($pathOnly, 0, $queryPos);
        }
        return encodeUrlPath($pathOnly) . $query . $fragment;
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
        static $emitted = [];
        if (isset($emitted[$relativePath])) return;
        $emitted[$relativePath] = true;

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
            '/admin/add-product' => 'public/assets/css/admin/add-product.css',
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

        // Blog category listing pages: /blog/{category}
        // Do not apply to blog homepage (/blog) or post detail pages (/blog/{category}/{post}).
        if (preg_match('#^/blog/[^/]+$#', $uri)) {
            emitCss('public/assets/css/frontend/post-category.css');
        }

        // News category listing page lives at /news (not /blog/news)
        if ($uri === '/news') {
            emitCss('public/assets/css/frontend/blog.css');
            emitCss('public/assets/css/frontend/post-category.css');
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

if (!function_exists('isProductInStock')) {
    function isProductInStock($productId)
    {
        static $stockCache = [];
        if (isset($stockCache[$productId])) {
            return $stockCache[$productId];
        }

        try {
            require_once dirname(__DIR__) . '/database/db.php';
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return true;

            $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE product_id = :id LIMIT 1");
            $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $inStock = ($result && (int)($result['stock_quantity'] ?? 0) > 0);
            $stockCache[$productId] = $inStock;
            return $inStock;
        } catch (Exception $e) {
            return true; // Default to in stock on error
        }
    }
}

if (!function_exists('renderBestMobilesSidebar')) {
    function renderBestMobilesSidebar($activeLimit)
    {
        $items = [
            '30000'  => ['img' => '/wp-content/uploads/2024/05/Realme-Note-50-128GB.webp',          'label' => 'Best Mobiles Under Rs. 30,000'],
            '40000'  => ['img' => '/wp-content/uploads/2024/01/Infinix-Smart-8-Plus.webp',          'label' => 'Best Mobiles Under Rs. 40,000'],
            '50000'  => ['img' => '/wp-content/uploads/2024/03/Tecno-camon-20-Pro.webp',            'label' => 'Best Mobiles Under Rs. 50,000'],
            '60000'  => ['img' => '/wp-content/uploads/2024/01/Oppo-A58.webp',                     'label' => 'Best Mobiles Under Rs. 60,000'],
            '80000'  => ['img' => '/wp-content/uploads/2024/11/Realme-13.webp',                    'label' => 'Best Mobiles Under Rs. 80,000'],
            '100000' => ['img' => '/wp-content/uploads/2024/11/vivo-3t-pro-price-in-pakistan.webp','label' => 'Best Mobiles Under Rs. 100,000'],
            '150000' => ['img' => '/wp-content/uploads/2024/03/Vivo-V30-5G.webp',                  'label' => 'Best Mobiles Under Rs. 150,000'],
        ];
        ?>
        <div class="shop-sidebar">
            <form id="best-mobiles-filter">
                <div class="best-mobiles-range">
                    <h4>Best Mobiles Under:</h4>
                    <?php foreach ($items as $limit => $data): ?>
                    <div class="best-mobiles-option">
                        <a href="<?= url('mobiles-price-list/best-mobiles-under-' . $limit . '/'); ?>" 
                           class="best-mobiles-link<?= ($activeLimit == $limit ? ' is-active' : '') ?>">
                            <img src="<?= $data['img'] ?>" alt="Mobile Image" class="sidebar-image">
                            <?= $data['label'] ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <?php
    }
}
