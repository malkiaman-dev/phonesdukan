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

if (!function_exists('getAppIconPath')) {
    /** Relative web path to the Phones Dukan app / site icon (PNG). */
    function getAppIconPath(): string
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }

        $candidates = [
            'public/assets/images/app-icon.png',
            'app-icon.png',
            'public/assets/images/Phones_dukan_favicon.png',
        ];

        $root = getProjectRootPath();
        foreach ($candidates as $relative) {
            $full = $root . '/' . ltrim($relative, '/');
            if (is_file($full) && is_readable($full)) {
                $resolved = $relative;
                return $resolved;
            }
        }

        $resolved = 'public/assets/images/app-icon.png';
        return $resolved;
    }
}

if (!function_exists('getAppIconUrl')) {
    function getAppIconUrl(bool $absolute = true): string
    {
        $path = getAppIconPath();
        $url = url($path);

        $fullPath = getProjectRootPath() . '/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'v=' . filemtime($fullPath);
        }

        if (!$absolute) {
            return $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host . $url;
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        $path = (string) $path;
        if ($path === '') {
            $base = getBaseURL();
            $base = str_replace('\\', '/', $base);
            $base = preg_replace('#/\.(?=/|$)#', '/', $base) ?? $base;
            $base = preg_replace('#^\./+#', '/', $base) ?? $base;
            return '/' . ltrim($base, '/');
        }

        if (preg_match('#^https?://#i', $path) || strpos($path, '//') === 0) {
            return $path;
        }

        $base = getBaseURL();
        $base = str_replace('\\', '/', (string) $base);
        if ($base === '.' || $base === './') {
            $base = '/';
        }
        $base = preg_replace('#/\.(?=/|$)#', '/', $base) ?? $base;
        $base = preg_replace('#^\./+#', '/', $base) ?? $base;

        $normalizedPath = ltrim($path, '/');
        $trimmedBase = rtrim($base, '/');

        // Prevent duplicated admin segment like /admin/admin/dashboard.php
        if ($trimmedBase === '/admin' && strpos($normalizedPath, 'admin/') === 0) {
            $normalizedPath = substr($normalizedPath, strlen('admin/'));
        }

        if ($trimmedBase === '' || $trimmedBase === '/') {
            return '/' . $normalizedPath;
        }

        return $trimmedBase . '/' . $normalizedPath;
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
     * Resolve the Flutter release APK for website download.
     * Priority: app-version.properties → configured storage path (phonesdukan-app.apk).
     */
    function pd_resolve_app_apk(bool $allowDirectoryScan = false): ?array
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
        return pd_build_apk_meta($configuredPath);
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
if (!function_exists('normalizeStoredUploadPath')) {
    /**
     * Canonicalize local upload paths for DB storage.
     * - Keeps /wp-content/uploads/... (legacy WordPress files)
     * - Keeps /public/uploads/... (new uploads)
     * - Converts absolute/domain/base-path URLs to a site-relative upload path
     * - Leaves external CDN URLs unchanged
     */
    function normalizeStoredUploadPath(string $mediaUrl): string
    {
        $mediaUrl = trim(str_replace(['\\', '�'], ['/', '-'], $mediaUrl));
        if ($mediaUrl === '') {
            return '';
        }

        $isAbsolute = (bool) preg_match('#^https?://#i', $mediaUrl);
        $path = $mediaUrl;

        if ($isAbsolute) {
            $parsedPath = parse_url($mediaUrl, PHP_URL_PATH);
            $path = is_string($parsedPath) ? $parsedPath : '';
            // External non-upload URLs (TikTok/Facebook CDN, etc.) stay absolute.
            if ($path === '' || !preg_match('#/(?:wp-content/|public/)?uploads/#i', $path)) {
                return $mediaUrl;
            }
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/\./#', '/', $path) ?? $path;

        // Strip current base path prefix (/phonesdukan, etc.) when it is a URL path (not a filesystem path).
        $base = '';
        if (defined('BASE_PATH')) {
            $candidate = str_replace('\\', '/', (string) BASE_PATH);
            // Ignore Windows filesystem paths wrongly assigned to BASE_PATH in CLI.
            if ($candidate !== '' && $candidate !== '/' && !preg_match('#^[A-Za-z]:/#', $candidate) && strpos($candidate, '/xampp/') === false) {
                $base = rtrim($candidate, '/');
            }
        }
        if ($base !== '' && strpos($path, $base . '/') === 0) {
            $path = substr($path, strlen($base));
        }

        $trimmed = ltrim($path, '/');

        // Preserve legacy WordPress upload locations.
        if (preg_match('#^(?:.*?)(wp-content/uploads/.+)$#i', $trimmed, $m)) {
            return '/' . $m[1];
        }

        // Preserve / public upload locations.
        if (preg_match('#^(?:.*?)((?:public/)?uploads/.+)$#i', $trimmed, $m)) {
            $uploadsPart = $m[1];
            if (stripos($uploadsPart, 'public/') !== 0) {
                $uploadsPart = 'public/' . ltrim($uploadsPart, '/');
            }
            return '/' . $uploadsPart;
        }

        if ($trimmed !== '' && !$isAbsolute) {
            return '/' . $trimmed;
        }

        return $mediaUrl;
    }
}

if (!function_exists('resolveLocalUploadFilesystemPath')) {
    /**
     * Map a stored upload URL to an absolute filesystem path, or null if not local.
     * Checks /public/uploads and falls back to legacy /wp-content/uploads.
     */
    function resolveLocalUploadFilesystemPath(string $mediaUrl): ?string
    {
        $normalized = normalizeStoredUploadPath($mediaUrl);
        if ($normalized === '' || preg_match('#^https?://#i', $normalized)) {
            return null;
        }

        if (!preg_match('#/(?:wp-content/|public/)?uploads/#i', $normalized)) {
            return null;
        }

        $root = dirname(__DIR__);
        $relative = ltrim($normalized, '/');
        $candidates = [$root . '/' . $relative];

        // If DB points at public/uploads but file only exists in wp-content/uploads (or vice versa).
        if (preg_match('#^(?:public/)?uploads/(.+)$#i', $relative, $m)) {
            $candidates[] = $root . '/public/uploads/' . $m[1];
            $candidates[] = $root . '/wp-content/uploads/' . $m[1];
        } elseif (preg_match('#^wp-content/uploads/(.+)$#i', $relative, $m)) {
            $candidates[] = $root . '/wp-content/uploads/' . $m[1];
            $candidates[] = $root . '/public/uploads/' . $m[1];
        }

        foreach ($candidates as $full) {
            $full = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full);
            if (is_file($full)) {
                return $full;
            }
        }

        return null;
    }
}

if (!function_exists('resolveExistingUploadWebPath')) {
    /**
     * Return the site-relative web path for an upload that actually exists on disk.
     * Prefers the stored location; falls back between public/ and wp-content/ uploads.
     */
    function resolveExistingUploadWebPath(string $mediaUrl): string
    {
        $normalized = normalizeStoredUploadPath($mediaUrl);
        if ($normalized === '' || preg_match('#^https?://#i', $normalized)) {
            return $normalized !== '' ? $normalized : $mediaUrl;
        }

        $fs = resolveLocalUploadFilesystemPath($normalized);
        if ($fs === null) {
            return $normalized;
        }

        $root = realpath(dirname(__DIR__));
        $real = realpath($fs);
        if ($root === false || $real === false) {
            return $normalized;
        }

        $relative = str_replace('\\', '/', substr($real, strlen($root)));
        $relative = '/' . ltrim($relative, '/');

        if (preg_match('#^/wp-content/uploads/#i', $relative) || preg_match('#^/public/uploads/#i', $relative)) {
            return $relative;
        }

        return $normalized;
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
            $canonical = normalizeStoredUploadPath($mediaUrl);
            // External CDN/absolute non-local URLs must not be rewritten.
            if ($canonical === $mediaUrl || preg_match('#^https?://#i', $canonical)) {
                return $mediaUrl;
            }
            $mediaUrl = $canonical;
        } else {
            $mediaUrl = normalizeStoredUploadPath($mediaUrl);
        }

        // Fast path only — do NOT probe the filesystem on every page render
        // (that made admin/home extremely slow on Hostinger). File location
        // fallback is handled by .htaccess (public/uploads → wp-content/uploads)
        // and by tools/repair-product-image-status.php for DB paths.

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

        $encoded = encodeUrlPath($pathOnly) . $query . $fragment;

        // Prefix BASE_PATH on subdirectory installs (localhost/phonesdukan).
        if ($encoded !== '' && !preg_match('#^https?://#i', $encoded) && function_exists('url')) {
            return url($encoded);
        }

        return $encoded;
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

if (!function_exists('getDeployAssetVersion')) {
    /**
     * Global deploy stamp. Bump public/assets/version.txt on each release
     * so every CSS/JS URL changes even if file mtimes are preserved.
     */
    function getDeployAssetVersion(): string
    {
        static $version = null;
        if ($version !== null) {
            return $version;
        }

        $file = getProjectRootPath() . '/public/assets/version.txt';
        if (is_file($file)) {
            $raw = trim((string) file_get_contents($file));
            if ($raw !== '') {
                $version = preg_replace('/[^a-zA-Z0-9._-]/', '', $raw) ?: '1';
                return $version;
            }
        }

        $version = '1';
        return $version;
    }
}

if (!function_exists('assetVersion')) {
    /**
     * Per-file cache-buster: deploy version + file mtime.
     */
    function assetVersion(string $relativePath): string
    {
        $fullPath = assetFilePath($relativePath);
        $parts = [getDeployAssetVersion()];
        if (is_file($fullPath)) {
            $parts[] = (string) filemtime($fullPath);
        }
        return implode('.', $parts);
    }
}

if (!function_exists('assetUrl')) {
    /**
     * Public URL for a CSS/JS/image asset with automatic cache busting.
     */
    function assetUrl(string $relativePath): string
    {
        $url = url($relativePath);
        $version = assetVersion($relativePath);
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'v=' . rawurlencode($version);
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

        echo '<link rel="stylesheet" href="' . htmlspecialchars(assetUrl($relativePath), ENT_QUOTES, 'UTF-8') . '">';
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

        echo '<script src="' . htmlspecialchars(assetUrl($relativePath), ENT_QUOTES, 'UTF-8') . '" defer></script>';
    }
}

if (!function_exists('loadCSS')) {
    function loadCSS()
    {
        $uri = rtrim(getRequestPath(), '/');
        $uri = $uri === '' ? '/' : $uri;

        emitCss('public/assets/css/style.css');
        emitCss('public/assets/css/frontend/header.css');
        emitCss('public/assets/css/frontend/footer.css');
        emitCss('public/assets/css/frontend/wholesale-access.css');
        emitCss('public/assets/css/frontend/deal-of-day.css');
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
        emitJs('public/assets/js/frontend/deal-of-day.js');
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
    /**
     * Only active (in-stock catalog) products should be indexed.
     * Coming soon (2) stays crawlable for users but noindex to avoid Soft 404 / thin pages.
     */
    function isProductStatusIndexable($status): bool
    {
        return (int) $status === 1;
    }
}

if (!function_exists('seoRedirectAwayFromJunkQueryParams')) {
    /**
     * 301-strip legacy WordPress / junk query params that waste crawl budget.
     * Keeps marketing params (utm_*, gclid, fbclid, etc.).
     */
    function seoRedirectAwayFromJunkQueryParams(): void
    {
        if (empty($_GET) || !is_array($_GET)) {
            return;
        }

        $junkKeys = [
            's', 'p', 'page_id', 'attachment_id', 'cat', 'tag', 'author',
            'year', 'monthnum', 'day', 'name', 'pagename', 'm', 'replytocom',
            'preview', 'post_type', 'TB_iframe',
        ];

        $cleanQuery = $_GET;
        $hadJunk = false;
        foreach ($junkKeys as $key) {
            if (array_key_exists($key, $cleanQuery)) {
                unset($cleanQuery[$key]);
                $hadJunk = true;
            }
        }

        if (!$hadJunk) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $qs = http_build_query($cleanQuery);
        $target = $path . ($qs !== '' ? '?' . $qs : '');
        header('Location: ' . $target, true, 301);
        exit;
    }
}

if (!function_exists('seoEnforceListingPagination')) {
    /**
     * Out-of-range ?paged=N listing URLs must hard-404 (not Soft 404 with empty grids).
     */
    function seoEnforceListingPagination(int $paged, int $totalPages): void
    {
        if ($paged <= 1) {
            return;
        }
        if ($totalPages < 1 || $paged > $totalPages) {
            http_response_code(404);
            $notFound = dirname(__DIR__) . '/app/Views/404.php';
            if (is_file($notFound)) {
                include $notFound;
            } else {
                echo '404 - Page Not Found';
            }
            exit;
        }
    }
}

if (!function_exists('seoMerchantShippingDetails')) {
    /** Google Merchant / Product snippet shippingDetails block. */
    function seoMerchantShippingDetails(): array
    {
        return [
            '@type' => 'OfferShippingDetails',
            'shippingRate' => [
                '@type' => 'MonetaryAmount',
                'currency' => 'PKR',
                'value' => '0',
            ],
            'shippingDestination' => [
                '@type' => 'DefinedRegion',
                'addressCountry' => 'PK',
            ],
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => 0,
                    'maxValue' => 1,
                    'unitCode' => 'DAY',
                ],
                'transitTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => 1,
                    'maxValue' => 5,
                    'unitCode' => 'DAY',
                ],
            ],
        ];
    }
}

if (!function_exists('seoMerchantReturnPolicy')) {
    /** Google Merchant / Product snippet return policy block. */
    function seoMerchantReturnPolicy(): array
    {
        return [
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => 'PK',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 14,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => 'https://schema.org/FreeReturn',
        ];
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

if (!function_exists('ensureSiteSettingsSchema')) {
    function ensureSiteSettingsSchema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $db->exec(
                'CREATE TABLE IF NOT EXISTS site_settings (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    site_name VARCHAR(255) NOT NULL DEFAULT \'\',
                    contact_email VARCHAR(255) NOT NULL DEFAULT \'\',
                    footer_text TEXT NULL,
                    announcement_enabled TINYINT(1) NOT NULL DEFAULT 1,
                    announcement_text TEXT NULL,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } catch (Throwable $e) {
            error_log('ensureSiteSettingsSchema create: ' . $e->getMessage());
        }

        $columns = [
            'announcement_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'announcement_text' => 'TEXT NULL',
            'deal_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'deal_badge' => 'VARCHAR(80) NULL DEFAULT NULL',
            'deal_title' => 'VARCHAR(255) NULL DEFAULT NULL',
            'deal_sale_price' => 'VARCHAR(40) NULL DEFAULT NULL',
            'deal_regular_price' => 'VARCHAR(40) NULL DEFAULT NULL',
            'deal_ends_at' => 'DATETIME NULL DEFAULT NULL',
            'deal_image' => 'VARCHAR(512) NULL DEFAULT NULL',
            'deal_cta_text' => 'VARCHAR(80) NULL DEFAULT NULL',
            'deal_cta_url' => 'VARCHAR(512) NULL DEFAULT NULL',
            'deal_note' => 'VARCHAR(255) NULL DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            try {
                $stmt = $db->prepare(
                    'SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = \'site_settings\'
                       AND COLUMN_NAME = ?'
                );
                $stmt->execute([$column]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $db->exec("ALTER TABLE site_settings ADD COLUMN `$column` $definition");
                }
            } catch (Throwable $e) {
                error_log("ensureSiteSettingsSchema column ($column): " . $e->getMessage());
            }
        }

        // Persist current storefront announcement copy when the column is empty
        try {
            $seed = $db->query(
                'SELECT id, announcement_text, footer_text, contact_email, deal_title, deal_enabled
                 FROM site_settings
                 ORDER BY id ASC
                 LIMIT 1'
            );
            $row = $seed ? ($seed->fetch(PDO::FETCH_ASSOC) ?: null) : null;
            if (is_array($row) && (int) ($row['id'] ?? 0) > 0) {
                $updates = [];
                $params = [':id' => (int) $row['id']];

                if (trim((string) ($row['announcement_text'] ?? '')) === '') {
                    $updates[] = 'announcement_text = :announcement_text';
                    $params[':announcement_text'] = getDefaultAnnouncementText();
                }

                $footer = (string) ($row['footer_text'] ?? '');
                if ($footer === '' || strpos($footer, '??') === 0 || strpos($footer, '?') === 0) {
                    $updates[] = 'footer_text = :footer_text';
                    $params[':footer_text'] = '© ' . date('Y') . ' Phones Dukan. All Rights Reserved.';
                }

                $email = trim((string) ($row['contact_email'] ?? ''));
                if ($email === '' || strcasecmp($email, 'admin@phonesdukan.com') === 0) {
                    $updates[] = 'contact_email = :contact_email';
                    $params[':contact_email'] = 'info@phonesdukan.com';
                }

                if (trim((string) ($row['deal_title'] ?? '')) === '') {
                    $defaults = getDefaultDealSettings();
                    $updates[] = 'deal_enabled = :deal_enabled';
                    $updates[] = 'deal_badge = :deal_badge';
                    $updates[] = 'deal_title = :deal_title';
                    $updates[] = 'deal_sale_price = :deal_sale_price';
                    $updates[] = 'deal_regular_price = :deal_regular_price';
                    $updates[] = 'deal_ends_at = :deal_ends_at';
                    $updates[] = 'deal_image = :deal_image';
                    $updates[] = 'deal_cta_text = :deal_cta_text';
                    $updates[] = 'deal_cta_url = :deal_cta_url';
                    $updates[] = 'deal_note = :deal_note';
                    foreach ($defaults as $key => $value) {
                        $params[':' . $key] = $value;
                    }
                }

                if ($updates !== []) {
                    $sql = 'UPDATE site_settings SET ' . implode(', ', $updates) . ' WHERE id = :id';
                    $upd = $db->prepare($sql);
                    $upd->execute($params);
                }
            }
        } catch (Throwable $e) {
            error_log('ensureSiteSettingsSchema seed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('getDefaultAnnouncementText')) {
    function getDefaultAnnouncementText(): string
    {
        return "<strong>Mobile Island</strong> Official Store • We Believe in Satisfaction\n"
            . "Free delivery across Pakistan on selected products\n"
            . "Call / WhatsApp: <strong>+92 311 6600031</strong>";
    }
}

if (!function_exists('getDefaultDealSettings')) {
    function getDefaultDealSettings(): array
    {
        return [
            'deal_enabled' => 1,
            'deal_badge' => 'Deal of the Day',
            'deal_title' => 'Premium Wireless Earbuds — Flash Deal',
            'deal_sale_price' => '4999',
            'deal_regular_price' => '7999',
            'deal_ends_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'deal_image' => 'public/uploads/l-210earbuds6.webp',
            'deal_cta_text' => 'Shop This Deal',
            'deal_cta_url' => '',
            'deal_note' => 'Hurry up! Offer ends in:',
        ];
    }
}

if (!function_exists('getSiteSettings')) {
    function getSiteSettings(?PDO $db = null): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $dealDefaults = getDefaultDealSettings();
        $defaults = array_merge([
            'id' => 0,
            'site_name' => '',
            'contact_email' => '',
            'footer_text' => '',
            'announcement_enabled' => 1,
            'announcement_text' => getDefaultAnnouncementText(),
        ], $dealDefaults);

        try {
            if (!$db instanceof PDO) {
                require_once dirname(__DIR__) . '/database/db.php';
                $db = (new Database())->getConnection();
            }
            ensureSiteSettingsSchema($db);
            $stmt = $db->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
            $row = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
            $cached = array_merge($defaults, is_array($row) ? $row : []);
            $cached['announcement_enabled'] = (int) ($cached['announcement_enabled'] ?? 1) === 1 ? 1 : 0;
            $cached['deal_enabled'] = (int) ($cached['deal_enabled'] ?? 0) === 1 ? 1 : 0;
            if (trim((string) ($cached['announcement_text'] ?? '')) === '') {
                $cached['announcement_text'] = getDefaultAnnouncementText();
            }
            if (trim((string) ($cached['deal_title'] ?? '')) === '') {
                foreach ($dealDefaults as $key => $value) {
                    if (trim((string) ($cached[$key] ?? '')) === '') {
                        $cached[$key] = $value;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('getSiteSettings: ' . $e->getMessage());
            $cached = $defaults;
        }

        return $cached;
    }
}

if (!function_exists('isAnnouncementBarEnabled')) {
    function isAnnouncementBarEnabled(?PDO $db = null): bool
    {
        $settings = getSiteSettings($db);
        return (int) ($settings['announcement_enabled'] ?? 1) === 1;
    }
}

if (!function_exists('getAnnouncementMessages')) {
    /**
     * @return list<string> Sanitized HTML fragments for marquee spans
     */
    function getAnnouncementMessages(?PDO $db = null): array
    {
        $settings = getSiteSettings($db);
        $raw = (string) ($settings['announcement_text'] ?? '');
        if (trim($raw) === '') {
            $raw = getDefaultAnnouncementText();
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $messages = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $messages[] = strip_tags($line, '<strong><b><em><i>');
        }

        if ($messages === []) {
            foreach (preg_split('/\r\n|\r|\n/', getDefaultAnnouncementText()) ?: [] as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $messages[] = strip_tags($line, '<strong><b><em><i>');
                }
            }
        }

        return $messages;
    }
}

if (!function_exists('renderAnnouncementBarHtml')) {
    function renderAnnouncementBarHtml(?PDO $db = null): string
    {
        $messages = getAnnouncementMessages($db);
        if ($messages === []) {
            return '';
        }

        // Duplicate once so the CSS marquee can loop seamlessly
        $loop = array_merge($messages, $messages);
        $html = '';
        foreach ($loop as $message) {
            $html .= '<span>' . $message . '</span>';
        }

        return $html;
    }
}

if (!function_exists('formatDealPriceDisplay')) {
    function formatDealPriceDisplay(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/[a-zA-Z]/', $raw)) {
            return $raw;
        }
        $numeric = preg_replace('/[^\d.]/', '', $raw);
        if ($numeric === '' || !is_numeric($numeric)) {
            return $raw;
        }
        $value = (float) $numeric;
        if (abs($value - round($value)) < 0.001) {
            return 'Rs. ' . number_format($value, 0);
        }
        return 'Rs. ' . number_format($value, 2);
    }
}

if (!function_exists('resolveDealImageUrl')) {
    function resolveDealImageUrl(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            $image = 'public/uploads/l-210earbuds6.webp';
        }
        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }
        return normalizeMediaUrl(url(ltrim($image, '/')));
    }
}

if (!function_exists('isDealCategoryOnlyPath')) {
    /**
     * True when a CTA looks like a category listing, not a product page.
     * Product URLs are brand/category[/subcategory]/product-slug.
     */
    function isDealCategoryOnlyPath(string $ctaUrl): bool
    {
        $path = trim($ctaUrl);
        if ($path === '') {
            return true;
        }
        if (preg_match('#^https?://#i', $path)) {
            $parsed = parse_url($path, PHP_URL_PATH);
            $path = is_string($parsed) ? $parsed : '';
        }
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return true;
        }

        $segments = array_values(array_filter(explode('/', $path), static function ($part) {
            return $part !== '' && $part !== '.';
        }));
        $count = count($segments);
        if ($count <= 1) {
            return true;
        }
        // brand + category only (2 segments) is still a listing page
        if ($count === 2) {
            return true;
        }
        return false;
    }
}

if (!function_exists('findDealProductPathByImage')) {
    function findDealProductPathByImage(string $image, ?PDO $db = null): string
    {
        $image = trim(str_replace('\\', '/', $image));
        if ($image === '') {
            return '';
        }

        $basename = basename($image);
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return '';
        }

        try {
            if ($db === null) {
                if (!class_exists('Database')) {
                    require_once dirname(__DIR__) . '/database/db.php';
                }
                $database = new Database();
                $db = $database->getConnection();
            }

            $sql = "SELECT p.product_id, p.product_slug,
                           b.slug AS brand_slug,
                           c.slug AS category_slug,
                           sc.slug AS subcategory_slug
                    FROM product_images pi
                    INNER JOIN products p ON p.product_id = pi.product_id
                    LEFT JOIN brands b ON b.brand_id = p.brand_id
                    LEFT JOIN categories c ON c.category_id = p.category_id
                    LEFT JOIN categories sc ON sc.category_id = p.subcategory_id
                    WHERE pi.status = 1
                      AND (
                        pi.image_url = :exact
                        OR pi.image_url = :slash
                        OR pi.image_url LIKE :basename
                      )
                    ORDER BY pi.is_primary DESC, p.product_id DESC
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $exact = ltrim($image, '/');
            $stmt->execute([
                ':exact' => $exact,
                ':slash' => '/' . $exact,
                ':basename' => '%' . $basename,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return '';
            }

            $brand = trim((string) ($row['brand_slug'] ?? ''));
            $category = trim((string) ($row['category_slug'] ?? ''));
            $product = trim((string) ($row['product_slug'] ?? ''));
            if ($brand === '' || $category === '' || $product === '') {
                return '';
            }

            return trim(buildProductPathFromRow($row), '/');
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('resolveDealCtaUrl')) {
    function resolveDealCtaUrl(string $ctaUrl, ?string $dealImage = null, ?PDO $db = null): string
    {
        $ctaUrl = trim($ctaUrl);

        // Prefer an explicit product URL when it has brand/category/product segments.
        if ($ctaUrl !== '' && !isDealCategoryOnlyPath($ctaUrl)) {
            if (preg_match('#^https?://#i', $ctaUrl)) {
                return rtrim($ctaUrl, '/') . '/';
            }
            return rtrim(url(trim($ctaUrl, '/')), '/') . '/';
        }

        // Category-only / empty CTAs: resolve from the deal product image.
        if ($dealImage !== null && trim($dealImage) !== '') {
            $fromImage = findDealProductPathByImage($dealImage, $db);
            if ($fromImage !== '') {
                return rtrim(url($fromImage), '/') . '/';
            }

            // Known deal asset fallback (matches live product permalink).
            $imageBase = strtolower(basename(str_replace('\\', '/', $dealImage)));
            if (strpos($imageBase, 'l-210') !== false) {
                return rtrim(url('login/wireless-earbuds/Login-L-210-Earbuds'), '/') . '/';
            }
        }

        if ($ctaUrl === '') {
            return url();
        }
        if (preg_match('#^https?://#i', $ctaUrl)) {
            return $ctaUrl;
        }
        return url(ltrim($ctaUrl, '/'));
    }
}

if (!function_exists('isDealPopupEnabled')) {
    function isDealPopupEnabled(?PDO $db = null): bool
    {
        if (function_exists('isPhonesDukanApp') && isPhonesDukanApp()) {
            return false;
        }

        $settings = getSiteSettings($db);
        if ((int) ($settings['deal_enabled'] ?? 0) !== 1) {
            return false;
        }
        if (trim((string) ($settings['deal_title'] ?? '')) === '') {
            return false;
        }

        $endsAt = trim((string) ($settings['deal_ends_at'] ?? ''));
        if ($endsAt !== '') {
            $ts = strtotime($endsAt);
            if ($ts !== false && $ts < time()) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('renderDealPopupHtml')) {
    function renderDealPopupHtml(?PDO $db = null): string
    {
        if (!isDealPopupEnabled($db)) {
            return '';
        }

        $settings = getSiteSettings($db);
        $badge = trim((string) ($settings['deal_badge'] ?? 'Deal of the Day'));
        $title = trim((string) ($settings['deal_title'] ?? ''));
        $note = trim((string) ($settings['deal_note'] ?? 'Hurry up! Offer ends in:'));
        $sale = formatDealPriceDisplay((string) ($settings['deal_sale_price'] ?? ''));
        $regular = formatDealPriceDisplay((string) ($settings['deal_regular_price'] ?? ''));
        $ctaText = trim((string) ($settings['deal_cta_text'] ?? 'Shop This Deal'));
        if ($ctaText === '') {
            $ctaText = 'Shop This Deal';
        }
        $ctaUrl = resolveDealCtaUrl(
            (string) ($settings['deal_cta_url'] ?? ''),
            (string) ($settings['deal_image'] ?? ''),
            $db
        );
        $imageUrl = resolveDealImageUrl((string) ($settings['deal_image'] ?? ''));
        $endsAt = trim((string) ($settings['deal_ends_at'] ?? ''));
        $endsAtIso = '';
        if ($endsAt !== '') {
            $ts = strtotime($endsAt);
            if ($ts !== false) {
                $endsAtIso = date('c', $ts);
            }
        }

        $badgeEsc = htmlspecialchars($badge, ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $noteEsc = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
        $saleEsc = htmlspecialchars($sale, ENT_QUOTES, 'UTF-8');
        $regularEsc = htmlspecialchars($regular, ENT_QUOTES, 'UTF-8');
        $ctaTextEsc = htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8');
        $ctaUrlEsc = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
        $imageUrlEsc = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
        $endsAtEsc = htmlspecialchars($endsAtIso, ENT_QUOTES, 'UTF-8');
        $dismissKey = htmlspecialchars('pd_deal_' . substr(sha1($title . '|' . $endsAt), 0, 12), ENT_QUOTES, 'UTF-8');

        $regularHtml = $regular !== ''
            ? '<span class="pd-deal-price-old">' . $regularEsc . '</span>'
            : '';

        $timerHtml = $endsAtIso !== ''
            ? '<p class="pd-deal-urgency">' . $noteEsc . '</p>
                <div class="pd-deal-timer" data-deal-ends="' . $endsAtEsc . '" aria-label="Offer countdown">
                    <div class="pd-deal-timer-unit"><span data-deal-days>00</span><small>Days</small></div>
                    <div class="pd-deal-timer-unit"><span data-deal-hours>00</span><small>Hours</small></div>
                    <div class="pd-deal-timer-unit"><span data-deal-mins>00</span><small>Mins</small></div>
                    <div class="pd-deal-timer-unit"><span data-deal-secs>00</span><small>Secs</small></div>
                </div>'
            : '';

        return '<div id="pd-deal-popup" class="pd-deal-popup" hidden aria-hidden="true" data-dismiss-key="' . $dismissKey . '">
            <div class="pd-deal-backdrop" data-deal-close tabindex="-1" aria-hidden="true"></div>
            <div class="pd-deal-dialog" role="dialog" aria-modal="true" aria-labelledby="pd-deal-title">
                <button type="button" class="pd-deal-close" data-deal-close aria-label="Close deal offer">&times;</button>
                <div class="pd-deal-grid">
                    <div class="pd-deal-copy">
                        <span class="pd-deal-badge">' . $badgeEsc . '</span>
                        <h2 id="pd-deal-title" class="pd-deal-title">' . $titleEsc . '</h2>
                        <div class="pd-deal-prices">
                            <span class="pd-deal-price-sale">' . $saleEsc . '</span>
                            ' . $regularHtml . '
                        </div>
                        ' . $timerHtml . '
                        <ul class="pd-deal-perks">
                            <li>Official store warranty</li>
                            <li>Fast delivery across Pakistan</li>
                            <li>Limited-time store deal</li>
                        </ul>
                        <a class="pd-deal-cta" href="' . $ctaUrlEsc . '">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            ' . $ctaTextEsc . '
                        </a>
                    </div>
                    <div class="pd-deal-media">
                        <div class="pd-deal-media-stage">
                            <span class="pd-deal-media-ring" aria-hidden="true"></span>
                            <span class="pd-deal-media-shine" aria-hidden="true"></span>
                            <img src="' . $imageUrlEsc . '" alt="' . $titleEsc . '" loading="eager" decoding="async">
                            <span class="pd-deal-media-base" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
}
