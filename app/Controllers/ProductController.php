<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/helpers.php';
require_once __DIR__ . '/../Models/ProductModel.php';
require_once __DIR__ . '/../Models/ReviewModel.php';

class ProductController
{
    public function showProduct($category_slug, $brand_slug, $product_slug)
    {
        $dbInstance = new Database();
        $db = $dbInstance->getConnection();

        $productModel = new ProductModel();
        $reviewModel = new ReviewModel();

        // Fetch the product by its slug
        $product = $productModel->getProductBySlug($category_slug, $brand_slug, $product_slug);

        if ($product) {
            // Get product images, reviews, and attributes
            $images = $productModel->getProductImages($product['product_id']);
            $isComingSoon = !empty($product['product_tag']) && stripos($product['product_tag'], 'coming_soon') !== false;

            // Fetch product attributes
            $productAttributes = $productModel->getProductAttributes($product['product_id']);

            // Fetch SEO data
            $stmt = $db->prepare('SELECT * FROM product_seo WHERE product_id = :product_id LIMIT 1');
            $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
            $stmt->execute();
            $seo = $stmt->fetch(PDO::FETCH_ASSOC);

            // SEO and Meta Tags
            $pageTitle = $seo['seo_title'] ?? $product['product_name'];
            $metaDescription = $seo['seo_description'] ?? $product['product_description'];
            $metaKeywords = $seo['focus_keyword'] ?? '';
            $metaRobots = ($product['product_status'] == 1) ? 'index, follow' : 'noindex';
            $canonicalUrl = $seo['canonical_url'] ?? '';
            // Build product URL without relying on theme helpers that may not be loaded here yet.
            $pageUrl = rtrim(getBaseURL(), '/') . '/'
                . ltrim($category_slug . '/' . $brand_slug . '/' . $product_slug, '/');

            // Product Price
            $productPrice = (isset($product['sale_price']) && $product['sale_price'] > 0)
                ? $product['sale_price']
                : (isset($product['regular_price']) && $product['regular_price'] > 0 ? $product['regular_price'] : 0);
            $productPrice = is_numeric($productPrice) ? (float)$productPrice : 0;
            $formattedProductPrice = ($productPrice > 0) ? number_format($productPrice, 2) : '0.00';

            // Product Availability
            $productAvailability = ($product['product_status'] == 1 && $product['stock_quantity'] > 0)
                ? 'instock'
                : 'outofstock';

            // Generate Schema JSON
            $schema = $productModel->generateProductSchema($product['product_id']);

            // Related Products
            $relatedProducts = $productModel->getRelatedProducts(
                $product['category_id'],
                $product['brand_id'],
                $product['product_id']
            );

            // Include View
            require_once __DIR__ . '/../Views/products/product.php';
            require_once __DIR__ . '/../Views/products/related-products.php';
        } else {
            // Display 404 page
            include __DIR__ . '/../Views/404.php';
        }
    }
}
?>