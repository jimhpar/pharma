<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('banners', 'banner_type')) {
            DB::table('banners')
                ->whereNotIn('banner_type', ['slider', 'homepage_middle_1', 'homepage_middle_2'])
                ->update(['banner_type' => 'slider']);

            DB::statement("ALTER TABLE `banners` MODIFY `banner_type` enum('slider','homepage_middle_1','homepage_middle_2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slider'");
        }

        if (! Schema::hasColumn('banners', 'position')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->enum('position', ['small_top', 'small_bottom', 'large'])->nullable()->after('banner_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banners', 'position')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }

        if (Schema::hasColumn('banners', 'banner_type')) {
            DB::statement("ALTER TABLE `banners` MODIFY `banner_type` enum('static','discount','free_delivery','campaign','slider','service') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'static'");
        }
    }
};
