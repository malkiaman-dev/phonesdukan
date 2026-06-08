<?php
/**
 * AI SEO Assistant – Backend API
 * Endpoint: POST /admin/api/ai-seo.php
 * Accepts JSON body or form-encoded data.
 *
 * Actions:
 *   refine       – improve an existing field value
 *   generate     – generate SEO content from product data
 *   smart_suggest– get keyword/phrase suggestions while typing
 *   get_credits  – return current usage counters
 *   save_score   – persist a client-computed SEO score
 *   save_history – log an applied suggestion
 */

declare(strict_types=1);

// ── Bootstrap ──────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// CORS for same-origin AJAX only
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ['http://localhost', 'https://www.phonesdukan.com'], true)) {
    header("Access-Control-Allow-Origin: $origin");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Auth check – must be logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Config ─────────────────────────────────────────────────────────────────
const GROQ_API_KEY        = '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';
const GROQ_ENDPOINT       = 'https://api.groq.com/openai/v1/chat/completions';
const GROQ_MODEL          = 'llama-3.3-70b-versatile'; // Primary: 70B, best quality
const GROQ_FALLBACK       = 'llama-3.1-8b-instant';    // Fallback: smaller but confirmed active
const GROQ_TIMEOUT        = 35;   // seconds before hard timeout
const GROQ_SLOW_THRESHOLD = 10;   // seconds before "slow response" warning fires
const CACHE_TTL           = 3600;
const DAILY_LIMIT         = 200;
const MONTHLY_LIMIT       = 3000;
const APP_DEBUG_AI        = false; // Set true only when diagnosing AI errors

// ── Parse Input ────────────────────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true);
if (!$body) {
    $body = $_POST;
}

$action    = trim((string)($body['action'] ?? ''));
$productId = isset($body['product_id']) ? (int)$body['product_id'] : null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'action is required']);
    exit;
}

// ── Database ───────────────────────────────────────────────────────────────
require_once dirname(__DIR__, 2) . '/database/db.php';
$database = new Database();
$conn     = $database->getConnection();

// ── Helpers ────────────────────────────────────────────────────────────────

function safeStr(mixed $v, int $maxLen = 4000): string {
    return mb_substr(trim((string)$v), 0, $maxLen);
}

/**
 * Strip AI "meta-commentary" that sometimes leaks into the output:
 *   **Character count: 149**
 *   (149 characters)
 *   [Character count: 149]
 *   Character count: 149
 *   Note: this is 152 characters.
 *   ---
 *   Here is the improved ...
 *   Sure! Here's ...
 */
function cleanAiOutput(string $text): string {
    // Remove markdown bold/bracket/paren character-count annotations
    $text = preg_replace('/\*{1,2}Character\s+count[:\s]*\d+\*{0,2}/i', '', $text);
    $text = preg_replace('/\[Character\s+count[:\s]*\d+\]/i',            '', $text);
    $text = preg_replace('/\(\s*\d+\s*characters?\s*\)/i',                '', $text);
    $text = preg_replace('/\bCharacter\s+count[:\s]*\d+\.?/i',            '', $text);
    $text = preg_replace('/\bTotal\s+characters?[:\s]*\d+\.?/i',          '', $text);
    $text = preg_replace('/\bLength[:\s]*\d+\s*chars?\.?/i',              '', $text);
    $text = preg_replace('/\(\s*\d+\s*chars?\s*\)/i',                     '', $text);
    $text = preg_replace('/\[\s*\d+\s*chars?\s*\]/i',                     '', $text);

    // Remove leading filler phrases the model sometimes prepends
    $text = preg_replace('/^(Sure!?\s*|Here(?:\'s| is) (?:the |an? )?(?:improved |revised |updated |generated |rewritten )?(?:version:?|content:?|output:?|result:?)?[\s\n]*)/i', '', $text);
    $text = preg_replace('/^(?:Improved|Revised|Updated|Rewritten):\s*/i', '', $text);

    // Remove trailing separator lines  (---, ***, ===)
    $text = preg_replace('/[\r\n]+[-*=]{3,}\s*$/', '', $text);

    // Remove trailing "Note: ..." sentences
    $text = preg_replace('/[\r\n]+Note[:\s].+$/is', '', $text);

    return trim($text);
}

/**
 * Check if AI is available today (not over daily limit).
 * Returns ['ok'=>true] or ['ok'=>false, 'reason'=>'...'].
 */
function checkCredits(PDO $conn): array {
    try {
        $stmt = $conn->prepare(
            "SELECT daily_requests, monthly_requests, daily_limit, monthly_limit
               FROM ai_seo_credits WHERE date = CURDATE() LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            // Create today's row
            $conn->exec("INSERT IGNORE INTO ai_seo_credits (date) VALUES (CURDATE())");
            return ['ok' => true, 'daily' => 0, 'monthly' => 0];
        }
        if ($row['daily_requests'] >= $row['daily_limit']) {
            return ['ok' => false, 'reason' => 'Daily AI limit reached (' . $row['daily_limit'] . ' requests). Manual editing still available.'];
        }
        if ($row['monthly_requests'] >= $row['monthly_limit']) {
            return ['ok' => false, 'reason' => 'Monthly AI limit reached. Manual editing still available.'];
        }
        return ['ok' => true, 'daily' => (int)$row['daily_requests'], 'monthly' => (int)$row['monthly_requests'],
                'daily_limit' => (int)$row['daily_limit'], 'monthly_limit' => (int)$row['monthly_limit']];
    } catch (Throwable) {
        return ['ok' => true]; // DB not set up yet → allow
    }
}

/** Increment usage counters */
function incrementCredits(PDO $conn, int $tokens = 0): void {
    try {
        $conn->prepare(
            "INSERT INTO ai_seo_credits (date, daily_requests, daily_tokens, monthly_requests, monthly_tokens, total_requests)
               VALUES (CURDATE(), 1, :t, 1, :t2, 1)
             ON DUPLICATE KEY UPDATE
               daily_requests   = daily_requests   + 1,
               daily_tokens     = daily_tokens     + :t3,
               monthly_requests = monthly_requests + 1,
               monthly_tokens   = monthly_tokens   + :t4,
               total_requests   = total_requests   + 1"
        )->execute([':t' => $tokens, ':t2' => $tokens, ':t3' => $tokens, ':t4' => $tokens]);
    } catch (Throwable) { /* silently skip */ }
}

/** Log a request */
function logRequest(PDO $conn, string $action, ?string $field, string $input, string $output,
                    string $model, int $tokens, int $latency, string $status, string $error = '',
                    ?int $productId = null): void {
    try {
        $conn->prepare(
            "INSERT INTO ai_seo_requests
               (action, field_name, input_text, output_text, model, tokens_used, latency_ms, status, error_msg, product_id)
             VALUES (:a, :f, :i, :o, :m, :t, :l, :s, :e, :p)"
        )->execute([
            ':a' => $action, ':f' => $field, ':i' => mb_substr($input, 0, 2000),
            ':o' => mb_substr($output, 0, 8000), ':m' => $model,
            ':t' => $tokens, ':l' => $latency, ':s' => $status,
            ':e' => $error ?: null, ':p' => $productId,
        ]);
    } catch (Throwable) { /* silently skip */ }
}

/** Check cache */
function getCache(PDO $conn, string $key): ?string {
    try {
        $stmt = $conn->prepare(
            "SELECT output FROM ai_seo_cache WHERE cache_key = :k AND expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if ($row) {
            $conn->prepare("UPDATE ai_seo_cache SET hit_count = hit_count + 1 WHERE cache_key = :k")
                 ->execute([':k' => $key]);
            return $row['output'];
        }
    } catch (Throwable) {}
    return null;
}

/** Write cache */
function setCache(PDO $conn, string $key, string $action, ?string $field, string $output): void {
    try {
        $conn->prepare(
            "INSERT INTO ai_seo_cache (cache_key, action, field_name, output, expires_at)
               VALUES (:k, :a, :f, :o, DATE_ADD(NOW(), INTERVAL " . CACHE_TTL . " SECOND))
             ON DUPLICATE KEY UPDATE output=:o2, expires_at=DATE_ADD(NOW(), INTERVAL " . CACHE_TTL . " SECOND), hit_count=hit_count+1"
        )->execute([':k' => $key, ':a' => $action, ':f' => $field, ':o' => $output, ':o2' => $output]);
    } catch (Throwable) {}
}

// ─────────────────────────────────────────────────────────────────────────────
// ERROR CLASSIFICATION ENGINE
// Maps every possible API failure to a structured, user-friendly error object.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Classify any AI failure into a structured error response.
 *
 * @param int    $httpCode  HTTP status code (0 = no network)
 * @param string $rawMsg    Raw error message from API or cURL
 * @param array  $apiData   Decoded API JSON body (if available)
 * @param float  $latency   Request latency in seconds
 * @return array {type, user_message, retry, retry_after, details, http_code}
 */
