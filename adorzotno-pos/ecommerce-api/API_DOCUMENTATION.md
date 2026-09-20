# Adorzotno Ecommerce API Documentation

## Environment Configuration

**This API uses Sanctum token-based authentication.**

### Base URL

Replace `{your-domain}` with your actual domain:

```
Production: https://{your-domain}/api
Local Development: http://localhost:8000/api
```

### CORS Configuration

The API restricts requests to whitelisted origins (see `CORS_ALLOWED_ORIGINS` in `.env`).

**Local Development Default:**
- `http://localhost:3000` (React, Vue, Next.js dev server)

**Update for Your Frontend:**

In `.env`, set `CORS_ALLOWED_ORIGINS`:
```
# Local
CORS_ALLOWED_ORIGINS=localhost:3000,localhost:5173,http://localhost:8080

# Production
CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com
```

---

## Response Format
All API responses follow a standardized format:

```json
{
  "status": "success|error",
  "message": "Response message",
  "data": { /* response data */ },
  "errors": { /* validation or error details */ }
}
```

### Success Response (HTTP 200-201)
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": { /* data object */ },
  "errors": null
}
```

### Error Response (HTTP 400-500)
```json
{
  "status": "error",
  "message": "Error description",
  "data": null,
  "errors": { /* error details */ }
}
```

---

## Authentication

This API uses **Sanctum token-based authentication**. Unlike session-based auth, you must include an authentication token with each request.

### Authentication Flow

1. **Register**: Create a new account via `/auth/register`
2. **Login**: Authenticate via `/auth/login` → receive a `token`
3. **Use Token**: Include `Authorization: Bearer {token}` header in all authenticated requests
4. **Change Password**: Use `/auth/change-password` with current, new, and confirm fields when needed
5. **Logout**: Revoke token via `/auth/logout`

### Sending Authenticated Requests

Include the token in the `Authorization` header:

```
Authorization: Bearer YOUR_API_TOKEN_HERE
```

**Example (curl):**
```bash
curl -X GET https://api.example.com/api/orders \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**Example (JavaScript/Fetch):**
```javascript
const token = 'YOUR_API_TOKEN_HERE';

fetch('https://api.example.com/api/orders', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
```

---

## Endpoints

### POST /auth/register
Register a new customer account.

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "01700000000"
}
```

**Response:** `201 Created`
```json
{
  "status": "success",
  "message": "Registration successful",
  "data": {
    "user": { /* user object */ },
    "customer": { /* linked customer profile */ },
    "token": "1|v1xZyAbCdEf..."
  }
}
```

### POST /auth/login
Login with email and password to get an API token.

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": { /* user object with customer relation */ },
    "token": "1|v1xZyAbCdEf..."
  }
}
```

**Usage**: Store the returned `token` and include it in the `Authorization: Bearer {token}` header for subsequent authenticated requests.

### POST /auth/logout
Revoke the current authentication token (authenticated).

**Headers:**
```
Authorization: Bearer YOUR_API_TOKEN_HERE
```

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Logout successful"
}
```

### GET /auth/me
Get current authenticated user profile.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Profile retrieved successfully",
  "data": {
    "user": { /* user object */ }
  }
}
```

### POST /auth/update-profile
Update user profile and linked customer addresses (authenticated). This endpoint does not update passwords.

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "01700000001",
  "billing_address": "House 12, Road 5, Dhanmondi, Dhaka",
  "shipping_address": "House 12, Road 5, Dhanmondi, Dhaka"
}
```

**Notes:**
- `billing_address` and `shipping_address` are nullable.
- Address fields are stored on the linked `customer` record when the authenticated user has one.
- Password fields are ignored by this endpoint; use `/auth/change-password`.

**Response:** `200 OK`

### POST /auth/change-password
Change current authenticated user's password.

**Headers:**
```
Authorization: Bearer YOUR_API_TOKEN_HERE
```

**Request:**
```json
{
  "current": "password123",
  "new": "newpassword123",
  "confirm": "newpassword123"
}
```

**Aliases Supported:**
- `current_password` can be used instead of `current`
- `new_password` can be used instead of `new`
- `new_password_confirmation` can be used instead of `confirm`

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Password changed successfully",
  "data": null
}
```

---

## Products

### GET /products
Get paginated list of active, online-enabled products with filters and sorting.

