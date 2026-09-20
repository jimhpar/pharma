SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION cte_max_recursion_depth = 7000;

INSERT INTO categories (
    parent_id,
    name,
    slug,
    icon,
    is_featured,
    is_popular,
    sort_order,
    is_top_deal,
    status
)
SELECT
    NULL,
    source.name,
    source.slug,
    NULL,
    0,
    0,
    source.sort_order,
    0,
    'active'
FROM (
    SELECT 'OTC Medicines' AS name, 'otc-medicines' AS slug, 10 AS sort_order
    UNION ALL SELECT 'Prescription Medicines', 'prescription-medicines', 20
    UNION ALL SELECT 'Vitamins & Supplements', 'vitamins-supplements', 30
    UNION ALL SELECT 'Personal Care', 'personal-care', 40
    UNION ALL SELECT 'Medical Devices', 'medical-devices', 50
    UNION ALL SELECT 'First Aid', 'first-aid', 60
) AS source
LEFT JOIN categories c
    ON c.slug = (source.slug COLLATE utf8mb4_unicode_ci)
WHERE c.id IS NULL;

INSERT INTO brands (
    name,
    slug,
    logo,
    is_featured,
    status,
    sort_order
)
SELECT
    source.name,
    source.slug,
    NULL,
    0,
    'active',
    source.sort_order
FROM (
    SELECT 'Square' AS name, 'square' AS slug, 10 AS sort_order
    UNION ALL SELECT 'Beximco', 'beximco', 20
    UNION ALL SELECT 'Renata', 'renata', 30
    UNION ALL SELECT 'Incepta', 'incepta', 40
    UNION ALL SELECT 'ACME', 'acme', 50
    UNION ALL SELECT 'Opsonin', 'opsonin', 60
    UNION ALL SELECT 'Healthcare', 'healthcare', 70
    UNION ALL SELECT 'Aristopharma', 'aristopharma', 80
) AS source
LEFT JOIN brands b
    ON b.slug = (source.slug COLLATE utf8mb4_unicode_ci)
WHERE b.id IS NULL;

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

CREATE TEMPORARY TABLE tmp_health_branches AS
SELECT
    ROW_NUMBER() OVER (ORDER BY b.id) AS row_num,
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
    ) AS warehouse_id
FROM branches b
WHERE b.is_active = 1;

CREATE TEMPORARY TABLE tmp_health_categories AS
SELECT
    ordered.row_num,
    c.id,
    c.name,
    c.slug
FROM categories c
JOIN (
    SELECT 1 AS row_num, 'otc-medicines' AS slug
    UNION ALL SELECT 2, 'prescription-medicines'
    UNION ALL SELECT 3, 'vitamins-supplements'
    UNION ALL SELECT 4, 'personal-care'
    UNION ALL SELECT 5, 'medical-devices'
    UNION ALL SELECT 6, 'first-aid'
) AS ordered
    ON ordered.slug = c.slug;

CREATE TEMPORARY TABLE tmp_health_brands AS
SELECT
    ordered.row_num,
    b.id,
    b.name,
    b.slug
FROM brands b
JOIN (
    SELECT 1 AS row_num, 'square' AS slug
    UNION ALL SELECT 2, 'beximco'
    UNION ALL SELECT 3, 'renata'
    UNION ALL SELECT 4, 'incepta'
    UNION ALL SELECT 5, 'acme'
    UNION ALL SELECT 6, 'opsonin'
    UNION ALL SELECT 7, 'healthcare'
    UNION ALL SELECT 8, 'aristopharma'
) AS ordered
    ON ordered.slug = b.slug;

CREATE TEMPORARY TABLE tmp_health_catalog (
    seq INT NOT NULL PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    brand_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sku_code VARCHAR(100) NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    short_description TEXT NULL,
    long_description LONGTEXT NULL,
    cost_price DECIMAL(12, 2) NOT NULL,
    retail_price DECIMAL(12, 2) NOT NULL,
    wholesale_price DECIMAL(12, 2) NOT NULL,
    minimum_selling_price DECIMAL(12, 2) NOT NULL,
    online_price DECIMAL(12, 2) NOT NULL,
    available_quantity DECIMAL(12, 2) NOT NULL,
    reorder_level DECIMAL(12, 2) NOT NULL
);

