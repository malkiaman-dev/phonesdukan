<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/helpers.php';
require_once __DIR__ . '/../Models/ProductModel.php';
require_once __DIR__ . '/../Models/ReviewModel.php';
require_once __DIR__ . '/../Models/VariationModel.php';

class ProductController
{
    public function showProduct($category_slug, $brand_slug, $product_slug)
    {
        $dbInstance = new Database();
        $db = $dbInstance->getConnection();

        $productModel = new ProductModel();
        $reviewModel = new ReviewModel();

        // Primary lookup: all three slugs must match
        $product = $productModel->getProductBySlug($category_slug, $brand_slug, $product_slug);

        // Fallback: match only by product_slug. If found, the category/brand
        // part of the URL is wrong — redirect to the canonical URL (301).
        if (!$product) {
            $product = $productModel->getProductByProductSlugOnly($product_slug);
            if ($product) {
                $correctCat   = (string)($product['category_slug'] ?? '');
                $correctBrand = (string)($product['brand_slug']   ?? '');
                $correctSlug  = (string)($product['product_slug'] ?? $product_slug);
                $baseUrl = rtrim(getBaseURL(), '/');
                header('Location: ' . $baseUrl . '/' . ltrim($correctCat . '/' . $correctBrand . '/' . $correctSlug, '/'), true, 301);
                exit();
            }
            // Also try case-insensitive match on product_slug
            $product = $productModel->getProductBySlugCaseInsensitive($category_slug, $brand_slug, $product_slug);
        }

        if ($product) {
            // Get product images, reviews, and attributes
            $images = $productModel->getProductImages($product['product_id']);
            $isComingSoon = !empty($product['product_tag']) && stripos($product['product_tag'], 'coming_soon') !== false;

            // Fetch product attributes (legacy system)
            $productAttributes = $productModel->getProductAttributes($product['product_id']);

            // Fetch new variation system data — wrapped so any DB error never kills the page
            $productVariations = [];
            $variationTypes    = [];
            $isVariableProduct = false;
            try {
                $variationModel    = new VariationModel();
                $productVariations = $variationModel->getProductVariationsForFrontend($product['product_id']);
                $variationTypes    = $variationModel->getVariationTypesWithValues();
                $isVariableProduct = !empty($product['product_type'])
                    && $product['product_type'] === 'variable'
                    && !empty($productVariations);
            } catch (\Throwable $e) {
                error_log('ProductController variation load error: ' . $e->getMessage());
            }

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