<?php
require_once __DIR__ . '/../Models/PostModel.php';
require_once __DIR__ . '/../../database/db.php';

$db = new Database();
$pdo = $db->getConnection();
$postModel = new PostModel($pdo);

// API key for exchangerate-api.com (replace with your own)
$api_key = '896ffc7e00ab5c6437743636';

// Cache file and duration (6 hours)
$cache_file = dirname(__DIR__, 2) . '/cache/exchange_rate.json';
$cache_duration = 6 * 60 * 60; // 6 hours in seconds

// Function to fetch USD to PKR exchange rate
function getExchangeRate($api_key, $cache_file, $cache_duration) {
    $default_rate = 284; // Fallback rate if API fails

    // Ensure cache directory exists
    $cache_dir = dirname($cache_file);
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    // Check if cache exists and is recent
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
        $cache = json_decode(file_get_contents($cache_file), true);
        return $cache['rate'];
    }

    // Fetch from API
    $url = "https://v6.exchangerate-api.com/v6/$api_key/latest/USD";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['conversion_rates']['PKR'])) {
        $rate = $data['conversion_rates']['PKR'];
        // Cache the rate
        file_put_contents($cache_file, json_encode(['rate' => $rate, 'timestamp' => time()]));
        return $rate;
    }

    // Return default rate if API fails
    return $default_rate;
}

$limit = 12;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

$category_slug = isset($_GET['category_slug']) ? $_GET['category_slug'] : null;
$post_slug = isset($_GET['post_slug']) ? $_GET['post_slug'] : null;
$search_term = isset($_GET['s']) ? trim($_GET['s']) : null;

// Handle AJAX search suggestions
if (isset($_GET['action']) && $_GET['action'] === 'get_search_suggestions') {
    $search_term = isset($_GET['s']) ? trim($_GET['s']) : '';
    if ($search_term) {
        $suggestions = $postModel->getPostSuggestions($search_term);
        header('Content-Type: application/json');
        echo json_encode($suggestions);
        exit;
    }
    echo json_encode([]);
    exit;
}

// Handle single post view
if ($post_slug && $category_slug) {
    $post = $postModel->getPostBySlug($post_slug);

    if ($post && $post['category_slug'] === $category_slug) {
        // Fetch category name based on category_slug
        $category = $postModel->getCategoryBySlug($category_slug);
        $category_name = $category ? $category['category_name'] : ucfirst($category_slug);

        // Add category_name to the post array
        $post['category_name'] = $category_name;

        $query = "SELECT * FROM post_seo WHERE post_id = :postId LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['postId' => $post['id']]);
        $postSeoData = $stmt->fetch(PDO::FETCH_ASSOC);

        $metaTitle = $postSeoData['meta_title'] ?? $post['title'];
        $metaDescription = $postSeoData['meta_description'] ?? 'Default description for blog post';
        $metaKeywords = $postSeoData['meta_keywords'] ?? null;
        $metaRobots = 'index, follow';

        // Fetch phone models and exchange rate
        $phone_models = $postModel->getPhoneModels();
        $usd_to_pkr = getExchangeRate($api_key, $cache_file, $cache_duration);
        $cache_timestamp = file_exists($cache_file) ? filemtime($cache_file) : time();

        $data = [
            'post' => $post,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaKeywords' => $metaKeywords,
            'metaRobots' => $metaRobots,
            'phone_models' => $phone_models,
            'usd_to_pkr' => $usd_to_pkr,
            'cache_timestamp' => $cache_timestamp,
        ];

        require_once dirname(__DIR__, 2) . '/app/Views/post_view.php';
        
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Post Not Found</h1>";
        exit;
    }
} elseif ($search_term) {
    // Handle search page
    $posts = $postModel->searchPosts($search_term, $limit, $offset);
    $total_rows = $postModel->getSearchPostCount($search_term);
    $total_pages = ceil($total_rows / $limit);
    $categories = $postModel->getAllCategories();

    $metaTitle = "Search Results for: " . htmlspecialchars($search_term) . " | Phones Dukan";

    $data = [
        'posts' => $posts,
        'categories' => $categories,
        'total_pages' => $total_pages,
        'current_page' => $paged,
        'category_slug' => $category_slug,
        'search_term' => $search_term,
        'metaTitle' => $metaTitle,
        'metaDescription' => "Showing search results for " . htmlspecialchars($search_term) . " on Phones Dukan.",
        'metaKeywords' => null,
        'metaRobots' => 'index, follow',
    ];

    require_once __DIR__ . '/../Views/blog-search.php';
} else {
    // Handle blog homepage or category view
    $categories = $postModel->getAllCategories();

    if ($category_slug) {
        // Category view
        $posts = $postModel->getPaginatedPosts($limit, $offset, $category_slug);
        $total_rows = $postModel->getTotalPostCount($category_slug);
        $total_pages = ceil($total_rows / $limit);
        $category = $postModel->getCategoryBySlug($category_slug);
        $category_name = $category ? $category['category_name'] : ucfirst($category_slug);
        $data = [
            'posts' => $posts,
            'categories' => $categories,
            'total_pages' => $total_pages,
            'paged' => $paged,
            'category_slug' => $category_slug,
            'category_name' => $category_name,
            'search_term' => $search_term,
            'metaTitle' => $category_name ? $category_name . " Posts" : "Blog Posts",
            'metaDescription' => "Explore the latest blog posts in " . htmlspecialchars($category_name) . " on Phones Dukan.",
            'metaKeywords' => null,
            'metaRobots' => 'index, follow',
            'phone_models' => $postModel->getPhoneModels(), // Added for calculator
            'usd_to_pkr' => getExchangeRate($api_key, $cache_file, $cache_duration), // Added for calculator
        ];
        $category_file = __DIR__ . '/../Views/post-categories/' . htmlspecialchars($category_slug) . '.php';
        if (file_exists($category_file)) {
            require_once $category_file;
        } else {
            require_once __DIR__ . '/../Views/posts_list.php';
        }
    } else {
        // Blog homepage (/blog)
        $category_posts = [];
        $home_limit = 4; // Limit to 4 posts per category on homepage
        foreach ($categories as $category) {
            $category_posts[$category['slug']] = [
                'category_name' => $category['category_name'],
                'posts' => $postModel->getPaginatedPosts($home_limit, 0, $category['slug']),
            ];
        }

        $data = [
            'category_posts' => $category_posts,
            'categories' => $categories,
            'metaTitle' => "Blog | Phones Dukan",
            'metaDescription' => "Explore all blog posts across categories on Phones Dukan.",
            'metaKeywords' => null,
            'metaRobots' => 'index, follow',
        ];

        require_once __DIR__ . '/../Views/blog_home.php';
    }
}
?>