Product payloads include:
- `product_images`
- `selling_price`, calculated as the lowest loaded SKU price using `online_price` first, then `retail_price`
- `sku` with SKU images, `selling_price`, stock summaries, and fields such as `rating` and `dosage_details`
- `stock`, `available_stock`, and `in_stock` computed from live SKU stock balances
- active `product_warnings`
- `category` and `categories`, including optional `image`
- `brand`, including brand profile fields, `brand_tags`, and `brand_certifications`
- flags such as `is_featured`, `is_flash_deals`, `is_popular`, `is_online_enabled`

Stock payloads are calculated from `stock_balances` across all loaded SKUs:

```json
{
  "selling_price": 95,
  "stock": {
    "total_quantity": 25,
    "reserved_quantity": 3,
    "available_quantity": 22,
    "in_stock": true
  },
  "available_stock": 22,
  "in_stock": true,
  "sku": [
    {
      "id": 1,
      "sku_code": "SKU-001",
      "selling_price": 95,
      "stock": {
        "track_stock": true,
        "total_quantity": 25,
        "reserved_quantity": 3,
        "available_quantity": 22,
        "in_stock": true
      },
      "available_stock": 22,
      "in_stock": true
    }
  ]
}
```

`available_quantity` is the sellable quantity: `available_quantity - reserved_quantity`, clamped at zero per stock balance before summing.

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 20, max: 100)
- `category_slug` (string): Filter by category slug
- `brand_slug` (string): Filter by brand slug
- `category_id` (int): Legacy category filter
- `brand_id` (int): Legacy brand filter
- `min_price` (decimal): Minimum price filter
- `max_price` (decimal): Maximum price filter
- `in_stock` (bool): Filter only products with live available stock
- `sort_by` (string): Sort field (default: created_at)
  - Options: `created_at`, `selling_price`, `name`, `quantity`
  - `quantity` sorts by live available stock, not the legacy `products.stock_quantity` column
- `sort_order` (string): Sort direction (default: desc)
  - Options: `asc`, `desc`

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Products retrieved successfully",
  "data": {
    "data": [ /* products array */ ],
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

### GET /products/{slug}
Get product details with images, category, brand, SKU details, stock summaries, active product warnings, and approved reviews.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Product retrieved successfully",
  "data": {
    "product": {
      "id": 1,
      "name": "Paracetamol 500mg",
      "selling_price": 95,
      "sku": [
        {
          "id": 1,
          "sku_code": "SKU-001",
          "selling_price": 95,
          "stock": {
            "track_stock": true,
            "total_quantity": 25,
            "reserved_quantity": 3,
            "available_quantity": 22,
            "in_stock": true
          },
          "available_stock": 22,
          "in_stock": true,
          "rating": "4.50",
          "dosage_details": "Take 1 tablet after meal",
          "images": []
        }
      ],
      "stock": {
        "total_quantity": 25,
        "reserved_quantity": 3,
        "available_quantity": 22,
        "in_stock": true
      },
      "available_stock": 22,
      "in_stock": true,
      "product_warnings": [
        {
          "id": 1,
          "warning": "Consult a doctor before use",
          "status": "active"
        }
      ],
      "brand": {
        "id": 1,
        "name": "Acme Pharma",
        "background_image": "/brandLogo/background.jpg",
        "rating": "4.80",
        "products_count": 120,
        "reviews_count": 340,
        "description": "Brand overview",
        "founded_year": 1998,
        "headquarter_address": "Dhaka, Bangladesh",
        "employees_count": 500,
        "is_verified": true,
        "brand_tags": [],
        "brand_certifications": []
      }
    }
  }
}
```

### GET /products/search
Search products by name/description.

**Query Parameters:**
- `q` (string, required): Search query (minimum 2 characters)
- `category_slug`, `brand_slug`, `category_id`, `brand_id`, `min_price`, `max_price`, `in_stock` (same as products list)
- `per_page` (int): Items per page

**Response:** `200 OK`

### GET /products/featured
Get paginated featured products.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `sort_by`, `sort_order`, `per_page`

**Response:** `200 OK`

### GET /products/flash-deals
Get paginated flash deal products where `is_flash_deals` is enabled.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `sort_by`, `sort_order`, `per_page`

**Response:** `200 OK`

### GET /products/product-type/{product_type}
Get paginated products by `product_type`.

Example values: `standard`, `variant_parent`, `combo`, `service`.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `sort_by`, `sort_order`, `per_page`

**Response:** `200 OK`

### GET /products/generic-name/{generic_name}
Get paginated products by exact `generic_name`. URL-encode names that contain spaces.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `sort_by`, `sort_order`, `per_page`

**Response:** `200 OK`

### GET /categories
Get all product categories. Category records may include both `icon` and `image`.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Categories retrieved successfully",
  "data": {
    "categories": [ /* categories array */ ]
  }
}
```

