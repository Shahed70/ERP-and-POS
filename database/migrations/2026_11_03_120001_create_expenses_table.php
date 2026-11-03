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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->string('category', 50)->index(); // shop_rent, electricity_bill, warehouse_rent, snack_expenses, others
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date')->index();
            $table->string('payment_method', 50)->default('cash'); // cash, bank, bkash, nagad, rocket
            $table->string('paid_to')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
