<?php
require_once dirname(__DIR__, 2) . '/database/db.php';

class BulkInquiryModel
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getB2BProducts()
    {
        $query = "
            SELECT p.product_id, p.product_name, p.b2b_regular_price, p.stock_quantity, pi.image_url,
                   c.category_id, c.slug AS category_slug, c.category_name
            FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN categories c ON c.category_id = p.category_id
            WHERE p.is_b2b_available = 1
            AND p.product_status = 1
            AND p.stock_quantity >= 5
            ORDER BY p.product_name
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getB2BCategories(): array
    {
        $query = "
            SELECT c.category_id, c.category_name, c.slug
            FROM categories c
            INNER JOIN products p ON p.category_id = c.category_id
            WHERE p.is_b2b_available = 1
              AND p.product_status = 1
              AND p.stock_quantity >= 5
            GROUP BY c.category_id, c.category_name, c.slug
            ORDER BY c.category_name ASC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById(int $productId): array|bool
    {
        $query = "
            SELECT product_id, b2b_regular_price, stock_quantity
            FROM products
            WHERE product_id = :product_id
            AND is_b2b_available = 1
            AND product_status = 1
            AND stock_quantity >= 5
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveInquiry(array $data): int|false
    {
        $query = "
            INSERT INTO bulk_inquiries 
            (name, email, phone, tel_no, business_name, message, address, total_price, status, created_at) 
            VALUES (:name, :email, :phone, :tel_no, :business_name, :message, :address, :total_price, 'Pending', NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
        $stmt->bindParam(':tel_no', $data['tel_no'], PDO::PARAM_STR);
        $stmt->bindParam(':business_name', $data['business_name'], PDO::PARAM_STR);
        $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);
        $stmt->bindParam(':address', $data['address'], PDO::PARAM_STR);
        $stmt->bindParam(':total_price', $data['total_price'], PDO::PARAM_STR);
        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }
        return false;
    }

    public function saveInquiryItems(int $inquiryId, array $items): bool
    {
        $query = "
            INSERT INTO bulk_inquiry_items 
            (inquiry_id, product_id, quantity) 
            VALUES (:inquiry_id, :product_id, :quantity)
        ";

        $stmt = $this->db->prepare($query);
        foreach ($items as $item) {
            $stmt->bindParam(':inquiry_id', $inquiryId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return false;
            }
        }
        return true;
    }

    public function getB2BOrdersCount(): int
    {
        $query = "SELECT COUNT(*) FROM bulk_inquiries";
        $stmt = $this->db->query($query);
        return (int) $stmt->fetchColumn();
    }

    public function getAllB2BOrders(): array
    {
        $query = "
            SELECT id, name, business_name, phone, tel_no, total_price, status, created_at, message
            FROM bulk_inquiries
            ORDER BY created_at DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingB2BOrdersCount(): int
    {
        $query = "SELECT COUNT(*) FROM bulk_inquiries WHERE status = 'Pending'";
        $stmt = $this->db->query($query);
        return (int) $stmt->fetchColumn();
    }

    public function updateB2BOrderStatus(int $inquiryId, string $status): bool
    {
        $query = "UPDATE bulk_inquiries SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $inquiryId]);
    }
}
?>