-- Prepaid discount amount for products (PKR)
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS prepaid_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER sale_price;