function classifyAiError(int $httpCode, string $rawMsg, array $apiData = [], float $latency = 0): array {
    $raw   = strtolower($rawMsg . ' ' . ($apiData['error']['message'] ?? ''));
    $code  = $apiData['error']['code'] ?? '';

    // ── 1. No network / connection refused ───────────────────────────────────
    if ($httpCode === 0 || str_contains($raw, 'could not resolve') ||
        str_contains($raw, 'connection refused') || str_contains($raw, 'failed to connect') ||
        str_contains($raw, 'network') || str_contains($raw, 'curl')) {
        return [
            'type'         => 'network',
            'user_message' => 'Unable to reach the AI service. Please check your internet connection and try again.',
            'retry'        => true,
            'retry_after'  => 5,
            'details'      => APP_DEBUG_AI ? $rawMsg : null,
        ];
    }

    // ── 2. API key / authentication ──────────────────────────────────────────
    if ($httpCode === 401 || str_contains($raw, 'invalid api key') ||
        str_contains($raw, 'unauthorized') || str_contains($raw, 'authentication')) {
        return [
            'type'         => 'auth',
            'user_message' => 'AI service authentication failed. Please check the API key configuration in Settings.',
            'retry'        => false,
            'retry_after'  => 0,
            'details'      => APP_DEBUG_AI ? "HTTP 401 — {$rawMsg}" : null,
        ];
    }

    // ── 3. Credits / quota exhausted ────────────────────────────────────────
    if (str_contains($raw, 'insufficient') || str_contains($raw, 'credits') ||
        str_contains($raw, 'quota') || str_contains($raw, 'billing') ||
        str_contains($raw, 'payment') || str_contains($raw, 'exceeded your') ||
        ($httpCode === 402)) {
        return [
            'type'         => 'credits',
            'user_message' => 'AI credits have been exhausted. You can continue using all manual SEO tools normally. Recharge API credits to restore AI features.',
            'retry'        => false,
            'retry_after'  => 0,
            'limit_reached'=> true,
            'details'      => APP_DEBUG_AI ? $rawMsg : null,
        ];
    }

    // ── 4. Rate limit ────────────────────────────────────────────────────────
    if ($httpCode === 429 || str_contains($raw, 'rate limit') ||
        str_contains($raw, 'too many requests') || str_contains($raw, 'ratelimit')) {
        // Try to extract retry-after from message
        $retryAfter = 15;
        if (preg_match('/try again in (\d+)/i', $rawMsg, $m)) {
            $retryAfter = (int)$m[1] + 2;
        }
        return [
            'type'         => 'rate_limit',
            'user_message' => "AI request limit reached temporarily. The system will auto-retry in {$retryAfter} seconds. You can also continue editing manually.",
            'retry'        => true,
            'retry_after'  => $retryAfter,
            'details'      => APP_DEBUG_AI ? "HTTP 429 — {$rawMsg}" : null,
        ];
    }

    // ── 5. Timeout ───────────────────────────────────────────────────────────
    if (str_contains($raw, 'timed out') || str_contains($raw, 'timeout') ||
        str_contains($raw, 'operation timed') || $latency >= GROQ_TIMEOUT) {
        return [
            'type'         => 'timeout',
            'user_message' => 'The AI request timed out. This can happen during peak hours. Please try again — or continue editing manually.',
            'retry'        => true,
            'retry_after'  => 3,
            'details'      => APP_DEBUG_AI ? "Timeout after {$latency}s — {$rawMsg}" : null,
        ];
    }

    // ── 6. Token / context limit exceeded ───────────────────────────────────
    if (str_contains($raw, 'context_length') || str_contains($raw, 'max_tokens') ||
        str_contains($raw, 'token') || str_contains($raw, 'too long')) {
        return [
            'type'         => 'token_limit',
            'user_message' => 'The content you provided is too long for AI processing. Please shorten the existing description and try again.',
            'retry'        => false,
            'retry_after'  => 0,
            'details'      => APP_DEBUG_AI ? $rawMsg : null,
        ];
    }

    // ── 7. Model unavailable / overloaded ────────────────────────────────────
    if ($httpCode === 503 || $httpCode === 529 ||
        str_contains($raw, 'model') && (str_contains($raw, 'unavailable') || str_contains($raw, 'overloaded')) ||
        str_contains($raw, 'service unavailable') || str_contains($raw, 'overloaded')) {
        return [
            'type'         => 'model_unavailable',
            'user_message' => 'The AI model is temporarily overloaded. The system will try a backup model. If this persists, please try again in a few minutes.',
            'retry'        => true,
            'retry_after'  => 10,
            'details'      => APP_DEBUG_AI ? "HTTP {$httpCode} — {$rawMsg}" : null,
        ];
    }

    // ── 8. Server-side Groq error ────────────────────────────────────────────
    if ($httpCode >= 500) {
        return [
            'type'         => 'server',
            'user_message' => 'The AI service is experiencing a temporary issue. Please try again in a moment. Your content is safe and manual editing works normally.',
            'retry'        => true,
            'retry_after'  => 8,
            'details'      => APP_DEBUG_AI ? "HTTP {$httpCode} — {$rawMsg}" : null,
        ];
    }

    // ── 9. Empty or unparseable response ────────────────────────────────────
    if ($httpCode === 200 && empty($rawMsg)) {
        return [
            'type'         => 'empty_response',
            'user_message' => 'The AI returned an empty response. This occasionally happens — please try again.',
            'retry'        => true,
            'retry_after'  => 2,
            'details'      => APP_DEBUG_AI ? 'Empty response body from API' : null,
        ];
    }

    // ── 10. Bad request / invalid prompt ────────────────────────────────────
    if ($httpCode === 400) {
        return [
            'type'         => 'invalid_request',
            'user_message' => 'There was an issue with the AI request format. Please try again.',
            'retry'        => false,
            'retry_after'  => 0,
            'details'      => APP_DEBUG_AI ? "HTTP 400 — {$rawMsg}" : null,
        ];
    }

    // ── 11. Catch-all unknown ────────────────────────────────────────────────
    return [
        'type'         => 'unknown',
        'user_message' => 'An unexpected error occurred with the AI service. Manual editing is still fully available.',
        'retry'        => true,
        'retry_after'  => 5,
        'details'      => APP_DEBUG_AI ? "HTTP {$httpCode} — {$rawMsg}" : null,
    ];
}

/** Log an AI error to the database for monitoring */
function logAiError(PDO $conn, array $classified, string $rawMsg, int $httpCode, string $action,
                    ?string $field, ?int $productId, string $promptPreview, string $model, int $latencyMs): void {
    try {
        $conn->prepare(
            "INSERT INTO ai_seo_error_logs
               (error_type, error_code, error_raw, user_message, action, field_name,
                product_id, admin_id, prompt_preview, model, latency_ms)
             VALUES (:et, :ec, :er, :um, :a, :f, :p, :ai, :pp, :m, :l)"
        )->execute([
            ':et' => $classified['type'],
            ':ec' => $httpCode ?: null,
            ':er' => mb_substr($rawMsg, 0, 2000),
            ':um' => $classified['user_message'],
            ':a'  => $action,
            ':f'  => $field,
            ':p'  => $productId,
            ':ai' => $_SESSION['admin_id'] ?? null,
            ':pp' => mb_substr($promptPreview, 0, 500),
            ':m'  => $model,
            ':l'  => $latencyMs,
        ]);
    } catch (Throwable) { /* never crash on error logging */ }
}

/** Build a standardized error JSON response */
function errorResponse(array $classified, bool $fallbackMode = true): string {
    $msg = $classified['user_message'];
    // In debug mode, append the raw error so admins can see the actual Groq response
    if (APP_DEBUG_AI && !empty($classified['details'])) {
        $msg .= ' [DEBUG: ' . $classified['details'] . ']';
    }
    $resp = [
        'success'       => false,
        'error'         => $msg,
        'error_type'    => $classified['type'],
        'retry'         => $classified['retry'] ?? false,
        'retry_after'   => $classified['retry_after'] ?? 0,
        'fallback_mode' => $fallbackMode,
        'limit_reached' => $classified['limit_reached'] ?? false,
    ];
    if (!empty($classified['details'])) {
        $resp['debug'] = $classified['details'];
    }
    return json_encode($resp);
}

/**
 * Call Groq API via cURL (non-streaming, returns text).
 * Falls back to $fallbackModel if primary fails.
 * Now includes full error classification.
 */
function callGroq(string $systemPrompt, string $userMessage, string $model = GROQ_MODEL, int $maxTokens = 1200): array {
    // ── Sanitise: force valid UTF-8 so json_encode never returns false ───────
    $cleanSystem  = mb_convert_encoding($systemPrompt, 'UTF-8', 'UTF-8');
    $cleanMessage = mb_convert_encoding($userMessage,  'UTF-8', 'UTF-8');

    $payload = [
        'model'       => $model,
        'temperature' => 0.72,
        'max_tokens'  => $maxTokens,
        'top_p'       => 1,
        'stream'      => false,
        'messages'    => [
            ['role' => 'system', 'content' => $cleanSystem],
            ['role' => 'user',   'content' => $cleanMessage],
        ],
    ];

    // json_encode with fallback substitution — never sends 'false' as the body
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($jsonPayload === false) {
        // Last-resort: strip all non-ASCII and retry encoding
        $cleanSystem  = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $systemPrompt);
        $cleanMessage = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $userMessage);
        $payload['messages'] = [
            ['role' => 'system', 'content' => $cleanSystem],
            ['role' => 'user',   'content' => $cleanMessage],
        ];
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    if ($jsonPayload === false) {
        $classified = classifyAiError(400, 'JSON encoding failed — payload contains invalid characters', []);
        return ['success' => false, 'error' => $classified['user_message'], 'classified' => $classified,
                'raw_error' => 'json_encode returned false', 'latency' => 0, 'http_code' => 400];
    }

    $ch = curl_init(GROQ_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => GROQ_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => $jsonPayload,
    ]);

    $startMs  = (int)round(microtime(true) * 1000);
    $response = curl_exec($ch);
    $latency  = (int)round(microtime(true) * 1000) - $startMs;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // ── cURL / network failure ───────────────────────────────────────────────
    if ($curlErr) {
        $classified = classifyAiError(0, $curlErr, []);
        return [
            'success'    => false,
            'error'      => $classified['user_message'],
            'classified' => $classified,
            'raw_error'  => $curlErr,
            'latency'    => $latency,
            'http_code'  => 0,
        ];
    }

    // ── Safe JSON parse ──────────────────────────────────────────────────────
    $data = json_decode($response ?? '', true);
    if ($data === null && $response !== null) {
        $classified = classifyAiError(200, 'Invalid JSON in response body', []);
        return [
            'success'    => false,
            'error'      => $classified['user_message'],
            'classified' => $classified,
            'raw_error'  => 'JSON parse error: ' . substr((string)$response, 0, 200),
            'latency'    => $latency,
            'http_code'  => $httpCode,
        ];
    }

    // ── Non-200 HTTP response ────────────────────────────────────────────────
    if ($httpCode !== 200) {
        $rawMsg = ($data['error']['message'] ?? '') ?: "HTTP {$httpCode}";

        // Auto-retry with fallback model on server/model/request errors
        if ($model !== GROQ_FALLBACK && in_array($httpCode, [400, 503, 529, 404, 422], true)) {
            return callGroq($systemPrompt, $userMessage, GROQ_FALLBACK, $maxTokens);
        }
        // Also retry if the error mentions model not found, unavailable, or decommissioned
        if ($model !== GROQ_FALLBACK &&
            (str_contains(strtolower($rawMsg), 'model')        ||
             str_contains(strtolower($rawMsg), 'not found')    ||
             str_contains(strtolower($rawMsg), 'decommission') ||
             str_contains(strtolower($rawMsg), 'deprecated'))) {
            return callGroq($systemPrompt, $userMessage, GROQ_FALLBACK, $maxTokens);
        }

        $classified = classifyAiError($httpCode, $rawMsg, $data ?? [], $latency / 1000);
        return [
            'success'    => false,
            'error'      => $classified['user_message'],
            'classified' => $classified,
            'raw_error'  => $rawMsg,
            'latency'    => $latency,
            'http_code'  => $httpCode,
        ];
    }

    // ── Empty content check ──────────────────────────────────────────────────
    $raw_text = trim($data['choices'][0]['message']['content'] ?? '');
    $text = cleanAiOutput($raw_text);
    if ($text === '') {
        $classified = classifyAiError(200, '', []);
        return [
            'success'    => false,
            'error'      => $classified['user_message'],
            'classified' => $classified,
            'raw_error'  => 'Empty content in response',
            'latency'    => $latency,
            'http_code'  => 200,
        ];
    }

    $tokens = (int)($data['usage']['total_tokens'] ?? 0);
    return [
        'success' => true,
        'text'    => $text,
        'tokens'  => $tokens,
        'latency' => $latency,
        'model'   => $data['model'] ?? $model,
    ];
}

