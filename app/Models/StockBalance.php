<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'product_id',
        'total_weight_kg',
    ];

    protected $casts = [
        'total_weight_kg' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Compute full sealed bags count.
     */
    public function getFullBagsAttribute(): int
    {
        $packSize = (float) ($this->product->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0;
        }

        return (int) floor((float) $this->total_weight_kg / $packSize);
    }

    /**
     * Compute remaining loose KG.
     */
    public function getLooseKgAttribute(): float
    {
        $packSize = (float) ($this->product->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0.00;
        }

        return round(fmod((float) $this->total_weight_kg, $packSize), 2);
    }

    /**
     * Formatted string: "42 Bags + 12.5 KG"
     */
    public function getFormattedStockAttribute(): string
    {
        return "{$this->full_bags} Bags + {$this->loose_kg} KG";
    }

    /**
     * Determine if stock is low according to product reorder level.
     */
    public function getIsLowStockAttribute(): bool
    {
        $reorderThreshold = (int) ($this->product->reorder_level_bags ?? 10);
        return $this->full_bags <= $reorderThreshold && $this->total_weight_kg > 0;
    }

    /**
     * Determine if stock is completely exhausted.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return (float) $this->total_weight_kg <= 0;
    }

    /**
     * Inventory valuation at purchase cost.
     */
    public function getCostValuationAttribute(): float
    {
        $packSize = (float) ($this->product->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0.00;
        }

        $costPerKg = (float) $this->product->purchase_price / $packSize;
        return round((float) $this->total_weight_kg * $costPerKg, 2);
    }

    /**
     * Inventory valuation at retail sale price.
     */
    public function getRetailValuationAttribute(): float
    {
        $packSize = (float) ($this->product->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0.00;
        }

        $retailPerKg = (float) $this->product->sale_price / $packSize;
        return round((float) $this->total_weight_kg * $retailPerKg, 2);
    }
}
