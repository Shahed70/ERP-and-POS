<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'unallocated_due' => 'decimal:2',
        'previous_due' => 'decimal:2',
        'total_due_after_sale' => 'decimal:2',
        'is_credit_overridden' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(SaleItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentCollectionAllocation::class);
    }

    public function getTotalWeightKgAttribute(): float
    {
        return (float) $this->items()->sum('deducted_weight_kg');
    }

    public static function generateInvoiceNo(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $latest = static::where('invoice_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $latest) {
            return $prefix . '0001';
        }

        $lastSeq = (int) substr($latest->invoice_no, -4);
        return $prefix . sprintf('%04d', $lastSeq + 1);
    }
}
