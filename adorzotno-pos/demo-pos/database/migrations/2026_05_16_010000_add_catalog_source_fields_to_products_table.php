<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('generic_name')->nullable()->after('name');
            $table->string('manufacturer_name')->nullable()->after('generic_name');
            $table->string('source_external_id')->nullable()->after('manufacturer_name')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['source_external_id']);
            $table->dropColumn(['generic_name', 'manufacturer_name', 'source_external_id']);
        });
    }
};
