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

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Total purchases made by customer (grand total of sales invoices).
     */
    public function getTotalPurchasesAttribute(): float
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            return (float) $this->sales()->sum('grand_total');
        }

        return (float) $this->ledgers()->where('transaction_type', 'sale_invoice')->sum('debit_amount');
    }

    /**
     * Total paid by customer across sales and due collections.
     */
    public function getTotalPaidAttribute(): float
    {
        $paidAtSale = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            $paidAtSale = (float) $this->sales()->sum('paid_amount');
        }

        $paidLater = (float) $this->ledgers()->where('transaction_type', 'due_collection')->sum('credit_amount');

        return round($paidAtSale + $paidLater, 2);
    }

    /**
     * Total new invoice dues accrued before collections.
     */
    public function getInvoiceDueAccruedAttribute(): float
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            return (float) $this->sales()->sum('due_amount');
        }

        return (float) $this->ledgers()->where('transaction_type', 'sale_invoice')->sum('debit_amount');
    }

    /**
     * Formatted string of distinct products bought by customer.
     */
    public function getPurchasedProductsListAttribute(): string
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sale_items')) {
            return __('No sales yet');
        }

        $products = Product::query()
            ->whereHas('saleItems', function ($q) {
                $q->whereHas('sale', fn ($sq) => $sq->where('customer_id', $this->id));
            })
            ->pluck('name')
            ->toArray();

        return ! empty($products) ? implode(', ', $products) : __('No purchases yet');
    }

    /**
     * Last purchase date of this customer.
     */
    public function getLastPurchaseDateAttribute(): ?string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            return $this->sales()->max('sale_date');
        }

        return null;
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
     * Collect a due payment from customer and synchronize ledger with FIFO invoice allocation.
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

        \App\Services\SaleService::collectPayment(
            customer: $this,
            amountPaid: $amountPaid,
            paymentMethod: $paymentMethod,
            receiptNo: $receiptNo,
            notes: $notes,
            userId: $performedBy
        );

        $this->refresh();

        return $this->ledgers()->where('transaction_type', 'due_collection')->latest('id')->first();
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
