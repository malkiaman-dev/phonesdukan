<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../Models/SitemapModel.php';
require_once __DIR__ . '/../Models/ProductImageSitemapModel.php';
require_once __DIR__ . '/../Models/NewsSitemapModel.php';
require_once __DIR__ . '/../../database/db.php';

class SitemapController {
    public function index() {
        header("Content-Type: application/xml; charset=UTF-8");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        $sitemaps = [
            'post-sitemap.xml',
            'post_category-sitemap.xml',
            'page-sitemap.xml',
            'product-sitemap.xml',
            'images-sitemap.xml',
            'product_cat-sitemap.xml',
            'news-sitemap.xml', // Add News Sitemap
        ];

        foreach ($sitemaps as $sitemap) {
            echo '  <sitemap>' . PHP_EOL;
            echo '    <loc>https://www.phonesdukan.com/' . $sitemap . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
            echo '  </sitemap>' . PHP_EOL;
        }

        echo '</sitemapindex>';

        exit;
    }
}
?>