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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->enum('unit_type', ['bag', 'kg'])->default('bag');
            $table->decimal('quantity', 10, 2);
            $table->decimal('pack_size', 8, 2)->default(25.00)->comment('KG per bag');
            $table->decimal('deducted_weight_kg', 10, 2)->comment('bag: qty * pack_size; kg: qty');
            $table->decimal('unit_price', 12, 2)->comment('Price per bag or per kg');
            $table->decimal('line_total', 12, 2)->comment('quantity * unit_price');
            $table->decimal('unit_cost_price', 12, 2)->default(0.00)->comment('Historical cost price per unit for P&L');
            $table->timestamps();

            $table->index(['sale_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
