<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ShopSetting extends Model
{
    use HasFactory;

    protected $table = 'shop_settings';

    protected $fillable = [
        'shop_name',
        'shop_tagline',
        'proprietor_name',
        'phone',
        'email',
        'address',
        'logo_path',
        'currency_symbol',
        'invoice_footer_note',
    ];

    /**
     * Singleton accessor for global shop settings.
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'shop_name' => 'বিসমিল্লাহ ফিশ ফিড স্টোর',
            'shop_tagline' => 'উন্নতমানের ভাসমান ও ডুবন্ত মাছের খাদ্য পাইকারী ও খুচরা বিক্রেতা',
            'proprietor_name' => 'প্রোপ্রাইটর',
            'phone' => '০১৭১১-০০০০০০',
            'email' => 'contact@fishfeed.com',
            'address' => 'ফিড মার্কেট, ত্রিশাল, ময়মনসিংহ',
            'logo_path' => null,
            'currency_symbol' => '৳',
            'invoice_footer_note' => 'আমাদের সাথে ব্যবসা করার জন্য ধন্যবাদ। পণ্য বুঝিয়া পাইলাম।',
        ]);
    }

    /**
     * Return public URL for the uploaded shop logo.
     */
    public function getLogoUrl(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return null;
    }
}
