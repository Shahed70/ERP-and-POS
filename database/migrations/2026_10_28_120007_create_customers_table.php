<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 20)->unique();
            $table->string('alternative_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('farm_details', 255)->nullable()->comment('Pond location, fish species, farming capacity');
            $table->decimal('credit_limit', 12, 2)->default(0.00)->comment('Max allowed due balance. 0 = Unlimited credit');
            $table->decimal('opening_due', 12, 2)->default(0.00)->comment('Historical debt balance prior to system setup');
            $table->decimal('current_due', 12, 2)->default(0.00)->comment('Current outstanding balance synced with ledger');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Insert baseline walk-in counter customer
        DB::table('customers')->insert([
            'name' => 'Walk-in Cash Customer (নগদ খরিদ্দার)',
            'phone' => '01000000000',
            'address' => 'Counter Retail Walk-in',
            'credit_limit' => 0.00,
            'opening_due' => 0.00,
            'current_due' => 0.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
