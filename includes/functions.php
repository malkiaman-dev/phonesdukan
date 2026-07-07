<?php
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

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
        $secondSegment = $segments[1] ?? '';

        // When running root admin scripts like /admin/dashboard.php,
        // treat base path as root so assets resolve as /public/... not /admin/public/...
        if (strtolower($firstSegment) === 'admin' && strpos($secondSegment, '.php') !== false) {
            $basePath = '';
            return $basePath;
        }

        $basePath = (strpos($firstSegment, '.php') !== false || $firstSegment === '') ? '' : '/' . $firstSegment;
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
            $base = getBaseURL();
            return '/' . ltrim($base, '/');
        }

        if (preg_match('#^https?://#i', $path) || strpos($path, '//') === 0) {
            return $path;
        }

        $base = getBaseURL();
        $normalizedPath = ltrim($path, '/');
        $trimmedBase = rtrim($base, '/');

        // Prevent duplicated admin segment like /admin/admin/dashboard.php
        if ($trimmedBase === '/admin' && strpos($normalizedPath, 'admin/') === 0) {
            $normalizedPath = substr($normalizedPath, strlen('admin/'));
        }

        $fullPath = $base . $normalizedPath;
        return '/' . ltrim($fullPath, '/');
    }
}

if (!function_exists('getAppApkFilePath')) {
    function getAppApkFilePath()
    {
        $apk = pd_resolve_app_apk();
        if ($apk !== null) {
            return $apk['path'];
        }

        if (!defined('APK_STORAGE_PATH')) {
            require_once dirname(__DIR__) . '/app/config/app_download.php';
        }

        return getProjectRootPath() . '/' . ltrim(APK_STORAGE_PATH, '/');
    }
}

if (!function_exists('pd_get_apk_downloads_dir')) {
    function pd_get_apk_downloads_dir(): string
    {
        if (!defined('APK_DOWNLOADS_DIR')) {
            require_once dirname(__DIR__) . '/app/config/app_download.php';
        }

        return getProjectRootPath() . '/' . ltrim(APK_DOWNLOADS_DIR, '/');
    }
}

if (!function_exists('pd_read_apk_version_manifest')) {
    function pd_read_apk_version_manifest(): ?array
    {
        if (!defined('APK_VERSION_FILE')) {
            require_once dirname(__DIR__) . '/app/config/app_download.php';
        }

        $manifestPath = getProjectRootPath() . '/' . ltrim(APK_VERSION_FILE, '/');
        if (!is_readable($manifestPath)) {
            return null;
        }

        $props = parse_ini_file($manifestPath, false, INI_SCANNER_RAW);
        return is_array($props) ? $props : null;
    }
}

if (!function_exists('pd_build_apk_meta')) {
    function pd_build_apk_meta(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $size = filesize($path);
        if ($size === false || $size < 500000) {
            return null;
        }

        $mtime = filemtime($path) ?: 0;

        return [
            'path' => $path,
            'size' => $size,
            'mtime' => $mtime,
            'version' => (string) $mtime,
            'hash' => md5_file($path) ?: '',
            'basename' => basename($path),
        ];
    }
}

if (!function_exists('pd_resolve_app_apk')) {
    /**
     * Resolve the latest deployable APK for website download.
     * Priority: app-version.properties → configured storage path → newest file in downloads/.
     */
    function pd_resolve_app_apk(bool $allowDirectoryScan = true): ?array
    {
        if (!defined('APK_STORAGE_PATH')) {
            require_once dirname(__DIR__) . '/app/config/app_download.php';
        }

        $downloadsDir = pd_get_apk_downloads_dir();
        $manifest = pd_read_apk_version_manifest();

        if ($manifest && !empty($manifest['apkFile'])) {
            $manifestPath = $downloadsDir . '/' . basename((string) $manifest['apkFile']);
            $apk = pd_build_apk_meta($manifestPath);
            if ($apk !== null) {
                if (!empty($manifest['versionName'])) {
                    $apk['version'] = (string) $manifest['versionName'];
                }
                if (!empty($manifest['builtAt']) && is_numeric($manifest['builtAt'])) {
                    $apk['builtAt'] = (int) $manifest['builtAt'];
                }
                return $apk;
            }
        }

        $configuredPath = getProjectRootPath() . '/' . ltrim(APK_STORAGE_PATH, '/');
        $apk = pd_build_apk_meta($configuredPath);
        if ($apk !== null) {
            return $apk;
        }

        if (!$allowDirectoryScan || !is_dir($downloadsDir)) {
            return null;
        }

        $newest = null;
        foreach (glob($downloadsDir . '/*.apk') ?: [] as $candidatePath) {
            $candidate = pd_build_apk_meta($candidatePath);
            if ($candidate === null) {
                continue;
            }
            if ($newest === null || $candidate['mtime'] > $newest['mtime']) {
                $newest = $candidate;
            }
        }

        return $newest;
    }
}

