<?php
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$apk = pd_resolve_app_apk(true);
$downloadName = 'PhonesDukan.apk';

if ($apk === null) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode([
        'error' => 'The app file is not available right now. Please try again later.',
    ]);
    exit;
}

$apkPath = $apk['path'];
$etag = '"' . (!empty($apk['hash']) ? $apk['hash'] : md5($apk['path'] . ':' . $apk['mtime'] . ':' . $apk['size'])) . '"';

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . $apk['size']);
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $apk['mtime']) . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-PhonesDukan-App-Version: ' . $apk['version']);

readfile($apkPath);
exit;