INSERT INTO tmp_health_catalog (
    seq,
    branch_id,
    warehouse_id,
    category_id,
    brand_id,
    name,
    slug,
    sku_code,
    barcode,
    short_description,
    long_description,
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
    WHERE n < 6000
)
SELECT
    seq.n,
    branch_map.branch_id,
    branch_map.warehouse_id,
    category_map.id,
    brand_map.id,
    CASE category_map.row_num
        WHEN 1 THEN CONCAT(
            ELT(1 + MOD(seq.n - 1, 10), 'Paracetamol', 'Ibuprofen', 'Cetirizine', 'Antacid', 'ORS', 'Cough Relief', 'Cold Relief', 'Nasal Decongestant', 'Pain Relief Gel', 'Vitamin C'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 10), 10), '500mg', '200mg', '10mg', '250mg', '5mg', '120ml', '1000mg', '20gm', '60ml', '12 Sachets'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 100), 5), 'Tablet', 'Capsule', 'Syrup', 'Suspension', 'Gel'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 500), 5), '10 pcs', '15 pcs', '20 pcs', '30 pcs', '1 pack')
        )
        WHEN 2 THEN CONCAT(
            ELT(1 + MOD(seq.n - 1, 10), 'Amoxicillin', 'Azithromycin', 'Cefixime', 'Metformin', 'Amlodipine', 'Losartan', 'Atorvastatin', 'Montelukast', 'Omeprazole', 'Doxycycline'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 10), 10), '250mg', '500mg', '10mg', '20mg', '40mg', '50mg', '850mg', '5mg', '100mg', '1gm'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 100), 4), 'Tablet', 'Capsule', 'Syrup', 'Injection'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 400), 5), '6 pcs', '10 pcs', '14 pcs', '20 pcs', '30 pcs')
        )
        WHEN 3 THEN CONCAT(
            ELT(1 + MOD(seq.n - 1, 10), 'Calcium + D3', 'Multivitamin', 'Zinc', 'Vitamin C', 'Omega 3', 'Protein Powder', 'Iron + Folic Acid', 'Biotin', 'Magnesium', 'Probiotic'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 10), 10), '500mg', '1000mg', '30 pcs', '60 pcs', '90 pcs', '200gm', '400gm', '120 capsules', '20 sachets', '250ml'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 100), 5), 'Tablet', 'Capsule', 'Powder', 'Sachet', 'Softgel')
        )
        WHEN 4 THEN CONCAT(
            ELT(1 + MOD(seq.n - 1, 10), 'Antiseptic Hand Wash', 'Herbal Toothpaste', 'Moisturizing Lotion', 'Gentle Face Wash', 'Medicated Shampoo', 'Baby Wipes', 'Sunscreen', 'Lip Balm', 'Body Wash', 'Hand Sanitizer'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 10), 10), '100ml', '120gm', '200ml', '250ml', '300ml', '20 pcs', '50gm', '75ml', '500ml', '1 pack')
        )
        WHEN 5 THEN CONCAT(
            ELT(1 + MOD(seq.n - 1, 10), 'Digital Thermometer', 'Blood Pressure Monitor', 'Glucometer', 'Nebulizer', 'Pulse Oximeter', 'Weighing Scale', 'Hot Water Bag', 'Heating Pad', 'Inhaler Spacer', 'Medicine Box'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 10), 10), 'Standard', 'Automatic', 'Premium', 'Family Pack', 'Compact', 'Deluxe', 'Home Care', 'Travel', 'Clinic', 'Portable'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 100), 3), '1 pc', '2 pcs', 'Combo Set')
        )
        ELSE CONCAT(
            ELT(1 + MOD(seq.n - 1, 10), 'Adhesive Bandage', 'Sterile Gauze Pad', 'Elastic Crepe Bandage', 'Micropore Tape', 'First Aid Box', 'Antiseptic Solution', 'Cotton Roll', 'Wound Dressing Pad', 'Disposable Gloves', 'Instant Ice Pack'),
            ' ',
            ELT(1 + MOD(FLOOR((seq.n - 1) / 10), 10), '10 pcs', '20 pcs', '1 roll', '50ml', '100ml', 'Small', 'Medium', 'Large', 'Sterile', 'Clinic Pack')
        )
    END AS name,
    CONCAT('health-product-', LPAD(seq.n, 5, '0')),
    CONCAT('HLT', LPAD(seq.n, 6, '0')),
    CONCAT('89012', LPAD(seq.n, 7, '0')),
    CASE category_map.row_num
        WHEN 1 THEN 'OTC medicine for common cold, pain relief, allergy, gastric discomfort, and hydration support.'
        WHEN 2 THEN 'Prescription medicine for physician-guided treatment plans and chronic care support.'
        WHEN 3 THEN 'Daily nutrition and immunity support supplement for wellness-focused customers.'
        WHEN 4 THEN 'Personal care essential for hygiene, skin care, and everyday family use.'
        WHEN 5 THEN 'Medical device designed for home monitoring and practical healthcare routines.'
        ELSE 'First aid essential for emergency response, wound care, and clinic shelf readiness.'
    END,
    CONCAT(
        'Seeded health product for POS demo inventory. Category: ',
        category_map.name,
        '. Brand: ',
        brand_map.name,
        '.'
    ),
    CASE category_map.row_num
        WHEN 1 THEN 40 + MOD(seq.n, 80)
        WHEN 2 THEN 85 + MOD(seq.n, 130)
        WHEN 3 THEN 95 + MOD(seq.n, 150)
        WHEN 4 THEN 60 + MOD(seq.n, 110)
        WHEN 5 THEN 350 + MOD(seq.n, 450)
        ELSE 35 + MOD(seq.n, 90)
    END AS cost_price,
    CASE category_map.row_num
        WHEN 1 THEN 65 + MOD(seq.n, 100)
        WHEN 2 THEN 120 + MOD(seq.n, 170)
        WHEN 3 THEN 145 + MOD(seq.n, 190)
        WHEN 4 THEN 95 + MOD(seq.n, 130)
        WHEN 5 THEN 480 + MOD(seq.n, 550)
        ELSE 55 + MOD(seq.n, 120)
    END AS retail_price,
    CASE category_map.row_num
        WHEN 1 THEN 58 + MOD(seq.n, 95)
        WHEN 2 THEN 112 + MOD(seq.n, 160)
        WHEN 3 THEN 135 + MOD(seq.n, 180)
        WHEN 4 THEN 88 + MOD(seq.n, 120)
        WHEN 5 THEN 450 + MOD(seq.n, 520)
        ELSE 48 + MOD(seq.n, 110)
    END AS wholesale_price,
    CASE category_map.row_num
        WHEN 1 THEN 55 + MOD(seq.n, 90)
        WHEN 2 THEN 108 + MOD(seq.n, 150)
        WHEN 3 THEN 128 + MOD(seq.n, 165)
        WHEN 4 THEN 84 + MOD(seq.n, 115)
        WHEN 5 THEN 430 + MOD(seq.n, 500)
        ELSE 44 + MOD(seq.n, 105)
    END AS minimum_selling_price,
    CASE category_map.row_num
        WHEN 1 THEN 62 + MOD(seq.n, 98)
        WHEN 2 THEN 118 + MOD(seq.n, 168)
        WHEN 3 THEN 140 + MOD(seq.n, 188)
        WHEN 4 THEN 92 + MOD(seq.n, 128)
        WHEN 5 THEN 470 + MOD(seq.n, 540)
        ELSE 52 + MOD(seq.n, 118)
    END AS online_price,
    18 + MOD(seq.n, 180),
    6 + MOD(seq.n, 24)
