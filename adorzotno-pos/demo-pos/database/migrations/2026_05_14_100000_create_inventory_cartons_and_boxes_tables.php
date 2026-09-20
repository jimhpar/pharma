<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cartons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('carton_code', 100)->unique();
            $table->unsignedInteger('boxes_per_carton');
            $table->unsignedInteger('units_per_box');
            $table->unsignedInteger('total_units');
            $table->unsignedInteger('available_units');
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sku_id', 'warehouse_id']);
        });

        Schema::create('inventory_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carton_id')->constrained('inventory_cartons')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->string('box_code', 100)->unique();
            $table->unsignedSmallInteger('box_number');
            $table->unsignedInteger('units_received');
            $table->unsignedInteger('units_available');
            $table->unsignedInteger('units_sold')->default(0);
            $table->enum('status', ['sealed', 'open', 'empty'])->default('sealed');
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->index(['sku_id', 'status']);
            $table->index('carton_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_boxes');
        Schema::dropIfExists('inventory_cartons');
    }
};
