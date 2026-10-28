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
        Schema::create('credit_limit_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->decimal('current_credit_limit', 12, 2);
            $table->decimal('existing_due', 12, 2);
            $table->decimal('attempted_invoice_amount', 12, 2);
            $table->foreignId('authorized_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('authorized_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_limit_overrides');
    }
};
