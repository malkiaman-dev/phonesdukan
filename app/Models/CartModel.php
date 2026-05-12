<?php
require_once __DIR__ . '/../../database/db.php';

class CartModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getProduct($product_id) {
        $query = "SELECT product_id, product_name, stock_quantity, 
                         IFNULL(sale_price, regular_price) AS price 
                  FROM products WHERE product_id = :product_id";
    
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function addToCart($product_id, $quantity, $userId, $sessionId, $unit_price, $attribute_value = null, $payment_method = 'cod', $variation_id = null, $variation_attributes = null) {
        // For variable products each variation_id is a separate cart line
        $varIdClause = $variation_id ? "AND (variation_id = :variation_id OR variation_id IS NULL)" : "AND variation_id IS NULL";
        $query = "SELECT cart_id, quantity FROM cart WHERE product_id = :product_id AND (user_id = :user_id OR session_id = :session_id) $varIdClause";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
        if ($variation_id) $stmt->bindParam(':variation_id', $variation_id, PDO::PARAM_INT);
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            $updateQuery = "UPDATE cart SET quantity = :quantity, unit_price = :unit_price, attribute_value = :attribute_value, payment_method = :payment_method, variation_id = :variation_id, variation_attributes = :variation_attributes WHERE cart_id = :cart_id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $updateStmt->bindParam(':unit_price', $unit_price, PDO::PARAM_STR);
            $updateStmt->bindParam(':attribute_value', $attribute_value, PDO::PARAM_STR);
            $updateStmt->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);
            $updateStmt->bindParam(':variation_id', $variation_id, PDO::PARAM_INT);
            $updateStmt->bindParam(':variation_attributes', $variation_attributes, PDO::PARAM_STR);
            $updateStmt->bindParam(':cart_id', $cartItem['cart_id'], PDO::PARAM_INT);
            return $updateStmt->execute();
        } else {
            $insertQuery = "INSERT INTO cart (product_id, quantity, user_id, session_id, unit_price, attribute_value, payment_method, variation_id, variation_attributes)
                            VALUES (:product_id, :quantity, :user_id, :session_id, :unit_price, :attribute_value, :payment_method, :variation_id, :variation_attributes)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
            $insertStmt->bindParam(':unit_price', $unit_price, PDO::PARAM_STR);
            $insertStmt->bindParam(':attribute_value', $attribute_value, PDO::PARAM_STR);
            $insertStmt->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);
            $insertStmt->bindParam(':variation_id', $variation_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':variation_attributes', $variation_attributes, PDO::PARAM_STR);
            return $insertStmt->execute();
        }
    }

    public function getProductAttributes($product_id) {
        $query = "
            SELECT 
                pa.attribute_name, 
                pav.value AS attribute_value,
                pap.regular_price, 
                pap.sale_price
            FROM 
                product_attributes pa
            JOIN 
                product_attribute_prices pap ON pa.attribute_id = pap.attribute_id
            JOIN 
                product_attribute_values pav ON pap.value_id = pav.value_id
            WHERE 
                pap.product_id = :product_id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function mergeGuestCartWithUser($sessionId, $userId) {
        $sql = "UPDATE cart SET user_id = :user_id WHERE session_id = :session_id AND user_id IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
        return $stmt->execute();
    }
    
    public function fetchCartItems($session_id, $user_id = null) {
        // Check variation columns exist AND product_variations has the expected 'id' column
        $hasVariationCols  = $this->columnExists('cart', 'variation_id');
        $hasVariationTable = $this->columnExists('product_variations', 'id'); // more specific than tableExists()

        if ($hasVariationCols && $hasVariationTable) {
            $sql = "SELECT
                        c.cart_id,
                        c.product_id,
                        p.product_name,
                        c.unit_price,
                        c.quantity AS total_quantity,
                        (c.unit_price * c.quantity) AS subtotal,
                        (c.unit_price * c.quantity) AS final_price,
                        COALESCE(pv.image, pi.image_url) AS image_url,
                        c.attribute_value,
                        c.payment_method,
                        c.variation_id,
                        c.variation_attributes
                    FROM cart c
                    JOIN products p ON c.product_id = p.product_id
                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN product_variations pv ON c.variation_id = pv.id
                    WHERE (c.user_id = :user_id AND c.user_id IS NOT NULL)
                       OR (c.session_id = :session_id AND c.user_id IS NULL)";
        } else {
            // Fallback query without variation columns (pre-migration)
            $sql = "SELECT
                        c.cart_id,
                        c.product_id,
                        p.product_name,
                        c.unit_price,
                        c.quantity AS total_quantity,
                        (c.unit_price * c.quantity) AS subtotal,
                        (c.unit_price * c.quantity) AS final_price,
                        pi.image_url,
                        c.attribute_value,
                        c.payment_method,
                        NULL AS variation_id,
                        NULL AS variation_attributes
                    FROM cart c
                    JOIN products p ON c.product_id = p.product_id
                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                    WHERE (c.user_id = :user_id AND c.user_id IS NOT NULL)
                       OR (c.session_id = :session_id AND c.user_id IS NULL)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':session_id', $session_id, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tableExists(string $table): bool {
        try {
            $r = $this->db->query("SELECT 1 FROM `$table` LIMIT 1");
            return $r !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool {
        try {
            $r = $this->db->query("SELECT `$column` FROM `$table` LIMIT 1");
            return $r !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    public function updateCartQuantity($sessionId, $userId, $productId, $newQuantity, $attributeValue, $payment_method = null) {
        $checkSql = "SELECT * FROM cart WHERE product_id = :product_id AND (session_id = :session_id OR user_id = :user_id)";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $checkStmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
        $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $checkStmt->execute();

        $cartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            if ($newQuantity < 1) {
                return false;
            }

            $updateSql = "UPDATE cart SET quantity = :quantity, attribute_value = :attribute_value";
            $params = [
                ':quantity' => $newQuantity,
                ':attribute_value' => $attributeValue,
                ':product_id' => $productId,
                ':session_id' => $sessionId,
                ':user_id' => $userId
            ];

            if ($payment_method !== null) {
                $updateSql .= ", payment_method = :payment_method";
                $params[':payment_method'] = $payment_method;
            }

            $updateSql .= " WHERE product_id = :product_id AND (session_id = :session_id OR user_id = :user_id)";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute($params);
            return true;
        } else {
            if ($newQuantity < 1) {
                return false;
            }

            $insertSql = "INSERT INTO cart (product_id, quantity, session_id, user_id, attribute_value";
            $params = [
                ':product_id' => $productId,
                ':quantity' => $newQuantity,
                ':session_id' => $sessionId,
                ':user_id' => $userId,
                ':attribute_value' => $attributeValue
            ];

            if ($payment_method !== null) {
                $insertSql .= ", payment_method";
                $params[':payment_method'] = $payment_method;
            }

            $insertSql .= ") VALUES (:product_id, :quantity, :session_id, :user_id, :attribute_value";
            if ($payment_method !== null) {
                $insertSql .= ", :payment_method";
            }
            $insertSql .= ")";

            $insertStmt = $this->db->prepare($insertSql);
            return $insertStmt->execute($params);
        }
    }

    public function removeCartItem($sessionId, $userId, $productId) {
        $checkSql = "SELECT * FROM cart WHERE product_id = :product_id AND (session_id = :session_id OR user_id = :user_id)";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $checkStmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
        $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $checkStmt->execute();
    
        $cartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$cartItem) {
            return false;
        }
    
        $sql = "DELETE FROM cart WHERE product_id = :product_id AND (session_id = :session_id OR user_id = :user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function clearCart($userId = null, $sessionId = null) {
        $sql = "DELETE FROM cart WHERE user_id = :user_id OR session_id = :session_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);

        return $stmt->execute();
    }
}
?>