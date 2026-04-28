<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct path to db.php

class TrackingModel {
    private $db;

    // Constructor accepts a database connection instance
    public function __construct($db) {
        $this->db = $db;
    }

    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'UNKNOWN';
    }
    
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    }

    public static function getDeviceType() {
        return (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false) ? 'Mobile' : 'Desktop';
    }

    public static function getReferralURL() {
        return $_SERVER['HTTP_REFERER'] ?? 'Direct';
    }

    public static function getLandingPage() {
        return $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
    }

    public static function getTrafficSource() {
        return isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct';
    }

    public static function getBrowserOS() {
        return php_uname();
    }

    public function storeTrackingData($orderId, $trackingData) {
        error_log("DEBUG: storeTrackingData() called for Order ID: " . $orderId);
        error_log("DEBUG: Tracking Data Before Insert - " . json_encode($trackingData));
    
        // Ensure 'customer_ip_address' is always present
        if (empty($trackingData['customer_ip_address'])) {
            $trackingData['customer_ip_address'] = self::getClientIP();
            error_log("WARNING: 'customer_ip_address' was missing. Assigned value: " . $trackingData['customer_ip_address']);
        }
    
        // SQL Query
        $query = "INSERT INTO order_tracking 
                    (order_id, customer_ip, user_agent, device_type, referral_url, landing_page, traffic_source, browser_os) 
                  VALUES 
                    (:order_id, :customer_ip, :user_agent, :device_type, :referral_url, :landing_page, :traffic_source, :browser_os)";
    
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->bindParam(':customer_ip', $trackingData['customer_ip_address'], PDO::PARAM_STR);
            $stmt->bindParam(':user_agent', $trackingData['user_agent'], PDO::PARAM_STR);
            $stmt->bindParam(':device_type', $trackingData['device_type'], PDO::PARAM_STR);
            $stmt->bindParam(':referral_url', $trackingData['referral_url'], PDO::PARAM_STR);
            $stmt->bindParam(':landing_page', $trackingData['landing_page'], PDO::PARAM_STR);
            $stmt->bindParam(':traffic_source', $trackingData['traffic_source'], PDO::PARAM_STR);
            $stmt->bindParam(':browser_os', $trackingData['browser_os'], PDO::PARAM_STR);
            
            $success = $stmt->execute();
    
            if ($success) {
                error_log("✅ SUCCESS: Tracking data stored for Order ID: " . $orderId);
            } else {
                error_log("❌ ERROR: Failed to store tracking data for Order ID: " . $orderId);
                error_log("❌ ERROR: SQL Error - " . json_encode($stmt->errorInfo()));
            }
    
            return $success;
        } catch (PDOException $e) {
            error_log("❌ ERROR: Exception during tracking data insert - " . $e->getMessage());
            return false;
        }
    }
    
}