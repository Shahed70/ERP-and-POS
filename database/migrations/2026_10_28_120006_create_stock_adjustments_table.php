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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('type', 30)->comment('damage, expired, rat_bite, theft, weighing_loss, surplus_found, opening_stock');
            $table->string('mode', 10)->comment('decrease, increase');
            $table->string('unit_type', 10)->default('bag')->comment('bag, kg');
            $table->decimal('input_quantity', 10, 2);
            $table->decimal('quantity_kg', 10, 2)->comment('Total converted KG');
            $table->text('reason');
            $table->foreignId('authorized_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