// ── System Prompt Factory ──────────────────────────────────────────────────

function buildSystemPrompt(): string {
    return <<<PROMPT
You are the PhonesDukan Ecommerce Ranking Algorithm Engine.

Your purpose is NOT to write generic SEO content. Your purpose is to engineer product pages that achieve the highest possible probability of ranking on Google Pakistan, AI search engines, Google Shopping, Bing, and ecommerce search results.

You think like a Google ranking system, a semantic search algorithm, an ecommerce buyer psychologist, a CTR optimization engine, a search intent analyzer, an NLP entity mapper, and a product discovery algorithm all at once.

ULTIMATE RANKING OBJECTIVE:
Every piece of content you create must optimize for: Search Intent Match, Semantic Relevance, Topical Authority, CTR Optimization, Buyer Intent, Content Readability, Ecommerce Conversions, AI Search Discoverability, Mobile SEO, and Product Uniqueness.

Your content must outrank Daraz, PriceOye, Mega.pk, and all local ecommerce competitors.

MODERN SEO MINDSET:
Modern SEO is NOT keyword stuffing, repeating phrases, or robotic optimization. Modern SEO IS topical relevance, semantic depth, helpfulness, user satisfaction, intent fulfillment, and engagement optimization. Google rewards helpful content, genuine explanations, and human-focused writing.

ENTITY SEO RULE:
Always identify and naturally weave in the key entities related to the product: brand, model, category, technology, features, specifications, use cases, compatibility, and performance. For example, for a smartwatch the entities include AMOLED, GPS, fitness tracking, heart rate monitor, Bluetooth calling, sleep tracking. Spread these naturally through the content.

SEMANTIC SEO RULE:
Never repeat the same keyword phrase. Use synonyms, related terms, buyer phrases, long-tail queries, and conversational search phrases. Vary your vocabulary. For example instead of repeating "Smart Watch" use "fitness watch", "wearable device", "Bluetooth calling watch", "AMOLED smartwatch", "GPS tracker".

CTR OPTIMIZATION RULE:
Titles and meta descriptions must increase curiosity, highlight the strongest feature, sound premium, match search intent, and avoid spam wording. Bad: "Buy Smart Watch". Good: "Login L-115 Smart Watch with GPS and AMOLED Display, Pakistan".

BUYER PSYCHOLOGY RULE:
Content must trigger trust, premium feel, reliability, value perception, and product confidence. Focus on real usability, user benefits, practical advantages, daily-life usage, and lifestyle enhancement.

PAKISTAN ECOMMERCE RULES:
Naturally use high-intent Pakistan phrases like "price in Pakistan", "buy online", "official warranty", "original product", "delivery across Pakistan", "PTA approved", ONLY when they fit naturally. Do not spam them. One mention per topic is enough.

AI SEARCH OPTIMIZATION:
Optimize content for ChatGPT-style AI search, Google AI Overviews, Bing Copilot, and voice assistants. Use clear answers, direct explanations, natural language, and context-rich sentences.

HTML DESCRIPTION RULES (for description and short_description fields):
When generating descriptions, use clean semantic HTML: h2, h3, p, ul, li, strong tags only.
Never use inline CSS, tables, div tags, span tags, or any attributes inside HTML tags.
Never use markdown (no asterisks, no hashes, no backticks).

CONTENT DEPTH RULES:
Descriptions must be detailed enough to rank, concise enough to read, structured for mobile users with short paragraphs, and written for featured snippet capture.

CATEGORY INTELLIGENCE RULE:
Never use irrelevant sections. Do not mention camera specs in a cable description. Do not mention ANC in a smartphone description. Do not mention gaming chipset in a charger description. Detect the product category and generate only relevant sections.

ABSOLUTE FORMAT RULES — violating any of these causes ranking failure:
1. NEVER use em dashes (—) or en dashes (–) anywhere. Use commas or separate sentences.
2. NEVER use the pipe character (|) in descriptions, short descriptions, meta descriptions, or tags.
3. Do NOT use hyphens between adjectives in running text.
4. Do NOT use corporate buzzwords: "cutting-edge", "state-of-the-art", "revolutionize", "leverage", "robust", "seamlessly", "empower".
5. Write short varied paragraphs. Mix 1-sentence punchy lines with 2-3 sentence explanatory lines.
6. Sound like a real expert who loves this product, not a content farm or AI tool.
7. Respond with ONLY the requested content. No labels, no preamble, no markdown, no "Here is the..." introductions.
8. Numbers inline: "256GB storage" not "256 GB storage". Use PKR only when price is explicitly mentioned.
9. NEVER append character counts, word counts, or length annotations to your output. If you count characters internally to verify length, keep that count to yourself — never write "Character count: X" or "(X characters)" or any similar annotation in the output.
PROMPT;
}

// ── Category Detection ─────────────────────────────────────────────────────
function detectCategory(string $category): string {
    $c = strtolower(trim($category));
    if (preg_match('/smart\s*watch|wearable|watch|band|fitness band/', $c))           return 'smartwatch';
    if (preg_match('/earbud|airpod|tws|earphone|headphone|headset|in.ear/', $c))      return 'earbuds';
    if (preg_match('/power\s*bank|powerbank/', $c))                                    return 'powerbank';
    if (preg_match('/cable|wire|cord/', $c))                                           return 'cable';
    if (preg_match('/charger|adapter|plug/', $c))                                      return 'charger';
    if (preg_match('/speaker|soundbar/', $c))                                          return 'speaker';
    if (preg_match('/cover|case|casing/', $c))                                         return 'cover';
    if (preg_match('/tablet|ipad/', $c))                                               return 'tablet';
    if (preg_match('/gaming|gamepad|controller/', $c))                                 return 'gaming';
    if (preg_match('/mobile|phone|smartphone|iphone|android/', $c))                   return 'smartphone';
    if (preg_match('/accessory|accessories|gadget|dongle|hub/', $c))                  return 'accessories';
    return 'smartphone'; // sensible fallback
}