### GET /categories/{slug}/products
Get products in a specific category by category slug.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `sort_by`, `sort_order`, `per_page`

**Response:** `200 OK`

### GET /brands
Get all brands. Brand records include profile fields, active `brand_tags`, and active `brand_certifications`.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Brands retrieved successfully",
  "data": {
    "brands": [
      {
        "id": 1,
        "name": "Acme Pharma",
        "logo": "/brandLogo/logo.jpg",
        "background_image": "/brandLogo/background.jpg",
        "rating": "4.80",
        "products_count": 120,
        "reviews_count": 340,
        "description": "Brand overview",
        "founded_year": 1998,
        "headquarter_address": "Dhaka, Bangladesh",
        "employees_count": 500,
        "is_verified": true,
        "brand_tags": [
          {
            "id": 1,
            "brand_id": 1,
            "tag": "Dermatologist tested",
            "status": "active"
          }
        ],
        "brand_certifications": [
          {
            "id": 1,
            "brand_id": 1,
            "certification": "GMP",
            "status": "active"
          }
        ]
      }
    ]
  }
}
```

### GET /brands/{slug}/products
Get products by brand slug. The `brand` object includes profile fields, active `brand_tags`, and active `brand_certifications`; product records include SKU details and active warnings.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `per_page`
- `sort_by` (string): Sort field
  - `most_popular`: products with `is_popular = true` first
  - `price_high_to_low`: highest SKU price first, using `product_skus.online_price` then `retail_price` when online price is null
  - `price_low_to_high`: lowest SKU price first, using `product_skus.online_price` then `retail_price` when online price is null
  - `highest_rated`: highest SKU `rating` first
  - Existing generic options also work: `created_at`, `selling_price`, `name`, `quantity`
- `sort_order` (string): Used by generic sort options only (`asc` or `desc`)

**Response:** `200 OK`

---

## Banners

### GET /banners
Get active banners.

**Query Parameters:**
- `banner_type` (string): Optional filter. Allowed values: `slider`, `homepage_middle_1`, `homepage_middle_2`
- `position` (string): Optional filter. Allowed values: `small_top`, `small_bottom`, `large`. Only applies to `homepage_middle_1` and `homepage_middle_2`; slider banners have `position: null`.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Banners retrieved successfully",
  "data": {
    "banners": [ /* banners array */ ]
  }
}
```

### GET /banners/{id}
Get one active banner by ID.

**Response:** `200 OK`

---

## FAQs

### GET /faqs
Get all frequently asked questions.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "FAQs retrieved successfully",
  "data": {
    "faqs": [
      {
        "id": 1,
        "question": "How do I place an order?",
        "answer": "Add products to cart and complete checkout."
      }
    ]
  },
  "errors": null
}
```

### GET /faqs/{id}
Get one FAQ by ID.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "FAQ retrieved successfully",
  "data": {
    "faq": {
      "id": 1,
      "question": "How do I place an order?",
      "answer": "Add products to cart and complete checkout."
    }
  },
  "errors": null
}
```

**Error Response:** `404 Not Found`
```json
{
  "status": "error",
  "message": "FAQ not found",
  "data": null,
  "errors": null
}
```

---

## Shopping Cart

### POST /cart/sync
Validate cart items and check stock availability. 

**Request:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 5,
      "quantity": 1
    }
  ]
}
```

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Cart synced successfully",
  "data": {
    "valid_items": [
      {
        "product_id": 1,
        "product_name": "Paracetamol 500mg",
        "quantity": 2,
        "price": 8.99,
        "item_total": 17.98,
        "image": "/productImage/..."
      }
    ],
    "total": 17.98,
    "errors": [ /* array of errors for invalid items */ ]
  }
}
```

### POST /cart/apply-coupon
Validate and apply coupon code to cart.

**Request:**
```json
{
  "coupon_code": "SAVE10",
  "total_amount": 100.00
}
```

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Coupon applied successfully",
  "data": {
    "coupon": {
      "id": 1,
      "code": "SAVE10",
      "discount_type": "percent",
      "discount_value": 10,
      "min_order_amount": 100,
      "max_discount_amount": 100,
      "usage_limit": 100,
      "used_count": 0,
      "status": "active"
    },
    "discount_amount": 10.00,
    "discount_percentage": 10,
    "final_total": 90.00
  }
}
```

---

## Orders

### POST /orders/checkout
Create order from cart (authenticated).

`shipment_zone_id` is optional. When provided, it must be an active shipping zone and checkout uses that zone's `charge` as the order `shipping_fee`; the fee is included in `grand_total`, `due_total`, and the returned `total_amount`.

`coupon_code` is optional. When provided, checkout revalidates the coupon against the server-calculated cart subtotal, stores the coupon usage on the order, and applies the coupon amount to `cart_discount_total`.

Checkout deducts item quantities from the active online branch warehouse using live `stock_balances`/batch inventory and records `inventory_transactions` with movement type `sale`. Each order item stores the allocated `warehouse_id` and `batch_id` when batch stock is used.

**Request:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "shipping_address": "123 Main St, Dhaka",
  "phone": "01700000000",
  "coupon_code": "SAVE10",
  "shipment_zone_id": 1,
  "notes": "Please deliver in the morning"
}
```

