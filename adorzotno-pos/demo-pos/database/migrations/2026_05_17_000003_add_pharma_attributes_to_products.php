<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'dosage_form')) {
                $table->string('dosage_form', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'strength')) {
                $table->string('strength', 100)->nullable()->after('dosage_form');
            }
            if (!Schema::hasColumn('products', 'coating_type')) {
                $table->string('coating_type', 100)->nullable()->after('strength');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['dosage_form', 'strength', 'coating_type']);
        });
    }
};