if (!function_exists('pd_write_apk_version_manifest')) {
    function pd_write_apk_version_manifest(string $apkBasename, ?string $versionName = null): bool
    {
        if (!defined('APK_VERSION_FILE')) {
            require_once dirname(__DIR__) . '/app/config/app_download.php';
        }

        $downloadsDir = pd_get_apk_downloads_dir();
        $apkPath = $downloadsDir . '/' . basename($apkBasename);
        if (!is_file($apkPath)) {
            return false;
        }

        $mtime = filemtime($apkPath) ?: time();
        $manifestPath = getProjectRootPath() . '/' . ltrim(APK_VERSION_FILE, '/');
        $dir = dirname($manifestPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $lines = [
            'apkFile=' . basename($apkBasename),
            'builtAt=' . $mtime,
            'versionName=' . ($versionName ?: date('Y.m.d.Hi', $mtime)),
        ];

        return file_put_contents($manifestPath, implode(PHP_EOL, $lines) . PHP_EOL) !== false;
    }
}

if (!function_exists('getAppDownloadUrl')) {
    function getAppDownloadUrl()
    {
        if (!defined('APP_DOWNLOAD_ROUTE')) {
            require_once dirname(__DIR__) . '/app/config/app_download.php';
        }

        $url = url(APP_DOWNLOAD_ROUTE);
        $version = getAppApkVersion();
        if ($version > 0) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'v=' . $version;
        }

        return $url;
    }
}

if (!function_exists('getAppApkVersion')) {
    function getAppApkVersion()
    {
        $apk = pd_resolve_app_apk();
        if ($apk === null) {
            return 0;
        }

        if (!empty($apk['builtAt'])) {
            return (int) $apk['builtAt'];
        }

        return (int) ($apk['mtime'] ?? 0);
    }
}

if (!function_exists('isAppDownloadAvailable')) {
    function isAppDownloadAvailable()
    {
        return pd_resolve_app_apk() !== null;
    }
}

