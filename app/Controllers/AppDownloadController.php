<?php

class AppDownloadController
{
    public function download(): void
    {
        require_once dirname(__DIR__, 2) . '/includes/functions.php';
        require_once dirname(__DIR__) . '/config/app_download.php';

        $apk = pd_resolve_app_apk();
        if ($apk === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'App download is not available yet. Please try again later.';
            exit;
        }

        $filename = defined('APK_DOWNLOAD_FILENAME') ? APK_DOWNLOAD_FILENAME : ($apk['basename'] ?? 'phonesdukan-app.apk');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $etag = '"' . (!empty($apk['hash']) ? $apk['hash'] : md5($apk['path'] . ':' . $apk['mtime'] . ':' . $apk['size'])) . '"';

        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $apk['size']);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $apk['mtime']) . ' GMT');
        header('ETag: ' . $etag);
        header('X-Content-Type-Options: nosniff');
        header('X-PhonesDukan-App-Version: ' . ($apk['version'] ?? $apk['mtime']));

        readfile($apk['path']);
        exit;
    }
}
