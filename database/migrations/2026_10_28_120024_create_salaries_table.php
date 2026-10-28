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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->string('salary_slip_no', 50)->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('salary_month', 7)->comment('YYYY-MM, e.g. 2026-10');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('bonus_amount', 12, 2)->default(0.00);
            $table->decimal('advance_deduction', 12, 2)->default(0.00)->comment('Deducted from employee current_advance_due');
            $table->decimal('other_deductions', 12, 2)->default(0.00);
            $table->decimal('net_payable', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('due_amount', 12, 2)->default(0.00);
            $table->date('disbursement_date')->nullable();
            $table->string('payment_method', 50)->default('cash');
            $table->enum('payment_status', ['paid', 'partial', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'salary_month']);
            $table->index('salary_month');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
