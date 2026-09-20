<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('category_promotion', 'sort_order')) {
            Schema::table('category_promotion', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('category_promotion', 'sort_order')) {
            Schema::table('category_promotion', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
