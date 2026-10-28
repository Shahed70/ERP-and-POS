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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_invoice_no', 50)->unique();
            $table->string('supplier_challan_no', 100)->nullable()->comment('Delivery challan/gate pass number issued by mill');
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('location_id')->default(1)->constrained('locations')->restrictOnDelete();
            $table->date('purchase_date');
            $table->decimal('subtotal_amount', 12, 2)->default(0.00);
            $table->decimal('transport_fare_labor_cost', 10, 2)->default(0.00)->comment('Truck fare and loading/unloading labor cost');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->comment('Mill discount or commercial rebate');
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('due_amount', 12, 2)->default(0.00);
            $table->enum('payment_status', ['paid', 'partial', 'due'])->default('due');
            $table->string('payment_method', 50)->nullable()->comment('cash, bank_transfer, cheque, mfs_bkash_nagad');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'purchase_date']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
