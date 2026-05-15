<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/app/Models/BulkInquiryModel.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Only POST is allowed.');
    }

    // Ensure it's an AJAX request
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        throw new Exception('This endpoint is for AJAX POST requests only.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        throw new Exception('Invalid JSON input.');
    }

    // Sanitize inputs
    $sanitizeString = function($value) {
        return is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
    };
    $input['name'] = $sanitizeString($input['name'] ?? '');
    $input['email'] = $sanitizeString($input['email'] ?? '');
    $input['phone'] = $sanitizeString($input['phone'] ?? '');
    $input['tel_no'] = $sanitizeString($input['tel_no'] ?? '');
    $input['business_name'] = $sanitizeString($input['business_name'] ?? '');
    $input['message'] = $sanitizeString($input['message'] ?? '');
    $input['address'] = $sanitizeString($input['address'] ?? '');

    $requiredFields = ['name', 'email', 'phone', 'business_name', 'address', 'token', 'items', 'total_price'];
    $errors = [];

    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || (is_string($input[$field]) && empty(trim($input[$field])))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    // Validate phone: exactly 11 digits
    if (!preg_match('/^\d{11}$/', $input['phone'])) {
        $errors[] = 'Phone must be exactly 11 digits.';
    }

    // Validate tel_no: up to 12 digits, optional
    if (isset($input['tel_no']) && $input['tel_no'] !== '' && !preg_match('/^\d{0,12}$/', $input['tel_no'])) {
        $errors[] = 'Tel No must contain only digits (up to 12).';
    }

    // Validate items and recalculate total price server-side
    $items = $input['items'] ?? [];
    if (!is_array($items) || empty($items)) {
        $errors[] = 'At least one product item is required.';
    } else {
        $model = new BulkInquiryModel();
        $calculatedTotal = 0.0;

        foreach ($items as &$item) {
            if (!isset($item['product_id']) || !is_numeric($item['product_id']) || $item['product_id'] <= 0) {
                $errors[] = 'Invalid product ID in items.';
                break;
            }
            if (!isset($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] < 5) {
                $errors[] = 'Product quantity must be at least 5 in items.';
                break;
            }

            // Sanitize item fields
            $item['product_id'] = (int) $item['product_id'];
            $item['quantity'] = (int) $item['quantity'];

            // Fetch product details
            $product = $model->getProductById($item['product_id']);
            if (!$product || !is_numeric($product['b2b_regular_price']) || $product['b2b_regular_price'] <= 0 || $product['stock_quantity'] < $item['quantity']) {
                $errors[] = 'Invalid product price or insufficient stock for product ID ' . $item['product_id'];
                break;
            }

            $calculatedTotal += (float) $product['b2b_regular_price'] * $item['quantity'];
        }

        // Validate submitted total_price against calculated total
        $submittedTotal = (float) ($input['total_price'] ?? 0);
        if (abs($calculatedTotal - $submittedTotal) > 0.01) {
            $errors[] = 'Invalid total price. Calculated: ' . $calculatedTotal . ', Submitted: ' . $submittedTotal;
        } else {
            $input['total_price'] = $calculatedTotal; // Use calculated value
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode(' ', $errors));
    }

    // Check for duplicate token in session
    session_start();
    if (!isset($_SESSION['bulk_inquiry_tokens'])) {
        $_SESSION['bulk_inquiry_tokens'] = [];
    }
    if (in_array($input['token'], $_SESSION['bulk_inquiry_tokens'])) {
        throw new Exception('Duplicate submission detected.');
    }
    $_SESSION['bulk_inquiry_tokens'][] = $input['token'];

    // Limit session tokens to last 100 to prevent memory issues
    if (count($_SESSION['bulk_inquiry_tokens']) > 100) {
        $_SESSION['bulk_inquiry_tokens'] = array_slice($_SESSION['bulk_inquiry_tokens'], -100);
    }

    $inquiryId = $model->saveInquiry($input);
    if ($inquiryId) {
        $itemsSaved = $model->saveInquiryItems($inquiryId, $items);
        if ($itemsSaved) {
            echo json_encode(['status' => 'success', 'message' => 'Inquiry submitted successfully.']);
        } else {
            throw new Exception('Failed to save inquiry items to database.');
        }
    } else {
        throw new Exception('Failed to save inquiry to database.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>