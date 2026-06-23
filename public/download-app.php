<?php
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$apkPath = dirname(__DIR__) . '/public/downloads/phonesdukan.apk';
$downloadName = 'PhonesDukan.apk';

if (!is_file($apkPath)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'The app file is not available right now. Please try again later.',
    ]);
    exit;
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($apkPath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($apkPath);
exit;