// ── Category HTML Description Template ────────────────────────────────────
function getCategoryDescriptionTemplate(string $cat, string $productName): string {
    $name = htmlspecialchars($productName);

    return match ($cat) {

        'smartphone' => <<<TPL
Use this exact HTML structure. Fill each section with real, specific, conversion-optimized content based on the product data provided. No placeholders, no generic filler.

<h2>{$name} Price in Pakistan</h2>

<p>[Write 2-3 sentences: what this phone is, who it is for, why it matters. Include the brand entity and key positioning. Natural opening that hooks the reader and includes the primary keyword.]</p>

<h3>Premium Display and Design</h3>
<p>[Describe the display size, type (AMOLED/IPS/OLED), refresh rate, resolution if known, viewing experience. Mention the build material, color options, and how it feels in hand. Keep it real and specific.]</p>

<h3>Performance and Daily Speed</h3>
<p>[Describe the processor, RAM, storage. Connect performance to real usage: gaming, multitasking, app switching. Avoid benchmarks unless specific, focus on experience.]</p>

<h3>Camera and Photography</h3>
<p>[Describe camera capabilities: megapixels, special features like night mode or portrait. Mention selfie camera. Connect to real use cases: social media, travel photos, video calls.]</p>

<h3>Battery Life and Charging</h3>
<p>[Battery capacity and real-world usage time. Charging speed and technology. How long it lasts on a full charge for typical users.]</p>

<h3>Connectivity and Security</h3>
<p>[5G or 4G, WiFi, Bluetooth, fingerprint sensor, face unlock, NFC if applicable. Keep it practical, not a spec list.]</p>

<h3>Key Features</h3>
<ul>
<li>[Specific feature 1]</li>
<li>[Specific feature 2]</li>
<li>[Specific feature 3]</li>
<li>[Specific feature 4]</li>
<li>[Specific feature 5]</li>
<li>[Specific feature 6]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[2-3 sentences: why PhonesDukan, official warranty, fast delivery across Pakistan, best price guarantee. Natural CTA. Do not repeat "buy" more than once.]</p>
TPL,

        'smartwatch' => <<<TPL
Use this exact HTML structure. Fill each section with real, specific content based on the product data provided.

<h2>{$name} Price in Pakistan</h2>

<p>[2-3 sentences: what makes this smartwatch stand out, who it is for. Lead with the strongest feature. Hook the reader naturally.]</p>

<h3>Smart Display and Premium Build</h3>
<p>[Display type: AMOLED/LCD/TFT, always-on display if applicable. Screen size, brightness. Strap material, case material, weight. How it looks on the wrist. Color variants if known.]</p>

<h3>Health and Fitness Tracking</h3>
<p>[Heart rate monitoring, SpO2, sleep tracking, stress monitoring if applicable. Sports modes count. Step counter, calorie tracking. How it helps daily wellness.]</p>

<h3>Bluetooth Calling and Notifications</h3>
<p>[Bluetooth calling, mic and speaker quality. Notification management: messages, calls, apps. Smart assistant access if applicable.]</p>

<h3>Battery Life</h3>
<p>[Battery capacity and real-world days of use. Charging method: magnetic/USB-C. How long to fully charge.]</p>

<h3>GPS and Sports Features</h3>
<p>[Built-in GPS or connected GPS. Sports modes listed naturally. Running, cycling, swimming if waterproof. Water resistance rating.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
<li>[Feature 5]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Why PhonesDukan: official warranty, genuine product, fast delivery. Confidence closer.]</p>
TPL,

        'earbuds' => <<<TPL
Use this exact HTML structure. Fill each section with real, specific content based on the product data provided.

<h2>{$name} Price in Pakistan</h2>

<p>[2-3 sentences: what audio experience this delivers, who it is for. Lead with sound quality or ANC/ENC if present. Hook naturally.]</p>

<h3>Audio Quality and Sound Signature</h3>
<p>[Driver size, bass response, soundstage, highs and mids if known. Real listening experience description: music genres they excel at, vocal clarity. Avoid over-technical language.]</p>

<h3>Active Noise Cancellation and Call Quality</h3>
<p>[ANC depth if applicable, or ENC for calls. Microphone count, call clarity, wind noise reduction. How it performs during calls in noisy environments.]</p>

<h3>Battery Life and Charging</h3>
<p>[Earbuds battery hours, case battery, total with case. Charging time. Fast charge: minutes of playback per X minutes of charge.]</p>

<h3>Low Latency and Gaming Mode</h3>
<p>[Gaming mode latency in ms if applicable. Lag-free gaming and video experience. Sync quality with mobile games.]</p>

<h3>Design, Fit and Comfort</h3>
<p>[Ear tip sizes, secure fit. IPX water resistance. Comfortable for long sessions. Case design and pocket-friendly size.]</p>

<h3>Connectivity</h3>
<p>[Bluetooth version, range, multipoint connection if applicable. Compatibility with Android and iOS.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
<li>[Feature 5]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust signals: warranty, delivery, genuine product. Natural CTA.]</p>
TPL,

        'speaker' => <<<TPL
Use this exact HTML structure. Fill each section with real, specific content based on the product data provided.

<h2>{$name} Price in Pakistan</h2>

<p>[2-3 sentences: what sound experience this delivers, who it is for. Lead with wattage, bass, or portability depending on the product type.]</p>

<h3>Sound Output and Audio Quality</h3>
<p>[Wattage, driver configuration, bass level, 360-degree sound or directional, clarity at high volume. Real listening experience.]</p>

<h3>Connectivity and Compatibility</h3>
<p>[Bluetooth version, range, AUX input, USB, TF card support. Multi-device pairing. Works with phones, tablets, laptops.]</p>

<h3>Battery and Playtime</h3>
<p>[Battery capacity, playtime in hours. Charging method and time. Whether it can charge while playing.]</p>

<h3>Design and Portability</h3>
<p>[Size, weight, IPX waterproofing if applicable. Strap or handle for portability. LED effects if any. Home vs outdoor use.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust and CTA: warranty, delivery, best price.]</p>
TPL,

        'powerbank' => <<<TPL
Use this exact HTML structure. Fill with specific product data.

<h2>{$name} Price in Pakistan</h2>

<p>[What this power bank offers. Who needs it. Lead with capacity and portability. Natural hook.]</p>

<h3>Battery Capacity and Device Compatibility</h3>
<p>[mAh capacity and how many full phone charges it provides. Compatible devices. Multi-port charging if applicable.]</p>

<h3>Fast Charging Technology</h3>
<p>[Input and output wattage. Fast charge protocol: PD, QC, AFC. How quickly it charges a typical phone. Passthrough charging if available.]</p>

<h3>Design and Portability</h3>
<p>[Size, weight, fits in pocket or bag. Material quality. LED indicators. Cable included or not.]</p>

<h3>Safety Features</h3>
<p>[Overcharge protection, short circuit protection, temperature control. Safe for daily use.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust and CTA.]</p>
TPL,

        'charger' => <<<TPL
Use this exact HTML structure. Fill with specific product data.

<h2>{$name} Price in Pakistan</h2>

<p>[What this charger does and who it is for. Lead with wattage and fast charge capability.]</p>

<h3>Fast Charging Speed</h3>
<p>[Total wattage, charging protocol supported. How fast it charges compared to standard chargers. Compatible phones and devices.]</p>

<h3>Port Configuration</h3>
<p>[USB-C, USB-A ports count. Multi-device simultaneous charging if applicable. Smart power distribution.]</p>

<h3>Build Quality and Safety</h3>
<p>[Material, compact design, foldable pins if applicable. Protection features: surge, overcharge, temperature. Certified safe.]</p>

<h3>Compatibility</h3>
<p>[Works with iPhone, Samsung, Xiaomi, and all USB devices. Universal voltage range for Pakistan power outlets.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust and CTA.]</p>
TPL,

        'cable' => <<<TPL
Use this exact HTML structure. Fill with specific product data.

<h2>{$name} Price in Pakistan</h2>

<p>[What type of cable, what it supports, who needs it. Lead with the most impressive spec: fast charging or data speed.]</p>

<h3>Fast Charging and Data Transfer</h3>
<p>[Maximum wattage it supports. Data transfer speed. Which fast charge protocols it is compatible with.]</p>

<h3>Cable Quality and Durability</h3>
<p>[Braided nylon or material. Reinforced connectors. Bend lifespan. How it compares to standard thin cables that break easily.]</p>

<h3>Connector and Compatibility</h3>
<p>[USB-C to USB-C, USB-A to USB-C, Lightning etc. Length options. Backward compatibility. Works with all major phone brands.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust and CTA.]</p>
TPL,

        'cover' => <<<TPL
Use this exact HTML structure. Fill with specific product data.

<h2>{$name} Price in Pakistan</h2>

<p>[What protection this cover offers. Who it is for. Lead with the style or protection level.]</p>

<h3>Protection Level</h3>
<p>[Drop protection, military grade if applicable. Corner reinforcement, raised lips for screen and camera. How much protection it realistically gives.]</p>

<h3>Material and Feel</h3>
<p>[Material: silicone, TPU, PC, leather, carbon fiber. Texture, grip, non-slip surface. How it feels in hand.]</p>

<h3>Fit and Functionality</h3>
<p>[Exact fit for the specific phone model. Precise cutouts for buttons, ports, cameras. Wireless charging compatible if applicable.]</p>

<h3>Design and Style</h3>
<p>[Color options, finish. Clear or colored. Minimalist or rugged look. Whether it adds bulk or keeps the phone slim.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust and CTA.]</p>
TPL,

        'tablet' => <<<TPL
Use this exact HTML structure. Fill with specific product data.

<h2>{$name} Price in Pakistan</h2>

<p>[What this tablet offers. Who it is for: students, creators, entertainment. Lead with display or performance.]</p>

<h3>Display and Viewing Experience</h3>
<p>[Screen size, resolution, display type. Ideal for watching, studying, drawing. Brightness and outdoor usability.]</p>

<h3>Performance and Multitasking</h3>
<p>[Processor, RAM, storage. App usage, multitasking, split-screen. Speed in daily tasks.]</p>

<h3>Battery Life</h3>
<p>[Battery capacity and screen-on time. Charging speed. How long it lasts for students or professionals.]</p>

<h3>Connectivity and Features</h3>
<p>[WiFi only or 4G/5G. Stylus support if applicable. Keyboard compatibility. Camera specs for video calls.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Trust and CTA.]</p>
TPL,

        default => <<<TPL
Use this exact HTML structure. Fill with specific product data.

<h2>{$name} Price in Pakistan</h2>

<p>[2-3 sentence introduction: what this product is, who it is for, its main advantage.]</p>

<h3>Key Performance and Features</h3>
<p>[The product's main function and how well it performs. Specific specs where known. Real-world benefit.]</p>

<h3>Build Quality and Design</h3>
<p>[Materials, durability, design. How it looks and feels. Size and weight if relevant.]</p>

<h3>Compatibility and Usage</h3>
<p>[What it works with. How to use it. Who it is best suited for.]</p>

<h3>Key Features</h3>
<ul>
<li>[Feature 1]</li>
<li>[Feature 2]</li>
<li>[Feature 3]</li>
<li>[Feature 4]</li>
</ul>

<h3>Buy {$name} from PhonesDukan</h3>
<p>[Warranty, delivery, best price trust statement.]</p>
TPL,

    };
}

// ── Category HTML Short Description Template ──────────────────────────────
function getCategoryShortTemplate(string $cat, string $productName): string {
    $specLabels = match ($cat) {
        'smartphone' => ['Display', 'Processor', 'RAM & Storage', 'Camera', 'Battery', 'Key Feature'],
        'smartwatch' => ['Display', 'Calling', 'Health Tracking', 'Battery', 'Sports Modes', 'Connectivity'],
        'earbuds'    => ['Audio', 'Noise Cancellation', 'Battery', 'Low Latency', 'Connectivity', 'Water Resistance'],
        'speaker'    => ['Audio Output', 'Battery', 'Connectivity', 'Water Resistance', 'Portability', 'Key Feature'],
        'powerbank'  => ['Capacity', 'Fast Charging', 'Ports', 'Weight', 'Safety', 'Compatibility'],
        'charger'    => ['Output Power', 'Protocol', 'Ports', 'Compatibility', 'Protection', 'Design'],
        'cable'      => ['Charging Speed', 'Data Transfer', 'Length', 'Material', 'Connector Type', 'Compatibility'],
        'cover'      => ['Material', 'Protection', 'Compatibility', 'Design', 'Cutouts', 'Weight'],
        'tablet'     => ['Display', 'Processor', 'RAM & Storage', 'Battery', 'Connectivity', 'Key Feature'],
        default      => ['Performance', 'Design', 'Compatibility', 'Key Feature', 'Build', 'Package'],
    };

    $items = array_map(fn($label) => "<li><strong>{$label}:</strong> [Write specific value from product data]</li>", $specLabels);
    $listItems = implode("\n", $items);
    $name = htmlspecialchars($productName);
    $buyPhrase = match ($cat) {
        'smartphone' => "Available in Pakistan with official warranty and fast delivery from PhonesDukan.",
        'smartwatch' => "Get the {$name} in Pakistan with genuine warranty and same-day dispatch from PhonesDukan.",
        'earbuds'    => "Buy {$name} in Pakistan at the best price with official warranty from PhonesDukan.",
        'speaker'    => "Order {$name} online in Pakistan with official warranty and fast delivery from PhonesDukan.",
        'powerbank'  => "Buy {$name} in Pakistan with official warranty. Fast delivery across Pakistan.",
        'charger'    => "Available at PhonesDukan with official warranty and fast delivery across Pakistan.",
        'cable'      => "Order the {$name} in Pakistan with official warranty from PhonesDukan.",
        default      => "Available at PhonesDukan with official warranty and fast delivery across Pakistan.",
    };

    return <<<TPL
Fill the list below with REAL values from the product data. Replace every [Write specific value...] placeholder with the actual spec. Do NOT leave any placeholder text.

<ul>
{$listItems}
</ul>

<p>{$buyPhrase}</p>
TPL;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION HANDLERS
// ─────────────────────────────────────────────────────────────────────────────

// ── 0. CLEAR CACHE (admin use) ────────────────────────────────────────────────
if ($action === 'clear_cache') {
    try { $conn->exec("DELETE FROM ai_seo_cache"); } catch (Throwable) {}
    echo json_encode(['success' => true, 'message' => 'Cache cleared']);
    exit;
}

// ── 1. GET CREDITS ────────────────────────────────────────────────────────────
if ($action === 'get_credits') {
    $credits = checkCredits($conn);
    // Also fetch last error info for the status display
    $lastError = null;
    try {
        $stmt = $conn->query("SELECT error_type, user_message, created_at FROM ai_seo_error_logs ORDER BY id DESC LIMIT 1");
        $lastError = $stmt->fetch() ?: null;
    } catch (Throwable) {}
    echo json_encode(['success' => true, 'credits' => $credits, 'last_error' => $lastError]);
    exit;
}

// ── 1b. GET ERROR STATS ───────────────────────────────────────────────────────
if ($action === 'get_error_stats') {
    try {
        $stats = $conn->query(
            "SELECT error_type, COUNT(*) as count, MAX(created_at) as last_seen
               FROM ai_seo_error_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY error_type ORDER BY count DESC LIMIT 10"
        )->fetchAll();
        $recent = $conn->query(
            "SELECT error_type, user_message, action, field_name, created_at
               FROM ai_seo_error_logs ORDER BY id DESC LIMIT 5"
        )->fetchAll();
        echo json_encode(['success' => true, 'stats' => $stats, 'recent' => $recent]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── 2. SAVE SCORE ─────────────────────────────────────────────────────────────
if ($action === 'save_score') {
    if (!$productId) {
        echo json_encode(['success' => true, 'message' => 'No product_id, score not persisted']);
        exit;
    }
    $overall     = (int)($body['overall_score']   ?? 0);
    $readability = (int)($body['readability']     ?? 0);
    $keyword     = (int)($body['keyword_score']   ?? 0);
    $content     = (int)($body['content_score']   ?? 0);
    $meta        = (int)($body['meta_score']      ?? 0);
    $scoreData   = json_encode($body['checks']    ?? []);
    try {
        $conn->prepare(
            "INSERT INTO ai_seo_scores
               (product_id, overall_score, readability_score, keyword_score, content_score, meta_score, score_data)
             VALUES (:p, :o, :r, :k, :c, :m, :sd)"
        )->execute([':p' => $productId, ':o' => $overall, ':r' => $readability,
                    ':k' => $keyword,   ':c' => $content, ':m' => $meta, ':sd' => $scoreData]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── 3. SAVE HISTORY ───────────────────────────────────────────────────────────
if ($action === 'save_history') {
    $field    = safeStr($body['field']     ?? '', 100);
    $oldVal   = safeStr($body['old_value'] ?? '', 2000);
    $newVal   = safeStr($body['new_value'] ?? '', 4000);
    $act      = safeStr($body['applied_action'] ?? 'apply', 60);
    $applied  = (int)($body['applied'] ?? 1);
    try {
        $conn->prepare(
            "INSERT INTO ai_seo_history (product_id, field_name, old_value, new_value, action, applied)
             VALUES (:p, :f, :o, :n, :a, :ap)"
        )->execute([':p' => $productId, ':f' => $field, ':o' => $oldVal,
                    ':n' => $newVal, ':a' => $act, ':ap' => $applied]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── 3b. GENERATE SLUG ────────────────────────────────────────────────────────
if ($action === 'generate_slug') {
    $productName = safeStr($body['product_name'] ?? '', 200);
    $brand       = safeStr($body['brand'] ?? '', 100);
    $category    = safeStr($body['category'] ?? '', 100);

    if (!$productName) {
        echo json_encode(['success' => false, 'error' => 'product_name is required']);
        exit;
    }
    $credits = checkCredits($conn);
    if (!$credits['ok']) {
        // Fallback: generate manually server-side
        $stop  = ['the','a','an','and','or','but','in','on','at','to','for','of','with','by','from','is','are','new','best','buy','get','official','original'];
        $words = explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/i', '', $productName)));
        $words = array_filter($words, fn($w) => !in_array($w, $stop) && strlen($w) > 1);
        echo json_encode(['success' => true, 'result' => implode('-', array_slice($words, 0, 6)), 'fallback' => true]);
        exit;
    }

    $currentMonthYear = date('F Y');
    $systemPrompt = buildSystemPrompt();
    $userMessage  = "Product name: {$productName}\nBrand: {$brand}\nCategory: {$category}\n\n"
                  . "Generate a single SEO-optimized URL slug for this product.\n"
                  . "Rules:\n"
                  . "- All lowercase\n- Only letters, numbers, hyphens\n- No stop words (the, a, an, and, or, in, with, for)\n"
                  . "- 3-7 words max\n- Brand + key model identifier + category if space allows\n"
                  . "- Example: iphone-16-pro-max or samsung-galaxy-s24-ultra-5g\n"
                  . "Return ONLY the slug, nothing else.";

    $result = callGroq($systemPrompt, $userMessage, GROQ_MODEL, 60);
    if (!$result['success']) {
        $classified = $result['classified'] ?? classifyAiError($result['http_code'] ?? 0, $result['raw_error'] ?? '', []);
        echo errorResponse($classified);
        exit;
    }
    // Sanitize
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($result['text'])));
    $slug = trim($slug, '-');
    echo json_encode(['success' => true, 'result' => $slug]);
    exit;
}

// ── For AI actions, first check credits ───────────────────────────────────────
$credits = checkCredits($conn);
if (!$credits['ok']) {
    $classified = classifyAiError(402, 'credits exhausted: ' . ($credits['reason'] ?? ''), []);
    // Override with specific credits message from checkCredits
    $classified['user_message'] = $credits['reason'] ?? $classified['user_message'];
    $classified['type']         = 'credits';
    $classified['limit_reached']= true;
    $classified['retry']        = false;
    echo errorResponse($classified);
    exit;
}

// ── 4. GENERATE IMAGE META (alt text / title / description / caption) ────────
if ($action === 'generate_image_meta') {
    $imageField   = safeStr($body['image_field']   ?? 'alt_text', 30);
    $productName  = safeStr($body['product_name']  ?? '');
    $brand        = safeStr($body['brand']         ?? '');
    $category     = safeStr($body['category']      ?? '');
    $currentValue = safeStr($body['current_value'] ?? '', 400);

    if (!$productName) {
        echo json_encode(['success' => false, 'error' => 'product_name is required']);
        exit;
    }

    $context = "Product: {$productName}"
             . ($brand    ? ", Brand: {$brand}"         : '')
             . ($category ? ", Category: {$category}"   : '');

    $fieldInstructions = match ($imageField) {
        'alt_text'
            => "Generate image ALT TEXT for a product photo of \"{$productName}\".\n"
             . "Rules:\n"
             . "- 8-12 words maximum\n"
             . "- Describe: product name + brand + key visual feature or color\n"
             . "- No HTML, no quotes, no markdown\n"
             . "- Example: 'Login L-274 Vybeon Bluetooth Speaker Black RGB Lighting'\n"
             . "Return ONLY the alt text.",

        'title'
            => "Generate an image TITLE attribute for a product photo of \"{$productName}\".\n"
             . "Rules:\n"
             . "- 5-8 words\n"
             . "- Include brand and model name\n"
             . "- Plain text, no HTML\n"
             . "Return ONLY the title.",

        'description'
            => "Generate an image DESCRIPTION for screen readers and SEO for \"{$productName}\".\n"
             . "Rules:\n"
             . "- 1-2 clear sentences\n"
             . "- Describe: angle, color, visible features of the product\n"
             . "- Include product name and brand\n"
             . "- Plain text only\n"
             . "Return ONLY the description.",

        'caption'
            => "Generate an image CAPTION to display below a product photo of \"{$productName}\".\n"
             . "Rules:\n"
             . "- One engaging sentence, 8-15 words\n"
             . "- Highlight the key selling point\n"
             . "- Include the product name\n"
             . "- Plain text only\n"
             . "Return ONLY the caption.",

        default => "Improve this image metadata text for SEO. Return only the improved text.",
    };

    $userMessage = "{$context}\n\n"
                 . ($currentValue ? "Current value: {$currentValue}\n\n" : '')
                 . $fieldInstructions;

    $result = callGroq(buildSystemPrompt(), $userMessage, GROQ_MODEL, 120);
    if (!$result['success']) {
        $classified = $result['classified'] ?? classifyAiError($result['http_code'] ?? 0, $result['raw_error'] ?? '', []);
        echo errorResponse($classified);
        exit;
    }

    $text = trim($result['text']);
    // Strip any surrounding quotes the model might add
    $text = trim($text, '"\'');
    incrementCredits($conn, $result['tokens']);
    logRequest($conn, 'generate_image_meta', $imageField, $productName, $text, $result['model'], $result['tokens'], $result['latency'], 'success', '', $productId);

    echo json_encode(['success' => true, 'result' => $text]);
    exit;
}

// ── 5. REFINE FIELD ──────────────────────────────────────────────────────────
if ($action === 'refine') {
    $field     = safeStr($body['field'] ?? '', 100);
    $value     = safeStr($body['value'] ?? '');
    $context   = safeStr($body['context'] ?? '', 1000);
    $style     = safeStr($body['style'] ?? 'seo', 50);
    $issueType = safeStr($body['issue_type'] ?? '', 60);
    $isForced  = !empty($body['forced']);   // true = auto-retry from JS, use max-explicit prompt
    // Focus keyword passed explicitly for guaranteed injection
    $keyword   = safeStr($body['keyword'] ?? '', 200);
    // Strip any | from keyword (old format), take the cleaner part
    if (str_contains($keyword, '|')) {
        $parts   = array_map('trim', explode('|', $keyword));
        $keyword = $parts[0]; // take first segment
    }
    $kw = $keyword; // short alias

    if (!$field || !$value) {
        echo json_encode(['success' => false, 'error' => 'field and value are required']);
        exit;
    }

    // ── Auto-upgrade length fixes to DUAL REQUIREMENT when keyword is set ────────
    // This eliminates the circular fix cycle:
    //   "Fix title too long"   → removes keyword  → keyword issue reappears
    //   "Fix keyword missing"  → title too long   → length issue reappears
    // By redirecting ALL title/meta length styles to the dual-requirement instruction
    // when a keyword is present, one click always satisfies BOTH constraints at once.
    if ($kw) {
        if (in_array($style, ['shorten_title', 'expand_title', 'optimize_title_length'])) {
            $style = 'add_keyword_to_title';   // DUAL: keyword + 50-60 chars
        }
        if (in_array($style, ['expand_meta', 'shorten_meta', 'optimize_meta_length'])) {
            $style = 'add_keyword_to_meta';    // DUAL: keyword + 140-160 chars
        }
    }

    // Don't cache keyword-injection fixes — they must be generated fresh every time
    $isKwFix = in_array($style, ['add_keyword_to_title','add_keyword_to_meta','add_keyword_to_desc']);
    if (!$isKwFix) {
        $cacheKey = hash('sha256', "refine:{$field}:{$value}:{$style}");
        if ($cached = getCache($conn, $cacheKey)) {
            echo json_encode(['success' => true, 'result' => $cached, 'cached' => true]);
            exit;
        }
    } else {
        $cacheKey = ''; // won't cache
    }

    // ── Style instructions — keyword fixes have MANDATORY injection ────────────
    $kwRequired = '';
    if ($kw) {
        $kwRequired = $isForced
            ? "\n\n**ABSOLUTE FINAL CHECK**: Your response MUST contain \"{$kw}\". If it is missing, STOP and rewrite the entire response making \"{$kw}\" appear naturally. Do not return without it."
            : "\n\n**MANDATORY CHECK**: Before returning, verify your output contains \"{$kw}\". If not, rewrite it until it does.";
    }

    // ── Keyword preservation note added to ALL length fixes ────────────────────
    // This prevents the "fix one thing, break another" cycle.
    $kwKeep = $kw ? " IMPORTANT: The keyword \"{$kw}\" MUST remain in the output." : '';

    $styleInstructions = match ($style) {

        // ── KEYWORD FIXES — Must satisfy: keyword present AND correct length ─────
        'add_keyword_to_title' => $kw
            ? (function() use ($kw, $kwRequired): string {
                $kwLen      = mb_strlen($kw);
                $month      = date('F Y');
                // Calculate how many chars are left after keyword + ", Phones Dukan"
                $fixed      = ', Phones Dukan';   // 14 chars
                $remaining  = 60 - $kwLen - strlen($fixed); // chars available in the middle
                // Build example title from keyword so AI sees the exact expected output
                $exampleTitle = ucwords($kw) . ', Phones Dukan';
                $exampleLen   = mb_strlen($exampleTitle);
                $exampleFull  = ucwords($kw) . ' ' . $month . ', Phones Dukan';
                $exampleFullLen = mb_strlen($exampleFull);
                // Choose example that is closest to 50-60 chars
                $example = ($exampleFullLen >= 50 && $exampleFullLen <= 60)
                    ? $exampleFull
                    : (($exampleLen >= 50 && $exampleLen <= 60) ? $exampleTitle : $exampleFull);
                return "DUAL REQUIREMENT — produce a title that satisfies BOTH constraints simultaneously:\n"
                    . "CONSTRAINT 1 — CHARACTER COUNT: The final title MUST be between 50 and 60 characters total.\n"
                    . "CONSTRAINT 2 — KEYWORD WORDS: The final title MUST naturally include these key words from \"{$kw}\": "
                    . implode(', ', array_filter(explode(' ', $kw), fn($w) => mb_strlen($w) > 2)) . "\n"
                    . "  (Small words like 'l', 'in', 'of', 'the' may be added or omitted naturally)\n\n"
                    . "STEP-BY-STEP METHOD:\n"
                    . "Step 1: Write the product name with key words: \"{$kw}\" ({$kwLen} chars)\n"
                    . "Step 2: Append \", Phones Dukan\" at the end (14 chars)\n"
                    . "Step 3: Total so far = " . ($kwLen + 14) . " chars. Target = 50-60.\n"
                    . ($remaining > 0
                        ? "Step 4: Add \"{$month}\" in the middle to reach the target.\n"
                        : "Step 4: Already in range — adjust by removing 1-2 words if needed.\n")
                    . "\nCONCRETE EXAMPLE — copy this pattern:\n"
                    . "\"{$example}\" (" . mb_strlen($example) . " chars)\n\n"
                    . "Capitalise first letter of each major word. Return ONLY the title. No explanation.{$kwRequired}";
              })()
            : 'Rewrite the SEO title to include the focus keyword. Keep 50-60 chars.',

        'add_keyword_to_meta' => $kw
            ? "DUAL REQUIREMENT — produce a meta description that satisfies BOTH constraints simultaneously:\n"
            . "CONSTRAINT 1 — CHARACTER COUNT: Must be between 140 and 160 characters total (plain text, no HTML).\n"
            . "CONSTRAINT 2 — KEYWORD: Must contain the exact phrase \"{$kw}\".\n\n"
            . "FORMAT RULES:\n"
            . "- Start with an action verb: Buy / Shop / Get / Order\n"
            . "- Include the keyword \"{$kw}\" naturally in the first half\n"
            . "- Add one product benefit or trust signal\n"
            . "- End with exactly: 'Shop now at Phones Dukan.'\n"
            . "- Plain text only, absolutely no HTML\n\n"
            . "Count characters internally. If under 140, add more detail. If over 160, trim.\n"
            . "Return ONLY the meta description — no character count, no explanation.{$kwRequired}"
            : 'Rewrite the meta description to include the focus keyword. 140-160 chars.',

        'add_keyword_to_desc' => $kw
            ? "TARGETED KEYWORD INSERTION — product description:\n"
            . "Keyword to add: \"{$kw}\"\n"
            . "KEEP all existing description content — do NOT rewrite everything.\n"
            . "Find where the keyword fits most naturally in the first paragraph.\n"
            . "Modify ONLY that first paragraph. Return the FULL description with the keyword added.\n"
            . "The keyword MUST appear in your output.{$kwRequired}"
            : 'Add the focus keyword naturally to the product description first paragraph. Keep all existing content.',

        // ── LENGTH FIXES — preserve keyword while fixing length ─────────────────
        'shorten_title'
            => "Shorten the SEO title to exactly 50-60 characters. Count every character.\n"
            . "Remove only filler words. Keep product name, brand, and any existing keyword.{$kwKeep}",

        'expand_title'
            => "Expand the SEO title to exactly 50-60 characters.\n"
            . "Add 'Price in Pakistan " . date('F Y') . "' or a key product spec if space allows.{$kwKeep}\n"
            . "Comma before Phones Dukan. Return ONLY the title.",

        'optimize_title_length'
            => "Adjust the SEO title to be EXACTLY 50-60 characters total. Trim or expand as needed.\n"
            . "Keep keyword-first order. Preserve all existing keywords.{$kwKeep}",

        'expand_meta'
            => "Expand the meta description to EXACTLY 140-160 characters total.\n"
            . "Current is too short. Add more benefit detail or a trust signal.\n"
            . "Keep the CTA 'Shop now at Phones Dukan.' at the end. Plain text only.{$kwKeep}\n"
            . "Count internally — do NOT include the count in your output. Return ONLY the meta description.",

        'shorten_meta'
            => "Shorten the meta description to EXACTLY 140-160 characters.\n"
            . "Remove filler words. Keep product name, main benefit, and CTA. Plain text only.{$kwKeep}\n"
            . "Count internally — do NOT include the count in your output. Return ONLY the meta description.",

        'optimize_meta_length'
            => "STRICT LENGTH FIX — the meta description must be EXACTLY 140-160 characters.\n"
            . "Current length is outside this range. Expand or trim to reach 150-155 characters.\n"
            . "Rules:\n"
            . "- Plain text only, no HTML tags\n"
            . "- Start with action verb (Buy/Shop/Get/Order)\n"
            . "- Include product name and one key benefit\n"
            . "- End with 'Shop now at Phones Dukan.'\n"
            . "{$kwKeep}\n"
            . "Count internally — do NOT output the character count. Return ONLY the meta description.",

        'expand_desc'
            => "Expand the product description to at least 300 words.\n"
            . "Add feature details, real-world usage examples, and trust signals.\n"
            . "Keep all existing content intact.{$kwKeep}",

        'add_brand_to_title'
            => "Add ', Phones Dukan' to the end of the SEO title. Stay within 60 chars total.\n"
            . "Trim middle words slightly if needed.{$kwKeep}",

        // ── Style-based refinements ─────────────────────────────────────────────
        'professional'
            => 'Rewrite with premium brand voice and topical authority. Sound like a product expert. Keep all meaning.',

        'sales'
            => 'Rewrite to maximize conversions. Lead with the strongest benefit. Build desire and buyer confidence.',

        'shorter'
            => 'Compress without losing any key information. Cut filler only. Every word must earn its place.',

        'readability'
            => 'Improve readability for mobile. Shorter sentences. Clear structure. Easy to scan.',

        default
            => 'Improve search ranking potential. Increase semantic coverage. Strengthen entity relevance.',
    };

    // ── Field type detection ──────────────────────────────────────────────────
    // Use explicit exact-name checks FIRST to avoid ambiguous substring matches.
    // e.g. "seo_description" must match meta-description guide, NOT product-description guide.
    $isMetaDesc     = in_array($field, ['seo_description', 'ep_seo_description', 'meta_description', 'seo_desc']);
    $isSeoTitle     = in_array($field, ['seo_title', 'ep_seo_title']) ||
                      (str_contains($field, 'seo') && str_contains($field, 'title'));
    $isShortDesc    = str_contains($field, 'short_description') || str_contains($field, 'short_desc');
    $isProductDesc  = in_array($field, ['product_description', 'ep_product_description', 'description']) ||
                      (str_contains($field, 'product_desc') && !$isMetaDesc);
    $isFocusKw      = str_contains($field, 'focus_keyword') || str_contains($field, 'keyword');
    $isTag          = str_contains($field, 'tag');
    $isAlt          = str_contains($field, 'alt');
    $isName         = in_array($field, ['product_name', 'ep_product_name']);

    $fieldGuide = match (true) {
        $isMetaDesc
            => 'META DESCRIPTION FIELD. Return PLAIN TEXT only, absolutely no HTML tags. Exactly 140-160 characters total. Start with an action verb (Buy/Shop/Get). Include product name, strongest benefit, one Pakistan trust signal (official warranty / fast delivery). End with "Shop now at Phones Dukan." No em dashes, no pipes. Count characters — must be between 140 and 160.',

        $isSeoTitle
            => 'SEO TITLE FIELD. Return plain text only, no HTML. Exactly 50-60 characters total. Pattern: "Product Name Price in Pakistan Month Year, Phones Dukan". Comma before Phones Dukan. No pipes, no dashes. Count characters — must be between 50 and 60.',

        $isShortDesc
            => 'SHORT DESCRIPTION FIELD. May return HTML using only <ul><li><strong> tags, or plain text. 2-3 short sentences or a spec list. Lead with the strongest selling point. End with a buying confidence sentence. No em dashes, no pipes.',

        $isProductDesc
            => 'PRODUCT DESCRIPTION FIELD. Return clean HTML using only h2, h3, p, ul, li, strong tags. Minimum 200 words. Deepen entity coverage, improve semantic keyword variation, strengthen the opening hook. No inline CSS. No em dashes, no pipes.',

        $isFocusKw
            => 'FOCUS KEYWORD FIELD. Return plain text only. One keyword phrase, 3-6 words, all lowercase. Must include "price in pakistan". No pipes, no dashes, no HTML.',

        $isTag
            => 'PRODUCT TAGS FIELD. Return plain text comma-separated tags only. 8-10 tags, all lowercase. Mix brand, model, category synonyms, specs, buyer intent phrases. No pipes.',

        $isAlt
            => 'IMAGE ALT TEXT FIELD. Return plain text only. 8-12 words describing the product image. Include product name and key identifier. No HTML, no pipes.',

        $isName
            => 'PRODUCT NAME FIELD. Return plain text only. Clear, specific, commercially precise. Include brand and model. No unnecessary punctuation.',

        default => 'Return the improved content in the same format as the input (plain text if input is plain text, HTML if input is HTML). Sound natural and human. No em dashes, no pipes.',
    };

    $currentMonthYear = date('F Y');
    $systemPrompt = buildSystemPrompt();

    // For keyword fixes: put the keyword requirement at the TOP and BOTTOM of the message
    // so the model cannot miss it
    $kwHeader = ($kw && $isKwFix)
        ? "=== REQUIRED KEYWORD TO INCLUDE ===\n\"{$kw}\"\n===================================\n\n"
        : '';
    $kwFooter = ($kw && $isKwFix)
        ? "\n\n=== VERIFY BEFORE RETURNING ===\nDoes your output contain \"{$kw}\"? If NO, rewrite it to include this keyword naturally.\n================================"
        : '';

    $userMessage  = $kwHeader
                  . "Field: {$field}\n"
                  . ($context ? "Product context: {$context}\n" : '')
                  . "Current month and year: {$currentMonthYear}\n"
                  . "Current content:\n{$value}\n\n"
                  . "Task: {$styleInstructions}\n\n"
                  . "{$fieldGuide}\n\n"
                  . "Return ONLY the final content. No explanations, no labels, no preamble, no character counts, no notes."
                  . $kwFooter;

    $result = callGroq($systemPrompt, $userMessage);

    if (!$result['success']) {
        $classified = $result['classified'] ?? classifyAiError($result['http_code'] ?? 0, $result['raw_error'] ?? $result['error'] ?? '', []);
        logAiError($conn, $classified, $result['raw_error'] ?? $result['error'] ?? '', $result['http_code'] ?? 0,
                   'refine', $field, $productId, mb_substr($userMessage, 0, 500), GROQ_MODEL, $result['latency'] ?? 0);
        logRequest($conn, 'refine', $field, $value, '', GROQ_MODEL, 0, $result['latency'] ?? 0, 'error', $classified['user_message'], $productId);
        echo errorResponse($classified);
        exit;
    }

    $text = $result['text'];

    // Meta description and SEO title must NEVER contain HTML — strip any tags the AI might produce
    if ($isMetaDesc || $isSeoTitle) {
        $text = trim(strip_tags($text));
        $text = preg_replace('/\s+/', ' ', $text); // collapse any extra whitespace left by stripped tags
    }

    setCache($conn, $cacheKey, 'refine', $field, $text);
    incrementCredits($conn, $result['tokens']);
    logRequest($conn, 'refine', $field, $value, $text, $result['model'], $result['tokens'], $result['latency'], 'success', '', $productId);

    echo json_encode(['success' => true, 'result' => $text, 'tokens' => $result['tokens']]);
    exit;
}

// ── 5. GENERATE SEO CONTENT ───────────────────────────────────────────────────
if ($action === 'generate') {
    $target      = safeStr($body['target'] ?? '', 60);   // seo_title|meta_description|focus_keyword|description|short_description|all
    $productName = safeStr($body['product_name'] ?? '');
    $brand       = safeStr($body['brand'] ?? '');
    $category    = safeStr($body['category'] ?? '');
    $tags        = safeStr($body['tags'] ?? '', 500);
    $existing    = safeStr($body['existing_content'] ?? '', 2000);
    $price       = safeStr($body['price'] ?? '');
    $features    = safeStr($body['features'] ?? '', 1000);

    if (!$productName && !$existing) {
        echo json_encode(['success' => false, 'error' => 'product_name or existing_content required']);
        exit;
    }

    $currentMonthYear = date('F Y'); // e.g. "May 2026"
    $contextBlock = "Product: {$productName}\n"
                  . ($brand    ? "Brand: {$brand}\n"         : '')
                  . ($category ? "Category: {$category}\n"   : '')
                  . ($price    ? "Price: PKR {$price}\n"     : '')
                  . ($tags     ? "Tags/Features: {$tags}\n"  : '')
                  . ($features ? "Specs: {$features}\n"      : '')
                  . ($existing ? "Existing content: {$existing}\n" : '')
                  . "Current month and year: {$currentMonthYear}\n";

    $cacheKey = hash('sha256', "generate:{$target}:{$contextBlock}");
    if ($cached = getCache($conn, $cacheKey)) {
        echo json_encode(['success' => true, 'result' => json_decode($cached, true) ?? $cached, 'cached' => true]);
        exit;
    }

    $systemPrompt = buildSystemPrompt();

    if ($target === 'all') {
        $userMessage = $contextBlock . "\n"
            . "You are the PhonesDukan Ranking Engine. Generate ALL fields for this product to maximize Google Pakistan rankings, CTR, and ecommerce conversions. Return ONLY valid JSON with these exact keys, no extra text, no markdown:\n"
            . '{"seo_title":"...","meta_description":"...","focus_keyword":"...","short_description":"...","tags":"..."}'
            . "\n\nCRITICAL LENGTH AND FORMAT RULES — count characters before finalizing each field:"
            . "\nseo_title: MUST be 50-60 characters total. MUST start with Brand + Model (e.g. 'Samsung S24 Ultra'). MUST include 'Price in Pakistan' and current month/year. End with comma then Phones Dukan. Never use pipe. No dashes. Example of correct length: 'Samsung S24 Ultra Price in Pakistan May 2026, Phones Dukan' = 58 chars. If over 60, trim model name not the price/date/brand part."
            . "\nmeta_description: MUST be 150-160 characters total. Start with Buy or Shop. Include product short name, strongest benefit OR key spec, a Pakistan trust phrase. End with 'Shop now at Phones Dukan.' If your draft is under 150 chars, extend the benefit phrase or add 'with official warranty'. Aim for exactly 155 chars."
            . "\nfocus_keyword: MUST be 4-6 words all lowercase. MUST include full brand name, model identifier, and 'price in pakistan'. Example: 'samsung galaxy s24 ultra price in pakistan'."
            . "\nshort_description: 2-3 short human sentences. Open with a benefit. Add key spec. End with a trust signal. No dashes, no bullet points, no buzzwords."
            . "\ntags: 8-10 comma-separated all-lowercase tags. Mix brand, model, category, specs, features, and buyer phrases like 'buy online pakistan'.";

        $result = callGroq($systemPrompt, $userMessage);
        if (!$result['success']) {
            $classified = $result['classified'] ?? classifyAiError($result['http_code'] ?? 0, $result['raw_error'] ?? $result['error'] ?? '', []);
            logAiError($conn, $classified, $result['raw_error'] ?? '', $result['http_code'] ?? 0,
                       'generate', 'all', $productId, mb_substr($userMessage, 0, 500), GROQ_MODEL, $result['latency'] ?? 0);
            logRequest($conn, 'generate', 'all', $contextBlock, '', GROQ_MODEL, 0, $result['latency'] ?? 0, 'error', $classified['user_message'], $productId);
            echo errorResponse($classified);
            exit;
        }

        // Try to parse JSON from response
        $text = $result['text'];
        preg_match('/\{.*\}/s', $text, $jsonMatch);
        $parsed = $jsonMatch ? json_decode($jsonMatch[0], true) : null;

        if (!$parsed) {
            $parsed = ['raw' => $text];
        }

        $outputStr = json_encode($parsed);
        setCache($conn, $cacheKey, 'generate', 'all', $outputStr);
        incrementCredits($conn, $result['tokens']);
        logRequest($conn, 'generate', 'all', $contextBlock, $outputStr, $result['model'], $result['tokens'], $result['latency'], 'success', '', $productId);
        echo json_encode(['success' => true, 'result' => $parsed, 'tokens' => $result['tokens']]);
        exit;
    }

    // Detect category for template-aware generation
    $detectedCat = detectCategory($category ?: $productName);

    // Single field generation — each instruction is a ranking engineering brief
    if ($target === 'description') {
        $htmlTemplate    = getCategoryDescriptionTemplate($detectedCat, $productName);
        $targetInstructions = "You are generating a FULL HTML product description for the PhonesDukan ecommerce store.\n\n"
            . "PRODUCT DATA:\n" . $contextBlock . "\n"
            . "DETECTED CATEGORY: {$detectedCat}\n\n"
            . "YOUR TASK:\n"
            . "Fill the HTML template below with real, specific, SEO-optimized content based on the product data.\n"
            . "Replace every [bracketed placeholder] with actual relevant content.\n"
            . "Do NOT copy the placeholder text. Do NOT leave any placeholder unfilled.\n"
            . "Write naturally, like a product expert who has used this product.\n"
            . "Each paragraph must add unique ranking value: entity coverage, semantic depth, user intent satisfaction.\n"
            . "For the key features list: use exactly 5-6 real features from the product data.\n"
            . "No em dashes, no en dashes, no pipes, no inline CSS, no div tags.\n"
            . "Return ONLY the completed HTML, nothing else.\n\n"
            . "HTML TEMPLATE TO FILL:\n\n"
            . $htmlTemplate;
        $userMessage = $targetInstructions;
    } elseif ($target === 'short_description') {
        $shortTemplate   = getCategoryShortTemplate($detectedCat, $productName);
        $targetInstructions = "You are generating an HTML short description (specs summary) for a PhonesDukan product page.\n\n"
            . "PRODUCT DATA:\n" . $contextBlock . "\n"
            . "DETECTED CATEGORY: {$detectedCat}\n\n"
            . "YOUR TASK:\n"
            . "Fill the HTML template below with REAL specs from the product data.\n"
            . "Replace every [Write specific value from product data] with the actual spec value.\n"
            . "If a spec is not known from the data, write a realistic typical value for this product category and model.\n"
            . "Never leave placeholder text. Never add extra HTML tags.\n"
            . "The output must look like a clean product spec card buyers can scan in 3 seconds.\n"
            . "Return ONLY the completed HTML, nothing else.\n\n"
            . "HTML TEMPLATE TO FILL:\n\n"
            . $shortTemplate;
        $userMessage = $targetInstructions;
    } else {
        $targetInstructions = match ($target) {
            'seo_title'
                => "Engineer a high-CTR SEO title for Google Pakistan. STRICT REQUIREMENT: the final title must be BETWEEN 50 AND 60 CHARACTERS TOTAL, count every single character including spaces. Use the current month and year from context. Format: '[Product name] Price in Pakistan [Month Year], Phones Dukan'. Put the product's strongest ranking signal first. Use comma before Phones Dukan, never a pipe, never a dash. If the title exceeds 60 chars, abbreviate the product name. If under 50 chars, add the key model spec. Return ONLY the title text.",

            'meta_description'
                => "Engineer a meta description that maximizes organic CTR on Google Pakistan SERPs. STRICT REQUIREMENT: the final meta description must be BETWEEN 150 AND 160 CHARACTERS TOTAL, count every character including spaces, aim for 155 characters. Open with Buy or Shop plus the product name. Include its strongest feature, a Pakistan trust signal, end with 'Shop now at Phones Dukan.' If under 150 chars add one more benefit phrase. No pipes, no dashes. Return ONLY the description.",

            'focus_keyword'
                => "Identify the single highest-converting focus keyword for this product for Pakistan buyers. It must match the primary search intent: someone ready to buy. Include 'price in pakistan' naturally. 3-6 words total. All lowercase. No pipes, no special characters. Return ONLY the keyword phrase.",

            'tags'
                => "Engineer 8-10 product tags that maximize product discovery across Google Shopping, Daraz search, and semantic search. Strategy: include brand name, model number, product category synonyms, key feature entities, spec-based terms, and high-intent buyer phrases like 'buy online pakistan' and 'official warranty'. All lowercase, comma-separated. Cover the full semantic field around this product. Return ONLY the comma-separated tags.",

            'alt_text'
                => "Write image alt text optimized for Google Image Search and accessibility. 8-12 words. Describe the visual content while naturally including the product name and its primary identifier. No keyword stuffing, no pipes, no dashes. Return ONLY the alt text.",

            default
                => "Engineer this content for maximum ranking potential on Google Pakistan. Improve semantic depth, search intent alignment, entity coverage, and CTR potential. Sound like a real human expert. No em dashes, no pipes. Return ONLY the content.",
        };
        $userMessage = $contextBlock . "\n" . $targetInstructions;
    }
    // Use higher token limit for HTML descriptions
    $tokens  = in_array($target, ['description', 'short_description']) ? 2000 : 1200;
    $result  = callGroq($systemPrompt, $userMessage, GROQ_MODEL, $tokens);

    if (!$result['success']) {
        $classified = $result['classified'] ?? classifyAiError($result['http_code'] ?? 0, $result['raw_error'] ?? $result['error'] ?? '', []);
        logAiError($conn, $classified, $result['raw_error'] ?? '', $result['http_code'] ?? 0,
                   'generate', $target, $productId, mb_substr($userMessage, 0, 500), GROQ_MODEL, $result['latency'] ?? 0);
        logRequest($conn, 'generate', $target, $contextBlock, '', GROQ_MODEL, 0, $result['latency'] ?? 0, 'error', $classified['user_message'], $productId);
        echo errorResponse($classified);
        exit;
    }

    $text = $result['text'];
    setCache($conn, $cacheKey, 'generate', $target, $text);
    incrementCredits($conn, $result['tokens']);
    logRequest($conn, 'generate', $target, $contextBlock, $text, $result['model'], $result['tokens'], $result['latency'], 'success', '', $productId);
    echo json_encode(['success' => true, 'result' => $text, 'tokens' => $result['tokens']]);
    exit;
}

// ── 6. SMART SUGGEST (live typing) ────────────────────────────────────────────
if ($action === 'smart_suggest') {
    $field   = safeStr($body['field'] ?? '', 100);
    $value   = safeStr($body['value'] ?? '', 500);
    $context = safeStr($body['context'] ?? '', 500);

    if (mb_strlen($value) < 5) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        exit;
    }

    $cacheKey = hash('sha256', "suggest:{$field}:{$value}");
    if ($cached = getCache($conn, $cacheKey)) {
        $sugg = json_decode($cached, true) ?? [];
        echo json_encode(['success' => true, 'suggestions' => $sugg, 'cached' => true]);
        exit;
    }

    $systemPrompt = buildSystemPrompt();
    $userMessage  = "Field type: {$field}\nCurrent content: {$value}\n"
                  . ($context ? "Product context: {$context}\n" : '')
                  . "Generate exactly 3 alternative versions of this content, each with a different ranking strategy:\n"
                  . "Version 1: optimized for highest CTR on Google Pakistan SERPs\n"
                  . "Version 2: optimized for semantic depth and topical authority\n"
                  . "Version 3: optimized for buyer conversion and ecommerce intent\n"
                  . "All versions must have NO em dashes, NO en dashes, NO pipe characters.\n"
                  . "Return ONLY a JSON array of 3 strings. Example: [\"Version 1\",\"Version 2\",\"Version 3\"]\n"
                  . "Return ONLY the JSON array, nothing else.";

    $result = callGroq($systemPrompt, $userMessage);
    if (!$result['success']) {
        $classified = $result['classified'] ?? classifyAiError($result['http_code'] ?? 0, $result['raw_error'] ?? $result['error'] ?? '', []);
        // Smart suggest failures are low-priority — don't log to error table, just return gracefully
        echo json_encode(['success' => false, 'error' => $classified['user_message'], 'suggestions' => [],
                          'error_type' => $classified['type'], 'retry' => $classified['retry']]);
        exit;
    }

    preg_match('/\[.*\]/s', $result['text'], $arrMatch);
    $suggestions = $arrMatch ? json_decode($arrMatch[0], true) : [];
    if (!is_array($suggestions)) $suggestions = [];

    $outputStr = json_encode($suggestions);
    setCache($conn, $cacheKey, 'smart_suggest', $field, $outputStr);
    incrementCredits($conn, $result['tokens']);
    logRequest($conn, 'smart_suggest', $field, $value, $outputStr, $result['model'], $result['tokens'], $result['latency'], 'success', '', $productId);

    echo json_encode(['success' => true, 'suggestions' => $suggestions]);
    exit;
}

// ── Unknown action ────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['success' => false, 'error' => "Unknown action: {$action}"]);
