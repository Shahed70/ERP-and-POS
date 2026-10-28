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
        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('transaction_type', 40)->index();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 50)->nullable()->index();
            $table->decimal('debit_amount', 12, 2)->default(0.00)->comment('Payment made to supplier; decreases payable');
            $table->decimal('credit_amount', 12, 2)->default(0.00)->comment('Bill received from supplier; increases payable');
            $table->decimal('running_payable_balance', 12, 2);
            $table->string('payment_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ledgers');
    }
};
