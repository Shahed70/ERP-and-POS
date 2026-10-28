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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('location_id')->default(1)->constrained('locations')->restrictOnDelete();
            $table->date('sale_date');
            $table->decimal('subtotal_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('due_amount', 12, 2)->default(0.00);
            $table->decimal('unallocated_due', 12, 2)->default(0.00)->comment('Remaining unpaid due on this invoice');
            $table->decimal('previous_due', 12, 2)->default(0.00)->comment('Customer outstanding due before this invoice');
            $table->decimal('total_due_after_sale', 12, 2)->default(0.00)->comment('previous_due + due_amount');
            $table->enum('payment_status', ['paid', 'partial', 'due'])->default('due');
            $table->string('payment_method', 50)->nullable()->comment('cash, bkash, nagad, bank_cheque');
            $table->enum('sale_type', ['retail', 'wholesale'])->default('retail');
            $table->boolean('is_credit_overridden')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'sale_date']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
