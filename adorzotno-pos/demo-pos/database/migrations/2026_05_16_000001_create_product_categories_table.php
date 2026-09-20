<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->primary(['product_id', 'category_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Seed from existing category_id so no data is lost
        $existing = DB::table('products')
            ->whereNotNull('category_id')
            ->select('id as product_id', 'category_id')
            ->get()
            ->map(fn ($r) => ['product_id' => $r->product_id, 'category_id' => $r->category_id])
            ->toArray();

        if (!empty($existing)) {
            DB::table('product_categories')->insertOrIgnore($existing);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
