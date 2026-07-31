<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once __DIR__ . '/../Models/ProductModel.php';
require_once __DIR__ . '/../Models/ReviewModel.php';
require_once __DIR__ . '/../Models/VariationModel.php';

class ProductController
{
    private function redirectToCanonical(array $product, int $status = 301): void
    {
        $baseUrl = rtrim(getBaseURL(), '/');
        header('Location: ' . $baseUrl . buildProductPathFromRow($product) . '/', true, $status);
        exit();
    }

    public function showProductThreeSegments(string $seg1, string $seg2, string $seg3): void
    {
        $productModel = new ProductModel();

        // New canonical: /brand/category/product
        $product = $productModel->getProductByPermalinkNoSub($seg1, $seg2, $seg3);
        if ($product) {
            $this->renderProduct($product, $seg1, $seg2, $seg3);
            return;
        }

        // Legacy: /category/brand/product → 301 to canonical
        $legacy = $productModel->getProductByLegacyPermalink($seg1, $seg2, $seg3);
        if ($legacy) {
            $this->redirectToCanonical($legacy);
        }

        // Slug-only fallback
        $product = $productModel->getProductByProductSlugOnly($seg3);
        if ($product) {
            $this->redirectToCanonical($product);
        }

        http_response_code(404);
        include __DIR__ . '/../Views/404.php';
    }

    public function showProduct(string $brand_slug, string $category_slug, string $subcategory_slug, string $product_slug): void
    {
        $productModel = new ProductModel();
        $product = $productModel->getProductByPermalink($brand_slug, $category_slug, $subcategory_slug, $product_slug);

        if (!$product) {
            $product = $productModel->getProductByProductSlugOnly($product_slug);
            if ($product) {
                $this->redirectToCanonical($product);
            }
            http_response_code(404);
            include __DIR__ . '/../Views/404.php';
            return;
        }

        $this->renderProduct($product, $brand_slug, $category_slug, $product_slug, $subcategory_slug);
    }

    private function renderProduct(
        array $product,
        string $brand_slug,
        string $category_slug,
        string $product_slug,
        ?string $subcategory_slug = null
    ): void {
        $dbInstance = new Database();
        $db = $dbInstance->getConnection();

        $productModel = new ProductModel();
        $reviewModel = new ReviewModel();

        $galleryMedia = $productModel->getProductGalleryMedia($product['product_id']);
        $images = array_values(array_filter($galleryMedia, static function (array $item): bool {
            return ($item['type'] ?? 'image') === 'image';
        }));
        $isComingSoon = isProductComingSoon($product);

        $productAttributes = $productModel->getProductAttributes($product['product_id']);

        $productVariations = [];
        $variationTypes = [];
        $isVariableProduct = false;
        try {
            $variationModel = new VariationModel();
            $productVariations = $variationModel->getProductVariationsForFrontend($product['product_id']);
            $variationTypes = $variationModel->getVariationTypesWithValues();
            $isVariableProduct = !empty($product['product_type'])
                && $product['product_type'] === 'variable'
                && !empty($productVariations);
        } catch (\Throwable $e) {
            error_log('ProductController variation load error: ' . $e->getMessage());
        }

        $stmt = $db->prepare('SELECT * FROM product_seo WHERE product_id = :product_id LIMIT 1');
        $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
        $stmt->execute();
        $seo = $stmt->fetch(PDO::FETCH_ASSOC);

        $pageTitle = $seo['seo_title'] ?? $product['product_name'];
        $metaDescription = $seo['seo_description'] ?? $product['product_description'];
        $metaKeywords = $seo['focus_keyword'] ?? '';
        $metaRobots = isProductStatusIndexable($product['product_status'] ?? 0) ? 'index, follow' : 'noindex';
        $canonicalUrl = $seo['canonical_url'] ?? '';
        $pageUrl = rtrim(getBaseURL(), '/') . buildProductPathFromRow($product);

        $productPrice = (isset($product['sale_price']) && $product['sale_price'] > 0)
            ? $product['sale_price']
            : (isset($product['regular_price']) && $product['regular_price'] > 0 ? $product['regular_price'] : 0);
        $productPrice = is_numeric($productPrice) ? (float) $productPrice : 0;
        $formattedProductPrice = ($productPrice > 0) ? number_format($productPrice, 2) : '0.00';

        if ($isComingSoon) {
            $productAvailability = 'comingsoon';
        } else {
            $productAvailability = ($product['product_status'] == 1 && $product['stock_quantity'] > 0)
                ? 'instock'
                : 'outofstock';
        }

        $schema = $productModel->generateProductSchema($product['product_id']);

        $relatedProducts = $productModel->getRelatedProducts(
            $product['category_id'],
            $product['brand_id'],
            $product['product_id']
        );

        require_once __DIR__ . '/../Models/ProductGroupModel.php';
        $groupProducts = (new ProductGroupModel())->getGroupProductsForDisplay((int) $product['product_id']);

        require_once __DIR__ . '/../Views/products/product.php';
        require_once __DIR__ . '/../Views/products/related-products.php';
    }
}
