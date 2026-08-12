<?php
// In index.php (typically at the top)

// Enable error reporting (can be disabled in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/config/bootstrap.php';

// Step 1: Include the redirects mapping file
$oldRoutes = include __DIR__ . '/app/old_routes.php'; // Adjust path to app/ directory

// Step 2: Check the current request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

if (BASE_PATH !== '' && strpos($requestUri, BASE_PATH) === 0) {
    $requestUri = substr($requestUri, strlen(BASE_PATH));
}

$requestUri = $requestUri === '' ? '/' : $requestUri;

// Strip legacy WordPress / junk query params that cause Soft 404 crawl waste
// (e.g. /?s=, /?p=, /?page_id= serving the homepage at HTTP 200).
if (function_exists('seoRedirectAwayFromJunkQueryParams')) {
    seoRedirectAwayFromJunkQueryParams();
} elseif (!empty($_GET)) {
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
    if ($hadJunk) {
        $qs = http_build_query($cleanQuery);
        $target = $requestUri . ($qs !== '' ? '?' . $qs : '');
        if (BASE_PATH !== '' && strpos($target, BASE_PATH) !== 0) {
            $target = rtrim(BASE_PATH, '/') . $target;
        }
        header('Location: ' . $target, true, 301);
        exit;
    }
}

// Step 3: Redirect if necessary
$redirectTarget = $oldRoutes[$requestUri]
    ?? $oldRoutes[rtrim($requestUri, '/') . '/']
    ?? null;

if ($redirectTarget !== null) {
    if (!preg_match('#^https?://#i', $redirectTarget)) {
        $redirectTarget = (BASE_PATH === '' ? '' : BASE_PATH) . '/' . ltrim($redirectTarget, '/');
    }

    // Send a 301 Moved Permanently response
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $redirectTarget);
    exit(); // Stop further execution of the script
}

// Step 4: If no redirect, continue loading the routes.php file
$routesFile = __DIR__ . '/app/routes.php';
if (file_exists($routesFile)) {
    require_once $routesFile;
} else {
    die("Error: routes.php file not found at $routesFile");
}
