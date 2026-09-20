<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('banners', 'banner_type')) {
            DB::statement("ALTER TABLE `banners` MODIFY `banner_type` enum('static','discount','free_delivery','campaign','slider','service') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'static'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banners', 'banner_type')) {
            DB::table('banners')
                ->whereIn('banner_type', ['slider', 'service'])
                ->update(['banner_type' => 'static']);

            DB::statement("ALTER TABLE `banners` MODIFY `banner_type` enum('static','discount','free_delivery','campaign') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'static'");
        }
    }
};
