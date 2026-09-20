# Adorzotno Admin Database Design

This admin database is structured for a typical electronics e-commerce workflow.

## Core areas

- `users`: admin users for dashboard access, with `role`, `status`, and `last_login_at`.
- `brands`, `categories`, `tags`, `products`, `product_images`, `product_variants`, `product_tag`: catalog management.
- `customers`, `customer_addresses`, `wishlists`, `reviews`: customer accounts and engagement.
- `orders`, `order_items`, `payments`, `shipments`: checkout, payment tracking, and fulfillment.
- `coupons`, `banners`, `pages`, `settings`: promotions, homepage content, CMS pages, and site configuration.

## Relationship summary

- A category can have a parent category.
- A product belongs to one category and one brand.
- A product can have many images, variants, and tags.
- A customer can have many addresses, wishlist items, reviews, and orders.
- An order belongs to a customer and can reference billing and shipping addresses.
- An order has many items, payments, and shipments.

## Suggested next step

Run the admin app migrations:

```bash
cd admin
php artisan migrate --seed
```
