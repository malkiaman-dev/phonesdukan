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
        $hasVarCols = $this->columnExists('cart', 'variation_id');

        // Build the duplicate-check SELECT
        $varClause = '';
        if ($hasVarCols) {
            $varClause = $variation_id
                ? " AND (variation_id = :variation_id OR variation_id IS NULL)"
                : " AND variation_id IS NULL";
        }
        $selectSql = "SELECT cart_id, quantity FROM cart
                      WHERE product_id = :product_id
                        AND session_id  = :session_id
                      $varClause";

        $stmt = $this->db->prepare($selectSql);
        $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        if ($hasVarCols && $variation_id) {
            $stmt->bindValue(':variation_id', $variation_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            // Row exists — update quantity / price
            if ($hasVarCols) {
                $updateSql = "UPDATE cart SET quantity=:qty, unit_price=:price,
                              attribute_value=:attr, payment_method=:pm,
                              variation_id=:vid, variation_attributes=:va
                              WHERE cart_id=:cid";
                $upd = $this->db->prepare($updateSql);
                $upd->bindValue(':vid', $variation_id, PDO::PARAM_INT);
                $upd->bindValue(':va',  $variation_attributes, PDO::PARAM_STR);
            } else {
                $updateSql = "UPDATE cart SET quantity=:qty, unit_price=:price,
                              attribute_value=:attr, payment_method=:pm
                              WHERE cart_id=:cid";
                $upd = $this->db->prepare($updateSql);
            }
            $upd->bindValue(':qty',  $quantity, PDO::PARAM_INT);
            $upd->bindValue(':price', $unit_price, PDO::PARAM_STR);
            $upd->bindValue(':attr', $attribute_value, PDO::PARAM_STR);
            $upd->bindValue(':pm',   $payment_method, PDO::PARAM_STR);
            $upd->bindValue(':cid',  $cartItem['cart_id'], PDO::PARAM_INT);
            return $upd->execute();
        } else {
            // New row
            if ($hasVarCols) {
                $insertSql = "INSERT INTO cart
                              (product_id, quantity, user_id, session_id, unit_price,
                               attribute_value, payment_method, variation_id, variation_attributes)
                              VALUES (:pid,:qty,:uid,:sid,:price,:attr,:pm,:vid,:va)";
                $ins = $this->db->prepare($insertSql);
                $ins->bindValue(':vid', $variation_id, PDO::PARAM_INT);
                $ins->bindValue(':va',  $variation_attributes, PDO::PARAM_STR);
            } else {
                $insertSql = "INSERT INTO cart
                              (product_id, quantity, user_id, session_id, unit_price,
                               attribute_value, payment_method)
                              VALUES (:pid,:qty,:uid,:sid,:price,:attr,:pm)";
                $ins = $this->db->prepare($insertSql);
            }
            $ins->bindValue(':pid',   $product_id, PDO::PARAM_INT);
            $ins->bindValue(':qty',   $quantity, PDO::PARAM_INT);
            $ins->bindValue(':uid',   $userId, PDO::PARAM_INT);
            $ins->bindValue(':sid',   $sessionId, PDO::PARAM_STR);
            $ins->bindValue(':price', $unit_price, PDO::PARAM_STR);
            $ins->bindValue(':attr',  $attribute_value, PDO::PARAM_STR);
            $ins->bindValue(':pm',    $payment_method, PDO::PARAM_STR);
            return $ins->execute();
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
        $sql = "UPDATE cart SET user_id = :user_id WHERE session_id = :session_id AND (user_id IS NULL OR user_id = 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        return $stmt->execute();
    }
    
    public function fetchCartItems($session_id, $user_id = null) {
        $uid = (is_numeric($user_id) && (int)$user_id > 0) ? (int)$user_id : 0;

        // Check optional variation support
        $hasVariationCols  = $this->columnExists('cart', 'variation_id');
        $hasVariationTable = $this->columnExists('product_variations', 'id');
        $useVariations     = $hasVariationCols && $hasVariationTable;

        // ── WHERE clause ──────────────────────────────────────────────────
        // Guests  : match only by session_id — works regardless of user_id column type/value
        // LoggedIn: match by user_id OR session_id (picks up any unmerged guest rows too)
        // No null parameter binding — avoids all PDO/MySQL null-comparison edge cases.
        if ($uid > 0) {
            $where = "c.user_id = :user_id OR c.session_id = :session_id";
        } else {
            $where = "c.session_id = :session_id";
        }

        if ($useVariations) {
            $sql = "SELECT
                        c.cart_id,
                        c.product_id,
                        p.product_name,
                        c.unit_price,
                        c.quantity          AS total_quantity,
                        c.unit_price * c.quantity AS subtotal,
                        c.unit_price * c.quantity AS final_price,
                        COALESCE(pv.image, pi.image_url) AS image_url,
                        c.attribute_value,
                        c.payment_method,
                        c.variation_id,
                        c.variation_attributes
                    FROM cart c
                    JOIN products p  ON c.product_id = p.product_id
                    LEFT JOIN product_images pi
                        ON p.product_id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN product_variations pv ON c.variation_id = pv.id
                    WHERE $where";
        } else {
            $sql = "SELECT
                        c.cart_id,
                        c.product_id,
                        p.product_name,
                        c.unit_price,
                        c.quantity          AS total_quantity,
                        c.unit_price * c.quantity AS subtotal,
                        c.unit_price * c.quantity AS final_price,
                        pi.image_url,
                        c.attribute_value,
                        c.payment_method,
                        NULL AS variation_id,
                        NULL AS variation_attributes
                    FROM cart c
                    JOIN products p ON c.product_id = p.product_id
                    LEFT JOIN product_images pi
                        ON p.product_id = pi.product_id AND pi.is_primary = 1
                    WHERE $where";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':session_id', $session_id, PDO::PARAM_STR);
            if ($uid > 0) {
                $stmt->bindValue(':user_id', $uid, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('CartModel fetchCartItems error: ' . $e->getMessage());
            return [];
        }
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
        $checkSql = "SELECT * FROM cart WHERE product_id = :product_id AND (session_id = :session_id OR (user_id = :user_id AND user_id > 0))";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $checkStmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $checkStmt->bindValue(':user_id', $userId ?? 0, PDO::PARAM_INT);
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

            $updateSql .= " WHERE product_id = :product_id AND (session_id = :session_id OR (user_id = :user_id AND user_id > 0))";
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
        $uid = $userId ?? 0;
        $checkSql = "SELECT cart_id FROM cart WHERE product_id = :product_id AND (session_id = :session_id OR (user_id = :user_id AND user_id > 0))";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $checkStmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $checkStmt->bindValue(':user_id', $uid, PDO::PARAM_INT);
        $checkStmt->execute();

        $cartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$cartItem) {
            return false;
        }

        $sql = "DELETE FROM cart WHERE cart_id = :cart_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cart_id', $cartItem['cart_id'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function clearCart($userId = null, $sessionId = null) {
        $uid = $userId ?? 0;
        if ($uid > 0) {
            $sql = "DELETE FROM cart WHERE user_id = :user_id OR session_id = :session_id";
        } else {
            $sql = "DELETE FROM cart WHERE session_id = :session_id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        if ($uid > 0) {
            $stmt->bindValue(':user_id', $uid, PDO::PARAM_INT);
        }
        return $stmt->execute();
    }
}
?>