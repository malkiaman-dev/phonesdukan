<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

class BrandController {
    public function showBrand($category, $brand) {
        // Keep existing static brand pages working first (e.g. mobiles_samsung.php).
        $view_file = __DIR__ . "/../Views/brands/{$category}_{$brand}.php";
        if (file_exists($view_file)) {
            include $view_file;
            return;
        }

        // Fallback: resolve category + brand slugs and open filtered shop listing.
        try {
            $db = (new Database())->getConnection();

            $catStmt = $db->prepare('SELECT category_id FROM categories WHERE slug = :slug LIMIT 1');
            $catStmt->bindValue(':slug', (string)$category, PDO::PARAM_STR);
            $catStmt->execute();
            $categoryId = (int)$catStmt->fetchColumn();

            $brandStmt = $db->prepare('SELECT brand_id FROM brands WHERE slug = :slug LIMIT 1');
            $brandStmt->bindValue(':slug', (string)$brand, PDO::PARAM_STR);
            $brandStmt->execute();
            $brandId = (int)$brandStmt->fetchColumn();

            if ($categoryId > 0 && $brandId > 0) {
                $query = http_build_query([
                    'category' => [$categoryId],
                    'brand' => [$brandId],
                ]);

                header('Location: ' . url('shop') . '?' . $query, true, 302);
                exit();
            }
        } catch (Throwable $e) {
            // Fall through to 404 below.
        }

        include __DIR__ . '/../Views/404.php';
    }
}
?>
