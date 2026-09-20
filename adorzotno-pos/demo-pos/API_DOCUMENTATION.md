# Adorzotno Ecommerce API Documentation

## Base URL
```
https://adorzotno2.techcloudltd.work/api
http://localhost/api (local development)
```

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

Authentication is session-based. For browser and Next.js clients, send requests with credentials enabled so the Laravel session cookie is included.

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
    "customer": { /* linked customer profile */ }
  }
}
```

### POST /auth/login
Login with email and password (session-based).

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
    "user": { /* user object with customer relation */ }
  }
}
```

### POST /auth/logout
Logout and invalidate session (authenticated).

**Response:** `200 OK`

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
Update user profile (authenticated).

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "01700000001",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Response:** `200 OK`

---

## Products

### GET /products
Get paginated list of active, online-enabled products with filters and sorting.

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 20, max: 100)
- `category_id` (int): Filter by category
- `brand_id` (int): Filter by brand
- `min_price` (decimal): Minimum price filter
- `max_price` (decimal): Maximum price filter
- `in_stock` (bool): Filter only in-stock items
- `sort_by` (string): Sort field (default: created_at)
  - Options: `created_at`, `selling_price`, `name`, `quantity`
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

### GET /products/{id}
Get product details with images, category, brand, and approved reviews.

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Product retrieved successfully",
  "data": {
    "product": { /* full product object */ }
  }
}
```

### GET /products/search
Search products by name/description.

**Query Parameters:**
- `q` (string, required): Search query (minimum 2 characters)
- `category_id`, `brand_id`, `min_price`, `max_price`, `in_stock` (same as products list)
- `per_page` (int): Items per page

**Response:** `200 OK`

### GET /categories
Get all product categories.

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

### GET /categories/{id}/products
Get products in a specific category.

**Query Parameters:**
- `min_price`, `max_price`, `in_stock`, `sort_by`, `sort_order`, `per_page`

**Response:** `200 OK`

### GET /brands
Get all brands.

**Response:** `200 OK`

### GET /brands/{id}/products
Get products by brand.

**Response:** `200 OK`

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
    "total_amount": 100.00
  }
}
```

### GET /orders
Get user's orders (authenticated, paginated).

**Query Parameters:**
- `per_page` (int): Items per page (default: 10)

**Response:** `200 OK`

### GET /orders/{id}
Get specific order details (authenticated).

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Order retrieved successfully",
  "data": {
    "order": { /* order object with items */ }
  }
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
Get user's wishlist (public for guests, personalized for authenticated).

**Response:** `200 OK`
```json
{
  "status": "success",
  "message": "Wishlist retrieved successfully",
  "data": {
    "items": [ /* wishlist items */ ]
  }
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

### GET /products/{id}/reviews
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
Create product review (authenticated, requires purchase). New and updated reviews are stored with `pending` status for moderation.

**Request:**
```json
{
  "product_id": 1,
  "rating": 5,
  "comment": "Great product!"
}
```

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
  "rating": 4,
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

### GET /products/related/{id}
Get products related to a specific product, prioritizing same-category and similar-price items with storefront-safe fallbacks.

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

All API endpoints are rate-limited to **100 requests per minute** per IP address/session.

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

Make sure to include credentials in your CORS requests if using session-based authentication:
```javascript
fetch('https://adorzotno2.techcloudltd.work/api/auth/me', {
  credentials: 'include'
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

1. **Authentication Setup**: Configure session management and CSRF tokens
2. **Payment Gateway**: Integrate Bkash/Rocket or other payment methods
3. **Inventory Sync**: Set up real-time stock updates
4. **Analytics**: Track product views, recommendations, trending items
5. **Admin APIs**: Create separate admin endpoints for order/inventory management
