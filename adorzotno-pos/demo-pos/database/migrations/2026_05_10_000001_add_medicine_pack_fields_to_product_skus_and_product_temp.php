<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->unsignedInteger('units_per_strip')->default(1)->after('weight');
            $table->decimal('medicine_unit_price', 12, 2)->nullable()->after('units_per_strip');
        });

        Schema::table('product_temp', function (Blueprint $table) {
            $table->unsignedInteger('variation_units_per_strip')->default(1)->after('weight');
            $table->decimal('variation_medicine_unit_price', 12, 2)->nullable()->after('variation_units_per_strip');
        });
    }

    public function down(): void
    {
        Schema::table('product_temp', function (Blueprint $table) {
            $table->dropColumn(['variation_units_per_strip', 'variation_medicine_unit_price']);
        });

        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn(['units_per_strip', 'medicine_unit_price']);
        });
    }
};
