<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'return_date' => 'date',
        'total_return_value' => 'decimal:2',
        'due_deduction_amount' => 'decimal:2',
        'cash_refund_amount' => 'decimal:2',
        'advance_credited_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public static function generateReturnNo(): string
    {
        $prefix = 'RET-' . date('Ym') . '-';
        $latest = static::where('return_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $latest) {
            return $prefix . '0001';
        }

        $lastSeq = (int) substr($latest->return_no, -4);
        return $prefix . sprintf('%04d', $lastSeq + 1);
    }
}