**Response:** `201 Created`
```json
{
  "status": "success",
  "message": "Order created successfully",
  "data": {
    "order": { /* order object */ },
    "order_number": "ORD-20260505123456-1",
    "sub_total": 100.00,
    "discount_amount": 10.00,
    "coupon": {
      "id": 1,
      "code": "SAVE10",
      "discount_type": "percent",
      "discount_value": 10,
      "discount_amount": 10.00
    },
    "shipping_fee": 60.00,
    "total_amount": 150.00
  }
}
```

### GET /orders
Get user's orders (authenticated, paginated).

Order list items include the same decorated product/SKU pricing fields used by product list/detail responses. Each order item includes `sku.selling_price`, and the nested `sku.product` includes `selling_price`.

**Query Parameters:**
- `per_page` (int): Items per page (default: 10)

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Orders retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "order_no": "ORD-20260505123456-1",
        "items": [
          {
            "id": 1,
            "sku_id": 1,
            "quantity": 2,
            "unit_price": "95.00",
            "sku": {
              "id": 1,
              "sku_code": "SKU-001",
              "selling_price": 95,
              "product": {
                "id": 1,
                "name": "Paracetamol 500mg",
                "selling_price": 95
              }
            }
          }
        ]
      }
    ],
    "current_page": 1,
    "per_page": 10,
    "total": 1
  },
  "errors": null
}
```

### GET /orders/{id}
Get specific order details (authenticated).

Order details include the same decorated `sku.selling_price` and nested `sku.product.selling_price` fields as the order list response.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Order retrieved successfully",
  "data": {
    "order": {
      "id": 1,
      "order_no": "ORD-20260505123456-1",
      "items": [
        {
          "id": 1,
          "sku_id": 1,
          "quantity": 2,
          "unit_price": "95.00",
          "sku": {
            "id": 1,
            "sku_code": "SKU-001",
            "selling_price": 95,
            "product": {
              "id": 1,
              "name": "Paracetamol 500mg",
              "selling_price": 95
            }
          }
        }
      ]
    }
  },
  "errors": null
}
```

### GET /orders/{id}/invoice
Get order invoice (authenticated).

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Invoice retrieved successfully",
  "data": {
    "order": { /* order object */ },
    "invoice": { /* invoice summary payload */ },
    "invoice_url": null
  }
}
```

### PUT /orders/{id}/cancel
Cancel a pending order (authenticated).

Cancelling a pending online order restores the exact quantities allocated at checkout back to `stock_balances` and batch inventory, and records `inventory_transactions` with movement type `sale_cancel`.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Order cancelled successfully",
  "data": {
    "order": { /* order object */ }
  }
}
```

---

## Wishlist

### GET /wishlist
Get authenticated user's wishlist. Requires `Authorization: Bearer {token}`.

Wishlist items include the same decorated SKU/product pricing fields used by product list/detail responses. Each item includes `sku.selling_price`, and the nested `sku.product` includes `selling_price`.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Wishlist retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "customer_id": 1,
        "sku_id": 1,
        "sku": {
          "id": 1,
          "sku_code": "SKU-001",
          "selling_price": 95,
          "product": {
            "id": 1,
            "name": "Paracetamol 500mg",
            "selling_price": 95
          }
        }
      }
    ]
  },
  "errors": null
}
```

### POST /wishlist/add
Add product to wishlist (authenticated).

**Request:**
```json
{
  "sku_id": 1
}
```

**Response:** `201 Created`

### DELETE /wishlist/{sku_id}
Remove item from wishlist by SKU (authenticated).

**Response:** `200 OK`

---

## Reviews

### GET /products/{slug}/reviews
Get approved product reviews only (paginated).

**Query Parameters:**
- `per_page` (int): Items per page (default: 10)

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Reviews retrieved successfully",
  "data": {
    "product": { /* product object */ },
    "reviews": {
      "data": [ /* reviews array */ ],
      "current_page": 1,
      "per_page": 10,
      "total": 25
    }
  }
}
```

