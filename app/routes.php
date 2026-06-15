<?php
require_once dirname(__DIR__, 1) . '/database/db.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$basePath = rtrim((string) $basePath, '/');

if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
    $requestPath = substr($requestPath, strlen($basePath));
}

$requestPath = '/' . ltrim($requestPath, '/');
$request_uri = trim($requestPath, '/');

$redirectTo = function (string $path, int $status = 302): void {
    $target = $path;

    if (!preg_match('#^https?://#i', $target)) {
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $target = ($baseUrl === '' ? '' : $baseUrl) . '/' . ltrim($target, '/');
    }

    header('Location: ' . $target, true, $status);
    exit;
};

// Fetch active blog category slugs from the database
$database = new Database();
$conn = $database->getConnection();
$categories_query = "SELECT slug FROM post_categories WHERE status = 1";
$categories_result = $conn->query($categories_query);
$blogCategories = [];
while ($category = $categories_result->fetch(PDO::FETCH_ASSOC)) {
    $blogCategories[] = $category['slug'];
}

// Routing based on the request URI
switch (true) {
    // Sitemap Routes
    case ($request_uri === 'sitemap_index.xml'):
        include __DIR__ . '/Controllers/SitemapController.php';
        $controller = new SitemapController();
        $controller->index();
        break;

        case ($request_uri === 'wholesale'):
            include __DIR__ . '/Controllers/WholesaleController.php';
            $controller = new WholesaleController();
            $controller->index();
            break;

            case ($request_uri === 'bulk-inquiry' && $_SERVER['REQUEST_METHOD'] === 'POST'):
                include __DIR__ . '/Controllers/BulkInquiryController.php';
                break;

    case ($request_uri === 'post-sitemap.xml'):
        include __DIR__ . '/Controllers/PostsSitemapController.php';
        break;

    case ($request_uri === 'post_category-sitemap.xml'):
        include __DIR__ . '/Controllers/CategoriesSitemapController.php';
        break;
    
    case ($request_uri === 'product-sitemap.xml'):
        include __DIR__ . '/Controllers/ProductSitemapController.php';
        break;

    case ($request_uri === 'news-sitemap.xml'):
        include __DIR__ . '/Controllers/NewsSitemapController.php';
        break;

    case ($request_uri === 'product_cat-sitemap.xml'):
        include __DIR__ . '/Controllers/ProductCatSitemapController.php';
        break;

    case ($request_uri === 'page-sitemap.xml'):
        include __DIR__ . '/Controllers/PagesSitemapController.php';
        break;

    case ($request_uri === 'images-sitemap.xml'):
        include __DIR__ . '/Controllers/ProductImageSitemapController.php';
        break;

    case ($request_uri === 'google-merchant-feed'):
        include __DIR__ . '/Controllers/GoogleMerchantFeedController.php';
        break;

 // User Page Route (e.g., /user/username)
 case (preg_match('#^user/([^/]+)$#', $request_uri, $matches)):
    $query = "
        SELECT id, name
        FROM admins
        WHERE username = :username AND is_indexed = 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([':username' => trim($matches[1])]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminsControllerPath = __DIR__ . '/Controllers/AdminsController.php';
    if ($user && file_exists($adminsControllerPath)) {
        $_GET['username'] = $matches[1];
        include $adminsControllerPath;
        $controller = new AdminsController();
        $controller->showUserPage();
    } else {
        http_response_code(404);
        include __DIR__ . '/Views/404.php';
    }
    break;
    // Admin Routes
    case ($request_uri === 'admin/categories'):
        include __DIR__ . '/Controllers/AdminCategoryController.php';
        $controller = new AdminCategoryController();
        $controller->index();
        break;

    case ($request_uri === 'admin/reviews'):
        include __DIR__ . '/Controllers/ReviewAdminController.php';
        $controller = new ReviewAdminController();
        $controller->index();
        break;

    case ($request_uri === 'admin/reviews/update-status'):
        include __DIR__ . '/Controllers/ReviewAdminController.php';
        $controller = new ReviewAdminController();
        $controller->updateStatus();
        break;

    case ($request_uri === 'admin/reviews/update-content'):
        include __DIR__ . '/Controllers/ReviewAdminController.php';
        $controller = new ReviewAdminController();
        $controller->updateContent();
        break;

    case ($request_uri === 'admin/add-post'):
        include __DIR__ . '/Controllers/AdminPostController.php';
        $controller = new AdminPostController();
        $controller->create();
        break;

    case (preg_match('#^admin/edit-post/(\d+)$#', $request_uri, $matches)):
        include __DIR__ . '/Controllers/AdminEditPostController.php';
        $controller = new AdminEditPostController();
        $controller->update($matches[1]);
        break;

    case ($request_uri === 'admin/manage-contact-messages'):
        include __DIR__ . '/../admin/manage-contact-messages.php';
        break;

    // E-commerce Product Edit Routes
    case (preg_match('#^edit-product/(\d+)$#', $request_uri, $matches)):
        $productId = $matches[1];
        include __DIR__ . '/Controllers/EditProductController.php';
        $controller = new EditProductController();
        $controller->showEditProductPage($productId);
        break;

    case (preg_match('#^update-product/(\d+)$#', $request_uri, $matches)):
        $productId = $matches[1];
        include __DIR__ . '/Controllers/EditProductController.php';
        $controller = new EditProductController();
        $controller->updateProduct($productId);
        break;

    // Order Tracking Routes
    case ($request_uri === 'track-order' && $_SERVER['REQUEST_METHOD'] === 'GET'):
        include __DIR__ . '/Views/order-tracking/track_order.php';
        break;

    case ($request_uri === 'track-order'):
        include __DIR__ . '/Controllers/OrderController.php';
        $controller = new OrderController();
        $controller->trackOrder();
        break;

    // Static Pages
    case ($request_uri === '' || $request_uri === '/' || $request_uri === 'index.php'):
        include __DIR__ . '/Views/home.php';
        break;

    case ($request_uri === 'about-us'):
        include __DIR__ . '/Views/static-pages/about-us.php';
        break;

    case ($request_uri === 'shop'):
        include __DIR__ . '/Views/shop/index.php';
        break;

    case ($request_uri === 'contact-us'):
        include __DIR__ . '/Views/static-pages/contact.php';
        break;

    case ($request_uri === 'return-policy'):
        include __DIR__ . '/Views/static-pages/return-policy.php';
        break;

    case ($request_uri === 'privacy-policy'):
        include __DIR__ . '/Views/static-pages/privacy-policy.php';
        break;

    case ($request_uri === 'shipping-policy'):
        include __DIR__ . '/Views/static-pages/shipping-policy.php';
        break;

    case ($request_uri === 'terms-and-conditions'):
        include __DIR__ . '/Views/static-pages/terms-and-conditions.php';
        break;

        case ($request_uri === 'write-for-us'):
            include __DIR__ . '/Views/static-pages/write-for-us.php';
            break;

    // User Authentication Routes
    case ($request_uri === 'register'):
        include __DIR__ . '/Views/user/register.php';
        break;

    case ($request_uri === 'resend-otp'):
        include __DIR__ . '/Views/user/resend_otp.php';
        break;

    case ($request_uri === 'verify'):
        include __DIR__ . '/Views/user/verify.php';
        break;

    case ($request_uri === 'login'):
        include __DIR__ . '/Views/user/login.php';
        break;

    case ($request_uri === 'forgot-password'):
        include __DIR__ . '/Views/user/forgot-password.php';
        break;

    case ($request_uri === 'reset-password'):
        include __DIR__ . '/Views/user/reset-password.php';
        break;

    case ($request_uri === 'my-account'):
        include __DIR__ . '/Views/user_dashboard/dashboard.php';
        break;

    case ($request_uri === 'logout'):
        session_start();
        session_unset();
        session_destroy();
        $redirectTo('/login');
        break;

    // Other Functional Routes
    case ($request_uri === 'search'):
        include __DIR__ . '/Views/search.php';
        break;

    case ($request_uri === 'blog-search'):
        include __DIR__ . '/Controllers/PostController.php';
        break;

    case ($request_uri === 'blog'):
        include __DIR__ . '/Controllers/PostController.php';
        break;

    case ($request_uri === 'cart'):
        include __DIR__ . '/Views/cart/cart.php';
        break;

    case ($request_uri === 'checkout'):
        include __DIR__ . '/Views/checkout/checkout.php';
        break;

    case ($request_uri === 'coming-soon-products'):
        include __DIR__ . '/Views/coming-soon-products.php';
        break;

    case ($request_uri === 'submit-review'):
        include __DIR__ . '/Views/products/submit-review.php';
        break;

    // Mobiles Price List Routes
    case ($request_uri === 'mobiles-price-list'):
        include __DIR__ . '/Views/mobiles-price-list/index.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-30000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-30000.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-40000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-40000.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-50000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-50000.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-60000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-60000.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-80000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-80000.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-100000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-100000.php';
        break;

    case ($request_uri === 'mobiles-price-list/best-mobiles-under-150000'):
        include __DIR__ . '/Views/mobiles-price-list/best-mobiles-under-150000.php';
        break;

    case (preg_match('/^thankyou\/order_id=(\d+)$/', $request_uri, $matches)):
        $_GET['order_id'] = $matches[1];
        include __DIR__ . '/Views/thankyou/order_confirmation.php';
        break;

    case ($request_uri === 'registerUser' && $_SERVER['REQUEST_METHOD'] === 'POST'):
        include __DIR__ . '/Controllers/UserController.php';
        $controller = new UserController();
        $controller->registerUser();
        break;

    // News Post Route (e.g., /news/post-slug)
    case (preg_match('#^news/([^/]+)$#', $request_uri, $matches)):
        // Validate that the news post exists
        $query = "
            SELECT p.id
            FROM posts p
            JOIN post_category_mappings pcm ON p.id = pcm.post_id
            JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE TRIM(pc.slug) = 'news' AND TRIM(p.slug) = :post_slug 
                  AND p.status = 'published' 
                  AND (p.published_at IS NULL OR p.published_at <= NOW())
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([':post_slug' => trim($matches[1])]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("News Post Route: post_slug={$matches[1]}, found_post=" . ($post ? $post['id'] : 'none'));
        if ($post) {
            $_GET['category_slug'] = 'news';
            $_GET['post_slug'] = $matches[1];
            include __DIR__ . '/Controllers/PostController.php';
        } else {
            error_log("News Post Route: No post found for post_slug={$matches[1]}");
            http_response_code(404);
            include __DIR__ . '/Views/404.php';
        }
        break;

    // Redirect Old News URLs (e.g., /blog/news/post-slug to /news/post-slug)
    case (preg_match('#^blog/news/([^/]+)$#', $request_uri, $matches)):
        $redirectTo('/news/' . $matches[1], 301);
        break;

    // Blog Post Routes (e.g., /blog/category-slug/post-slug)
    case (preg_match('#^blog/([^/]+)/([^/]+)$#', $request_uri, $matches) && in_array($matches[1], $blogCategories)):
        // Validate that the post exists with the given slug and category
        $query = "
            SELECT p.id
            FROM posts p
            JOIN post_category_mappings pcm ON p.id = pcm.post_id
            JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE TRIM(pc.slug) = :category_slug AND TRIM(p.slug) = :post_slug 
                  AND p.status = 'published' 
                  AND (p.published_at IS NULL OR p.published_at <= NOW())
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':category_slug' => trim($matches[1]),
            ':post_slug' => trim($matches[2])
        ]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Blog Post Route: category_slug={$matches[1]}, post_slug={$matches[2]}, found_post=" . ($post ? $post['id'] : 'none'));
        if ($post) {
            $_GET['category_slug'] = $matches[1];
            $_GET['post_slug'] = $matches[2];
            include __DIR__ . '/Controllers/PostController.php';
        } else {
            error_log("Blog Post Route: No post found for category_slug={$matches[1]}, post_slug={$matches[2]}");
            http_response_code(404);
            include __DIR__ . '/Views/404.php';
        }
        break;

        case ($request_uri === 'news'):
            $_GET['category_slug'] = 'news';
            include __DIR__ . '/Controllers/PostController.php';
            break;

            case ($request_uri === 'blog/news'):
                $redirectTo('/news', 301);
                break;

            case ($request_uri === 'blog/mobile-reviews'):
                $redirectTo('/blog/mobile-tips', 301);
                break;
                
    // Blog Category Routes (e.g., /blog/category-slug)
    case (preg_match('#^blog/([^/]+)$#', $request_uri, $matches) && in_array($matches[1], $blogCategories)):
        $_GET['category_slug'] = $matches[1];
        include __DIR__ . '/Controllers/PostController.php';
        break;

    // E-commerce Routes (Category, Brand, Product)
    default:
        $segments = explode('/', $request_uri);
        $segments = array_map('urldecode', $segments);

        // Category Page (e.g., /mobiles, /smartwatches, /tablets)
        if (count($segments) === 1 && !empty($segments[0])) {
            require_once __DIR__ . '/Controllers/CategoryController.php';
            $controller = new CategoryController();
            $controller->showCategory($segments[0]);

        // Brand + category listing (e.g., /samsung/mobiles)
        } elseif (count($segments) === 2) {
            require_once __DIR__ . '/Controllers/BrandController.php';
            $controller = new BrandController();
            $controller->showBrand($segments[0], $segments[1]);

        // Product page — new 3-segment (brand/category/product) or legacy 3-segment (category/brand/product)
        } elseif (count($segments) === 3) {
            require_once __DIR__ . '/Controllers/ProductController.php';
            $controller = new ProductController();
            $controller->showProductThreeSegments($segments[0], $segments[1], $segments[2]);

        // Product page with subcategory (brand/category/subcategory/product)
        } elseif (count($segments) === 4) {
            require_once __DIR__ . '/Controllers/ProductController.php';
            $controller = new ProductController();
            $controller->showProduct($segments[0], $segments[1], $segments[2], $segments[3]);

        // 404 Page for Invalid Routes
        } else {
            http_response_code(404);
            include __DIR__ . '/Views/404.php';
        }
        break;
}
?>