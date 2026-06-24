<?php
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$apk = pd_resolve_app_apk(true);
$downloadName = 'PhonesDukan.apk';

if ($apk === null) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'The app file is not available right now. Please try again later.',
    ]);
    exit;
}

$apkPath = $apk['path'];
$etag = '"' . md5($apk['path'] . ':' . $apk['mtime'] . ':' . $apk['size']) . '"';
$lastModified = gmdate('D, d M Y H:i:s', $apk['mtime']) . ' GMT';

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . $apk['size']);
header('ETag: ' . $etag);
header('Last-Modified: ' . $lastModified);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-PhonesDukan-App-Version: ' . $apk['version']);

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag)
    || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $apk['mtime'])
) {
    http_response_code(304);
    exit;
}

readfile($apkPath);
exit;
