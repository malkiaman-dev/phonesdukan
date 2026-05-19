<?php
require_once __DIR__ . '/../Models/TrackingModel.php';
require_once __DIR__ . '/../../database/db.php'; // Ensure correct database connection
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
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
            die("Database connection error.");
        }
    }


    public function createOrder($orderData) {
        try {
            if (!isset($orderData['payment_method']) || empty($orderData['payment_method'])) {
                $orderData['payment_method'] = 'COD';
            }

            $query = "INSERT INTO orders (customer_name, customer_email, customer_phone, total_price, order_status, payment_method)
                      VALUES (:customer_name, :customer_email, :customer_phone, :total_price, :order_status, :payment_method)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':customer_name', $orderData['customer_name']);
            $stmt->bindParam(':customer_email', $orderData['customer_email']);
            $stmt->bindParam(':customer_phone', $orderData['customer_phone']);
            $stmt->bindParam(':total_price', $orderData['total_price']);
            $stmt->bindParam(':order_status', $orderData['order_status']);
            $stmt->bindParam(':payment_method', $orderData['payment_method'], PDO::PARAM_STR);

            if ($stmt->execute()) {
                return ['success' => true, 'order_id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Order creation failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function sendOrderConfirmationEmail($user_email, $customer_name, $order_id, $total_price) {
        // Check if email already sent
        $stmt = $this->db->prepare("SELECT confirmation_email_sent FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($order && $order['confirmation_email_sent']) {
            return false;
        }
    
        // Admin email
        $admin_email = 'admin@phonesdukan.com';
    
        $formattedTotal = 'Rs. ' . number_format((float)$total_price, 2);
        $orderDate      = date('F j, Y');

        // ── User email body ───────────────────────────────────────────────────
        $user_body  = "<tr><td style='background:#f7d117;padding:24px 40px;text-align:center;'>";
        $user_body .= "<p style='margin:0 0 4px 0;font-size:11px;font-weight:700;color:#111111;letter-spacing:3px;text-transform:uppercase;font-family:Arial,sans-serif;'>Order Confirmed</p>";
        $user_body .= "<h2 style='margin:0;font-size:26px;color:#111111;font-family:Arial,sans-serif;'>Thank You, $customer_name!</h2>";
        $user_body .= "</td></tr>";

        $user_body .= "<tr><td style='padding:32px 40px;'>";
        $user_body .= "<p style='margin:0 0 24px 0;font-size:15px;color:#555555;line-height:1.7;font-family:Arial,sans-serif;'>Your order has been received and is now being processed. We will notify you once your order is dispatched.</p>";

        // Order summary box
        $user_body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #e8e8e8;border-radius:8px;overflow:hidden;margin-bottom:28px;'>";
        $user_body .= "<tr><td style='background:#111111;padding:14px 24px;'><span style='font-size:12px;font-weight:700;color:#f7d117;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;'>Order Summary</span></td></tr>";
        $user_body .= "<tr><td style='padding:0 24px;'>";
        $user_body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>";
        $user_body .= "<tr><td style='padding:14px 0;border-bottom:1px solid #f0f0f0;font-size:13px;color:#888888;font-family:Arial,sans-serif;'>Order ID</td><td style='padding:14px 0;border-bottom:1px solid #f0f0f0;font-size:14px;font-weight:700;color:#111111;font-family:Arial,sans-serif;text-align:right;'>#$order_id</td></tr>";
        $user_body .= "<tr><td style='padding:14px 0;border-bottom:1px solid #f0f0f0;font-size:13px;color:#888888;font-family:Arial,sans-serif;'>Date</td><td style='padding:14px 0;border-bottom:1px solid #f0f0f0;font-size:14px;color:#111111;font-family:Arial,sans-serif;text-align:right;'>$orderDate</td></tr>";
        $user_body .= "<tr><td style='padding:14px 0;border-bottom:1px solid #f0f0f0;font-size:13px;color:#888888;font-family:Arial,sans-serif;'>Status</td><td style='padding:14px 0;border-bottom:1px solid #f0f0f0;text-align:right;'><span style='background:#f7d117;color:#111111;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;font-family:Arial,sans-serif;'>Processing</span></td></tr>";
        $user_body .= "<tr><td style='padding:16px 0;font-size:15px;font-weight:700;color:#111111;font-family:Arial,sans-serif;'>Total Amount</td><td style='padding:16px 0;font-size:20px;font-weight:700;color:#111111;font-family:Arial,sans-serif;text-align:right;'>$formattedTotal</td></tr>";
        $user_body .= "</table></td></tr></table>";

        // Track order button
        $user_body .= "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:0 auto 28px;'>";
        $user_body .= "<tr><td style='background:#111111;border-radius:6px;border:2px solid #f7d117;'>";
        $user_body .= "<a href='https://phonesdukan.com/track-order/' style='display:inline-block;padding:14px 36px;font-size:15px;font-weight:700;color:#f7d117;text-decoration:none;font-family:Arial,sans-serif;letter-spacing:0.5px;'>Track My Order &rarr;</a>";
        $user_body .= "</td></tr></table>";

        // Help box
        $user_body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>";
        $user_body .= "<tr><td style='background:#f8f8f8;border-radius:8px;padding:20px 24px;'>";
        $user_body .= "<p style='margin:0 0 6px 0;font-size:14px;font-weight:700;color:#111111;font-family:Arial,sans-serif;'>Need Help?</p>";
        $user_body .= "<p style='margin:0;font-size:13px;color:#777777;font-family:Arial,sans-serif;line-height:1.7;'>WhatsApp: <a href='https://wa.me/+923116600031' style='color:#f7d117;font-weight:600;text-decoration:none;'>+92 311 6600031</a><br>Email: <a href='mailto:info@phonesdukan.com' style='color:#f7d117;text-decoration:none;'>info@phonesdukan.com</a></p>";
        $user_body .= "</td></tr></table>";
        $user_body .= "</td></tr>";

        // ── Admin email body (simple internal) ────────────────────────────────
        $admin_body  = "<tr><td style='padding:32px 40px;'>";
        $admin_body .= "<h2 style='margin:0 0 20px 0;font-size:20px;color:#111111;font-family:Arial,sans-serif;'>New Order Received</h2>";
        $admin_body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #e8e8e8;border-radius:8px;overflow:hidden;'>";
        $admin_body .= "<tr><td style='background:#111111;padding:12px 20px;'><span style='font-size:12px;font-weight:700;color:#f7d117;letter-spacing:1px;text-transform:uppercase;font-family:Arial,sans-serif;'>Order Details</span></td></tr>";
        $admin_body .= "<tr><td style='padding:0 20px;'><table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>";
        $admin_body .= "<tr><td style='padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:13px;color:#888;font-family:Arial,sans-serif;'>Order ID</td><td style='padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:14px;font-weight:700;color:#111;font-family:Arial,sans-serif;text-align:right;'>#$order_id</td></tr>";
        $admin_body .= "<tr><td style='padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:13px;color:#888;font-family:Arial,sans-serif;'>Customer</td><td style='padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:14px;color:#111;font-family:Arial,sans-serif;text-align:right;'>$customer_name</td></tr>";
        $admin_body .= "<tr><td style='padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:13px;color:#888;font-family:Arial,sans-serif;'>Email</td><td style='padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:14px;color:#111;font-family:Arial,sans-serif;text-align:right;'>$user_email</td></tr>";
        $admin_body .= "<tr><td style='padding:14px 0;font-size:14px;font-weight:700;color:#111;font-family:Arial,sans-serif;'>Total</td><td style='padding:14px 0;font-size:18px;font-weight:700;color:#111;font-family:Arial,sans-serif;text-align:right;'>$formattedTotal</td></tr>";
        $admin_body .= "</table></td></tr></table>";
        $admin_body .= "</td></tr>";

        try {
            $mail = createMailer();
            $mail->addAddress($user_email);
            $mail->Subject = "Order Confirmation - Order #$order_id";
            $mail->Body    = emailShell($user_body);
            $mail->AltBody = "Hi $customer_name, your Phones Dukan order #$order_id has been confirmed.\nTotal: $formattedTotal\nTrack your order at: https://phonesdukan.com/track-order/";

            if (!$mail->send()) {
                throw new Exception("Error sending email to user: " . $mail->ErrorInfo);
            }

            // Send to admin
            $mail->clearAddresses();
            $mail->addAddress($admin_email);
            $mail->Subject = "New Order #$order_id - $customer_name";
            $mail->Body    = emailShell($admin_body);
            $mail->AltBody = "New order received. Order #$order_id | Customer: $customer_name | Total: $formattedTotal";

            if (!$mail->send()) {
                throw new Exception("Error sending email to admin: " . $mail->ErrorInfo);
            }

            $updateStmt = $this->db->prepare("UPDATE orders SET confirmation_email_sent = 1 WHERE order_id = ?");
            $updateStmt->execute([$order_id]);
            return true;

        } catch (Exception $e) {
            error_log('Order email send failed (Order #' . $order_id . '): ' . $e->getMessage());
            return false;
        }
    }
    
    
    public function addOrderItem($orderId, $item) {
        try {
            if (!isset($item['product_id'], $item['quantity'], $item['subtotal_price'])) {
                return false;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, subtotal_price)
                 VALUES (:order_id, :product_id, :quantity, :subtotal_price)"
            );
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':subtotal_price', $item['subtotal_price'], PDO::PARAM_STR);
            return $stmt->execute();

        } catch (Exception $e) {
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
        $completionDate = date('F j, Y');

        $body  = "<tr><td style='background:#111111;padding:32px 40px;text-align:center;border-bottom:4px solid #f7d117;'>";
        $body .= "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:0 auto 16px;'>";
        $body .= "<tr><td style='width:64px;height:64px;background:#f7d117;border-radius:50%;text-align:center;vertical-align:middle;line-height:64px;font-size:32px;font-weight:700;color:#111111;font-family:Arial,sans-serif;'>&#10003;</td></tr></table>";
        $body .= "<h2 style='margin:0 0 6px 0;font-size:26px;color:#ffffff;font-family:Arial,sans-serif;'>Order Delivered!</h2>";
        $body .= "<p style='margin:0;font-size:13px;color:#888888;font-family:Arial,sans-serif;'>Order #$order_id &bull; $completionDate</p>";
        $body .= "</td></tr>";

        $body .= "<tr><td style='padding:32px 40px;'>";
        $body .= "<p style='margin:0 0 24px 0;font-size:15px;color:#555555;line-height:1.7;font-family:Arial,sans-serif;'>";
        $body .= "Dear <strong style='color:#111111;'>$customer_name</strong>,<br><br>Your order <strong style='color:#111111;'>#$order_id</strong> has been marked as <strong style='color:#111111;'>Completed</strong>. We hope you are enjoying your new device!";
        $body .= "</p>";

        // Review request box
        $body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom:28px;'>";
        $body .= "<tr><td style='background:#fffbf0;border:1px solid #f7d117;border-radius:8px;padding:28px 24px;text-align:center;'>";
        $body .= "<p style='margin:0 0 6px 0;font-size:16px;font-weight:700;color:#111111;font-family:Arial,sans-serif;'>How was your experience?</p>";
        $body .= "<p style='margin:0 0 20px 0;font-size:13px;color:#777777;font-family:Arial,sans-serif;'>Your feedback helps us serve you better.</p>";
        $body .= "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:0 auto;'>";
        $body .= "<tr><td style='background:#f7d117;border-radius:6px;'>";
        $body .= "<a href='https://phonesdukan.com/submit-review' style='display:inline-block;padding:12px 28px;font-size:14px;font-weight:700;color:#111111;text-decoration:none;font-family:Arial,sans-serif;'>Leave a Review</a>";
        $body .= "</td></tr></table>";
        $body .= "</td></tr></table>";

        // Shop again button
        $body .= "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:0 auto;'>";
        $body .= "<tr><td style='background:#111111;border-radius:6px;'>";
        $body .= "<a href='https://phonesdukan.com' style='display:inline-block;padding:12px 32px;font-size:14px;font-weight:700;color:#f7d117;text-decoration:none;font-family:Arial,sans-serif;'>Shop Again &rarr;</a>";
        $body .= "</td></tr></table>";
        $body .= "</td></tr>";

        try {
            $mail = createMailer();
            $mail->addAddress($user_email);
            $mail->Subject = "Your Order #$order_id is Complete - Phones Dukan";
            $mail->Body    = emailShell($body);
            $mail->AltBody = "Hi $customer_name, your Phones Dukan order #$order_id has been completed.\n\nWe'd love your feedback: https://phonesdukan.com/submit-review\n\nThank you for shopping with us!";
            $mail->send();
            error_log("Order completion email sent to $user_email for Order ID: $order_id");
        } catch (Exception $e) {
            error_log("Failed to send completion email: " . $e->getMessage());
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
