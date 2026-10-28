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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20);
            $table->string('alternative_phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable()->comment('Mill / Depot / Office location');
            $table->text('bank_details')->nullable()->comment('Bank Name, Branch, Account No, Routing');
            $table->decimal('opening_payable', 12, 2)->default(0.00)->comment('Historical debt owed to supplier prior to system setup');
            $table->decimal('current_payable', 12, 2)->default(0.00)->comment('Current total payable balance synchronized with ledger');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