FROM seq
JOIN tmp_health_categories category_map
    ON category_map.row_num = 1 + MOD(seq.n - 1, 6)
JOIN tmp_health_brands brand_map
    ON brand_map.row_num = 1 + MOD(seq.n - 1, 8)
JOIN tmp_health_branches branch_map
    ON branch_map.row_num = CASE WHEN MOD(seq.n, 2) = 1 THEN 1 ELSE 2 END
WHERE @unit_id IS NOT NULL
  AND @tax_rule_id IS NOT NULL
  AND branch_map.warehouse_id IS NOT NULL;

UPDATE products p
JOIN product_skus sku
    ON sku.product_id = p.id
JOIN tmp_health_catalog catalog
    ON catalog.seq <= 1000
   AND (
        p.slug = (CONCAT('demo-product-', LPAD(catalog.seq, 4, '0')) COLLATE utf8mb4_unicode_ci)
        OR p.slug = (catalog.slug COLLATE utf8mb4_unicode_ci)
   )
SET
    p.category_id = catalog.category_id,
    p.brand_id = catalog.brand_id,
    p.unit_id = @unit_id,
    p.tax_rule_id = @tax_rule_id,
    p.name = catalog.name,
    p.slug = catalog.slug,
    p.product_type = 'standard',
    p.short_description = catalog.short_description,
    p.long_description = catalog.long_description,
    p.status = 'active',
    p.is_featured = 0,
    p.is_popular = 1,
    p.is_online_enabled = 1,
    p.is_pos_enabled = 1,
    p.seo_title = catalog.name,
    p.seo_description = catalog.short_description,
    p.created_by = COALESCE(p.created_by, @created_by),
    sku.sku_code = catalog.sku_code,
    sku.barcode = catalog.barcode,
    sku.variant_name = NULL,
    sku.cost_price = catalog.cost_price,
    sku.retail_price = catalog.retail_price,
    sku.wholesale_price = catalog.wholesale_price,
    sku.minimum_selling_price = catalog.minimum_selling_price,
    sku.online_price = catalog.online_price,
    sku.weight = 0.50,
    sku.track_stock = 1,
    sku.track_batch = 0,
    sku.track_expiry = 0,
    sku.track_serial = 0,
    sku.status = 'active';

