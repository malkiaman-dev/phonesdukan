<?php
require_once __DIR__ . '/../Models/SitemapModel.php';
require_once __DIR__ . '/../Models/ProductImageSitemapModel.php';
require_once __DIR__ . '/../Models/NewsSitemapModel.php';
require_once __DIR__ . '/../../database/db.php';

class SitemapController {
    public function index() {
        header("Content-Type: application/xml; charset=UTF-8");

        ob_clean();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        $today = date('Y-m-d');

        // Priority order: products first (highest crawl value), then posts, then supporting sitemaps
        $sitemaps = [
            ['name' => 'product-sitemap.xml',      'lastmod' => $today],
            ['name' => 'product_cat-sitemap.xml',  'lastmod' => $today],
            ['name' => 'images-sitemap.xml',       'lastmod' => $today],
            ['name' => 'post-sitemap.xml',         'lastmod' => $today],
            ['name' => 'post_category-sitemap.xml','lastmod' => $today],
            ['name' => 'news-sitemap.xml',         'lastmod' => $today],
            ['name' => 'page-sitemap.xml',         'lastmod' => $today],
        ];

        foreach ($sitemaps as $sitemap) {
            echo '  <sitemap>' . PHP_EOL;
            echo '    <loc>https://www.phonesdukan.com/' . htmlspecialchars($sitemap['name']) . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $sitemap['lastmod'] . '</lastmod>' . PHP_EOL;
            echo '  </sitemap>' . PHP_EOL;
        }

        echo '</sitemapindex>';

        exit;
    }
}
?>