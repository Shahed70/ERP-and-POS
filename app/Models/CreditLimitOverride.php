<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditLimitOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_id',
        'current_credit_limit',
        'existing_due',
        'attempted_invoice_amount',
        'authorized_by',
        'authorized_at',
        'notes',
    ];

    protected $casts = [
        'current_credit_limit' => 'decimal:2',
        'existing_due' => 'decimal:2',
        'attempted_invoice_amount' => 'decimal:2',
        'authorized_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
