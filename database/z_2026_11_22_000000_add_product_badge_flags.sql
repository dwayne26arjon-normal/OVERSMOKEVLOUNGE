ALTER TABLE products
    ADD COLUMN is_hot TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured,
    ADD COLUMN is_best_seller TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hot;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_22_000000_add_hot_and_best_seller_flags_to_products', COALESCE(MAX(batch), 0) + 1
FROM migrations;
