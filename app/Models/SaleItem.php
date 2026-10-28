<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'pack_size' => 'decimal:2',
        'deducted_weight_kg' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'unit_cost_price' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedQuantityAttribute(): string
    {
        $qty = (float) $this->quantity;
        if ($this->unit_type === 'bag') {
            return (int) $qty . ' ' . __('Bags') . ' (' . number_format($this->deducted_weight_kg, 1) . ' KG)';
        }

        return number_format($qty, 2) . ' KG';
    }
}
