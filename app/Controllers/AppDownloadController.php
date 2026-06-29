<?php

class AppDownloadController
{
    public function download(): void
    {
        require_once dirname(__DIR__) . '/config/app_download.php';

        $apkPath = getAppApkFilePath();
        if (!is_file($apkPath) || !is_readable($apkPath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'App download is not available yet. Please try again later.';
            exit;
        }

        $filename = defined('APK_DOWNLOAD_FILENAME') ? APK_DOWNLOAD_FILENAME : 'phonesdukan-app.apk';
        $fileSize = filesize($apkPath);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $fileSize);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($apkPath)) . ' GMT');
        header('ETag: "' . md5($apkPath . ':' . filemtime($apkPath) . ':' . $fileSize) . '"');
        header('X-Content-Type-Options: nosniff');

        $handle = fopen($apkPath, 'rb');
        if ($handle === false) {
            http_response_code(500);
            echo 'Unable to read app file.';
            exit;
        }

        fpassthru($handle);
        fclose($handle);
        exit;
    }
}
