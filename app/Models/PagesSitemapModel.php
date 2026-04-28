<?php
class PagesSitemapModel {
    public function getAllPhpPages($directories) {
        $allPages = [];

        foreach ($directories as $baseUrl => $dirPath) {
            if (!is_dir($dirPath)) continue;

            $files = scandir($dirPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $slug = pathinfo($file, PATHINFO_FILENAME);
                    $filePath = $dirPath . '/' . $file;

                    // Ensure the file exists and get correct ISO 8601 format
                    if (file_exists($filePath)) {
                        $lastmodTime = filemtime($filePath);
                        $allPages[] = [
                            'url' => rtrim($baseUrl, '/') . '/' . $slug . '/',
                            'lastmod' => date('c', $lastmodTime) // ISO 8601 format (e.g. 2025-04-24T12:34:56+00:00)
                        ];
                    }
                }
            }
        }

        return $allPages;
    }
}
