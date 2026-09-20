<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_cartons', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable()->after('available_units');
            $table->string('batch_no_label', 100)->nullable()->after('unit_cost');
            $table->date('expiry_date')->nullable()->after('batch_no_label');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_cartons', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'batch_no_label', 'expiry_date']);
        });
    }
};
