<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reviews', 'rating')) {
            return;
        }

        DB::statement('ALTER TABLE `reviews` MODIFY `rating` DECIMAL(3,2) NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reviews', 'rating')) {
            return;
        }

        DB::statement('ALTER TABLE `reviews` MODIFY `rating` TINYINT NOT NULL');
    }
};
