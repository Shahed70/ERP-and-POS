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
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->default('বিসমিল্লাহ ফিশ ফিড স্টোর');
            $table->string('shop_tagline')->nullable()->default('উন্নতমানের ভাসমান ও ডুবন্ত মাছের খাদ্য পাইকারী ও খুচরা বিক্রেতা');
            $table->string('proprietor_name')->nullable()->default('প্রোপ্রাইটর');
            $table->string('phone')->nullable()->default('০১৭১১-০০০০০০');
            $table->string('email')->nullable()->default('contact@fishfeed.com');
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency_symbol')->default('৳');
            $table->text('invoice_footer_note')->nullable();
            $table->timestamps();
        });

        // Insert initial default settings row
        DB::table('shop_settings')->insert([
            'shop_name' => 'বিসমিল্লাহ ফিশ ফিড স্টোর',
            'shop_tagline' => 'উন্নতমানের ভাসমান ও ডুবন্ত মাছের খাদ্য পাইকারী ও খুচরা বিক্রেতা',
            'proprietor_name' => 'প্রোপ্রাইটর',
            'phone' => '০১৭১১-০০০০০০',
            'email' => 'contact@fishfeed.com',
            'address' => 'ফিড মার্কেট, ত্রিশাল, ময়মনসিংহ',
            'logo_path' => null,
            'currency_symbol' => '৳',
            'invoice_footer_note' => 'আমাদের সাথে ব্যবসা করার জন্য ধন্যবাদ। পণ্য বুঝিয়া পাইলাম।',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
