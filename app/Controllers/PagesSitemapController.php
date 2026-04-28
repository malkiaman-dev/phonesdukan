<?php
require_once __DIR__ . '/../Models/PagesSitemapModel.php';

class PagesSitemapController {
    public function generateSitemap() {
        ob_clean();
        header("Content-Type: application/xml; charset=UTF-8");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        $directories = [
            'https://www.phonesdukan.com/mobiles-price-list' => __DIR__ . '/../Views/mobiles-price-list/',
            'https://www.phonesdukan.com/static-pages'       => __DIR__ . '/../Views/static-pages/',
        ];

        $model = new PagesSitemapModel();
        $pages = $model->getAllPhpPages($directories);

        // Explicitly add wholesale.php from /app/Views/products/
        $wholesaleFile = __DIR__ . '/../Views/products/wholesale.php';
        if (file_exists($wholesaleFile)) {
            $pages[] = [
                'url' => 'https://www.phonesdukan.com/wholesale',
                'lastmod' => date('Y-m-d', filemtime($wholesaleFile))
            ];
        }

        if (empty($pages)) {
            echo '<error>No pages found</error>' . PHP_EOL;
        } else {
            foreach ($pages as $page) {
                echo '  <url>' . PHP_EOL;
                echo '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
                echo '    <lastmod>' . $page['lastmod'] . '</lastmod>' . PHP_EOL;
                echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
                echo '    <priority>0.5</priority>' . PHP_EOL;
                echo '  </url>' . PHP_EOL;
            }
        }

        echo '</urlset>' . PHP_EOL;
    }
}

$controller = new PagesSitemapController();
$controller->generateSitemap();
?>