if (!function_exists('isPhonesDukanApp')) {
    function isPhonesDukanApp()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return stripos($userAgent, 'PhonesDukanApp') !== false;
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

if (!function_exists('buildBrandCategoryPath')) {
    function buildBrandCategoryPath(string $brandSlug, string $categorySlug): string
    {
        return '/' . rawurlencode(trim($brandSlug, '/')) . '/' . rawurlencode(trim($categorySlug, '/'));
    }
}

if (!function_exists('buildProductCanonicalUrl')) {
    function buildProductCanonicalUrl(
        string $brandSlug,
        string $categorySlug,
        string $productSlug,
        ?string $subcategorySlug = null,
        string $domain = 'https://www.phonesdukan.com'
    ): string {
        $path = buildProductPath($brandSlug, $categorySlug, $productSlug, $subcategorySlug);
        return rtrim($domain, '/') . $path . '/';
    }
}
if (!function_exists('normalizeMediaUrl')) {
    function normalizeMediaUrl(string $mediaUrl): string
    {
        $mediaUrl = trim(str_replace('�', '-', $mediaUrl));
        if ($mediaUrl === '') {
            return $mediaUrl;
        }

        // External URLs (e.g. signed TikTok/Facebook CDN links) must not be re-encoded.
        if (preg_match('#^https?://#i', $mediaUrl)) {
            return $mediaUrl;
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

if (!function_exists('isProductDetailPath')) {
    /**
     * True for storefront product permalinks (3- or 4-segment paths).
     * Excludes blog posts and other reserved multi-segment routes.
     */
    function isProductDetailPath(?string $uri = null): bool
    {
        $path = trim($uri ?? getRequestPath(), '/');
        if ($path === '') {
            return false;
        }

        if (strpos($path, 'blog/') === 0) {
            return false;
        }

        $segments = explode('/', $path);
        $count = count($segments);

        return $count === 3 || $count === 4;
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
        static $emitted = [];
        if (isset($emitted[$relativePath])) return;
        $emitted[$relativePath] = true;

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
        emitCss('public/assets/css/frontend/wholesale-access.css');
        emitCss('public/assets/css/frontend/download-app.css');
        if (isPhonesDukanApp()) {
            emitCss('public/assets/css/frontend/chatbot.css');
        }

        if ($uri === '/' || $uri === '/index.php') {
            emitCss('public/assets/css/frontend/index.css');
        }

        emitCss('public/assets/css/frontend/ui-controls.css');

        if (defined('CATEGORY_LISTING_PAGE') && CATEGORY_LISTING_PAGE) {
            emitCss('public/assets/css/frontend/category-listing.css');

            // When a category uses the dynamic fallback, still load its dedicated
            // stylesheet if one exists (e.g. headphones.css on /headphones).
            $categorySlug = trim($uri, '/');
            if ($categorySlug !== '' && strpos($categorySlug, '/') === false) {
                $categoryCss = 'public/assets/css/frontend/' . $categorySlug . '.css';
                if (is_file(assetFilePath($categoryCss))) {
                    emitCss($categoryCss);
                }
            }
        }

        if (defined('BRAND_LISTING_PAGE') && BRAND_LISTING_PAGE) {
            emitCss('public/assets/css/frontend/brand-listing.css');
        }

        $cssMap = [
            '/admin/' => 'public/assets/css/admin/admin.css',
            '/admin/add-product' => 'public/assets/css/admin/add-product.css',
            '/mobiles' => 'public/assets/css/frontend/mobiles.css',
            '/power-banks' => 'public/assets/css/frontend/power-banks.css',
            '/smart-watches' => 'public/assets/css/frontend/smart-watches.css',
            '/wireless-earbuds' => 'public/assets/css/frontend/wireless-earbuds.css',
            '/headphones' => 'public/assets/css/frontend/headphones.css',
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
            '/privacy-policy' => 'public/assets/css/frontend/return-policy.css',
            '/shipping-policy' => 'public/assets/css/frontend/return-policy.css',
            '/terms-and-conditions' => 'public/assets/css/frontend/return-policy.css',
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
            if (str_ends_with($pathPrefix, '/')) {
                if (strpos($uri, $pathPrefix) === 0) {
                    emitCss($cssFile);
                }
                continue;
            }

            if ($uri === rtrim($pathPrefix, '/')) {
                emitCss($cssFile);
                continue;
            }

            // e.g. brand slug "login" must not load /login styles on product URLs (/login/category/product)
            if (isProductDetailPath($uri) && str_starts_with($uri, $pathPrefix . '/')) {
                continue;
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

        if (preg_match('#^/mobiles/[^/]+$#', $uri) && !(defined('BRAND_LISTING_PAGE') && BRAND_LISTING_PAGE)) {
            emitCss('public/assets/css/frontend/mobile_brands.css');
        }

        // Product detail pages: /{brand}/{category}/{product} or /{brand}/{category}/{subcategory}/{product}
        if (isProductDetailPath($uri)) {
            emitCss('public/assets/css/frontend/product.css');
        }

        if (strpos($uri, 'mobiles-price-list/best-mobiles-under-') !== false) {
            emitCss('public/assets/css/frontend/best-mobiles-page.css');
        }

        // Only single-segment routes (e.g. /cart, /login) — not product or nested paths
        if (!isProductDetailPath($uri) && strpos($uri, '/') === strrpos($uri, '/')) {
            $pageCss = 'public/assets/css/frontend/' . getCurrentPage() . '.css';
            emitCss($pageCss);
        }
    }
}

if (!function_exists('loadJS')) {
    function loadJS()
    {
        $uri = getRequestPath();

        emitJs('public/assets/js/pd-app.js');
        emitJs('public/assets/js/safe-area.js');
        emitJs('public/assets/js/common.js');
        if (!isPhonesDukanApp()) {
            emitJs('public/assets/js/download-app.js');
        }
        emitJs('public/assets/js/frontend/wholesale-access.js');
        emitJs('public/assets/js/faqs.js');

        if ($uri === '/' || strpos($uri, '/index') !== false) {
            emitJs('public/assets/js/frontend/index.js');
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (strpos($uri, '/wholesale') === 0) {
            emitJs('public/assets/js/jquery-3.6.0.min.js');
            emitJs('public/assets/js/sweetalert2.all.min.js');
        }

        if (defined('CATEGORY_LISTING_PAGE') && CATEGORY_LISTING_PAGE) {
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        $listingSlug = trim(getRequestPath(), '/');
        if ($listingSlug !== '' && strpos($listingSlug, '/') === false && is_file(assetFilePath('public/assets/css/frontend/' . $listingSlug . '.css'))) {
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (defined('BRAND_LISTING_PAGE') && BRAND_LISTING_PAGE) {
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (defined('SEARCH_RESULTS_PAGE') && SEARCH_RESULTS_PAGE) {
            emitJs('public/assets/js/frontend/buy-now.js');
        }

        if (isProductDetailPath($uri)) {
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

        $jsUri = getRequestPath();
        if (!isProductDetailPath($jsUri) && strpos($jsUri, '/') === strrpos($jsUri, '/')) {
            emitJs('public/assets/js/frontend/' . getCurrentPage() . '.js');
        }
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

if (!function_exists('mapProductStatusFromForm')) {
    function mapProductStatusFromForm($status): int
    {
        $status = strtolower(trim((string) $status));
        if ($status === 'active' || $status === '1') {
            return 1;
        }
        if ($status === 'coming_soon' || $status === '2') {
            return 2;
        }
        return 0;
    }
}

if (!function_exists('isProductComingSoon')) {
    function isProductComingSoon(array $product): bool
    {
        if ((int) ($product['product_status'] ?? 0) === 2) {
            return true;
        }
        return !empty($product['product_tag']) && stripos($product['product_tag'], 'coming_soon') !== false;
    }
}

if (!function_exists('getProductStatusLabel')) {
    function getProductStatusLabel($status): string
    {
        return match ((int) $status) {
            1 => 'Active',
            2 => 'Coming Soon',
            default => 'Inactive',
        };
    }
}

if (!function_exists('getProductStatusCssClass')) {
    function getProductStatusCssClass($status): string
    {
        return match ((int) $status) {
            1 => 'status-active',
            2 => 'status-coming-soon',
            default => 'status-inactive',
        };
    }
}

if (!function_exists('isProductStatusIndexable')) {
    function isProductStatusIndexable($status): bool
    {
        return in_array((int) $status, [1, 2], true);
    }
}

if (!function_exists('ensureProductExpectedComingDateColumn')) {
    function ensureProductExpectedComingDateColumn(PDO $db): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute(['products', 'expected_coming_date']);
            if ((int) $stmt->fetchColumn() === 0) {
                $db->exec('ALTER TABLE products ADD COLUMN expected_coming_date DATE NULL DEFAULT NULL AFTER product_status');
            }
        } catch (Throwable $e) {
            error_log('expected_coming_date migration: ' . $e->getMessage());
        }
    }
}

if (!function_exists('ensureProductPrepaidDiscountColumn')) {
    function ensureProductPrepaidDiscountColumn(PDO $db): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute(['products', 'prepaid_discount_amount']);
            if ((int) $stmt->fetchColumn() === 0) {
                $db->exec('ALTER TABLE products ADD COLUMN prepaid_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER sale_price');
            }
        } catch (Throwable $e) {
            error_log('prepaid_discount_amount migration: ' . $e->getMessage());
        }
    }
}

if (!function_exists('normalizeExpectedComingDate')) {
    function normalizeExpectedComingDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $date, $matches)) {
            $dateOnly = $matches[1];
            $dt = DateTime::createFromFormat('Y-m-d', $dateOnly);
            if ($dt && $dt->format('Y-m-d') === $dateOnly) {
                return $dateOnly;
            }
        }

        return null;
    }
}

if (!function_exists('getExpectedComingDateCountdownIso')) {
    function getExpectedComingDateCountdownIso($date): ?string
    {
        $normalized = normalizeExpectedComingDate($date);
        if ($normalized === null) {
            return null;
        }

        try {
            $tz = new DateTimeZone('Asia/Karachi');
            $dt = DateTime::createFromFormat('Y-m-d', $normalized, $tz);
            if (!$dt) {
                return null;
            }
            $dt->setTime(23, 59, 59);
            return $dt->format(DateTime::ATOM);
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('formatExpectedComingDate')) {
    function formatExpectedComingDate($date): ?string
    {
        $normalized = normalizeExpectedComingDate($date);
        if ($normalized === null) {
            return null;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $normalized);
        return $dt ? $dt->format('j M Y') : null;
    }
}

if (!function_exists('resolveExpectedComingDateFromPost')) {
    function resolveExpectedComingDateFromPost($status, $postedDate): ?string
    {
        $statusValue = mapProductStatusFromForm($status);
        if ($statusValue !== 2) {
            return null;
        }

        return normalizeExpectedComingDate($postedDate);
    }
}

if (!function_exists('prepareProductCardFromRow')) {
    function prepareProductCardFromRow(array $row): array
    {
        $regularPrice = (float) ($row['regular_price'] ?? 0);
        $salePrice = (float) ($row['sale_price'] ?? 0);
        $isComingSoon = isProductComingSoon($row);
        $isSoldOut = !$isComingSoon && (int) ($row['stock_quantity'] ?? 0) <= 0;
        $hasSale = !$isSoldOut && !$isComingSoon && $salePrice > 0 && $regularPrice > 0 && $salePrice < $regularPrice;
        $discountPct = $hasSale ? max(1, (int) round((($regularPrice - $salePrice) / $regularPrice) * 100)) : 0;
        $unitPrice = $hasSale ? $salePrice : $regularPrice;

        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'product_url' => buildProductPath(
                (string) ($row['brand_slug'] ?? ''),
                (string) ($row['category_slug'] ?? ''),
                (string) ($row['product_slug'] ?? ''),
                !empty($row['subcategory_slug']) ? (string) $row['subcategory_slug'] : null
            ),
            'product_name' => htmlspecialchars($row['product_name'] ?? 'Unnamed Product', ENT_QUOTES, 'UTF-8'),
            'product_image' => !empty($row['image_url'])
                ? htmlspecialchars(normalizeMediaUrl((string) $row['image_url']), ENT_QUOTES, 'UTF-8')
                : '/public/assets/images/Phones_dukan_favicon.png',
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'is_coming_soon' => $isComingSoon,
            'is_sold_out' => $isSoldOut,
            'has_sale' => $hasSale,
            'discount_pct' => $discountPct,
            'unit_price' => $unitPrice,
        ];
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

if (!function_exists('adminTooltipIcon')) {
    function adminTooltipIcon(string $message, string $id = ''): string
    {
        $escaped = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';

        return '<span class="ad-tooltip ad-tooltip--icon"' . $idAttr . ' tabindex="0" data-tooltip="'
            . $escaped . '" aria-label="More information" role="button">'
            . '<i class="fas fa-circle-info" aria-hidden="true"></i></span>';
    }
}

if (!function_exists('adminTooltipLabel')) {
    function adminTooltipLabel(string $label, string $message): string
    {
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $messageEsc = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return '<span class="ad-tooltip ad-tooltip--label cp-check-tooltip" tabindex="0" data-tooltip="'
            . $messageEsc . '" aria-label="' . $messageEsc . '">' . $labelEsc . '</span>';
    }
}

if (!function_exists('adminToggleSwitch')) {
    function adminToggleSwitch(bool $enabled, array $dataAttributes = [], string $ariaLabel = 'Toggle setting'): string
    {
        $attrs = '';
        foreach ($dataAttributes as $key => $value) {
            $attrs .= ' data-' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        $checked = $enabled ? ' checked' : '';
        $state = $enabled ? 'Enabled' : 'Disabled';
        $aria = htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8');

        return '<label class="ad-toggle" aria-label="' . $aria . '">'
            . '<input type="checkbox" class="ad-toggle-input"' . $attrs . $checked . '>'
            . '<span class="ad-toggle-track" aria-hidden="true"></span>'
            . '<span class="ad-toggle-text">' . $state . '</span>'
            . '</label>';
    }
}
