<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentCollection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'amount_paid' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentCollectionAllocation::class);
    }

    public static function generateReceiptNo(): string
    {
        $prefix = 'MR-' . date('Ym') . '-';
        $latest = static::where('receipt_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $latest) {
            return $prefix . '0001';
        }

        $lastSeq = (int) substr($latest->receipt_no, -4);
        return $prefix . sprintf('%04d', $lastSeq + 1);
    }
}
