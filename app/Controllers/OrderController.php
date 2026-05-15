<?php
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../Models/TrackingModel.php';
require_once __DIR__ . '/../Models/OrderModel.php';

class OrderController
{
    private $orderModel;
    private $db;  // Database connection
    private $trackingModel;

    public function __construct()
    {
        try {
            $database = new Database();
            $this->db = $database->getConnection();

            if (!$this->db) {
                throw new Exception('Database connection failed.');
            }

            $this->orderModel = new OrderModel($this->db);
            $this->trackingModel = new TrackingModel($this->db);  // Instantiate TrackingModel

        } catch (Exception $e) {
            error_log('OrderController init failed: ' . $e->getMessage());
            die('Database connection error: ' . $e->getMessage());
        }
    }

    public function trackOrder()
    {
        $order = null;
        $error = null;
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $orderId = $_POST['order_id'] ?? '';
    
            if (empty($email) || empty($orderId)) {
                $error = "Both email and order ID are required.";
            } else {
                require_once __DIR__ . '/../Models/OrderModel.php';
                $model = new OrderModel();
                $order = $model->getOrderByEmailAndId($email, $orderId);
    
                if (!$order) {
                    $error = "No order found with this email and order ID.";
                }
            }
        }
    
        // Make variables available to view
        extract([
            'order' => $order,
            'error' => $error
        ]);
    
        include __DIR__ . '/../Views/order-tracking/track_order.php';
    }
    

    public function createOrder($orderData, $cartItems, $screenshotData = null)
    {
        try {
            // Start transaction
            $this->db->beginTransaction();
    
            // Get all product names from cart items
            $orderedProductNames = array_map(function ($item) {
                return $item['product_name'];
            }, $cartItems);
            $orderedProductString = implode(', ', $orderedProductNames);
    
            // Check if the user is logged in
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $orderData['user_id'] = $_SESSION['user_id'];
            } else {
                $orderData['user_id'] = 0;
            }
    
            // New: Add screenshot to orderData if provided
            $screenshotPath = $screenshotData ? $screenshotData['path'] : null;
            $orderData['payment_screenshot'] = $screenshotPath;
    
            // ✅ Insert Order into Database (Added payment_screenshot)
            $query = 'INSERT INTO orders (customer_name, customer_email, customer_phone, total_price, order_status, payment_method, payment_method_title, shipping_address, shipping_city, shipping_country, customer_note, applied_coupons, ordered_product, user_id, payment_screenshot)
            VALUES (:customer_name, :customer_email, :customer_phone, :total_price, :order_status, :payment_method, :payment_method_title, :shipping_address, :shipping_city, :shipping_country, :customer_note, :applied_coupons, :ordered_product, :user_id, :payment_screenshot)';
    
            $stmt = $this->db->prepare($query);
    
            // Bind parameters (Added :payment_screenshot)
            $stmt->bindParam(':customer_name', $orderData['customer_name']);
            $stmt->bindParam(':customer_email', $orderData['customer_email']);
            $stmt->bindParam(':customer_phone', $orderData['customer_phone']);
            $stmt->bindParam(':total_price', $orderData['total_price']);
            $stmt->bindParam(':order_status', $orderData['order_status']);
            $stmt->bindParam(':payment_method', $orderData['payment_method']);
            $stmt->bindParam(':payment_method_title', $orderData['payment_method_title']);
            $stmt->bindParam(':shipping_address', $orderData['shipping_address']);
            $stmt->bindParam(':shipping_city', $orderData['shipping_city']);
            $stmt->bindParam(':shipping_country', $orderData['shipping_country']);
            $stmt->bindParam(':customer_note', $orderData['customer_note']);
            $stmt->bindParam(':applied_coupons', $orderData['applied_coupons']);
            $stmt->bindParam(':ordered_product', $orderedProductString);
            $stmt->bindParam(':user_id', $orderData['user_id']);
            $stmt->bindParam(':payment_screenshot', $orderData['payment_screenshot']);
    
            $executeResult = $stmt->execute();
    
            if (!$executeResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Database insert failed.', 'order_id' => null];
            }
    
            $orderId = $this->db->lastInsertId();

            foreach ($cartItems as $item) {

                $variation_id         = isset($item['variation_id']) && $item['variation_id'] ? (int)$item['variation_id'] : null;
                $variation_attributes = $item['variation_attributes'] ?? null;
                $variation_sku        = null;

                // Fetch variation SKU if we have a variation_id
                if ($variation_id) {
                    $vSkuStmt = $this->db->prepare("SELECT sku FROM product_variations WHERE id=?");
                    $vSkuStmt->execute([$variation_id]);
                    $vRow = $vSkuStmt->fetch(PDO::FETCH_ASSOC);
                    $variation_sku = $vRow ? $vRow['sku'] : null;
                }

                $stmt = $this->db->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, subtotal_price, variation_id, variation_sku, variation_attributes)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $result = $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['subtotal'],
                    $variation_id,
                    $variation_sku,
                    $variation_attributes,
                ]);

                if (!$result) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Failed to insert order items.', 'order_id' => null];
                }
            }
    
            // ✅ Extract tracking information using TrackingModel
            $trackingData = [
                'customer_ip_address' => TrackingModel::getClientIP(),
                'user_agent' => TrackingModel::getUserAgent(),
                'device_type' => TrackingModel::getDeviceType(),
                'referral_url' => TrackingModel::getReferralURL(),
                'landing_page' => TrackingModel::getLandingPage(),
                'traffic_source' => TrackingModel::getTrafficSource(),
                'browser_os' => TrackingModel::getBrowserOS()
            ];
    
            // Store tracking data including latitude & longitude
            $this->trackingModel->storeTrackingData($orderId, $trackingData);
    
            // ✅ Commit transaction
            $this->db->commit();
    
            // Call the email function after committing the transaction
            $this->orderModel->sendOrderConfirmationEmail($orderData['customer_email'], $orderData['customer_name'], $orderId, $orderData['total_price']);
    
            return ['success' => true, 'order_id' => $orderId];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('OrderController createOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'order_id' => null];
        }
    }
}    
// Instantiate OrderController
$orderController = new OrderController();

?>