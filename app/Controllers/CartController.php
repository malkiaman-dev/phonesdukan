<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear output buffer to prevent stray output
ob_start();
header('Content-Type: application/json');

// Ensure session file is included
$sessionFile = __DIR__ . '/../config/session.php';
if (file_exists($sessionFile)) {
    include_once $sessionFile;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Session file missing']);
    exit;
}

// Include required model
require_once __DIR__ . '/../Models/CartModel.php';

class CartController {
    private $cartModel;

    public function __construct() {
        $this->cartModel = new CartModel();
    }

    public function addToCart() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse('error', 'Invalid request method');
        }
    
        $data = json_decode(file_get_contents("php://input"), true);
    
        $product_id       = isset($data['product_id']) ? (int)$data['product_id'] : 0;
        $quantity         = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        $attribute_value  = isset($data['attribute_value']) ? $data['attribute_value'] : null;
        $unit_price       = isset($data['unit_price']) ? (float)$data['unit_price'] : 0;
        $payment_method   = isset($data['payment_method']) ? $data['payment_method'] : 'cod';
        $variation_id     = isset($data['variation_id']) && $data['variation_id'] ? (int)$data['variation_id'] : null;
        $variation_attributes = isset($data['variation_attributes']) ? $data['variation_attributes'] : null;
    
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
    
        if ($product_id <= 0) {
            $this->sendResponse('error', 'Invalid product ID');
        }
    
        $product = $this->cartModel->getProduct($product_id);
        if (!$product) {
            $this->sendResponse('error', 'Product not found');
        }
    
        // For variable products, validate variation stock instead of base product stock
        if ($variation_id) {
            require_once __DIR__ . '/../Models/VariationModel.php';
            $varModel = new VariationModel();
            $variation = $varModel->getVariationById($variation_id);
            if (!$variation || $variation['product_id'] != $product_id) {
                $this->sendResponse('error', 'Invalid variation');
            }
            if ($variation['stock_quantity'] < $quantity) {
                $this->sendResponse('error', 'Not enough stock for this variation');
            }
            if (!$variation_attributes) {
                $variation_attributes = $varModel->buildAttributesLabel($variation_id);
            }
        } elseif (!isset($product['stock_quantity']) || $product['stock_quantity'] < $quantity) {
            $this->sendResponse('error', 'Not enough stock available');
        }

        $result = $this->cartModel->addToCart($product_id, $quantity, $userId, $sessionId, $unit_price, $attribute_value, $payment_method, $variation_id, $variation_attributes);
        if ($result) {
            $cartItems = $this->cartModel->fetchCartItems($sessionId, $userId);
            $totals = $this->calculateCartTotals($cartItems);
            $this->sendResponse('success', 'Item added to cart', [
                'cart_items' => $cartItems,
                'cart_summary' => $totals
            ]);
        } else {
            $this->sendResponse('error', 'Failed to add item');
        }
    }
    
    public function getCartItems() {
        session_start();
        $sessionId = $_SESSION['session_id'] ?? session_id();  
        $userId = $_SESSION['user_id'] ?? null;
    
        $cartItems = $this->cartModel->fetchCartItems($sessionId, $userId);
    
        if (empty($cartItems)) {
            $this->sendResponse('error', 'No cart items found', ['cart_summary' => ['subtotal' => 0, 'discount' => 0, 'total' => 0, 'discount_rate' => 0]]);
        } else {
            $totals = $this->calculateCartTotals($cartItems);
            $this->sendResponse('success', 'Cart items retrieved', [
                'cart_items' => $cartItems,
                'cart_summary' => $totals
            ]);
        }
    }
    
    public function removeCartItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse('error', 'Invalid request method');
        }
    
        $data = json_decode(file_get_contents("php://input"), true);
        error_log("RemoveCartItem Input: " . json_encode($data));
    
        $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    
        if ($productId <= 0) {
            $this->sendResponse('error', 'Invalid product ID');
        }
    
        $sessionId = $_SESSION['session_id'] ?? session_id();
        $userId = $_SESSION['user_id'] ?? null;
    
        $removed = $this->cartModel->removeCartItem($sessionId, $userId, $productId);
        if ($removed) {
            $cartItems = $this->cartModel->fetchCartItems($sessionId, $userId);
            $totals = $this->calculateCartTotals($cartItems);
            $response = ['status' => 'success', 'message' => 'Item removed from cart', 'cart_items' => $cartItems, 'cart_summary' => $totals];
            error_log("RemoveCartItem Response: " . json_encode($response));
            $this->sendResponse('success', 'Item removed from cart', [
                'cart_items' => $cartItems,
                'cart_summary' => $totals
            ]);
        } else {
            $this->sendResponse('error', 'Failed to remove item');
        }
    }
    
    public function updateCartQuantity() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse('error', 'Invalid request method');
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
        $newQuantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        $unitPrice = isset($data['unit_price']) ? (float)$data['unit_price'] : 0;
        $attributeValue = isset($data['attribute_value']) ? $data['attribute_value'] : null;
        $paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : null;

        if ($productId <= 0 || $newQuantity < 1 || $unitPrice <= 0) {
            $this->sendResponse('error', 'Invalid input data');
        }

        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;

        $result = $this->cartModel->updateCartQuantity($sessionId, $userId, $productId, $newQuantity, $attributeValue, $paymentMethod);

        if ($result) {
            $cartItems = $this->cartModel->fetchCartItems($sessionId, $userId);
            $totals = $this->calculateCartTotals($cartItems);

            $itemIndex = array_search($productId, array_column($cartItems, 'product_id'));
            $updatedItem = $itemIndex !== false ? $cartItems[$itemIndex] : null;
            if ($updatedItem) {
                $updatedSubtotal = $unitPrice * $newQuantity;
                $updatedFinalPrice = $updatedSubtotal;
                $response = [
                    'status' => 'success',
                    'message' => 'Item updated in cart',
                    'updated_item' => [
                        'subtotal' => $updatedSubtotal,
                        'final_price' => $updatedFinalPrice,
                        'attribute_value' => $attributeValue,
                    ],
                    'cart_items' => $cartItems,
                    'cart_summary' => $totals
                ];
                echo json_encode($response);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Item not found after update']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update item']);
        }
    }

    private function calculateCartTotals($cartItems) {
        $totalQty = 0;
        $grandSubtotal = 0;

        foreach ($cartItems as $item) {
            $totalQty += (int)$item['total_quantity'];
            $grandSubtotal += (float)$item['subtotal'];
        }

        $discountRate = 0;
        if ($totalQty >= 10) {
            $discountRate = 0.07;
        } elseif ($totalQty >= 3) {
            $discountRate = 0.05;
        }

        $discountAmount = $grandSubtotal * $discountRate;
        $finalTotal = $grandSubtotal - $discountAmount;

        return [
            'total_quantity' => $totalQty,
            'subtotal' => number_format($grandSubtotal, 2, '.', ''),
            'discount_rate' => round($discountRate * 100),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'total' => number_format($finalTotal, 2, '.', '')
        ];
    }

    public function renderCartPage() {
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
    
        $cartItems = $this->cartModel->fetchCartItems($sessionId, $userId);
        $totals = $this->calculateCartTotals($cartItems);
    
        require_once __DIR__ . '/../Views/cart/cart.php';
    }
    
    private function sendResponse($status, $message, $data = []) {
        ob_end_clean(); // Clear buffer before sending
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
        exit;
    }
}

$cartController = new CartController();
$data = json_decode(file_get_contents("php://input"), true);

$action = $_GET['action'] ?? $data['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'removeCartItem') {
        $cartController->removeCartItem();
    } elseif ($action === 'updateCartQuantity') {
        $cartController->updateCartQuantity();
    } else {
        $cartController->addToCart();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($action === 'getCartItems' || isset($_GET['session_id']))) {
    $cartController->getCartItems();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}
?>