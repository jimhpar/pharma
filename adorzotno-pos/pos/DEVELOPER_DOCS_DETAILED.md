# Adorzotno POS — Detailed Developer Documentation

> **Framework:** Laravel 12 · **PHP:** 8.x · **DB:** MySQL  
> **Updated:** 2026-05-18  
> This document explains every function, every field, and what happens when you change them.

---

## Table of Contents

1. [Project Architecture](#1-project-architecture)
2. [Routes — Complete Reference](#2-routes--complete-reference)
3. [PosController — Line by Line](#3-poscontroller--line-by-line)
4. [CartController — Line by Line](#4-cartcontroller--line-by-line)
5. [OrderService — Complete Flow](#5-orderservice--complete-flow)
6. [PosInventoryService — Stock Logic](#6-posinventoryservice--stock-logic)
7. [LoyaltyService — Points Logic](#7-loyaltyservice--points-logic)
8. [ProductController — Full Reference](#8-productcontroller--full-reference)
9. [PurchaseOrderController — Full Reference](#9-purchaseordercontroller--full-reference)
10. [OrderController — Full Reference](#10-ordercontroller--full-reference)
11. [Models — Every Field Explained](#11-models--every-field-explained)
12. [Middleware — How Permissions Work](#12-middleware--how-permissions-work)
13. [BranchContext — Multi-Branch Logic](#13-branchcontext--multi-branch-logic)
14. [Frontend JavaScript Reference](#14-frontend-javascript-reference)
15. [Change Impact Guide](#15-change-impact-guide)

---

## 1. Project Architecture

```
HTTP Request
    → Middleware (auth → initialize.branch → require.permission)
    → Controller (validates input, orchestrates)
        → Service (business logic, DB transactions)
            → Model (Eloquent ORM)
                → Database
        → View / JSON response
```

**Rule:** Controllers never contain business logic. All calculation, stock deduction, and financial operations happen inside Services. If you need to add new business logic, add it to the relevant Service, not the Controller.

---

## 2. Routes — Complete Reference

**File:** `routes/web.php`

### Middleware Applied to All Authenticated Routes

```php
middleware(['auth', 'initialize.branch'])
```

`initialize.branch` calls `BranchContext::ensureInitialized()` on every request to set the current branch in session.

### Full Route Table

| Method | URI | Controller@Method | Permission Required | Route Name |
|---|---|---|---|---|
| GET | /login | LoginController@create | guest | login |
| POST | /login | LoginController@store | guest | login.store |
| POST | /logout | LoginController@destroy | auth | logout |
| GET | /dashboard | HomeController@index | dashboard.view | dashboard |
| GET | /pos/show | PosController@show | pos.access | pos.show |
| POST | /pos/skuSearch | PosController@skuSearch | pos.access | pos.skuSearch |
| GET | /pos/productDetails/{id} | PosController@productDetails | pos.access | pos.productDetails |
| POST | /pos/completeSale | PosController@completeSale | pos.access | pos.completeSale |
| GET | /pos/customerLoyaltyInfo | PosController@customerLoyaltyInfo | pos.access | pos.customerLoyaltyInfo |
| GET | /pos/invoice/thermal/{id} | PosController@thermalInvoice | pos.access | pos.invoice.thermal |
| POST | /cart/addToCart | CartController@addToCart | pos.access | cart.addToCart |
| POST | /cart/updateCart | CartController@updateCart | pos.access | cart.updateCart |
| POST | /cart/updateItemDiscount | CartController@updateItemDiscount | pos.access | cart.updateItemDiscount |
| POST | /cart/removeCartItem | CartController@removeCartItem | pos.access | cart.removeCartItem |
| POST | /cart/clearCart | CartController@clearCart | pos.access | cart.clearCart |
| POST | /cart/applyConditions | CartController@applyConditions | pos.access | cart.applyConditions |
| GET | /posCustomer/customerSearch | PosCustomerController@customerSearch | pos.access | posCustomer.search |
| POST | /posCustomer/store | PosCustomerController@store | pos.access | posCustomer.store |
| POST | /posCustomer/updateNote/{id} | PosCustomerController@updateNote | pos.access | posCustomer.updateNote |
| GET | /product/show | ProductController@show | — | product.show |
| POST | /product/list | ProductController@list | — | product.list |
| GET | /product/create | ProductController@create | — | product.create |
| POST | /product/store | ProductController@store | — | product.store |
| GET | /product/edit/{id} | ProductController@edit | — | product.edit |
| POST | /product/update/{id} | ProductController@update | — | product.update |
| POST | /product/delete | ProductController@delete | — | product.delete |
| POST | /product/imageDelete | ProductController@imageDelete | — | product.imageDelete |
| GET | /purchase-order/show | PurchaseOrderController@show | purchases.manage | purchaseOrder.show |
| GET | /purchase-order/sku-search | PurchaseOrderController@skuSearch | purchases.manage | purchaseOrder.skuSearch |
| GET | /purchase-order/create | PurchaseOrderController@create | purchases.manage | purchaseOrder.create |
| POST | /purchase-order/store | PurchaseOrderController@store | purchases.manage | purchaseOrder.store |
| GET | /purchase-order/edit/{id} | PurchaseOrderController@edit | purchases.manage | purchaseOrder.edit |
| POST | /purchase-order/update/{id} | PurchaseOrderController@update | purchases.manage | purchaseOrder.update |
| GET | /purchase-order/receive/{id} | PurchaseOrderController@receive | purchases.manage | purchaseOrder.receive |
| POST | /purchase-order/receive-store/{id} | PurchaseOrderController@receiveStore | purchases.manage | purchaseOrder.receiveStore |
| POST | /purchase-order/delete | PurchaseOrderController@delete | purchases.manage | purchaseOrder.delete |
| GET | /order/show | OrderController@show | orders.view | order.show |
| POST | /order/list | OrderController@list | orders.view | order.list |
| GET | /order/details/{id} | OrderController@details | orders.view | order.details |
| GET | /order/invoice/{id} | OrderController@invoice | orders.view | order.invoice |
| GET | /order/invoice/thermal/{id} | OrderController@thermalInvoice | orders.view | order.invoice.thermal |
| GET | /order/direct-thermal-print/{id} | OrderController@directThermalPrint | orders.view | order.directThermalPrint |
| GET | /customer/show | CustomerController@show | customers.manage | customer.show |
| GET | /inventory-adjustment/show | InventoryAdjustmentController@show | inventory.adjust | inventoryAdjustment.show |
| GET | /stock-transfer/show | StockTransferController@show | stock_transfers.manage | stockTransfer.show |
| GET | /loyalty/settings | LoyaltyController@settingsShow | — | loyalty.settings |

### How to Add a New Route

1. Open `routes/web.php`
2. Add inside the appropriate middleware group
3. If it needs a permission, wrap with `middleware('require.permission:your.slug')`
4. Add the permission slug to `PermissionCatalog.php`
5. Assign the permission to relevant roles via the Role management UI

---

## 3. PosController — Line by Line

**File:** `app/Http/Controllers/PosController.php`

### Constructor

```php
public function __construct(
    OrderService $orderService,
    PosInventoryService $posInventoryService,
    LoyaltyService $loyaltyService
)
```

**What it does:** Laravel dependency injection. All three services are injected automatically. The `$posWarehouse` property is `null` initially and gets populated lazily via `resolvePosWarehouse()`.

**If you add a new service:** Add it as a constructor parameter and store it as a property.

---

### `show()` — POS Page Load

```php
public function show()
```

**What it does step by step:**
1. Loads all categories ordered by name (only `id` and `name` columns — keep it lightweight)
2. Loads all brands ordered by name
3. Calls `LoyaltyService::getSettings()` to know if loyalty is enabled (shown/hidden in UI)
4. Gets the latest active VAT rule (used as default VAT at checkout)
5. Gets all active shipment zones with charge amounts
6. Calls `buildPosProductQuery()` then `.latest()->limit(20)->get()` — loads the first 20 products on page load
7. Calls `appendProductCardMeta()` to attach display fields to each product
8. Calls `resolvePosWarehouse()` to determine which warehouse this POS terminal is linked to
9. Returns `pos.index` view with all above variables

**What to change if:**
- You want more/fewer initial products → change `->limit(20)` number
- You want products sorted differently → change `->latest()` to `->oldest()` or `->orderBy('name')`
- You want to add a new variable to the POS page → add it to `compact(...)` at the end

---

### `skuSearch(Request $request)` — Product Search AJAX

```php
public function skuSearch(Request $request): JsonResponse
```

**Parameters received from frontend:**
- `q` → search text (searches product name, SKU code, barcode)
- `category_ids[]` → array of selected category IDs
- `brand_id` → single brand filter
- `per_page` → results per page (1–50, default 20)
- `page` → current page (default 1)

**What it does step by step:**
1. Trims and reads the search query
2. Filters category IDs (removes empty/null values, converts to int)
3. Calls `buildPosProductQuery($query, $categoryIds, $brandId)` which returns an Eloquent Builder
4. Paginates the results
5. Calls `appendProductCardMeta($products, $query)` to attach display meta
6. Calls `toCompactJson($products)` to flatten to array
7. Returns JSON with `data` array and `meta` (pagination info)

**JSON response structure:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "A-Clox 500",
      "thumbnail_url": "http://...",
      "card_badge": "SKU-001",
      "price_label": "BDT 44.97",
      "available_stock": 25,
      "sku_count": 1,
      "category_id": 5,
      "brand_id": 3,
      "category_name": "Penicillins",
      "brand_name": "ACME",
      "is_variant_product": false,
      "is_medicine": true,
      "action_sku_id": 12,
      "dosage_form": "Capsule",
      "strength": "500mg",
      "coating_type": null,
      "mrp": 50.00,
      "product_sale_price": 44.97,
      "default_discount_type": "percent",
      "default_discount_value": 10
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 55,
    "has_more_pages": true
  }
}
```

**What to change if:**
- You want to add a new field to the product card → add it to `toCompactJson()` method
- You want to change max results per page → change `min(50, ...)` to a different number
- You want to search by a new field → add `->orWhere(...)` inside `buildPosProductQuery()`

---

### `productDetails(Request $request, int $productId)` — SKU Detail for Modal

**What it does step by step:**
1. Gets search query from request (used to highlight matching SKU)
2. Resolves warehouse and its branch_id
3. Loads the product with these eager-loaded relations:
   - `category:id,name`
   - `brand:id,name`
   - `sku.stockBalances` (for stock calculation)
   - `sku.branchPrices` filtered to current branch and active only
   - `sku.images`
   - `sku.variationRelation.variation` (for variant labels)
4. For each SKU builds:
   - `available_stock` → calls `getSkuAvailableStock()`
   - `display_price` → calls `PosInventoryService::getSkuSellingPrice()`
   - `medicine_unit_price` → from accessor
   - `variation_values` → sorted by type, nulls removed
   - `combination_label` → "Color: Red | Size: L"
   - `variation_map` → {"Color": 5, "Size": 8} (type → variation ID)
5. Builds `variation_groups` → groups all SKUs' variation values by type
6. Determines `preferred_sku_id`:
   - If search query matches a SKU code exactly → use that SKU
   - Else if exactly 1 in-stock SKU → use that
   - Else null (user must pick)

**What to change if:**
- You want to return a new field per SKU → add it to the map inside `$product->sku->map(...)`
- You want different variation sorting → change `->sortBy('type')`
- You want to preselect a different SKU → modify the `$preferredSkuId` logic

---

### `completeSale(Request $request)` — Checkout

**Validated fields:**
```
customer_id          nullable, must exist in customers table
shipment_zone_id     nullable, must exist in shipping_zones table
delivery_charge      nullable, numeric, min 0
order_discount       nullable, numeric, min 0
vat_amount           nullable, numeric, min 0
vat_is_inclusive     nullable, boolean
payment_method       nullable, string
payments[]           array of {method, amount, note}
sale_type            required: Sale|Credit Sale|Quotation|Draft|Suspend
redeemed_points      nullable, integer, min 0
customer_note        nullable, string, max 1000
```

**What it does:**
1. Validates all fields
2. Calls `OrderService::createOrder($validated)` inside try/catch
3. On success: clears cart (`Cart::clear()` + `Cart::clearCartConditions()`) and returns JSON
4. On failure: returns `{success: false, error: message}` with HTTP 400

**What to change if:**
- You want a new checkout field → add to `$request->validate()` array AND handle in `OrderService::createOrder()`
- You don't want cart cleared on success → remove the `Cart::clear()` lines (⚠️ not recommended)

---

### `buildPosProductQuery()` — Internal Query Builder

```php
private function buildPosProductQuery(
    string $query = '',
    array $categoryIds = [],
    ?int $brandId = null
): Builder
```

**What it does step by step:**
1. Gets `$branchId` from resolved warehouse
2. Expands each selected category to include all descendants (recursive via `getCategoryDescendantIds()`)
3. `array_unique()` on all expanded IDs
4. Builds query:
   - SELECT only needed columns (not `*`) for performance: `id, category_id, brand_id, name, dosage_form, strength, coating_type, thumbnail_image, status`
   - Eager loads: `category`, `brand`, `sku` (with stock balances and branch prices)
   - WHERE `status = 'Active'`
   - WHERE has at least 1 SKU (`->whereHas('sku')`)
   - IF categories selected → `->whereIn('category_id', $allCategoryIds)`
   - IF brand selected → `->where('brand_id', $brandId)`
   - IF search query → searches name OR SKU code OR barcode

**What to change if:**
- You want to search inactive products → remove `->where('status', 'Active')`
- You want to show products with no SKUs → remove `->whereHas('sku')`
- You want to add a new search field → add `->orWhere('field', 'like', "%{$query}%")` in the query closure
- You want to add a new eager load → add to the `with([...])` array

---

### `appendProductCardMeta()` — Decorates Products for Card Display

```php
private function appendProductCardMeta(Collection $products, string $query = ''): void
```

**What it does** (adds dynamic properties to each product object):

| Property | How calculated |
|---|---|
| `sku_count` | Count of SKUs |
| `available_stock` | Sum of (available − reserved) across all SKUs and batches |
| `is_variant_product` | `sku_count > 1` |
| `is_medicine` | Category name contains "medicine" or "ঔষধ" |
| `thumbnail_url` | Full URL from `thumbnail_image` path |
| `category_name` | From eager-loaded category relation |
| `brand_name` | From eager-loaded brand relation |
| `min_price` | Lowest price across all SKUs |
| `max_price` | Highest price across all SKUs |
| `price_label` | Formatted price (variant: shows first SKU price, single: shows min) |
| `direct_sku_id` | SKU id if only 1 SKU, else null |
| `action_sku_id` | Matched SKU if search query matches exact code, else `direct_sku_id` |
| `card_badge` | "3 Variants" if multiple SKUs, else first SKU's product_code |
| `mrp` | `retail_price` of first SKU (this IS the MRP) |

**What to change if:**
- You want to add a new card property → add a new line `$product->my_field = ...`
- You want `mrp` to come from somewhere else → change `$product->mrp = $firstSku->retail_price`
- You want `is_medicine` to detect differently → modify `isMedicineProduct()`

---

### `toCompactJson()` — Serializes for JS

Converts the decorated product collection to a plain array for the frontend. If you add a new property in `appendProductCardMeta()`, you MUST also add it here for the JS card renderer to receive it.

---

### `getCategoryDescendantIds()` — Recursive Category Expansion

```php
private function getCategoryDescendantIds(int $categoryId): array
```

**What it does:** Returns `[$categoryId, ...all children IDs, ...all grandchildren IDs]` recursively.

**Warning:** This makes N database queries where N = number of categories in the tree. For deep category trees with many branches, consider caching the result. Current implementation is fine for typical pharmacy category structures (2–3 levels deep).

**What to change if:**
- Performance is slow with many categories → cache with `Cache::remember('cat_tree_'.$categoryId, 300, fn() => ...)`

---

### `resolvePosWarehouse()` — Singleton Warehouse

Lazy-loads the warehouse once per request and caches in `$this->posWarehouse`. Delegates to `PosInventoryService::resolveWarehouse()`.

**What to change if:**
- You want to allow the cashier to select a warehouse → pass the selected warehouse ID here

---

## 4. CartController — Line by Line

**File:** `app/Http/Controllers/CartController.php`  
**Package:** `darryldecode/cart` (session-based)

---

### `addToCart(Request $request)`

**Required fields:** `sku_id`, `quantity`, `sale_unit`  
**Optional:** `price` (override price)

**Step-by-step:**
1. Finds the SKU with its product loaded
2. Determines the **stock multiplier**:
   - `unit` → multiplier = 1
   - `strip` → multiplier = `units_per_strip` (e.g. 10 tablets)
   - `medicine` → multiplier = 1 (single unit from a strip)
3. Calculates actual quantity to deduct from stock: `$quantity × $multiplier`
4. **Stock check:** Gets available stock from `PosInventoryService`. If `qty_to_deduct > available_stock` AND warehouse doesn't allow negative stock → returns error JSON
5. Resolves the selling price via `PosInventoryService::getSkuSellingPrice()`
6. For `medicine` sale unit → uses `medicine_unit_price` instead of SKU price
7. **Discount resolution** (priority order):
   - Check SKU's own `discount_type` + `discount_value` (if set)
   - Else check product's `default_discount_type` + `default_discount_value`
   - Else no discount
8. Calculates `discount_amount` based on type (percent or fixed)
9. Final `price` = selling price − discount_amount
10. Calls `Cart::add()` with all attributes
11. Returns updated cart JSON

**Cart item ID:** Uses `sku_id` as the cart item ID. Adding the same SKU again increments quantity.

**What to change if:**
- You want a new sale unit type → add a new case in the multiplier switch and price calculation
- You want to disable stock checking → remove the stock validation block (⚠️ dangerous)
- You want to add a new cart attribute → add it to the `attributes` array in `Cart::add()`

---

### `updateCart(Request $request)`

**Required:** `row_id`, `quantity`, `sale_unit`

Finds the cart row by `row_id` (not sku_id — row_id is a hash generated by the cart package). Validates new quantity against stock. Calls `Cart::update($rowId, ['quantity' => $qty])`.

**Important:** The `row_id` is different from `sku_id`. The JS must send the correct `row_id` from the cart item.

---

### `updateItemDiscount(Request $request)`

**Required:** `row_id`, `discount_amount`

Reads the existing cart item, sets a new `discount_amount` attribute, recalculates price, and updates via `Cart::update()`. Used when cashier manually overrides a line item discount.

**What to change if:**
- You want percentage discounts via this endpoint → add `discount_percent` parameter and calculate

---

### `removeCartItem(Request $request)`

Calls `Cart::remove($rowId)`. Simple.

---

### `clearCart()`

Calls `Cart::clear()` (removes all items) and `Cart::clearCartConditions()` (removes delivery charge, discount, VAT). Both must be called — just `clear()` leaves conditions in session.

---

### `applyConditions(Request $request)`

**Fields accepted:**
- `delivery_charge` → added as `type: 'shipping'` condition
- `order_discount` → added as `type: 'discount'` (negative, reduces total)
- `vat_amount` → added as `type: 'tax'` on subtotal
- `vat_is_inclusive` → stored in session for OrderService to read

**How conditions work:** Cart package applies conditions in order. Shipping adds to total, discount subtracts, tax applies to subtotal.

**What to change if:**
- You want a new charge type (e.g. packaging fee) → add a new condition with appropriate type

---

## 5. OrderService — Complete Flow

**File:** `app/Services/OrderService.php`

### `createOrder(array $validated)` — The Most Critical Function

Everything runs inside `DB::transaction()`. If anything throws, everything rolls back.

**Step 1 — Validate Cart**
```php
$cartItems = Cart::getContent();
// Throws if cart is empty
```

**Step 2 — Resolve Warehouse**
```php
$warehouse = $this->posInventoryService->resolveWarehouse();
// Throws if no warehouse configured for this branch
```

**Step 3 — Load Customer (if provided)**
```php
$customer = Customer::find($validated['customer_id']);
// For Credit Sale: validates customer is active, credit limit not exceeded
```

**Step 4 — Calculate Totals**
```php
$subTotal         // Sum of (item price × qty) before any discount
$itemDiscountTotal // Sum of all line-item discounts
$cartDiscountTotal // Order-level discount from cart conditions
$taxTotal          // VAT from cart conditions
$shippingFee       // Delivery charge from cart conditions
$grandTotal        // subTotal − discounts + tax + shipping
```

**Step 5 — Handle Loyalty Redemption**
```php
if ($validated['redeemed_points'] > 0) {
    // Validates customer has enough points
    // Calculates point_discount_amount = points × point_value
    // Deducts from grandTotal
}
```

**Step 6 — Generate Order Numbers**
```php
$orderNo   // Format: {branch_prefix}-{YYYYMMDD}-{sequence}
$invoiceNo // Same format, different prefix
```

**Step 7 — Create SalesOrder Record**
```php
SalesOrder::create([...all totals, customer, warehouse, cashier, sale_type...])
```

**Step 8 — Create SalesOrderItems + Deduct Stock**
```php
foreach ($cartItems as $item) {
    SalesOrderItem::create([...]);
    
    if ($sku->track_stock) {
        $this->posInventoryService->deductSkuStockForSale(
            sku: $sku,
            warehouse: $warehouse,
            quantity: $actualQty,  // units (strip × units_per_strip)
            salesOrderItem: $orderItem
        );
    }
}
```

**Step 9 — Create Payment Records**
```php
foreach ($validated['payments'] as $payment) {
    Payment::create([
        'sales_order_id' => $order->id,
        'payment_method' => $payment['method'],
        'amount'         => $payment['amount'],
        'payment_date'   => today(),
    ]);
}
```

**Step 10 — Update Customer Financials**
```php
// Debit customer account (they owe us)
CustomerLedger::create(['debit' => $grandTotal, ...]);

// Credit for payments received
CustomerLedger::create(['credit' => $totalPaid, ...]);

// Update customer.current_due
$customer->increment('current_due', $grandTotal - $totalPaid);
```

**Step 11 — Loyalty Points**
```php
// Deduct redeemed points
$this->loyaltyService->redeemPoints($customer, $order, $redeemedPoints);

// Award earned points (only for Sale and Credit Sale types)
$this->loyaltyService->applyEarnedPoints($customer, $order);

// Check membership upgrade
$this->loyaltyService->updateMembershipStatus($customer, $totalPaid);
```

**Step 12 — Return**
```php
return ['sales_order' => $order->load([...relations...])];
```

**What to change if:**
- You want a new sale type → add to the validation in PosController and handle in step 11 (loyalty check)
- You want to send an SMS on sale → add after step 12
- You want to record cashier shift → add shift_id in step 7
- You want to prevent sales of out-of-stock items even when negative stock is allowed → add stock check in step 8

---

## 6. PosInventoryService — Stock Logic

**File:** `app/Services/PosInventoryService.php`

### `resolveWarehouse(?int $branchId = null): ?Warehouse`

Finds the active warehouse for the current branch. Returns `null` if none configured.

**What to change if:** You want multiple warehouses per branch selectable at POS → accept a `$warehouseId` parameter and find by that.

---

### `getSkuAvailableStock(int $skuId, ?Warehouse $warehouse): int`

```
available_stock = SUM(available_quantity - reserved_quantity)
                  FROM stock_balance
                  WHERE sku_id = $skuId
                  AND (warehouse_id = $warehouse->id OR warehouse is null)
```

Returns 0 if negative. Never returns negative number.

**What to change if:** You want to show stock across all branches combined → remove the warehouse filter.

---

### `getSkuSellingPrice(Sku $sku, ?Warehouse $warehouse): float`

**Priority order:**
1. Active branch price for this branch (from `branch_prices` table)
2. SKU's own `retail_price` (MRP)
3. 0.0 as fallback

**What to change if:**
- You want sale_price to be used instead of retail_price → check `$sku->sale_price > 0` before falling back to `retail_price`
- You want customer-group pricing → add a `$customer` parameter and check group prices first

---

### `deductSkuStockForSale(Sku $sku, Warehouse $warehouse, int $quantity, SalesOrderItem $item): void`

**The core FIFO deduction algorithm:**

1. **Lock batches** (SELECT FOR UPDATE to prevent race conditions):
   ```sql
   ORDER BY 
     CASE WHEN expiry_date IS NULL THEN 0 ELSE 1 END,  -- no-expiry first
     expiry_date ASC,                                    -- earliest expiry next
     received_at ASC                                     -- oldest received last
   ```

2. **For each batch** (until quantity is fully allocated):
   - Calculate `sellable = min(batch.available_quantity, stockBalance.available - stockBalance.reserved)`
   - Deduct `deducted = min(remaining_qty, sellable)` from both batch and stockBalance
   - Create `InventoryTransaction` record (type: `sale`)
   - Subtract `deducted` from `remaining_qty`

3. **If quantity remains** (all batches exhausted):
   - If warehouse allows negative stock → create a negative allocation with `batch_id = null`
   - Else → throw exception (prevents overselling)

4. **Box deduction** → calls `deductFromBoxes()` for box-level tracking

**What to change if:**
- You want LIFO instead of FIFO → change `ASC` to `DESC` in sort order (⚠️ impacts batch costing)
- You want to prevent negative stock always → remove the negative stock allowance block
- You want to allocate from a specific batch → add batch_id parameter and filter

---

### `restoreStockForSalesOrder(SalesOrder $order, string $movementType): void`

Used when an order is cancelled or returned. Reads `SalesOrderItem` records, finds which batches were used, and restores the quantities. Creates `InventoryTransaction` records with type = `$movementType` (e.g. `sale_return`).

---

## 7. LoyaltyService — Points Logic

**File:** `app/Services/LoyaltyService.php`

### `getSettings(): array`

Reads from `loyalty_settings` table. Returns array with defaults:
```php
[
    'enabled'              => false,
    'redemption_enabled'   => false,
    'points_per_amount'    => 10,    // spend ৳10 → earn 1 point
    'point_value'          => 0.50,  // 1 point = ৳0.50
    'membership_threshold' => 5000,  // spend ৳5000 total → become member
]
```

**What to change if:** You want new loyalty settings → add to the `loyalty_settings` table and return them here.

---

### `calculateEarnedPoints(float $paidAmount): int`

```php
return (int) floor($paidAmount / $settings['points_per_amount']);
```

Example: paid ৳150, points_per_amount = 10 → earn 15 points.

---

### `calculatePointDiscount(int $points): float`

```php
return round($points * $settings['point_value'], 2);
```

Example: 100 points, point_value = 0.50 → ৳50.00 discount.

---

### `canRedeemPoints(Customer $customer, int $points, float $payableAmount): array`

Returns `['can' => bool, 'error' => string|null]`.

Checks:
- Redemption is enabled in settings
- Customer has at least `$points` in balance
- Point discount does not exceed payable amount

---

### `applyEarnedPoints(Customer $customer, SalesOrder $order): void`

1. Calls `calculateEarnedPoints($order->paid_total)`
2. Increments `customer.loyalty_points` and `customer.lifetime_earned_points`
3. Creates `LoyaltyPointLedger` record (type: `earned`)
4. Updates `order.earned_points`

Only called for `Sale` and `Credit Sale` order types.

---

### `updateMembershipStatus(Customer $customer, float $paidAmount): void`

1. Increments `customer.total_purchase_amount` by `$paidAmount`
2. If `!is_member` AND new total >= threshold → sets `is_member = true`, `membership_started_at = now()`
3. Saves customer

---

## 8. ProductController — Full Reference

**File:** `app/Http/Controllers/ProductController.php`

### `show()`

Loads categories using `getCategoryOptions()` helper (returns hierarchical category tree with `level` attribute for indentation). Loads active brands. Returns `product.index` view.

---

### `list(Request $request)` — DataTables AJAX

**Filters accepted:**
- `search_query` → searches product name, SKU code, barcode
- `category_ids[]` → array, matches `category_id` OR product_categories pivot
- `brand_ids[]` → array, matches `brand_id`
- `product_type` → exact match on `product_type` column
- `status` → exact match on `status` column

**Columns returned:**
- `brand` → brand name or 'N/A'
- `type_label` → human-readable type (Standard/Variant/Combo/Service)
- `sku_summary` → first 2 SKU codes + "+N more"
- `thumbnail_image` → full URL
- `category` → parent/child category path or comma-separated categories
- `status` → 'Active' or 'Inactive'
- `raw_status` → lowercase status for JS comparison
- `dosage_form`, `strength`, `coating_type` → pharma fields (included automatically from model)

**What to change if:**
- You want a new column in the product list → add `->addColumn('column_name', fn($p) => ...)` and add to DataTables columns config in the JS

---

### `store(Request $request)` — Create Product

**Validation rules:**
- `name` required
- `retail_price` required, numeric (this is MRP on the SKU)
- `sku_code` required, unique in product_skus
- `barcode` nullable, unique in product_skus
- `sale_price` nullable, numeric, must be <= retail_price if provided
- `minimum_selling_price` nullable, must be <= sale_price

**For Single SKU products:**
1. Creates `Product` record with all product-level fields
2. Creates exactly 1 `Sku` record linked to the product
3. Syncs category (updates `product_categories` pivot if multi-category)
4. Handles product warnings
5. Handles thumbnail image upload via `ImageTrait`

**For Variation products:**
1. Creates `Product` with `product_type = 'variant_parent'`
2. Creates multiple `Sku` records from the variation data
3. Creates `VariationRelation` records linking SKU to variation values

**What to change if:**
- You want a new product-level field → add to `Product::$fillable` AND add to the store/update method
- You want a new SKU-level field → add to `Sku::$fillable` AND handle in store/update

---

### `update(Request $request, $productId)` — Update Product

Same as store but:
- Checks SKU uniqueness excluding the current SKU ID
- Can add/remove variation SKUs
- Syncs categories (deletes old pivot records, inserts new ones)
- Can delete/add product images

---

### `delete(Request $request)` — Delete Product

**Blocked if:**
- Product has any `SalesOrderItem` records (product was ever sold)
- Product has any `InventoryTransaction` records (product has stock history)
- Product has any `PurchaseOrderItem` records

If blocked → returns error JSON. If allowed → deletes product and all related SKUs, images.

---

## 9. PurchaseOrderController — Full Reference

**File:** `app/Http/Controllers/PurchaseOrderController.php`

### `skuSearch(Request $request)` — For PO SKU Autocomplete

**Returns up to 40 results** matching query against `sku_code`, `barcode`, product `name`.

**Filters:** Only SKUs where `track_stock = true` and product `status = 'Active'`.

**Response per item:**
```json
{
  "id": 12,
  "text": "A-Clox 500 (Capsule 500mg) — SKU001",
  "retail_price": 50.00,
  "mrp": 50.00
}
```

The `text` field uses `Sku::getDisplayNameAttribute()` which appends pharma info.

---

### `store(Request $request)` — Create Purchase Order

**Validated fields per item:**
```
items[].sku_id           required
items[].batch_no         nullable
items[].expiry_date      nullable, date
items[].quantity         required, integer, min 1
items[].bonus_qty        nullable, integer, min 0
items[].unit_cost        required, numeric (Trade Price)
items[].unit_price       nullable, numeric (Unit Price after VAT)
items[].vat_amount       nullable, numeric
items[].mrp              nullable, numeric
items[].sale_price       nullable, numeric
items[].carton_plan      nullable, JSON (carton/box config)
```

**After saving:** Calls `syncSupplierLedger()` to record the payable in supplier's account.

---

### `receiveStore(Request $request, $id)` — Receive Stock

**What it does:**
1. Loads the PO with items
2. For each item in request:
   - Creates `InventoryBatch` record with batch_no, expiry_date, quantity, cost_price
   - Finds or creates `StockBalance` record for this sku/warehouse/batch combination
   - Increments `available_quantity` in StockBalance
   - Creates `InventoryTransaction` (type: `purchase_receipt`)
   - Updates `received_quantity` on PurchaseOrderItem
3. Updates PO status:
   - All items fully received → `received`
   - Some items received → `partial`
4. Processes carton plan → creates `InventoryCarton` and `InventoryBox` records

---

### `delete(Request $request)` — Delete PO

**Blocked if:** Any PurchaseOrderItem has `received_quantity > 0`.

---

## 10. OrderController — Full Reference

**File:** `app/Http/Controllers/OrderController.php`

### `list(Request $request)` — DataTables for Orders

**Filters:**
- `search` → order_no, invoice_no, customer name/phone
- `status` → Sale, Credit Sale, Quotation, Draft, Suspend
- `payment_status` → paid, partial, unpaid
- `fulfillment_status` → pending, processing, shipped, delivered, cancelled
- `date_from`, `date_to` → order_date range
- `amount_from`, `amount_to` → grand_total range
- `customer_id` → specific customer
- `cashier_id` → specific cashier
- `branch_id` → specific branch (respects user's branch access)

---

### `directThermalPrint($id)` — Windows Thermal Printer

**How it works:**
1. Builds ESC/POS receipt bytes via `buildEscposReceipt()`
2. Converts to hex string
3. Uses PowerShell to send raw bytes to the default printer:
   ```powershell
   [System.IO.File]::WriteAllBytes("$printerPath", [byte[]]($hexData -split ' '))
   ```

**Warning:** This only works on Windows. For Linux deployment, this needs to be rewritten using a proper ESC/POS library or print spooler.

---

## 11. Models — Every Field Explained

### Product Model — `app/Models/Product.php`

| Field | Type | Purpose | Notes |
|---|---|---|---|
| `id` | PK | — | Auto-increment |
| `category_id` | FK | Primary category | Can also have multiple via `product_categories` pivot |
| `brand_id` | FK | Brand | Nullable |
| `unit_id` | FK | Unit of measure | e.g. Piece, Box |
| `tax_rule_id` | FK | Default tax rule | Nullable |
| `name` | string | Product name | Required, displayed everywhere |
| `sale_price` | decimal | Default POS selling price | If null, uses SKU retail_price |
| `default_discount_type` | enum | 'percent' or 'amount' | Applied at POS if no SKU-level discount |
| `default_discount_value` | decimal | Discount value | Percent (0-100) or fixed amount |
| `dosage_form` | string | e.g. Tablet, Capsule | Pharmacy field, shown in POS card |
| `strength` | string | e.g. 500mg | Pharmacy field, shown in POS card |
| `coating_type` | string | e.g. Film-coated | Pharmacy field, shown in POS card |
| `generic_name` | string | Active ingredient | Pharmacy field |
| `manufacturer_name` | string | Company name | Pharmacy field |
| `product_type` | string | standard/variant_parent/combo/service | Stored value |
| `thumbnail_image` | string | Image path | Relative path, URL generated in controller |
| `status` | string | 'Active' or 'Inactive' | Case-sensitive, must match exactly |
| `is_featured` | boolean | Featured product flag | Used in ecommerce |
| `is_popular` | boolean | Popular product flag | Used in ecommerce |
| `is_online_enabled` | boolean | Show on website | |
| `is_pos_enabled` | boolean | Show in POS | Currently not enforced in POS query |
| `seo_title` | string | SEO title | Accessed via `meta_title` accessor |
| `seo_description` | string | SEO description | Accessed via `meta_description` accessor |
| `long_description` | text | Full description | Accessed via `description` accessor |

**Accessors (virtual fields):**

| Accessor | Returns | Source |
|---|---|---|
| `type` | 'Variation'/'Single'/'Combo'/'Service' | Maps from `product_type` column |
| `type_label` | 'Variant'/'Standard'/'Combo'/'Service' | Different mapping from `product_type` |
| `description` | string | Reads `long_description` column |
| `meta_title` | string | Reads `seo_title` column |
| `meta_description` | string | Reads `seo_description` column |
| `selling_price` | float | `sale_price ?? base_price ?? 0` |
| `quantity` | int | `stock_quantity ?? 0` |

**Mutators:**
- `setTypeAttribute($value)` → saves 'Single' as 'standard', 'Variation' as 'variant_parent', etc.
- `setDescriptionAttribute($value)` → saves to `long_description` column

---

### Sku Model — `app/Models/Sku.php`

| Field | Type | Purpose | Notes |
|---|---|---|---|
| `product_id` | FK | Parent product | |
| `sku_code` | string | Unique stock code | Must be unique across all SKUs |
| `barcode` | string | Barcode | Nullable, must be unique if provided |
| `cost_price` | decimal | Purchase cost (TP) | Used for profit calculation |
| `retail_price` | decimal | MRP | **This is the MRP. Never create a separate mrp column.** |
| `sale_price` | decimal | SKU-level sale price | Overrides product.sale_price if set |
| `wholesale_price` | decimal | Wholesale price | Used for wholesale customers |
| `minimum_selling_price` | decimal | Floor price | Cannot sell below this |
| `online_price` | decimal | E-commerce price | |
| `discount_type` | enum | 'percent'/'amount' | SKU-level discount, overrides product default |
| `discount_value` | decimal | Discount amount | |
| `units_per_strip` | int | Units per pack | Default 1. Used for medicine sale unit calculation |
| `medicine_unit_price` | decimal | Price per pill | Auto-calculated or manually set |
| `dosage_details` | text | Instructions | Pharmacy field |
| `track_stock` | boolean | Enable inventory | If false, no stock deduction |
| `track_batch` | boolean | Enable batch | |
| `track_expiry` | boolean | Enable expiry | |
| `track_serial` | boolean | Enable serial | |
| `status` | enum | 'active'/'inactive' | Only active SKUs shown in POS |
| `weight` | decimal | Weight in grams | |
| `rating` | decimal | Average rating | |

**Key Accessors:**

| Accessor | Returns | Purpose |
|---|---|---|
| `product_code` | `sku_code` | Alias — used everywhere for display |
| `base_price` | `retail_price` | Alias for backwards compatibility |
| `display_name` | "Product (Form Strength)" | Used in PO SKU search dropdown |
| `medicine_unit_price_for_pos` | calculated | `retail_price / units_per_strip` or stored value |

---

### SalesOrder Model — `app/Models/SalesOrder.php`

| Field | Purpose | Notes |
|---|---|---|
| `order_no` | Unique order reference | Format: BRANCH-YYYYMMDD-SEQ |
| `invoice_no` | Invoice number | May differ from order_no |
| `status` | Sale type | Sale, Credit Sale, Quotation, Draft, Suspend |
| `payment_status` | paid/partial/unpaid | Calculated from paid vs grand total |
| `fulfillment_status` | pending/shipped/delivered | Updated manually |
| `sub_total` | Sum of line items | Before any deductions |
| `item_discount_total` | Sum of line discounts | From individual item discounts |
| `cart_discount_total` | Order-level discount | From applyConditions() |
| `tax_total` | VAT amount | |
| `shipping_fee` | Delivery charge | |
| `grand_total` | Final amount due | sub_total − discounts + tax + shipping |
| `paid_total` | Amount actually paid | Sum of Payment records |
| `due_total` | Remaining due | grand_total − paid_total |
| `earned_points` | Points given | Set by LoyaltyService |
| `redeemed_points` | Points used | Customer applied at checkout |
| `point_discount_amount` | Taka discount from points | |
| `sales_channel` | POS or ECOMMERCE | |

---

### Payment Model — `app/Models/Payment.php`

| Field | Purpose |
|---|---|
| `payment_direction` | 'in' (received) or 'out' (refunded) |
| `payment_purpose` | 'sale', 'purchase', 'refund', etc. |
| `payment_method` | cash, card, mobile_banking, bank, cheque, wallet |
| `amount` | Payment amount |
| `sales_order_id` | Linked order (nullable) |
| `customer_id` | Linked customer (nullable) |
| `account_id` | Which account received this money |
| `received_by` | Cashier user ID |

**Supported payment methods:**
`cash`, `card`, `bank_transfer`, `mobile_banking`, `wallet`, `cheque`

---

### StockBalance Model — `app/Models/StockBalance.php`

| Field | Purpose |
|---|---|
| `sku_id` | Which SKU |
| `warehouse_id` | Which warehouse |
| `batch_id` | Which batch (nullable for untracked) |
| `available_quantity` | Physically in stock |
| `reserved_quantity` | Reserved for pending orders |

**Available for sale = `available_quantity - reserved_quantity`**

If you see negative `available_quantity`, it means overselling occurred (allowed per warehouse config).

---

### InventoryBatch Model — `app/Models/InventoryBatch.php`

| Field | Purpose |
|---|---|
| `sku_id` | Which SKU |
| `warehouse_id` | Which warehouse |
| `purchase_order_item_id` | Source PO item |
| `batch_no` | Batch number from manufacturer |
| `expiry_date` | When this batch expires |
| `received_at` | When received into system |
| `received_quantity` | Original qty received |
| `available_quantity` | Remaining qty (decremented on sale) |
| `cost_price` | Cost at time of receipt |
| `mrp` | MRP at time of receipt |

**FIFO sort order:** No-expiry batches first, then earliest expiry, then earliest received_at.

---

## 12. Middleware — How Permissions Work

**File:** `app/Http/Middleware/EnsurePermission.php`

### How it works

```php
// Route definition
Route::get('/product/show', ...)->middleware('require.permission:purchases.manage');
```

1. Middleware receives the slug (`purchases.manage`)
2. Gets current user from `auth()->user()`
3. Gets current branch ID from session
4. Checks `user_branch_roles` table: does this user have a role in this branch that includes this permission?
5. Super-admin users bypass all checks
6. If check fails → `abort(403)`

### Adding a New Permission

1. Add to `app/Support/PermissionCatalog.php`:
   ```php
   'my_new_permission' => 'Description of what it does',
   ```
2. Apply to route: `->middleware('require.permission:my_new_permission')`
3. Go to Roles management UI and assign to appropriate roles

---

## 13. BranchContext — Multi-Branch Logic

**File:** `app/Support/BranchContext.php`

### How Branch Switching Works

1. On login → `ensureInitialized()` sets `session('current_branch_id')` to user's default branch
2. Every request → `InitializeBranchContext` middleware calls `ensureInitialized()`
3. User can switch branch via branch switcher in header
4. All queries that are branch-scoped call `BranchContext::currentBranchId()`

### `accessibleBranches(User $user): Collection`

Returns only branches where this user has at least one role. Super-admins get all branches.

**Used by:** Stock transfer forms, order filters, any report that needs branch filtering.

### `scopeToCurrentBranch(Builder $query, string $column = 'branch_id'): Builder`

```php
$query->where($column, BranchContext::currentBranchId(auth()->user()));
```

Use this in any query that should be filtered to the current branch.

### `resolveDefaultWarehouseForCurrentBranch(User $user): ?Warehouse`

Finds the warehouse linked to the current branch. Used by POS to know where stock comes from.

---

## 14. Frontend JavaScript Reference

### POS Page — `resources/views/pos/posJs.blade.php`

#### Key Global Variables

| Variable | Type | Purpose |
|---|---|---|
| `posSelectedCats` | array | Selected category IDs for filtering |
| `activeBrandId` | int/null | Selected brand ID |
| `activeProductQuery` | string | Current search text |
| `currentProductPage` | int | Current page for load-more |
| `lastProductPage` | int | Total pages available |
| `isProductLoading` | boolean | Prevents double-fetch |

#### `loadProducts(page, appendMode)`

```javascript
function loadProducts(page = 1, appendMode = false)
```

**Parameters:**
- `page` → page number to fetch
- `appendMode` → if `true`, appends to existing cards (Load More). If `false`, replaces.

**Sends to server:**
```javascript
{
    q: activeProductQuery,
    category_ids: posSelectedCats,  // array
    brand_id: activeBrandId,
    per_page: 20,
    page: page
}
```

**What to change if:**
- You want auto-search on type → change the debounce delay (currently 400ms)
- You want more products per page → change `per_page: 20` to a higher number
- You want to add a new filter → add it to the data object AND handle in `PosController::skuSearch()`

---

#### `renderProductCard(product)`

Takes a product object (from server JSON) and returns an HTML string for the card.

**Card sections:**
1. `.product-thumb` — image or "No Image" fallback
2. `.product-card-body`:
   - `.product-card-title` — product name
   - `.pos-pharma-sub` — "Dosage · Strength · Coating" (if any pharma fields exist)
   - `hr.pc-divider` — visual separator
   - `.product-card-meta` — SKU badge + category|brand
   - `.product-card-stats` — price + stock
   - `.product-card-footer` — Tap To Add + SKU count

**What to change if:**
- You want a new field on the card → add the HTML here AND add the field to `PosController::toCompactJson()`
- You want to change the card layout → edit the HTML template string

---

#### `openProductSelection(productId, skuId, isVariant, isMedicine)`

Called when a product card is clicked.

- If `skuId` is not null (single SKU or matched search) → directly adds to cart via `CartController::addToCart()`
- If `skuId` is null (multiple variants) → fetches `productDetails()` and opens variant picker modal

---

#### Product Filter — `.fdd` Dropdown Component

The custom dropdown component (no external library). CSS is in `layouts/partials/header.blade.php`.

**Key elements:**
- `.fdd` → container
- `.fdd-btn` → trigger button with count badge
- `.fdd-panel` → dropdown panel
- `.fdd-options` → scrollable option list
- `.fdd-option[data-value]` → individual option
- `.fdd-count` → shows number of selected items

**JavaScript:** Each `.fdd` component has its own IIFE (immediately invoked function expression) that handles:
- Click outside to close
- Search filtering within options
- Selection state (adds `.is-selected` class)
- Chip rendering in `.active-bar`

**If you want a new fdd filter:** Copy an existing fdd HTML block, change the IDs, and wire the `change` callback to trigger `loadData()` or `loadProducts()`.

---

### Product List Page — `resources/views/product/index.blade.php`

#### `loadData()`

Fetches from `ProductController::list()` (DataTables format) via AJAX. Sends:
```javascript
{
    search_query: $('#fS').val(),
    category_ids: sel.cats,
    brand_ids: sel.brands,
    product_type: $('#fT').val(),
    status: $('#fSt').val(),
    page: st.page,
    per_page: st.pp,
}
```

#### `renderRows(rows)`

Builds table HTML for each product row. Includes:
- Thumbnail image
- Product name + pharma subtitle (`.p-pharma`) + SKU code
- Category chips (`.c-chip`)
- Brand name
- Type badge (color-coded by type)
- Status badge (green active / red inactive)
- Action buttons (Edit, Purchase)

**What to change if:**
- You want a new column → add a `<td>` here AND add `<th>` in the table header HTML AND add the field in `ProductController::list()`

---

## 15. Change Impact Guide

### "If I change X, what breaks?"

---

#### Changing `retail_price` column name on `product_skus`

**Impact (HIGH):**
- `Sku` model accessor `getBasePriceAttribute()` breaks
- `Sku` model accessor `getMedicineUnitPriceForPosAttribute()` breaks
- `PosController::appendProductCardMeta()` — `$product->mrp` assignment breaks
- `PurchaseOrderController::skuSearch()` breaks
- All views showing "MRP" break
- **Fix:** Update model accessors, all controller references, all views

---

#### Changing `sale_price` on `products` table

**Impact (MEDIUM):**
- `Product` model `getSellingPriceAttribute()` breaks
- `PosController::toCompactJson()` — `product_sale_price` field breaks
- `CartController::addToCart()` — discount resolution breaks
- POS card JS price display breaks
- **Fix:** Update model, controller, and frontend JS

---

#### Changing `status` values on `products` (e.g. 'Active' → 'active')

**Impact (HIGH):**
- `PosController::buildPosProductQuery()` — `->where('status', 'Active')` breaks (case-sensitive)
- `ProductController::list()` — status filter breaks
- All views checking status break
- **Fix:** Change all hardcoded 'Active'/'Inactive' strings OR add a scope

---

#### Adding a new field to `product_skus`

**Steps required:**
1. Create migration
2. Add to `Sku::$fillable`
3. Handle in `ProductController::store()` and `update()`
4. Add to product create/edit views
5. If needed in POS → add to `PosController::buildPosProductQuery()` select and `toCompactJson()`
6. If needed in PO → add to `PurchaseOrderController::skuSearch()` response

---

#### Changing `darryldecode/cart` session structure

**Impact (CRITICAL):**
- Any existing sessions in production will have incompatible cart data
- `CartController` all methods break
- `OrderService::createOrder()` breaks (reads cart)
- **Fix:** Always `Cart::clear()` all existing sessions before deploying changes to cart structure

---

#### Adding a new payment method

**Steps:**
1. Add to `Payment` model's static method that lists payment methods
2. Add to POS checkout UI (payment method selector)
3. Add to `OrderController::list()` filter if needed
4. Add icon/label in the invoice view

---

#### Changing warehouse assignment logic

**File to change:** `PosInventoryService::resolveWarehouse()`

**Impact:** All stock deduction, stock lookup, and price resolution depends on the resolved warehouse. Test thoroughly — wrong warehouse = wrong stock levels shown.

---

#### Changing the loyalty points formula

**File:** `LoyaltyService::calculateEarnedPoints()` and `calculatePointDiscount()`

**Impact:**
- Only affects new sales (existing balances unchanged)
- If `points_per_amount` changes → existing customers have disproportionate balances
- **Recommendation:** Keep a history of rate changes in the settings table with effective dates

---

#### Changing FIFO to LIFO for stock deduction

**File:** `PosInventoryService::deductSkuStockForSale()`

**Change:** Reverse the ORDER BY in the batch query (change `ASC` to `DESC`).

**Impact:**
- Newest stock deducted first
- Older batches remain → may expire before use
- Affects cost calculations (newer batches may have different costs)
- **Not recommended** for pharmacy (expiry management requires FIFO)

---

#### Changing category filter to non-recursive

**File:** `PosController::getCategoryDescendantIds()` — return only `[$categoryId]` instead of recursive expansion

**Impact:**
- Selecting "Medicine" will no longer show subcategory products like "Antibiotic", "Antifungal"
- Much faster for large category trees

---

#### Adding a new filter to the POS product search

**Files to change:**
1. `pos/index.blade.php` → Add filter UI (fdd dropdown or select)
2. `pos/posJs.blade.php` → Add to `loadProducts()` data object
3. `PosController::skuSearch()` → Accept and apply new parameter
4. `PosController::buildPosProductQuery()` → Add `->when(...)` filter

---

#### Changing thermal printer format

**File:** `OrderController::buildEscposReceipt()`

**Impact:** Only affects thermal printing. A4 invoice (`invoice()`) is separate in `order/invoice.blade.php`.

**Windows only warning:** The `directThermalPrint()` method uses PowerShell. On Linux, rewrite to use CUPS or a print queue.

---

#### Adding a new product type (beyond standard/variant/combo/service)

**Files to change:**
1. `Product` model — add new constant and update `getTypeAttribute()`, `setTypeAttribute()`, `getTypeLabelAttribute()` match blocks
2. `ProductController::store()` — handle new type validation
3. POS card JS — handle new type in `openProductSelection()`
4. Product list JS — add new type color in `TYPE` object

---

## Appendix: Database Tables Quick Reference

| Table | Records | Purpose |
|---|---|---|
| `products` | Product catalogue | Master product data |
| `product_skus` | SKU variants | Pricing, stock tracking |
| `product_categories` | Pivot | Product ↔ Category many-to-many |
| `product_images` | Images | Multiple images per SKU |
| `sales_orders` | Orders | POS transaction header |
| `sales_order_items` | Line items | Products in each order |
| `payments` | Payments | Payment records per order |
| `purchase_orders` | PO header | Supplier purchase orders |
| `purchase_order_items` | PO lines | Items in each PO |
| `inventory_batches` | Batches | Batch-level stock with expiry |
| `stock_balance` | Stock levels | Current qty per SKU/warehouse/batch |
| `inventory_transactions` | Audit log | Every stock movement |
| `inventory_cartons` | Cartons | Carton-level packaging |
| `inventory_boxes` | Boxes | Box-level packaging |
| `customers` | Customers | CRM data + loyalty |
| `customer_ledgers` | AR ledger | Customer account history |
| `loyalty_point_ledgers` | Points log | Every point earn/redeem |
| `loyalty_settings` | Config | Loyalty programme rules |
| `suppliers` | Suppliers | Vendor master |
| `supplier_ledgers` | AP ledger | Supplier account history |
| `accounts` | Chart of accounts | Financial accounts |
| `accounting_journals` | Journals | Double-entry records |
| `branches` | Branches | Physical locations |
| `warehouses` | Warehouses | Stock locations |
| `branch_prices` | Prices | Branch-specific SKU pricing |
| `users` | Users | System users |
| `roles` | Roles | Permission groups |
| `permissions` | Permissions | Individual access rights |
| `user_branch_roles` | Access | User role per branch |
| `categories` | Categories | Product category tree |
| `brands` | Brands | Product brands |
| `units` | Units | Units of measure |
| `variations` | Variants | Variation types and values |
| `variation_relations` | Pivot | SKU ↔ Variation mapping |
| `settings` | Config | System settings |
