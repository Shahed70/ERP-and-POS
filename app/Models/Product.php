<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'brand',
        'feed_type',
        'pellet_size',
        'unit',
        'pack_size',
        'allow_loose_sale',
        'purchase_price',
        'sale_price',
        'loose_sale_price_per_kg',
        'wholesale_price',
        'reorder_level_bags',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'pack_size' => 'decimal:2',
        'allow_loose_sale' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'loose_sale_price_per_kg' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'reorder_level_bags' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Helper to compute default loose rate if not explicitly overridden.
     */
    public function getEffectiveLoosePricePerKgAttribute(): float
    {
        if ($this->loose_sale_price_per_kg !== null && (float) $this->loose_sale_price_per_kg > 0) {
            return (float) $this->loose_sale_price_per_kg;
        }

        if ((float) $this->pack_size > 0) {
            return round((float) $this->sale_price / (float) $this->pack_size, 2);
        }

        return 0.00;
    }

    /**
     * Boot model events for auto-generation of SKU and loose price.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            // Auto generate unique SKU if not provided
            if (empty($product->sku)) {
                $brandPrefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $product->brand ?? 'FF'), 0, 3));
                $randSuffix = strtoupper(Str::random(4));
                $product->sku = "{$brandPrefix}-{$randSuffix}";
            }

            // Auto-calculate loose price per kg if loose sale is allowed and price is not set
            if ($product->allow_loose_sale && empty($product->loose_sale_price_per_kg) && (float) $product->pack_size > 0) {
                $product->loose_sale_price_per_kg = round((float) $product->sale_price / (float) $product->pack_size, 2);
            }
        });

        static::updating(function (Product $product) {
            // If loose sale is newly allowed and loose price is empty, auto-compute
            if ($product->allow_loose_sale && empty($product->loose_sale_price_per_kg) && (float) $product->pack_size > 0) {
                $product->loose_sale_price_per_kg = round((float) $product->sale_price / (float) $product->pack_size, 2);
            }
        });
    }
}
