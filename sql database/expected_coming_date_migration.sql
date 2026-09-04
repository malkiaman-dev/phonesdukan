-- Optional expected launch date for coming-soon products
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS expected_coming_date DATE NULL DEFAULT NULL AFTER product_status;
