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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity_bags')->unsigned();
            $table->decimal('pack_size', 8, 2)->comment('KG per bag');
            $table->decimal('total_weight_kg', 10, 2)->comment('quantity_bags * pack_size');
            $table->decimal('purchase_rate_per_bag', 12, 2)->comment('Cost rate per bag in BDT');
            $table->decimal('line_total', 12, 2)->comment('quantity_bags * purchase_rate_per_bag');
            $table->timestamps();

            $table->index(['purchase_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
