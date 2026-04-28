<?php
require_once __DIR__ . '/../Models/TrackingModel.php';
require_once __DIR__ . '/../../database/db.php'; // Ensure correct database connection
require_once dirname(__DIR__, 2) . '/app/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__, 2) . '/app/PHPMailer/SMTP.php';
require_once dirname(__DIR__, 2) . '/app/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class OrderModel {
    private $db;


    // Count pending orders
    public function getPendingOrdersCount()
    {
        $query = 'SELECT COUNT(*) as count FROM orders WHERE order_status = :status';
        $stmt = $this->db->prepare($query);
        $status = 'pending';
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            error_log("ERROR: Database connection failed.");
            die("Database connection error.");
        } else {
            error_log("DEBUG: Database connection established successfully.");
        }
    }


    public function createOrder($orderData) {
        try {
            error_log("DEBUG: Received Order Data: " . json_encode($orderData));
    
            // Ensure payment_method is set
            if (!isset($orderData['payment_method']) || empty($orderData['payment_method'])) {
                $orderData['payment_method'] = 'COD'; // Default value
            }
    
            error_log("DEBUG: Payment Method: " . $orderData['payment_method']);
    
            $query = "INSERT INTO orders (customer_name, customer_email, customer_phone, total_price, order_status, payment_method)
                      VALUES (:customer_name, :customer_email, :customer_phone, :total_price, :order_status, :payment_method)";
    
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':customer_name', $orderData['customer_name']);
            $stmt->bindParam(':customer_email', $orderData['customer_email']);
            $stmt->bindParam(':customer_phone', $orderData['customer_phone']);
            $stmt->bindParam(':total_price', $orderData['total_price']);
            $stmt->bindParam(':order_status', $orderData['order_status']);
            $stmt->bindParam(':payment_method', $orderData['payment_method'], PDO::PARAM_STR);
    
            error_log("DEBUG: Running Query...");
    
            if ($stmt->execute()) {
                $orderId = $this->db->lastInsertId();
                error_log("✅ Order Created! ID: " . $orderId);
                return ['success' => true, 'order_id' => $orderId];
            } else {
                error_log("❌ SQL Error: " . implode(", ", $stmt->errorInfo()));
                return ['success' => false, 'message' => 'Order creation failed'];
            }
        } catch (Exception $e) {
            error_log("❌ Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function sendOrderConfirmationEmail($user_email, $customer_name, $order_id, $total_price) {
        // Check if email already sent
        $stmt = $this->db->prepare("SELECT confirmation_email_sent FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($order && $order['confirmation_email_sent']) {
            error_log("Order confirmation email already sent for Order ID: $order_id. Skipping.");
            return false;
        }
    
        // Admin email
        $admin_email = 'admin@phonesdukan.com';
    
        // Create instance of PHPMailer
        $mail = new PHPMailer(true);
    
        try {
            // SMTP server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'info@phonesdukan.com';
            $mail->Password = '@Azmeryal@123##';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
    
            // Email debug
            $mail->SMTPDebug = 0;
    
            // User email setup
            $mail->setFrom('info@phonesdukan.com', 'Phones Dukan');
            $mail->addAddress($user_email);
            $mail->isHTML(true);
            $mail->Subject = "Order Confirmation - Order #$order_id";
    
            // Load external CSS file content
            $css = file_get_contents('/public/assets/css/frontend/email.css/'); // Make sure this path is correct
    
            // Creating a colorful, professional user email message
            $user_message = "
            <html>
            <head>
                <style>
                    $css
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <h2>Thank You for Your Order!</h2>
                    </div>
                    <div class='email-body'>
                        <p>Dear $customer_name,</p>
                        <p>Your order with <strong>Order ID: $order_id</strong> has been successfully placed.</p>
                        <p><strong>Total Price: $total_price</strong></p>
                        <p>We will notify you once your order has been processed. You can track your order status in your account.</p>
                        <a href='https://phonesdukan.com/track-order/' class='cta-button'>Track Your Order</a>
                    </div>
                    <div class='email-footer'>
                        <p>Thank you for shopping with Phones Dukan!</p>
                        <p>If you have any questions, feel free to contact our support team.</p>
                    </div>
                </div>
            </body>
            </html>";
    
            $mail->Body = $user_message;
    
            if (!$mail->send()) {
                throw new Exception("Error sending email to user: " . $mail->ErrorInfo);
            }
    
            // Send to admin
            $mail->clearAddresses();
            $mail->addAddress($admin_email);
    
            // Admin email content
            $admin_message = "
            <html>
            <body>
                <h2>New Order Received!</h2>
                <p><strong>Order ID:</strong> $order_id</p>
                <p><strong>Customer Name:</strong> $customer_name</p>
                <p><strong>Total Price:</strong> $total_price</p>
                <p>Please process the order accordingly.</p>
            </body>
            </html>";
            $mail->Body = $admin_message;
    
            if (!$mail->send()) {
                throw new Exception("Error sending email to admin: " . $mail->ErrorInfo);
            }
    
            // ✅ Update email sent status
            $updateStmt = $this->db->prepare("UPDATE orders SET confirmation_email_sent = 1 WHERE order_id = ?");
            $updateStmt->execute([$order_id]);
    
            error_log("Order confirmation emails sent and status updated for Order ID: $order_id");
    
            return true;
    
        } catch (Exception $e) {
            error_log("Error in sending email: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    
    public function addOrderItem($orderId, $item) {
        try {
            error_log("DEBUG: Entered addOrderItem() for Order ID: " . $orderId);
            error_log("DEBUG: Order Item Data: " . json_encode($item));

            // Validate required fields
            if (!isset($item['product_id'], $item['quantity'], $item['subtotal_price'])) {
                error_log("ERROR: Missing required item data. Item: " . json_encode($item));
                return false;
            }

            $query = "INSERT INTO order_items (order_id, product_id, quantity, subtotal_price) 
                      VALUES (:order_id, :product_id, :quantity, :subtotal_price)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':subtotal_price', $item['subtotal_price'], PDO::PARAM_STR);

            error_log("DEBUG: Executing addOrderItem() for Order ID: $orderId, Product ID: " . $item['product_id']);

            if (!$stmt->execute()) {
                error_log("ERROR: Order item insertion failed - Order ID: $orderId, Product ID: " . $item['product_id']);
                error_log("SQL Error: " . implode(", ", $stmt->errorInfo()));
                return false;
            } 

            error_log("DEBUG: Order item inserted successfully - Order ID: $orderId, Product ID: " . $item['product_id']);
            return true;

        } catch (Exception $e) {
            error_log("ERROR: Exception in addOrderItem - " . $e->getMessage());
            return false;
        }
    }



    // users
    public function getOrdersByUser($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT order_id, order_status, total_price, currency, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ Error Fetching Orders: " . $e->getMessage());
            return [];
        }
    }
    
    public function storeTrackingData($orderId) {
        $trackingModel = new TrackingModel($this->db);
        $trackingData = [
            'customer_ip'    => TrackingModel::getClientIP(),
            'user_agent'     => TrackingModel::getUserAgent(),
            'device_type'    => TrackingModel::getDeviceType(),
            'referral_url'   => TrackingModel::getReferralURL(),
            'landing_page'   => TrackingModel::getLandingPage(),
            'traffic_source' => TrackingModel::getTrafficSource(),
            'browser_os'     => TrackingModel::getBrowserOS()
        ];

        error_log("DEBUG: Tracking function called with Order ID: " . $orderId);
        error_log("DEBUG: Tracking Data: " . print_r($trackingData, true));

        $trackingModel->storeTrackingData($orderId, $trackingData);
    }
    

    public function updateOrderStatus($orderId, $newStatus) {
        try {
            // Get current order info
            $stmt = $this->db->prepare("SELECT customer_email, customer_name, total_price FROM orders WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$order) {
                error_log("❌ Order not found for ID: $orderId");
                return false;
            }
    
            // Update the order status
            $updateStmt = $this->db->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            if (!$updateStmt->execute([$newStatus, $orderId])) {
                error_log("❌ Failed to update order status for ID: $orderId");
                return false;
            }
    
            error_log("✅ Order status updated for ID: $orderId to $newStatus");
    
            // Send email only when status is completed
            if (strtolower($newStatus) === 'completed') {
                $emailResult = $this->sendOrderCompletionEmail(
                    $order['customer_email'],
                    $order['customer_name'],
                    $orderId
                );
    
                if (!$emailResult) {
                    error_log("❌ Failed to send completion email for order ID: $orderId");
                }
            }
    
            return true;
    
        } catch (Exception $e) {
            error_log("❌ Exception in updateOrderStatus: " . $e->getMessage());
            return false;
        }
    }
    

    private function sendOrderCompletionEmail($user_email, $customer_name, $order_id) {
        $mail = new PHPMailer(true);
    
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'info@phonesdukan.com';
            $mail->Password = '@Azmeryal@123##';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
    
            $mail->setFrom('info@phonesdukan.com', 'Phones Dukan');
            $mail->addAddress($user_email);
    
            $mail->isHTML(true);
            $mail->Subject = "Your Order #$order_id is Now Complete!";
            $mail->Body = "<html><body>
                            <h2>Order Completed</h2>
                            <p>Dear $customer_name,</p>
                            <p>Your order <strong>#$order_id</strong> has been marked as <strong>Complete</strong>.</p>
                            <p>Thank you for shopping with Phones Dukan!</p>
                           </body></html>";
    
            if (!$mail->send()) {
                throw new Exception("Error sending order completion email: " . $mail->ErrorInfo);
            }
    
            error_log("✅ Order completion email sent to $user_email for Order ID: $order_id");
    
        } catch (Exception $e) {
            error_log("❌ Failed to send completion email: " . $e->getMessage());
        }
    }

        // Updated: Include payment_screenshot in fetch
        public function getOrderByEmailAndId($email, $orderId)
        {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_email = ?");
            $stmt->execute([$orderId, $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    
}



?>
