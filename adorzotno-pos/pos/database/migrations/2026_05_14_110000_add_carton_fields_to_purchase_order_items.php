<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedInteger('carton_qty')->nullable()->after('expiry_date');
            $table->unsignedInteger('boxes_per_carton')->nullable()->after('carton_qty');
            $table->unsignedInteger('units_per_box')->nullable()->after('boxes_per_carton');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['carton_qty', 'boxes_per_carton', 'units_per_box']);
        });
    }
};
