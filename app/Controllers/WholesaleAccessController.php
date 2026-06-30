<?php
require_once dirname(__DIR__, 2) . '/app/config/session.php';
require_once dirname(__DIR__, 2) . '/app/config/wholesale_config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        throw new Exception('This endpoint is for AJAX POST requests only.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON input.');
    }

    $code = trim((string) ($input['code'] ?? ''));
    if ($code === '') {
        throw new Exception('Shopkeeper code is required.');
    }

    if (!wholesaleVerifyCode($code)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid shopkeeper code. This section is for registered shopkeepers only.',
        ]);
        exit;
    }

    wholesaleGrantAccess();

    echo json_encode([
        'status' => 'success',
        'message' => 'Access granted.',
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