### POST /reviews
Create product review (authenticated, requires purchase). New and updated reviews are stored with `pending` status for moderation. `rating` accepts numeric values from `1` to `5`, including decimal values such as `3.5` or `4.5`.

**Request:**
```json
{
  "product_slug": "paracetamol-500mg",
  "rating": 4.5,
  "comment": "Great product!"
}
```

`product_id` is still accepted for backward compatibility, but new clients should send `product_slug`.

**Response:** `201 Created`
```json
{
  "status": "success",
  "message": "Review created successfully",
  "data": {
    "review": { /* review object */ }
  }
}
```

### PUT /reviews/{id}
Update review (authenticated, own review only).

**Request:**
```json
{
  "rating": 3.5,
  "comment": "Good product overall"
}
```

**Response:** `200 OK`

### DELETE /reviews/{id}
Delete review (authenticated, own review only).

**Response:** `200 OK`

---

## Promotions

### GET /promotions
Get active promotions.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Promotions retrieved successfully",
  "data": {
    "promotions": [ /* promotions array */ ]
  }
}
```

---

## Recommendations

### GET /recommendations
Get personalized recommendations for authenticated customers based on purchase categories. Guests receive trending products.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Recommendations retrieved successfully",
  "data": {
    "recommendations": [ /* products array */ ]
  }
}
```

### GET /products/trending
Get trending products ranked by sold quantity when order history exists, otherwise newest popular storefront products.

**Query Parameters:**
- `limit` (int): Number of products (default: 10, max: 50)

**Response:** `200 OK`

### GET /products/related/{slug}
Get products related to a specific product slug, prioritizing same-category and similar-price items with storefront-safe fallbacks.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Related products retrieved successfully",
  "data": {
    "product": { /* product object */ },
    "related": [ /* related products array */ ]
  }
}
```

---

## Settings

### GET /settings
Get store settings and information.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Settings retrieved successfully",
  "data": {
    "store_name": "Adorzotno",
    "store_email": "support@adorzotno.health",
    "store_phone": "+1-800-ADORZOTNO",
    "store_address": "...",
    "store_hours": "9:00 AM - 9:00 PM",
    "currency": "BDT",
    "currency_symbol": "৳"
  }
}
```

### GET /shipping-info
Get shipping information and zones.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Shipping information retrieved successfully",
  "data": {
    "default_shipping_cost": 80,
    "free_shipping_threshold": 1500,
    "zones": [
      {
        "id": 1,
        "name": "Dhaka",
        "charge": 60,
        "status": "active"
      }
    ]
  }
}
```

### GET /shipment-zones
Get active shipment zones only.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Shipment zones retrieved successfully",
  "data": {
    "shipment_zones": [
      {
        "id": 1,
        "name": "Dhaka",
        "charge": 60,
        "status": "active"
      }
    ]
  },
  "errors": null
}
```

---

## Error Codes

| Status Code | Meaning |
|-----------|---------|
| 200 | OK - Request succeeded |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Authentication required or failed |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded (100/min) |
| 500 | Server Error - Internal server error |

---

## Rate Limiting

All API endpoints are rate-limited to **100 requests per minute** per IP address/token.

When rate limit is exceeded, you'll receive:
```
HTTP 429 Too Many Requests
```

---

## CORS

The API accepts requests from:
- `https://adorzotno2.techcloudltd.work`
- `http://localhost:3000`
- `http://127.0.0.1:3000`

For authenticated browser requests, send the Sanctum bearer token in the `Authorization` header:
```javascript
fetch('https://adorzotno2.techcloudltd.work/api/auth/me', {
  headers: {
    Authorization: `Bearer ${token}`
  }
})
```

---

## Testing

### Postman Collection
Import the API collection into Postman for easy testing.

### Sample cURL Requests

**Register:**
```bash
curl -X POST https://adorzotno2.techcloudltd.work/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Get Products:**
```bash
curl https://adorzotno2.techcloudltd.work/api/products?page=1&per_page=10
```

**Sync Cart:**
```bash
curl -X POST https://adorzotno2.techcloudltd.work/api/cart/sync \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id": 1, "quantity": 2}
    ]
  }'
```

---

## Next Steps

1. **Authentication Setup**: Store Sanctum bearer tokens securely in your client
2. **Payment Gateway**: Integrate Bkash/Rocket or other payment methods
3. **Inventory Sync**: Set up real-time stock updates
4. **Analytics**: Track product views, recommendations, trending items
5. **Admin APIs**: Create separate admin endpoints for order/inventory management
