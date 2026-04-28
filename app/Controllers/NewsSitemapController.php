<?php
require_once __DIR__ . '/../Models/NewsSitemapModel.php';
require_once __DIR__ . '/../../database/db.php';

class NewsSitemapController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function generateSitemap() {
        ob_clean();
        header("Content-Type: application/xml; charset=UTF-8");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . PHP_EOL;

        try {
            $model = new NewsSitemapModel($this->db);
            $articles = $model->getRecentNewsArticles();

            if (empty($articles)) {
                echo '<error>No recent news articles found</error>' . PHP_EOL;
            } else {
                foreach ($articles as $article) {
                    echo '  <url>' . PHP_EOL;
                    echo '    <loc>' . htmlspecialchars($article['url']) . '</loc>' . PHP_EOL;
                    echo '    <news:news>' . PHP_EOL;
                    echo '      <news:publication>' . PHP_EOL;
                    echo '        <news:name>Phones Dukan</news:name>' . PHP_EOL;
                    echo '        <news:language>en</news:language>' . PHP_EOL;
                    echo '      </news:publication>' . PHP_EOL;
                    echo '      <news:publication_date>' . $article['publication_date'] . '</news:publication_date>' . PHP_EOL;
                    echo '      <news:title>' . $article['title'] . '</news:title>' . PHP_EOL;
                    echo '    </news:news>' . PHP_EOL;
                    echo '  </url>' . PHP_EOL;
                }
            }
        } catch (Exception $e) {
            error_log("News Sitemap Error: " . $e->getMessage());
            echo '<error>Failed to generate sitemap: ' . htmlspecialchars($e->getMessage()) . '</error>' . PHP_EOL;
        }

        echo '</urlset>' . PHP_EOL;
    }
}

$controller = new NewsSitemapController();
$controller->generateSitemap();
?>