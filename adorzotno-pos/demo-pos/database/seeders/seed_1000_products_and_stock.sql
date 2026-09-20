SET @category_id := (
    SELECT id
    FROM categories
    WHERE status = 'active'
    ORDER BY id
    LIMIT 1
);

SET @brand_id := (
    SELECT id
    FROM brands
    WHERE status = 'active'
    ORDER BY id
    LIMIT 1
);

SET @unit_id := (
    SELECT id
    FROM units
    WHERE status = 'active'
    ORDER BY id
    LIMIT 1
);

SET @tax_rule_id := (
    SELECT id
    FROM tax_rules
    WHERE status = 'active'
    ORDER BY id
    LIMIT 1
);

SET @created_by := (
    SELECT id
    FROM users
    ORDER BY id
    LIMIT 1
);

CREATE TEMPORARY TABLE tmp_seed_products (
    seq INT NOT NULL PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sku_code VARCHAR(100) NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    cost_price DECIMAL(12, 2) NOT NULL,
    retail_price DECIMAL(12, 2) NOT NULL,
    wholesale_price DECIMAL(12, 2) NOT NULL,
    minimum_selling_price DECIMAL(12, 2) NOT NULL,
    online_price DECIMAL(12, 2) NOT NULL,
    available_quantity DECIMAL(12, 2) NOT NULL,
    reorder_level DECIMAL(12, 2) NOT NULL
);

INSERT INTO tmp_seed_products (
    seq,
    branch_id,
    warehouse_id,
    name,
    slug,
    sku_code,
    barcode,
    cost_price,
    retail_price,
    wholesale_price,
    minimum_selling_price,
    online_price,
    available_quantity,
    reorder_level
)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1
    FROM seq
    WHERE n < 1000
)
SELECT
    seq.n,
    branch_map.branch_id,
    branch_map.warehouse_id,
    CONCAT('Demo Product ', LPAD(seq.n, 4, '0')),
    CONCAT('demo-product-', LPAD(seq.n, 4, '0')),
    CONCAT('DP', LPAD(seq.n, 6, '0')),
    CONCAT('88011', LPAD(seq.n, 7, '0')),
    50 + seq.n,
    80 + seq.n,
    75 + seq.n,
    70 + seq.n,
    78 + seq.n,
    25 + MOD(seq.n, 175),
    10 + MOD(seq.n, 30)
FROM seq
JOIN (
    SELECT
        b.id AS branch_id,
        COALESCE(
            (
                SELECT w.id
                FROM warehouses w
                WHERE w.branch_id = b.id AND w.is_default = 1
                ORDER BY w.id
                LIMIT 1
            ),
            (
                SELECT w.id
                FROM warehouses w
                WHERE w.branch_id = b.id
                ORDER BY w.id
                LIMIT 1
            )
        ) AS warehouse_id,
        ROW_NUMBER() OVER (ORDER BY b.id) AS row_num
    FROM branches b
    WHERE b.is_active = 1
) AS branch_map
    ON branch_map.row_num = CASE WHEN MOD(seq.n, 2) = 1 THEN 1 ELSE 2 END
WHERE @category_id IS NOT NULL
  AND @unit_id IS NOT NULL
  AND @tax_rule_id IS NOT NULL;

INSERT INTO products (
    category_id,
    brand_id,
    unit_id,
    tax_rule_id,
    name,
    slug,
    product_type,
    short_description,
    long_description,
    status,
    is_featured,
    is_popular,
    is_online_enabled,
    is_pos_enabled,
    seo_title,
    seo_description,
    created_by
)
SELECT
    @category_id,
    @brand_id,
    @unit_id,
    @tax_rule_id,
    temp.name,
    temp.slug,
    'standard',
    CONCAT(temp.name, ' short description'),
    CONCAT(temp.name, ' generated for POS stock seeding.'),
    'active',
    0,
    0,
    1,
    1,
    temp.name,
    CONCAT(temp.name, ' SEO description'),
    @created_by
FROM tmp_seed_products temp
LEFT JOIN products p ON p.slug = (temp.slug COLLATE utf8mb4_unicode_ci)
WHERE p.id IS NULL;

INSERT INTO product_skus (
    product_id,
    sku_code,
    barcode,
    variant_name,
    cost_price,
    retail_price,
    wholesale_price,
    minimum_selling_price,
    online_price,
    weight,
    track_stock,
    track_batch,
    track_expiry,
    track_serial,
    status
)
SELECT
    p.id,
    temp.sku_code,
    temp.barcode,
    NULL,
    temp.cost_price,
    temp.retail_price,
    temp.wholesale_price,
    temp.minimum_selling_price,
    temp.online_price,
    0.50,
    1,
    0,
    0,
    0,
    'active'
FROM tmp_seed_products temp
JOIN products p ON p.slug = (temp.slug COLLATE utf8mb4_unicode_ci)
LEFT JOIN product_skus sku ON sku.sku_code = (temp.sku_code COLLATE utf8mb4_unicode_ci)
WHERE sku.id IS NULL;

INSERT INTO stock_balances (
    branch_id,
    warehouse_id,
    sku_id,
    batch_id,
    available_quantity,
    reserved_quantity,
    reorder_level,
    updated_at
)
SELECT
    temp.branch_id,
    temp.warehouse_id,
    sku.id,
    NULL,
    temp.available_quantity,
    0,
    temp.reorder_level,
    NOW()
FROM tmp_seed_products temp
JOIN product_skus sku ON sku.sku_code = (temp.sku_code COLLATE utf8mb4_unicode_ci)
LEFT JOIN stock_balances stock
    ON stock.branch_id = temp.branch_id
   AND stock.warehouse_id = temp.warehouse_id
   AND stock.sku_id = sku.id
   AND stock.batch_id IS NULL
WHERE temp.warehouse_id IS NOT NULL
  AND stock.id IS NULL;

DROP TEMPORARY TABLE tmp_seed_products;
