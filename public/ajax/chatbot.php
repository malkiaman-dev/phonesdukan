<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../app/config/chatbot_config.php';
require_once __DIR__ . '/../../database/db.php';

if (empty(CHATBOT_API_KEY)) {
    error_log('[Chatbot] API key is not configured (chatbot_config.local.php missing or empty)');
    echo json_encode(['error' => 'We are a little busy right now. Please try again in a moment, or visit phonesdukan.com for help.']);
    exit;
}

// ── Input ─────────────────────────────────────────────────────────────────────
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

$message = trim((string)($input['message'] ?? ''));
$history = is_array($input['history'] ?? null) ? $input['history'] : [];

if ($message === '') {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// Strip HTML, limit length
$message = strip_tags($message);
$message = mb_substr($message, 0, 500);

// ── Smart product search ──────────────────────────────────────────────────────
$productContext = '';
try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        $lowerMsg = strtolower($message);

        // Extract budget (e.g. 50000, 50,000, 50k, PKR 50000, Rs 50000)
        $budget = null;
        if (preg_match('/\b(\d[\d,]*)\s*k\b/i', $lowerMsg, $m)) {
            $budget = (float) str_replace(',', '', $m[1]) * 1000;
        } elseif (preg_match('/(?:pkr|rs\.?|rupees?)?\s*(\d[\d,]{3,})/i', $lowerMsg, $m)) {
            $budget = (float) str_replace(',', '', $m[1]);
        }

        // Detect product category from message keywords
        $categorySlug = null;
        $categoryMap  = [
            'mobiles'            => ['phone', 'mobile', 'smartphone', 'android', 'iphone', 'samsung', 'xiaomi', 'realme', 'oppo', 'vivo', 'tecno', 'infinix', 'itel', 'nokia', 'motorola', 'huawei', 'poco', 'redmi', 'oneplus'],
            'smart-watches'      => ['watch', 'smartwatch', 'smart watch'],
            'wireless-earbuds'   => ['earbud', 'earbuds', 'airpod', 'tws', 'headphone', 'earphone'],
            'bluetooth-speakers' => ['speaker', 'bluetooth speaker'],
            'power-banks'        => ['powerbank', 'power bank', 'portable charger'],
            'mobile-accessories' => ['accessory', 'accessories', 'case', 'cover', 'screen protector', 'cable'],
        ];
        foreach ($categoryMap as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($lowerMsg, $kw) !== false) {
                    $categorySlug = $slug;
                    break 2;
                }
            }
        }

        $results = [];

        // Effective price: use sale_price if set, otherwise regular_price
        $priceExpr = 'COALESCE(p.sale_price, p.regular_price)';

        if ($budget && $categorySlug) {
            // Budget + category: products within 30% below budget up to budget
            $minBudget = $budget * 0.7;
            $stmt = $db->prepare("
                SELECT p.product_name, p.regular_price, p.sale_price,
                       {$priceExpr} AS effective_price, p.product_slug,
                       c.slug AS category_slug, b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands     b ON p.brand_id     = b.brand_id
                WHERE p.product_status = 1
                  AND c.slug = :cat
                  AND {$priceExpr} BETWEEN :min AND :max
                ORDER BY {$priceExpr} DESC
                LIMIT 6
            ");
            $stmt->bindValue(':cat', $categorySlug);
            $stmt->bindValue(':min', (int)$minBudget);
            $stmt->bindValue(':max', (int)$budget);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Widen to closest matches if nothing found in that range
            if (empty($results)) {
                $stmt = $db->prepare("
                    SELECT p.product_name, p.regular_price, p.sale_price,
                           {$priceExpr} AS effective_price, p.product_slug,
                           c.slug AS category_slug, b.slug AS brand_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    LEFT JOIN brands     b ON p.brand_id     = b.brand_id
                    WHERE p.product_status = 1
                      AND c.slug = :cat
                    ORDER BY ABS({$priceExpr} - :budget)
                    LIMIT 5
                ");
                $stmt->bindValue(':cat', $categorySlug);
                $stmt->bindValue(':budget', (int)$budget);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

        } elseif ($budget) {
            // Budget only: search across all categories
            $minBudget = $budget * 0.7;
            $stmt = $db->prepare("
                SELECT p.product_name, p.regular_price, p.sale_price,
                       {$priceExpr} AS effective_price, p.product_slug,
                       c.slug AS category_slug, b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands     b ON p.brand_id     = b.brand_id
                WHERE p.product_status = 1
                  AND {$priceExpr} BETWEEN :min AND :max
                ORDER BY {$priceExpr} DESC
                LIMIT 6
            ");
            $stmt->bindValue(':min', (int)$minBudget);
            $stmt->bindValue(':max', (int)$budget);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($categorySlug) {
            // Category only: cheapest products in that category
            $stmt = $db->prepare("
                SELECT p.product_name, p.regular_price, p.sale_price,
                       {$priceExpr} AS effective_price, p.product_slug,
                       c.slug AS category_slug, b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands     b ON p.brand_id     = b.brand_id
                WHERE p.product_status = 1
                  AND c.slug = :cat
                ORDER BY {$priceExpr} ASC
                LIMIT 5
            ");
            $stmt->bindValue(':cat', $categorySlug);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            // Fallback: keyword match on product name
            $stopWords = ['the','and','for','can','you','are','have','best','good','want','need','looking','give','suggest','show','what','which','any','some','get','tell','your','store'];
            $words = array_filter(
                explode(' ', preg_replace('/[^a-z0-9 ]/i', ' ', $message)),
                fn($w) => strlen($w) >= 3 && !in_array(strtolower($w), $stopWords)
            );
            $seen = [];
            foreach (array_slice(array_values($words), 0, 3) as $word) {
                $stmt = $db->prepare("
                    SELECT p.product_name, p.regular_price, p.sale_price,
                           {$priceExpr} AS effective_price, p.product_slug,
                           c.slug AS category_slug, b.slug AS brand_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    LEFT JOIN brands     b ON p.brand_id     = b.brand_id
                    WHERE p.product_status = 1
                      AND p.product_name LIKE :q
                    LIMIT 5
                ");
                $stmt->bindValue(':q', '%' . $word . '%');
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (!isset($seen[$row['product_slug']])) {
                        $seen[$row['product_slug']] = true;
                        $results[] = $row;
                    }
                }
                if (count($results) >= 5) break;
            }
        }

        if (!empty($results)) {
            $productContext = "\n\nProducts from our store that match this query:\n";
            foreach ($results as $p) {
                $price = !empty($p['effective_price']) ? (float)$p['effective_price'] : null;
                $productContext .= '- ' . $p['product_name'];
                if ($price) {
                    $productContext .= ', PKR ' . number_format($price);
                }
                if (!empty($p['category_slug']) && !empty($p['brand_slug']) && !empty($p['product_slug'])) {
                    $productContext .= ', Link: https://phonesdukan.com/' . $p['category_slug'] . '/' . $p['brand_slug'] . '/' . $p['product_slug'];
                }
                $productContext .= "\n";
            }
        }
    }
} catch (Exception $e) {
    error_log('[Chatbot] Product search error: ' . $e->getMessage());
}

// ── Build messages ────────────────────────────────────────────────────────────
$systemContent = CHATBOT_SYSTEM_PROMPT . $productContext;
$messages = [['role' => 'system', 'content' => $systemContent]];

// Keep last 10 turns of history
$history = array_slice($history, -10);
foreach ($history as $turn) {
    if (
        isset($turn['role'], $turn['content']) &&
        in_array($turn['role'], ['user', 'assistant'], true) &&
        is_string($turn['content'])
    ) {
        $messages[] = [
            'role'    => $turn['role'],
            'content' => mb_substr(strip_tags((string)$turn['content']), 0, 1000),
        ];
    }
}

$messages[] = ['role' => 'user', 'content' => $message];

// ── OpenRouter API call ───────────────────────────────────────────────────────
$payload = json_encode([
    'model'       => CHATBOT_MODEL,
    'messages'    => $messages,
    'max_tokens'  => 800,
    'temperature' => 0.7,
]);

// Resolve CA bundle: use PHP's configured path, common system paths, or skip verification
$caCandidates = [
    ini_get('curl.cainfo'),
    ini_get('openssl.cafile'),
    '/etc/ssl/certs/ca-certificates.crt',   // Debian/Ubuntu
    '/etc/pki/tls/certs/ca-bundle.crt',     // RHEL/CentOS
];
$caBundle = null;
foreach ($caCandidates as $path) {
    if ($path && file_exists($path)) {
        $caBundle = $path;
        break;
    }
}
$verifySsl = ($caBundle !== null);

$ch = curl_init(CHATBOT_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . CHATBOT_API_KEY,
        'HTTP-Referer: ' . CHATBOT_SITE_URL,
        'X-Title: Phones Dukan Chat',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => $verifySsl,
    CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    CURLOPT_CAINFO         => $caBundle,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$busyMessage  = 'We are a little busy right now. Please try again in a moment, or feel free to browse our products at phonesdukan.com or call us on (+92) 3116600031.';
$errorMessage = 'Something went wrong on our end. Please try again shortly or visit phonesdukan.com for help.';
$networkMessage = 'We could not reach our assistant right now. Please check your connection or visit phonesdukan.com and we will be happy to help.';

// cURL-level failure (network, DNS, timeout, SSL)
if ($response === false || $curlError) {
    $isTimeout = stripos($curlError, 'timed out') !== false || stripos($curlError, 'timeout') !== false;
    error_log('[Chatbot] cURL error: ' . $curlError);
    echo json_encode(['error' => $isTimeout
        ? 'Our assistant is taking too long to respond. Please try again or visit phonesdukan.com.'
        : $networkMessage
    ]);
    exit;
}

// Parse JSON — guard against completely invalid response body
$data = json_decode($response, true);
if (!is_array($data)) {
    error_log('[Chatbot] Invalid JSON response (HTTP ' . $httpCode . '): ' . substr($response, 0, 300));
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Map HTTP status codes to friendly messages
switch ($httpCode) {
    case 200:
        break; // handled below

    case 401:
        error_log('[Chatbot] 401 Unauthorized: invalid API key');
        echo json_encode(['error' => $busyMessage]);
        exit;

    case 402:
        // Payment required — quota/credits exhausted
        error_log('[Chatbot] 402 Payment Required: API quota exhausted');
        echo json_encode(['error' => $busyMessage]);
        exit;

    case 429:
        // Rate limit hit
        error_log('[Chatbot] 429 Rate limit hit');
        echo json_encode(['error' => $busyMessage]);
        exit;

    case 503:
    case 502:
    case 504:
        error_log('[Chatbot] Gateway/service error ' . $httpCode);
        echo json_encode(['error' => $busyMessage]);
        exit;

    default:
        $apiMsg = $data['error']['message'] ?? null;
        error_log('[Chatbot] API error ' . $httpCode . ': ' . $response);
        echo json_encode(['error' => $busyMessage]);
        exit;
}

// OpenRouter can embed an error object inside a 200 response
if (isset($data['error'])) {
    $code   = $data['error']['code']    ?? 0;
    $apiMsg = $data['error']['message'] ?? '';
    error_log('[Chatbot] API error in 200 body (code ' . $code . '): ' . $apiMsg);

    // Quota / auth errors inside the body
    if (in_array((int)$code, [401, 402, 429], true) || stripos($apiMsg, 'credit') !== false || stripos($apiMsg, 'quota') !== false || stripos($apiMsg, 'billing') !== false) {
        echo json_encode(['error' => $busyMessage]);
    } else {
        echo json_encode(['error' => $errorMessage]);
    }
    exit;
}

$message_data = $data['choices'][0]['message'] ?? [];
// Some reasoning models put output in 'content', others finish reasoning first
$reply = $message_data['content'] ?? null;

// Fallback: reasoning-only models may produce content on a second pass;
// if content is still null after a full response, surface a friendly retry
if (empty($reply)) {
    error_log('[Chatbot] Empty content. finish_reason=' . ($data['choices'][0]['finish_reason'] ?? '?') . ' Full: ' . substr($response, 0, 500));
    echo json_encode(['error' => 'Our assistant is thinking but ran out of space. Please try again with a shorter question.']);
    exit;
}

echo json_encode(['reply' => trim($reply)]);
