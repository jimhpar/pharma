# Adorzotno POS — Developer Documentation

> **Framework:** Laravel 12 · **PHP:** 8.x · **DB:** MySQL  
> **Last updated:** 2026-05-18

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Folder Structure](#2-folder-structure)
3. [Environment & Setup](#3-environment--setup)
4. [Routing](#4-routing)
5. [Controllers](#5-controllers)
6. [Models & Database Schema](#6-models--database-schema)
7. [Services (Business Logic)](#7-services-business-logic)
8. [POS Checkout Flow](#8-pos-checkout-flow)
9. [Inventory System](#9-inventory-system)
10. [Purchase Order Flow](#10-purchase-order-flow)
11. [Loyalty System](#11-loyalty-system)
12. [Pharmacy Domain Fields](#12-pharmacy-domain-fields)
13. [Cart System](#13-cart-system)
14. [Authentication & Permissions](#14-authentication--permissions)
15. [Frontend Architecture](#15-frontend-architecture)
16. [Key Dependencies](#16-key-dependencies)
17. [Support & Trait Classes](#17-support--trait-classes)

---

## 1. Project Overview

A full-featured pharmacy Point-of-Sale system supporting:

- Multi-branch, multi-warehouse inventory
- Batch/expiry tracking (FIFO deduction)
- Box/carton stock tracking
- Pharmacy-specific product fields (dosage, strength, coating)
- Loyalty points & membership system
- Purchase orders with stock receipt
- Double-entry accounting (journals, ledgers)
- Role-based access control per branch
- Thermal printer support
- DataTables server-side rendering throughout

---

## 2. Folder Structure

```
pos/
├── app/
│   ├── Console/                    # Artisan commands
│   ├── Http/
│   │   ├── Controllers/            # All HTTP controllers
│   │   └── Middleware/             # Custom middleware
│   ├── Models/                     # Eloquent models
│   ├── Services/                   # Business logic layer
│   ├── Support/                    # Helper/utility classes
│   └── Traits/                     # Reusable traits (ImageTrait)
├── database/
│   └── migrations/                 # All DB migrations
├── resources/
│   └── views/                      # Blade templates
│       ├── layouts/                # Master layout + partials
│       ├── pos/                    # POS interface
│       ├── product/                # Product CRUD
│       ├── order/                  # Sales orders
│       ├── purchase_order/         # Purchase orders
│       └── ...                     # One folder per module
├── routes/
│   └── web.php                     # All routes
└── DEVELOPER_DOCS.md               # This file
```

---

## 3. Environment & Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed        # if seeders exist
php artisan storage:link
```

**Key `.env` values:**
```
APP_URL=http://localhost/adorzotno-pos/pos
DB_DATABASE=adorzotno_pos
SESSION_DRIVER=file        # Cart uses session — do not change to array
```

---

## 4. Routing

All routes live in `routes/web.php`. Every route group applies:
- `auth` middleware (logged-in users)
- `initialize.branch` middleware (sets branch context)
- `require.permission:{slug}` per sensitive group

### Route Groups Summary

| Prefix | Permission | Purpose |
|---|---|---|
| `/dashboard` | `dashboard.view` | Home |
| `/pos` | `pos.access` | POS terminal |
| `/cart` | `pos.access` | Cart operations (AJAX) |
| `/product` | — | Product catalogue |
| `/purchase-order` | `purchases.manage` | Purchasing |
| `/order` | `orders.view` | Sales orders |
| `/customer` | `customers.manage` | CRM |
| `/supplier` | — | Supplier management |
| `/inventory-adjustment` | `inventory.adjust` | Stock corrections |
| `/stock-transfer` | `stock_transfers.manage` | Inter-warehouse |
| `/account` | — | Chart of accounts |
| `/accounting` | — | Journals & transfers |
| `/expense` | — | Expenses |
| `/loyalty` | — | Loyalty programme |
| `/report` | — | All reports |
| `/user`, `/role`, `/permission` | `users.manage` etc. | Administration |
| `/branch`, `/warehouse` | `branches.manage` etc. | Locations |

### POS-specific AJAX Routes

```
GET  /pos/show                          → PosController@show
POST /pos/skuSearch                     → PosController@skuSearch
GET  /pos/productDetails/{productId}    → PosController@productDetails
POST /pos/completeSale                  → PosController@completeSale
GET  /pos/customerLoyaltyInfo           → PosController@customerLoyaltyInfo
GET  /purchase-order/sku-search         → PurchaseOrderController@skuSearch
POST /cart/addToCart                    → CartController@addToCart
POST /cart/updateCart                   → CartController@updateCart
POST /cart/updateItemDiscount           → CartController@updateItemDiscount
POST /cart/removeCartItem               → CartController@removeCartItem
POST /cart/clearCart                    → CartController@clearCart
POST /cart/applyConditions              → CartController@applyConditions
GET  /posCustomer/customerSearch        → PosCustomerController@customerSearch
POST /posCustomer/store                 → PosCustomerController@store
```

---

## 5. Controllers

### PosController — `app/Http/Controllers/PosController.php`

The main POS terminal controller.

| Method | Description |
|---|---|
| `show()` | Loads POS page with initial products (limit 20), categories, brands, loyalty settings, VAT rule, shipment zones |
| `skuSearch(Request)` | Paginated product search. Params: `q`, `category_ids[]`, `brand_id`, `per_page`, `page`. Returns compact JSON |
| `productDetails(Request, int $productId)` | Full SKU list for a product including variations, stock per warehouse, branch prices |
| `completeSale(Request)` | Validates cart, calls `OrderService::createOrder()`, clears cart. Returns `sales_order` JSON |
| `customerLoyaltyInfo(Request)` | Returns loyalty points, value, membership status for a customer |

**Private helpers:**

- `buildPosProductQuery()` — applies filters; uses `getCategoryDescendantIds()` for recursive subcategory matching
- `appendProductCardMeta()` — attaches `price_label`, `available_stock`, `mrp`, `card_badge`, etc. to product collection
- `toCompactJson()` — maps collection to flat array for the JS card renderer
- `isMedicineProduct()` — checks category name contains "medicine" or "ঔষধ"
- `getSkuAvailableStock()` — sums `available_quantity - reserved_quantity` per warehouse
- `resolvePosWarehouse()` — singleton warehouse resolver via `PosInventoryService`
- `getCategoryDescendantIds()` — recursive; returns flat array of a category + all descendants

---

### CartController — `app/Http/Controllers/CartController.php`

Manages the session cart (Darryldecode Cart package).

| Method | Key logic |
|---|---|
| `addToCart(Request)` | Resolves sale unit (`unit`/`strip`/`medicine`), checks stock, applies SKU-level discount then product default discount, adds to cart |
| `updateCart(Request)` | Update qty with stock re-check |
| `updateItemDiscount(Request)` | Set fixed discount on a cart row |
| `removeCartItem(Request)` | Remove row by row ID |
| `clearCart()` | `Cart::clear()` + `Cart::clearCartConditions()` |
| `applyConditions(Request)` | Applies delivery charge, order-level discount, VAT as cart conditions |

**Discount priority chain (in addToCart):**
1. SKU `discount_type` / `discount_value` (if set)
2. Product `default_discount_type` / `default_discount_value`
3. No discount

**Cart item attributes stored:**
```php
'sku_id', 'product_id', 'product_name', 'sku_code',
'sale_unit', 'units_per_strip', 'medicine_unit_price',
'discount', 'discount_amount', 'discount_type', 'discount_value', 'discount_percent',
'is_medicine', 'thumbnail_url'
```

---

### ProductController — `app/Http/Controllers/ProductController.php`

| Method | Notes |
|---|---|
| `show()` | Passes `$categories` (hierarchical) and `$brands` to view |
| `list(Request)` | DataTables AJAX. Filters: `search_query`, `category_ids[]`, `brand_ids[]`, `product_type`, `status`. Returns: name, brand, type_label, sku_summary, thumbnail, category, status, dosage_form, strength, coating_type |
| `store(Request)` | Handles Single and Variation type; validates SKU uniqueness; saves pharma fields |
| `update(Request, $id)` | Full update including variant sync, category pivot, product warnings |
| `delete(Request)` | Blocked if product has orders or stock transactions |

**Product types (constants on Product model):**
```php
Product::TYPE_STANDARD       = 'standard'
Product::TYPE_VARIANT_PARENT = 'variant_parent'
Product::TYPE_COMBO          = 'combo'
Product::TYPE_SERVICE        = 'service'
```

---

### PurchaseOrderController — `app/Http/Controllers/PurchaseOrderController.php`

| Method | Notes |
|---|---|
| `skuSearch(Request)` | Returns up to 40 active `track_stock=true` SKUs matching query. Fields: `id`, `text`, `retail_price`, `mrp` |
| `store(Request)` | Creates PO + items; syncs supplier ledger |
| `update(Request, $id)` | Only editable if no received stock |
| `receiveStore(Request, $id)` | Creates `InventoryBatch` per item with batch_no, expiry, qty; updates stock balances; logs inventory transaction |
| `delete(Request)` | Blocked if any stock already received |

**PO status values:** `draft`, `ordered`, `partial`, `received`, `canceled`

---

### OrderController — `app/Http/Controllers/OrderController.php`

| Method | Notes |
|---|---|
| `list(Request)` | DataTables with: status, payment_status, fulfillment_status, date range, amount range, customer, cashier, branch |
| `details($id)` | Loads order with items (+ batches), payments, customer |
| `invoice($id)` | A4 HTML invoice |
| `thermalInvoice($id)` | Thermal-formatted receipt |
| `directThermalPrint($id)` | Sends ESC/POS data to Windows printer via PowerShell |

---

## 6. Models & Database Schema

### Product — `products`

```php
// Key fields
'name', 'product_type', 'status', 'category_id', 'brand_id', 'unit_id',
'thumbnail_image', 'sale_price', 'default_discount_type', 'default_discount_value',
// Pharmacy fields
'dosage_form', 'strength', 'coating_type', 'generic_name', 'manufacturer_name',
// SEO
'seo_title', 'seo_description', 'slug'
```

**Relationships:**
- `sku()` → HasMany Sku
- `category()` → BelongsTo Category
- `categories()` → BelongsToMany Category (pivot: `product_categories`)
- `brand()` → BelongsTo Brand
- `productImages()` → HasMany ProductImage

**Accessors/Mutators:**
- `getTypeAttribute()` / `setTypeAttribute()` — maps between DB value and display label
- `getDescriptionAttribute()` — reads from `long_description`
- `getSellingPriceAttribute()` — `sale_price ?? base_price ?? 0`

---

### Sku — `product_skus`

```php
// Pricing
'cost_price', 'retail_price',   // retail_price = MRP
'wholesale_price', 'minimum_selling_price', 'online_price', 'sale_price',
// Discount
'discount_type', 'discount_value', 'discount_amount',
// Pharmacy
'units_per_strip', 'medicine_unit_price', 'dosage_details',
// Tracking flags
'track_stock', 'track_batch', 'track_expiry', 'track_serial',
// Codes
'sku_code', 'barcode', 'product_code'
```

**Important note:** `retail_price` = MRP everywhere in the system. All "Retail Price" labels are displayed as "MRP".

**Relationships:**
- `product()` → BelongsTo Product
- `stockBalances()` → HasMany StockBalance
- `branchPrices()` → HasMany BranchPrice
- `inventoryBatches()` → HasMany InventoryBatch
- `variationRelation()` → HasMany VariationRelation
- `images()` → HasMany ProductImage

**Key accessors:**
- `getProductCodeAttribute()` → returns `sku_code`
- `getBasePriceAttribute()` → returns `retail_price`
- `getDisplayNameAttribute()` → `"Name (DosageForm Strength)"` — used in dropdowns
- `getMedicineUnitPriceForPosAttribute()` → calculated per-unit price for POS

---

### SalesOrder — `sales_orders`

```php
'branch_id', 'warehouse_id', 'customer_id', 'cashier_id',
'order_no', 'invoice_no', 'order_date', 'status',
'payment_status', 'fulfillment_status',
'sub_total', 'item_discount_total', 'cart_discount_total',
'tax_total', 'shipping_fee', 'grand_total', 'paid_total', 'due_total',
'earned_points', 'redeemed_points', 'point_discount_amount',
'customer_note'
```

**Status values:** `Sale`, `Credit Sale`, `Quotation`, `Draft`, `Suspend`

**Relationships:** `items()`, `payments()`, `customer()`, `cashier()`, `branch()`, `warehouse()`

---

### PurchaseOrder — `purchase_orders`

```php
'purchase_no', 'invoice_no', 'purchase_date', 'status',
'supplier_id', 'branch_id', 'warehouse_id',
'sub_total', 'discount_total', 'tax_total', 'other_charge_total',
'grand_total', 'paid_total', 'due_total', 'note'
```

### PurchaseOrderItem — `purchase_order_items`

```php
'purchase_order_id', 'sku_id', 'batch_no', 'expiry_date',
'quantity', 'received_quantity', 'bonus_qty',
'unit_cost',     // Trade Price (TP)
'unit_price',    // Unit Price after VAT
'mrp',           // MRP for this batch
'sale_price',    // Suggested sale price
'vat_amount', 'discount_amount',
'sub_total', 'total',
'carton_plan'    // JSON: carton/box configuration
```

### Customer — `customers`

```php
'name', 'email', 'phone', 'billing_address', 'shipping_address',
'customer_code', 'opening_balance', 'credit_limit', 'current_due',
'loyalty_points', 'lifetime_earned_points', 'lifetime_redeemed_points',
'total_purchase_amount', 'is_member', 'membership_started_at', 'status'
```

### StockBalance — `stock_balance`

```php
'sku_id', 'warehouse_id', 'batch_id',
'available_quantity', 'reserved_quantity'
```

Available stock = `available_quantity - reserved_quantity`

### InventoryBatch — `inventory_batches`

```php
'sku_id', 'warehouse_id', 'purchase_order_item_id',
'batch_no', 'expiry_date', 'received_at',
'quantity', 'available_quantity', 'cost_price', 'mrp'
```

FIFO order: null-expiry first → earliest expiry → earliest `received_at`

---

## 7. Services (Business Logic)

### OrderService — `app/Services/OrderService.php`

`createOrder(array $validated)` — the single entry point for all POS sales.

**Steps (inside DB transaction):**
1. Load cart items; abort if empty
2. Resolve warehouse via `PosInventoryService::resolveWarehouse()`
3. Validate customer status, credit limit (for Credit Sale)
4. Calculate totals (subtotal, item discounts, cart conditions, VAT, shipping)
5. Handle loyalty point redemption (if `redeemed_points` provided)
6. Generate `order_no` and `invoice_no` (branch prefix + sequence)
7. Create `SalesOrder` record
8. For each cart item: create `SalesOrderItem`, deduct stock via `PosInventoryService::deductSkuStockForSale()`
9. Create `Payment` records for each payment method
10. Update `CustomerLedger` (debit: order amount, credit: payments)
11. Award loyalty points; check membership upgrade threshold
12. Return order with all relations loaded

---

### PosInventoryService — `app/Services/PosInventoryService.php`

| Method | Purpose |
|---|---|
| `resolveWarehouse(?int $branchId)` | Get default/active warehouse for current branch |
| `getSkuAvailableStock(int $skuId, ?Warehouse)` | Sum `available - reserved` across batches |
| `getSkuSellingPrice(Sku, ?Warehouse)` | Branch price → SKU retail_price fallback |
| `deductSkuStockForSale(...)` | FIFO batch deduction + InventoryTransaction log |
| `restoreStockForSalesOrder(SalesOrder, $type)` | Reverse deduction (returns/cancellations) |
| `deductFromBoxes(...)` | Preferred box selection + FIFO box deduction |

**FIFO deduction logic:**
1. Lock batches: `null expiry` first → earliest expiry → earliest `received_at`
2. Deduct from `stock_balance` and `inventory_batches`
3. Log `InventoryTransaction`
4. If remaining qty and warehouse allows negative stock: create null-batch allocation
5. Deduct from `inventory_boxes` (preferred box first, then FIFO)

---

### LoyaltyService — `app/Services/LoyaltyService.php`

| Method | Purpose |
|---|---|
| `getSettings()` | Returns config array: `enabled`, `redemption_enabled`, `points_per_amount`, `point_value`, `membership_threshold` |
| `calculateEarnedPoints(float)` | `floor(paidAmount / points_per_amount)` |
| `calculatePointDiscount(int)` | `points × point_value` |
| `canRedeemPoints(Customer, points, amount)` | Validates sufficient points, redemption enabled |
| `applyEarnedPoints(Customer, SalesOrder)` | Increment `loyalty_points`, log ledger |
| `redeemPoints(Customer, SalesOrder, int)` | Decrement `loyalty_points`, log ledger |
| `updateMembershipStatus(Customer, amount)` | Auto-promote when `total_purchase_amount >= threshold` |
| `adjustPoints(Customer, int, note)` | Admin manual adjust with ledger entry |

Points only awarded for sale types: `Sale`, `Credit Sale` (not Draft/Quotation/Suspend).

---

## 8. POS Checkout Flow

```
User searches product
    → PosController::skuSearch() [AJAX]
    → Returns product cards (JS renderProductCard())

User clicks product
    → PosController::productDetails() [AJAX]
    → Shows SKU/variant picker modal

User selects SKU + qty
    → CartController::addToCart() [AJAX]
    → Validates stock
    → Resolves discount (SKU → Product → none)
    → Darryldecode Cart::add()

User applies charges
    → CartController::applyConditions() [AJAX]
    → Cart conditions: delivery, order discount, VAT

User clicks Checkout
    → PosController::completeSale() [POST]
    → OrderService::createOrder()
        → DB::transaction()
        → Create SalesOrder
        → Per item: PosInventoryService::deductSkuStockForSale()
        → Create Payments
        → Update CustomerLedger
        → Award loyalty points
        → Cart::clear()
    → Returns JSON { success: true, sales_order: {...} }

User prints invoice
    → /order/invoice/{id}       (A4)
    → /order/invoice/thermal/{id}  (thermal)
```

---

## 9. Inventory System

### Tables

| Table | Purpose |
|---|---|
| `stock_balance` | Current qty per SKU/warehouse/batch |
| `inventory_batches` | Batch-level detail (expiry, cost, received date) |
| `inventory_transactions` | Immutable log of every stock movement |
| `inventory_boxes` | Box-level tracking |
| `inventory_cartons` | Carton-level tracking |
| `inventory_serials` | Serial number tracking |

### Movement Types (InventoryTransaction)
`purchase_receipt`, `sale`, `sale_return`, `adjustment_add`, `adjustment_deduct`, `stock_issue`, `transfer_out`, `transfer_in`, `supplier_return`

### Stock Adjustment Types
- **Opening Stock** — initial balance on system setup
- **Stock Issue** — write-off (damaged, expired)
- **General Adjustment** — add or deduct

---

## 10. Purchase Order Flow

```
1. PurchaseOrderController::store()
   → Create PurchaseOrder (status: draft/ordered)
   → Create PurchaseOrderItems (qty, TP, unit_price, MRP, sale_price, VAT, batch_no, expiry)
   → Sync SupplierLedger (debit: grand_total)

2. PurchaseOrderController::receiveStore()
   → Per item: create InventoryBatch (batch_no, expiry_date, received qty, cost)
   → Update/create StockBalance
   → Log InventoryTransaction (type: purchase_receipt)
   → Update PO status (partial / received)
   → Sync SupplierLedger payments

3. Carton tracking (optional per item)
   → JSON carton_plan on PurchaseOrderItem
   → Creates InventoryCarton + InventoryBox records
```

**PO Item fields quick reference:**
```
batch_no        → Batch number printed on packaging
expiry_date     → Product expiry
quantity        → Ordered qty
bonus_qty       → Free goods (not invoiced)
unit_cost       → Trade Price (TP) — what we pay supplier
unit_price      → Our cost after VAT
vat_amount      → VAT value
mrp             → Manufacturer/printed MRP
sale_price      → Suggested selling price
```

---

## 11. Loyalty System

### Settings (stored in `loyalty_settings` table)

| Key | Meaning |
|---|---|
| `enabled` | Master on/off |
| `redemption_enabled` | Allow point redemption at POS |
| `points_per_amount` | Taka spent per 1 point earned (e.g. 10 = spend ৳10 → 1 point) |
| `point_value` | Taka value of 1 point (e.g. 0.50 = 1 point = ৳0.50 discount) |
| `membership_threshold` | Total purchase amount to become a member |

### Earn / Redeem

```php
// Earn
$points = floor($paidAmount / $settings['points_per_amount']);

// Redeem discount
$discount = $redeemedPoints * $settings['point_value'];
```

### Customer Loyalty Fields

```
loyalty_points              Current balance (spendable)
lifetime_earned_points      Total ever earned (read-only history)
lifetime_redeemed_points    Total ever spent
total_purchase_amount       Sum of all paid orders (triggers membership)
is_member                   Boolean — member status
membership_started_at       Date of membership upgrade
```

---

## 12. Pharmacy Domain Fields

### On `products` table

| Column | Example | Notes |
|---|---|---|
| `dosage_form` | Tablet, Capsule, Syrup | Free text |
| `strength` | 500mg, 125mg/5ml | Free text |
| `coating_type` | Film-coated, Sugar-coated | Free text, optional |
| `generic_name` | Amoxicillin | Active ingredient |
| `manufacturer_name` | The ACME Laboratories | Company name |

### On `product_skus` table

| Column | Purpose |
|---|---|
| `units_per_strip` | Number of units in one strip/pack |
| `medicine_unit_price` | Price per single unit (auto = retail_price / units_per_strip) |
| `dosage_details` | Free-text dosage instructions |

### Sale Unit Types (POS Cart)

| `sale_unit` | Qty deducted | Price charged |
|---|---|---|
| `unit` | 1 | SKU price |
| `strip` | `units_per_strip` | SKU price |
| `medicine` | 1 | `medicine_unit_price` |

### Display Rules (POS Card & Product List)

Pharma info shown as subtitle below product name:
```
Powder for Syrup · 125mg/5ml
```
Null fields are omitted. Separator is ` · `.

---

## 13. Cart System

**Package:** `darryldecode/cart` (session-based)

### Cart Item Structure

```php
Cart::add([
    'id'         => $sku->id,
    'name'       => $product->name,
    'price'      => $price,
    'quantity'   => $qty,
    'attributes' => [
        'sku_id', 'product_id', 'product_name', 'sku_code',
        'sale_unit', 'units_per_strip', 'medicine_unit_price',
        'discount', 'discount_amount', 'discount_type',
        'discount_value', 'discount_percent',
        'is_medicine', 'thumbnail_url'
    ]
]);
```

### Cart Conditions

```php
// Applied via CartController::applyConditions()
'delivery_charge'   → type: 'shipping'
'order_discount'    → type: 'discount'
'vat'               → type: 'tax', target: 'subtotal'
```

### Reading Cart Total

```php
Cart::getTotal();           // After conditions
Cart::getSubTotal();        // Before conditions
Cart::getContent();         // All items (Collection)
Cart::getConditions();      // All conditions
```

---

## 14. Authentication & Permissions

### Middleware Stack

| Middleware | Purpose |
|---|---|
| `auth` | Must be logged in |
| `guest` | Login/register pages only |
| `initialize.branch` | Set `session('current_branch_id')` |
| `require.permission:{slug}` | Check permission for current branch |

### Permission Slugs (key ones)

```
dashboard.view          pos.access
purchases.manage        orders.view          orders.manage
payments.manage         customers.manage     inventory.adjust
stock_transfers.manage  branches.manage      warehouses.manage
users.manage            roles.manage         permissions.manage
sales.credit_limit_override
```

### BranchContext — `app/Support/BranchContext.php`

```php
BranchContext::currentBranchId(User)
BranchContext::accessibleBranches(User)         // Collection of Branch
BranchContext::hasBranchAccess(User, $branchId)
BranchContext::accessibleWarehouses(User)
BranchContext::resolveDefaultWarehouseForCurrentBranch(User)
```

Users have roles per branch (`user_branch_roles` table).

---

## 15. Frontend Architecture

### Layout

- **Master:** `resources/views/layouts/main.blade.php`
- **Header (global CSS):** `resources/views/layouts/partials/header.blade.php`
  - Contains ALL filter bar CSS (classes: `list-root`, `list-head`, `filter-bar`, `filter-row`, `fdd`, `fdd-btn`, `fdd-panel`, `fdd-options`, `a-chip`, etc.)

### Product List Page (`product/index.blade.php`)

Custom AJAX table (not DataTables). Key functions:
```javascript
loadData()          // Fetch from ProductController::list()
renderRows(rows)    // Build table HTML
renderPager()       // Pagination controls
renderChips()       // Active filter chips
```
Filters: search (`#fS`), categories (`#ddCat` fdd), brands (`#ddBrand` fdd), type (`#fT`), status (`#fSt`)

### POS Page (`pos/index.blade.php` + `pos/posJs.blade.php`)

```javascript
// Key globals
posSelectedCats = []        // Selected category IDs (multi-select)
activeBrandId               // Selected brand ID
currentProductPage          // Current page for load-more

// Key functions
loadProducts(page, append)  // AJAX → PosController::skuSearch()
renderProductCard(product)  // Returns HTML string for a product card
openProductSelection(...)   // Opens variant/SKU picker modal
```

**Product card structure:**
```
[Thumbnail image]
Product Name
Pharma subtitle (Dosage · Strength · Coating)
────────────────
[SKU badge]
Category | Brand
Price         Stock
Tap To Add    1 SKU
```

### Filter Bar Components (`.fdd` dropdown)

Used on: Product list, POS (category multi-select)

```html
<div class="fdd" id="ddCat">
    <button class="fdd-btn">Category <span class="fdd-count">0</span></button>
    <div class="fdd-panel">
        <div class="fdd-panel-search"><input></div>
        <div class="fdd-options">
            <div class="fdd-option" data-value="1" data-text="Medicine">
                <span class="fdd-checkbox"></span>
                Medicine
            </div>
        </div>
    </div>
</div>
```

CSS for this component is in `header.blade.php` (global scope).

### DataTables Pages

All other list pages use Yajra DataTables with `filter_status` param passed via `ajax.data`. Controllers accept `$request->filter_status` and apply `->where('status', $v)`.

---

## 16. Key Dependencies

| Package | Version | Use |
|---|---|---|
| `laravel/framework` | ^12.0 | Core |
| `yajra/laravel-datatables-oracle` | 12.6 | Server-side tables |
| `darryldecode/cart` | ~4.2 | Session cart |
| `intervention/image` | 2.7 | Image upload/resize |
| `laravel/ui` | ^4.6 | Auth scaffolding |
| `laravel/tinker` | ^2.10 | REPL |

---

## 17. Support & Trait Classes

### `app/Support/Currency.php`
```php
Currency::format(float $amount): string
// Returns: "৳1,234.56"
```

### `app/Support/DateFormatter.php`
```php
DateFormatter::date($datetime): string       // "18 May 2026"
DateFormatter::dateTime($datetime): string   // "18 May 2026 14:30"
```

### `app/Support/BranchContext.php`
Central authority for resolving which branches/warehouses a user can access.

### `app/Support/PermissionCatalog.php`
Defines all system permission slugs. Used when seeding roles.

### `app/Traits/ImageTrait.php`
Used by `ProductController`. Handles:
- Image upload to `public/productImage/`
- Image deletion
- Filename: SHA1 hash of file contents

---

## Common Pitfalls & Notes

1. **`retail_price` = MRP** — Never use a separate `mrp` column on `products`. The `retail_price` on `product_skus` is the MRP. All UI labels should say "MRP".

2. **Cart is session-based** — `SESSION_DRIVER=file` must be used in production. Switching to Redis will lose cart data between requests if session IDs differ.

3. **Category filter is recursive** — `PosController::getCategoryDescendantIds()` expands selected categories to include all children. This is intentional for pharmacy category trees.

4. **`no-select2` class** — Any `<select>` inside the POS filter bar must have `class="no-select2"` to prevent the global `initSelect2()` from overriding the custom `.fdd` component.

5. **Purchase Order editing** — A PO can only be edited/deleted if `received_quantity = 0` on all items. Once stock is received, the PO is locked.

6. **Negative stock** — Configurable per warehouse. If enabled, `deductSkuStockForSale()` will create a negative-balance allocation when batches run out.

7. **Thermal printing** — Uses Windows PowerShell to send ESC/POS commands. Will not work on Linux servers without modification.

8. **Migration naming** — Use `YYYY_MM_DD_HHMMSS_description.php` format. Migrations with `_000001` to `_000009` suffixes in the same day are ordered by suffix, not timestamp.
