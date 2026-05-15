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

        try {
            $model    = new NewsSitemapModel($this->db);
            $articles = $model->getRecentNewsArticles();
        } catch (Exception $e) {
            error_log('News Sitemap Error: ' . $e->getMessage());
            $articles = [];
        }

        header('Content-Type: application/xml; charset=UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        echo '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . PHP_EOL;

        foreach ($articles as $article) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars($article['url'], ENT_XML1, 'UTF-8') . '</loc>' . PHP_EOL;
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

        echo '</urlset>' . PHP_EOL;
    }
}

$controller = new NewsSitemapController();
$controller->generateSitemap();
?>