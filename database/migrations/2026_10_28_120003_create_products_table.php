<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 200);
            $table->string('sku', 50)->unique();
            $table->string('brand', 100)->index();
            $table->string('feed_type', 30)->nullable()->index(); // floating, sinking, powder, medicine
            $table->string('pellet_size', 20)->nullable(); // 0.5mm, 1.0mm, 2.0mm, 3.0mm
            $table->string('unit', 20)->default('KG'); // base unit is always KG
            $table->decimal('pack_size', 8, 2)->comment('Weight in KG for a full sealed sack, e.g. 25.00 or 50.00');
            $table->boolean('allow_loose_sale')->default(true)->index();
            $table->decimal('purchase_price', 12, 2)->comment('Purchase/cost price per bag');
            $table->decimal('sale_price', 12, 2)->comment('Retail sale price per bag');
            $table->decimal('loose_sale_price_per_kg', 12, 2)->nullable()->comment('Retail sale price per loose KG');
            $table->decimal('wholesale_price', 12, 2)->nullable()->comment('Wholesale sale price per bag');
            $table->unsignedInteger('reorder_level_bags')->default(10)->comment('Low stock threshold in bags');
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