UPDATE stock_balances stock
JOIN product_skus sku
    ON sku.id = stock.sku_id
JOIN products p
    ON p.id = sku.product_id
JOIN tmp_health_catalog catalog
    ON catalog.seq <= 1000
   AND p.slug = (catalog.slug COLLATE utf8mb4_unicode_ci)
SET
    stock.branch_id = catalog.branch_id,
    stock.warehouse_id = catalog.warehouse_id,
    stock.available_quantity = catalog.available_quantity,
    stock.reserved_quantity = 0,
    stock.reorder_level = catalog.reorder_level,
    stock.updated_at = NOW()
WHERE stock.batch_id IS NULL;

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
    catalog.category_id,
    catalog.brand_id,
    @unit_id,
    @tax_rule_id,
    catalog.name,
    catalog.slug,
    'standard',
    catalog.short_description,
    catalog.long_description,
    'active',
    0,
    1,
    1,
    1,
    catalog.name,
    catalog.short_description,
    @created_by
FROM tmp_health_catalog catalog
LEFT JOIN products p
    ON p.slug = (catalog.slug COLLATE utf8mb4_unicode_ci)
WHERE catalog.seq > 1000
  AND p.id IS NULL;

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
    catalog.sku_code,
    catalog.barcode,
    NULL,
    catalog.cost_price,
    catalog.retail_price,
    catalog.wholesale_price,
    catalog.minimum_selling_price,
    catalog.online_price,
    0.50,
    1,
    0,
    0,
    0,
    'active'
FROM tmp_health_catalog catalog
JOIN products p
    ON p.slug = (catalog.slug COLLATE utf8mb4_unicode_ci)
LEFT JOIN product_skus sku
    ON sku.sku_code = (catalog.sku_code COLLATE utf8mb4_unicode_ci)
WHERE catalog.seq > 1000
  AND sku.id IS NULL;

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
    catalog.branch_id,
    catalog.warehouse_id,
    sku.id,
    NULL,
    catalog.available_quantity,
    0,
    catalog.reorder_level,
    NOW()
FROM tmp_health_catalog catalog
JOIN product_skus sku
    ON sku.sku_code = (catalog.sku_code COLLATE utf8mb4_unicode_ci)
LEFT JOIN stock_balances stock
    ON stock.branch_id = catalog.branch_id
   AND stock.warehouse_id = catalog.warehouse_id
   AND stock.sku_id = sku.id
   AND stock.batch_id IS NULL
WHERE catalog.seq > 1000
  AND stock.id IS NULL;

DROP TEMPORARY TABLE tmp_health_catalog;
DROP TEMPORARY TABLE tmp_health_brands;
DROP TEMPORARY TABLE tmp_health_categories;
DROP TEMPORARY TABLE tmp_health_branches;
