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
     * Relationship: Stock Balances across locations
     */
    public function stockBalances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /**
     * Relationship: Stock Ledgers
     */
    public function stockLedgers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    public function saleItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function suppliers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['last_purchase_price', 'last_purchased_at'])
            ->withTimestamps();
    }

    /**
     * Total stock in KG across all locations (or default shop).
     */
    public function getTotalStockKgAttribute(): float
    {
        return (float) $this->stockBalances()->sum('total_weight_kg');
    }

    /**
     * Full sealed bags in stock.
     */
    public function getTotalBagsAttribute(): int
    {
        $packSize = (float) ($this->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0;
        }

        return (int) floor($this->total_stock_kg / $packSize);
    }

    /**
     * Loose KG in stock.
     */
    public function getTotalLooseKgAttribute(): float
    {
        $packSize = (float) ($this->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0.00;
        }

        return round(fmod($this->total_stock_kg, $packSize), 2);
    }

    /**
     * Formatted stock display: "42 Bags + 12.5 KG"
     */
    public function getStockDisplayAttribute(): string
    {
        return "{$this->total_bags} Bags + {$this->total_loose_kg} KG";
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

        static::created(function (Product $product) {
            $locId = Location::getDefault()?->id ?? 1;
            StockBalance::firstOrCreate([
                'location_id' => $locId,
                'product_id' => $product->id,
            ], [
                'total_weight_kg' => 0.00,
            ]);
        });

        static::updating(function (Product $product) {
            // If loose sale is newly allowed and loose price is empty, auto-compute
            if ($product->allow_loose_sale && empty($product->loose_sale_price_per_kg) && (float) $product->pack_size > 0) {
                $product->loose_sale_price_per_kg = round((float) $product->sale_price / (float) $product->pack_size, 2);
            }
        });
    }
}
