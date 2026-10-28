<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCollectionAllocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function paymentCollection(): BelongsTo
    {
        return $this->belongsTo(PaymentCollection::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
