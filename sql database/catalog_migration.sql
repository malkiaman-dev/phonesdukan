-- Catalog hierarchy: parent categories + subcategories + product subcategory_id

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS parent_id INT NULL DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0 AFTER parent_id;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS subcategory_id INT NULL DEFAULT NULL AFTER category_id;

ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_parent (parent_id);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_subcategory (subcategory_id);
