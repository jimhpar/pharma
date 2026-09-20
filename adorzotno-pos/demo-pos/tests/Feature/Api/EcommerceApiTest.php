<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class EcommerceApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->resetTables();
    }

    public function test_coupon_endpoint_applies_a_valid_percentage_coupon(): void
    {
        DB::table('coupons')->insert([
            'code' => 'SPRING10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_amount' => 100,
            'max_discount_amount' => 50,
            'usage_limit' => 100,
            'used_count' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/cart/apply-coupon', [
            'coupon_code' => 'spring10',
            'total_amount' => 250,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.discount_amount', 25)
            ->assertJsonPath('data.final_total', 225)
            ->assertJsonPath('data.coupon.code', 'SPRING10');
    }

    public function test_coupon_endpoint_rejects_below_minimum_order_amount(): void
    {
        DB::table('coupons')->insert([
            'code' => 'SAVE50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_order_amount' => 500,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/cart/apply-coupon', [
            'coupon_code' => 'SAVE50',
            'total_amount' => 300,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Order total does not meet the coupon minimum amount');
    }

    public function test_shipping_info_endpoint_returns_active_zones_and_settings_values(): void
    {
        DB::table('settings')->insert([
            ['group' => 'shipping', 'key' => 'default_shipping_cost', 'value' => '80', 'type' => 'number', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '1500', 'type' => 'number', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('shipping_zones')->insert([
            ['name' => 'Dhaka', 'charge' => 60, 'status' => 'active'],
            ['name' => 'Chittagong', 'charge' => 120, 'status' => 'Active'],
            ['name' => 'Inactive Zone', 'charge' => 500, 'status' => 'inactive'],
        ]);

        $response = $this->getJson('/api/shipping-info');

        $response->assertOk()
            ->assertJsonPath('data.default_shipping_cost', 80)
            ->assertJsonPath('data.free_shipping_threshold', 1500)
            ->assertJsonCount(2, 'data.zones');
    }

    public function test_public_product_reviews_only_return_approved_reviews(): void
    {
        DB::table('products')->insert([
            'id' => 1,
            'category_id' => null,
            'brand_id' => null,
            'name' => 'Vitamin C',
            'slug' => 'vitamin-c',
            'sku' => 'VC-001',
            'description' => 'Immune support',
            'base_price' => 120,
            'sale_price' => 100,
            'stock_quantity' => 25,
            'status' => 'active',
            'is_online_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('reviews')->insert([
            ['customer_id' => 1, 'product_id' => 1, 'rating' => 5, 'comment' => 'Approved review', 'status' => 'approved', 'is_verified_purchase' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['customer_id' => 2, 'product_id' => 1, 'rating' => 1, 'comment' => 'Pending review', 'status' => 'pending', 'is_verified_purchase' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson('/api/products/1/reviews');

        $response->assertOk()
            ->assertJsonCount(1, 'data.reviews.data')
            ->assertJsonPath('data.reviews.data.0.comment', 'Approved review');
    }

    public function test_recommendations_use_trending_order_quantities_for_guests(): void
    {
        DB::table('products')->insert([
            [
                'id' => 1,
                'name' => 'Top Seller',
                'slug' => 'top-seller',
                'sku' => 'TOP-001',
                'base_price' => 500,
                'sale_price' => 450,
                'stock_quantity' => 20,
                'status' => 'active',
                'is_online_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Lower Seller',
                'slug' => 'lower-seller',
                'sku' => 'LOW-001',
                'base_price' => 200,
                'sale_price' => 180,
                'stock_quantity' => 20,
                'status' => 'active',
                'is_online_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('product_skus')->insert([
            ['id' => 11, 'product_id' => 1, 'sku_code' => 'TOP-001', 'product_code' => 'TOP-001', 'retail_price' => 500, 'online_price' => 450, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'product_id' => 2, 'sku_code' => 'LOW-001', 'product_code' => 'LOW-001', 'retail_price' => 200, 'online_price' => 180, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('sales_orders')->insert([
            'id' => 101,
            'branch_id' => 1,
            'order_no' => 'ORD-101',
            'order_date' => now(),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'fulfillment_status' => 'unfulfilled',
            'sub_total' => 0,
            'grand_total' => 0,
            'paid_total' => 0,
            'due_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_order_items')->insert([
            ['sales_order_id' => 101, 'sku_id' => 11, 'quantity' => 7, 'unit_price' => 450, 'line_total' => 3150, 'created_at' => now(), 'updated_at' => now()],
            ['sales_order_id' => 101, 'sku_id' => 22, 'quantity' => 2, 'unit_price' => 180, 'line_total' => 360, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson('/api/products/trending?limit=2');

        $response->assertOk()
            ->assertJsonPath('data.products.0.name', 'Top Seller')
            ->assertJsonPath('data.products.1.name', 'Lower Seller');
    }

    public function test_product_listing_excludes_inactive_products(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Visible Product',
                'slug' => 'visible-product',
                'sku' => 'VIS-001',
                'base_price' => 100,
                'sale_price' => 90,
                'stock_quantity' => 5,
                'status' => 'active',
                'is_online_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hidden Product',
                'slug' => 'hidden-product',
                'sku' => 'HID-001',
                'base_price' => 100,
                'sale_price' => 90,
                'stock_quantity' => 5,
                'status' => 'inactive',
                'is_online_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Visible Product');
    }

    private function createSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->rememberToken()->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('shipping_address')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('customer_code')->nullable();
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->decimal('current_due', 14, 2)->default(0);
                $table->unsignedInteger('loyalty_points')->default(0);
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('brand_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('sku')->nullable();
                $table->text('description')->nullable();
                $table->decimal('base_price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->string('status')->nullable();
                $table->boolean('is_online_enabled')->default(true);
                $table->boolean('is_popular')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('sku_id')->nullable();
                $table->string('image_path')->nullable();
                $table->string('alt_text')->nullable();
            });
        }

        if (!Schema::hasTable('product_skus')) {
            Schema::create('product_skus', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('sku_code')->nullable();
                $table->string('product_code')->nullable();
                $table->decimal('retail_price', 12, 2)->default(0);
                $table->decimal('online_price', 12, 2)->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->string('status')->nullable();
                $table->boolean('is_verified_purchase')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('discount_type', 30)->default('fixed');
                $table->decimal('discount_value', 12, 2);
                $table->decimal('min_order_amount', 12, 2)->nullable();
                $table->decimal('max_discount_amount', 12, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->string('status', 20)->default('active');
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('group', 50)->default('general');
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type', 30)->default('string');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('shipping_zones')) {
            Schema::create('shipping_zones', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('charge', 14, 2)->default(0);
                $table->string('status')->nullable();
            });
        }

        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('cashier_id')->nullable();
                $table->string('sales_channel')->nullable();
                $table->string('order_no')->unique();
                $table->string('invoice_no')->nullable();
                $table->dateTime('order_date');
                $table->string('status')->default('pending');
                $table->string('payment_status')->default('unpaid');
                $table->string('fulfillment_status')->default('unfulfilled');
                $table->decimal('sub_total', 14, 2)->default(0);
                $table->decimal('grand_total', 14, 2)->default(0);
                $table->decimal('paid_total', 14, 2)->default(0);
                $table->decimal('due_total', 14, 2)->default(0);
                $table->decimal('shipping_fee', 14, 2)->default(0);
                $table->text('customer_note')->nullable();
                $table->text('internal_note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sales_order_items')) {
            Schema::create('sales_order_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sales_order_id');
                $table->unsignedBigInteger('sku_id');
                $table->integer('quantity')->default(0);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('line_total', 14, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    private function resetTables(): void
    {
        foreach ([
            'sales_order_items',
            'sales_orders',
            'branches',
            'shipping_zones',
            'settings',
            'coupons',
            'reviews',
            'product_images',
            'product_skus',
            'products',
            'brands',
            'categories',
            'customers',
            'users',
        ] as $table) {
            DB::table($table)->delete();
        }
    }
}
