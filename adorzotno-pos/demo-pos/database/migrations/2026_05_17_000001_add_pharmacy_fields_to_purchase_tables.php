<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'invoice_no')) {
                $table->string('invoice_no', 100)->nullable()->after('purchase_no');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'bonus_qty')) {
                $table->unsignedInteger('bonus_qty')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('purchase_order_items', 'mrp')) {
                $table->decimal('mrp', 12, 2)->nullable()->after('unit_cost');
            }
            if (!Schema::hasColumn('purchase_order_items', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable()->after('mrp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_no']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['bonus_qty', 'mrp', 'sale_price']);
        });
    }
};
