<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('current_stock')->default(0);
            $table->decimal('estimated_unit_cost', 12, 2)->nullable();
            $table->text('item_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_items');
    }
};
