<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product-level pricing & discount defaults
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'mrp')) {
                $table->decimal('mrp', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'default_discount_type')) {
                $table->enum('default_discount_type', ['percent', 'amount'])->nullable();
            }
            if (!Schema::hasColumn('products', 'default_discount_value')) {
                $table->decimal('default_discount_value', 10, 2)->nullable()->default(0);
            }
        });

        // SKU-level discount (per variant override)
        Schema::table('product_skus', function (Blueprint $table) {
            if (!Schema::hasColumn('product_skus', 'discount_type')) {
                $table->enum('discount_type', ['percent', 'amount'])->nullable();
            }
            if (!Schema::hasColumn('product_skus', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('product_skus', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable()->default(0);
            }
        });

        // Cart item-level discount (per sale override)
        Schema::table('cart_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_items', 'discount_type')) {
                $table->enum('discount_type', ['percent', 'amount'])->nullable();
            }
            if (!Schema::hasColumn('cart_items', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('cart_items', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable()->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['mrp', 'sale_price', 'default_discount_type', 'default_discount_value']);
        });
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });
    }
};
