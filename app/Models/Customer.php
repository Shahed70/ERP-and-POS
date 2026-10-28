<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'alternative_phone',
        'address',
        'farm_details',
        'credit_limit',
        'opening_due',
        'current_due',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_due' => 'decimal:2',
        'current_due' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ledgers(): HasMany
    {
        return $this->hasMany(CustomerLedger::class)->orderBy('created_at', 'asc')->orderBy('id', 'asc');
    }

    public function creditLimitOverrides(): HasMany
    {
        return $this->hasMany(CreditLimitOverride::class);
    }

    /**
     * Check if customer has an active credit limit.
     * 0.00 denotes unlimited credit.
     */
    public function hasCreditLimit(): bool
    {
        return (float) $this->credit_limit > 0;
    }

    /**
     * Check if a proposed additional debt exceeds credit limit.
     */
    public function exceedsCreditLimit(float $additionalDue = 0.00): bool
    {
        if (! $this->hasCreditLimit()) {
            return false;
        }

        return ((float) $this->current_due + $additionalDue) > (float) $this->credit_limit;
    }

    /**
     * Collect a due payment from customer and synchronize ledger.
     */
    public function collectDuePayment(
        float $amountPaid,
        string $paymentMethod = 'cash',
        ?string $receiptNo = null,
        ?string $notes = null,
        ?int $performedBy = null
    ): CustomerLedger {
        if ($amountPaid <= 0) {
            throw new Exception(__('Payment amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($amountPaid, $paymentMethod, $receiptNo, $notes, $performedBy) {
            $customer = static::where('id', $this->id)->lockForUpdate()->first();
            $userId = $performedBy ?? (auth()->id() ?? 1);

            $currentDue = (float) $customer->current_due;
            $newDue = round($currentDue - $amountPaid, 2);

            $receipt = $receiptNo ?? sprintf('MR-%s-%04d', date('Ym'), CustomerLedger::where('transaction_type', 'due_collection')->count() + 1);

            $ledger = CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_type' => 'due_collection',
                'reference_no' => $receipt,
                'debit_amount' => 0.00,
                'credit_amount' => $amountPaid,
                'running_due_balance' => $newDue,
                'payment_method' => $paymentMethod,
                'notes' => $notes ?? __('Customer due payment received'),
                'performed_by' => $userId,
                'created_at' => now(),
            ]);

            $customer->current_due = $newDue;
            $customer->save();

            $this->current_due = $newDue;

            return $ledger;
        });
    }

    protected static function booted(): void
    {
        static::created(function (Customer $customer) {
            // If customer has an initial opening debt, record it in ledger
            if ((float) $customer->opening_due > 0) {
                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'transaction_type' => 'opening_due',
                    'reference_no' => 'OPENING-BALANCE',
                    'debit_amount' => (float) $customer->opening_due,
                    'credit_amount' => 0.00,
                    'running_due_balance' => (float) $customer->opening_due,
                    'notes' => __('Initial opening debt balance on registration'),
                    'performed_by' => auth()->id() ?? 1,
                    'created_at' => now(),
                ]);

                // Sync current_due
                $customer->current_due = $customer->opening_due;
                $customer->saveQuietly();
            }
        });
    }
}
