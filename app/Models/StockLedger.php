<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'location_id',
        'product_id',
        'transaction_type',
        'reference_id',
        'reference_no',
        'quantity_kg_change',
        'running_balance_kg',
        'unit_cost_price',
        'notes_or_reason',
        'performed_by',
        'created_at',
    ];

    protected $casts = [
        'quantity_kg_change' => 'decimal:2',
        'running_balance_kg' => 'decimal:2',
        'unit_cost_price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Compute change in bags.
     */
    public function getQuantityBagsChangeAttribute(): float
    {
        $packSize = (float) ($this->product->pack_size ?? 25.00);
        if ($packSize <= 0) {
            return 0.00;
        }

        return round((float) $this->quantity_kg_change / $packSize, 2);
    }
}
