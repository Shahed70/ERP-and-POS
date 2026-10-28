<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'transport_fare_labor_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getTotalBagsAttribute(): int
    {
        return (int) $this->items()->sum('quantity_bags');
    }

    public function getTotalWeightKgAttribute(): float
    {
        return (float) $this->items()->sum('total_weight_kg');
    }

    public static function generateInvoiceNo(): string
    {
        $prefix = 'PUR-' . date('Ym') . '-';
        $latest = static::where('purchase_invoice_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $latest) {
            return $prefix . '0001';
        }

        $lastSeq = (int) substr($latest->purchase_invoice_no, -4);
        return $prefix . sprintf('%04d', $lastSeq + 1);
    }
}
