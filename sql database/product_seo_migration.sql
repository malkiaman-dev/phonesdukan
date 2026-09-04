-- Product SEO extended fields
ALTER TABLE product_seo
    ADD COLUMN IF NOT EXISTS canonical_url VARCHAR(500) NULL DEFAULT NULL AFTER focus_keyword,
    ADD COLUMN IF NOT EXISTS secondary_keywords TEXT NULL DEFAULT NULL AFTER canonical_url;
