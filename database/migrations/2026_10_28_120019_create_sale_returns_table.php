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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 50)->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('location_id')->default(1)->constrained('locations')->restrictOnDelete();
            $table->date('return_date');
            $table->decimal('total_return_value', 12, 2);
            $table->decimal('due_deduction_amount', 12, 2)->default(0.00)->comment('Portion applied against outstanding debt');
            $table->decimal('cash_refund_amount', 12, 2)->default(0.00)->comment('Cash paid out to customer');
            $table->decimal('advance_credited_amount', 12, 2)->default(0.00)->comment('Credited to customer advance balance');
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
