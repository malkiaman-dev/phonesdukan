<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

class BrandController {
    public function showBrand($brand, $category) {
        // Static brand pages: try brand_category and legacy category_brand filenames.
        foreach ([$brand . '_' . $category, $category . '_' . $brand] as $viewKey) {
            $view_file = __DIR__ . "/../Views/brands/{$viewKey}.php";
            if (file_exists($view_file)) {
                include $view_file;
                return;
            }
        }

        // Fallback: resolve brand + category slugs and open filtered shop listing.
        try {
            $db = (new Database())->getConnection();

            $brandStmt = $db->prepare('SELECT brand_id FROM brands WHERE slug = :slug LIMIT 1');
            $brandStmt->bindValue(':slug', (string)$brand, PDO::PARAM_STR);
            $brandStmt->execute();
            $brandId = (int)$brandStmt->fetchColumn();

            $catStmt = $db->prepare('SELECT category_id FROM categories WHERE slug = :slug AND parent_id IS NULL LIMIT 1');
            $catStmt->bindValue(':slug', (string)$category, PDO::PARAM_STR);
            $catStmt->execute();
            $categoryId = (int)$catStmt->fetchColumn();

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

        http_response_code(404);
        include __DIR__ . '/../Views/404.php';
    }
}
?>
