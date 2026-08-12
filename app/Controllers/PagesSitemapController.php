<?php
require_once __DIR__ . '/../Models/PagesSitemapModel.php';

class PagesSitemapController {
    public function generateSitemap() {
        ob_clean();
        header("Content-Type: application/xml; charset=UTF-8");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Real public routes only — never emit /static-pages/... paths (those 404).
        $staticPages = [
            'about-us' => __DIR__ . '/../Views/static-pages/about-us.php',
            'contact-us' => __DIR__ . '/../Views/static-pages/contact.php',
            'return-policy' => __DIR__ . '/../Views/static-pages/return-policy.php',
            'privacy-policy' => __DIR__ . '/../Views/static-pages/privacy-policy.php',
            'shipping-policy' => __DIR__ . '/../Views/static-pages/shipping-policy.php',
            'terms-and-conditions' => __DIR__ . '/../Views/static-pages/terms-and-conditions.php',
            'write-for-us' => __DIR__ . '/../Views/static-pages/write-for-us.php',
            'wholesale' => __DIR__ . '/../Views/products/wholesale.php',
            'coming-soon-products' => __DIR__ . '/../Views/coming-soon-products.php',
            'shop' => __DIR__ . '/../Views/shop/index.php',
        ];

        $pages = [];
        foreach ($staticPages as $slug => $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $pages[] = [
                'url' => 'https://www.phonesdukan.com/' . $slug . '/',
                'lastmod' => date('Y-m-d', filemtime($filePath)),
            ];
        }

        $priceListDir = __DIR__ . '/../Views/mobiles-price-list/';
        if (is_dir($priceListDir)) {
            foreach (scandir($priceListDir) ?: [] as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }
                $slug = pathinfo($file, PATHINFO_FILENAME);
                if ($slug === '' || strtolower($slug) === 'index') {
                    continue;
                }
                $filePath = $priceListDir . $file;
                $pages[] = [
                    'url' => 'https://www.phonesdukan.com/mobiles-price-list/' . $slug . '/',
                    'lastmod' => date('Y-m-d', filemtime($filePath)),
                ];
            }
        }

        foreach ($pages as $page) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $page['lastmod'] . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
            echo '    <priority>0.5</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>' . PHP_EOL;
    }
}

$controller = new PagesSitemapController();
$controller->generateSitemap();
?>
