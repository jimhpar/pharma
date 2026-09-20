<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('icon');
        });

        Schema::table('product_skus', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->nullable()->after('medicine_unit_price');
            $table->text('dosage_details')->nullable()->after('rating');
        });

        Schema::table('product_temp', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->nullable()->after('variation_medicine_unit_price');
            $table->text('dosage_details')->nullable()->after('rating');
        });

        Schema::create('product_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->text('warning');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('background_image')->nullable()->after('logo');
            $table->decimal('rating', 3, 2)->nullable()->after('background_image');
            $table->unsignedInteger('products_count')->default(0)->after('rating');
            $table->unsignedInteger('reviews_count')->default(0)->after('products_count');
            $table->text('description')->nullable()->after('reviews_count');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('description');
            $table->text('headquarter_address')->nullable()->after('founded_year');
            $table->unsignedInteger('employees_count')->nullable()->after('headquarter_address');
            $table->boolean('is_verified')->default(false)->after('employees_count');
        });

        Schema::create('brand_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('tag');
            $table->enum('status', ['active', 'inactive'])->default('active');
        });

        Schema::create('brand_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('certification');
            $table->enum('status', ['active', 'inactive'])->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_certifications');
        Schema::dropIfExists('brand_tags');
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                'background_image',
                'rating',
                'products_count',
                'reviews_count',
                'description',
                'founded_year',
                'headquarter_address',
                'employees_count',
                'is_verified',
            ]);
        });
        Schema::dropIfExists('product_warnings');
        Schema::table('product_temp', function (Blueprint $table) {
            $table->dropColumn(['rating', 'dosage_details']);
        });
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn(['rating', 'dosage_details']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
