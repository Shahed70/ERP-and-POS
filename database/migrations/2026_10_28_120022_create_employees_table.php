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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 20)->unique();
            $table->string('nid', 30)->nullable();
            $table->string('designation', 100);
            $table->date('joining_date');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('current_advance_due', 12, 2)->default(0.00)->comment('Running balance of unpaid cash advances');
            $table->text('address')->nullable();
            $table->string('emergency_contact', 100)->nullable();
            $table->text('bank_or_mfs_details')->nullable()->comment('bKash, Nagad, or Bank account for salary transfer');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
