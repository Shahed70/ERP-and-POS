<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_number',
        'location_id',
        'product_id',
        'type',
        'mode',
        'unit_type',
        'input_quantity',
        'quantity_kg',
        'reason',
        'authorized_by',
    ];

    protected $casts = [
        'input_quantity' => 'decimal:2',
        'quantity_kg' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    protected static function booted(): void
    {
        static::creating(function (StockAdjustment $adjustment) {
            if (empty($adjustment->adjustment_number)) {
                $datePrefix = date('Ym');
                $count = static::whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'))->count() + 1;
                $adjustment->adjustment_number = sprintf('ADJ-%s-%04d', $datePrefix, $count);
            }
        });
    }
}
