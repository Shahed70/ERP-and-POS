<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone',
        'alternative_phone',
        'email',
        'address',
        'bank_details',
        'opening_payable',
        'current_payable',
        'is_active',
    ];

    protected $casts = [
        'opening_payable' => 'decimal:2',
        'current_payable' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ledgers(): HasMany
    {
        return $this->hasMany(SupplierLedger::class)->orderBy('created_at', 'asc')->orderBy('id', 'asc');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot(['last_purchase_price', 'last_purchased_at'])
            ->withTimestamps();
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Total purchases billed from this supplier.
     */
    public function getTotalPurchasesAttribute(): float
    {
        if (Schema::hasTable('purchases')) {
            return (float) $this->purchases()->sum('grand_total');
        }

        return (float) $this->ledgers()->where('transaction_type', 'purchase_bill')->sum('credit_amount');
    }

    /**
     * Total payments made to this supplier.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->ledgers()->where('transaction_type', 'supplier_payment')->sum('debit_amount');
    }

    /**
     * Record a payment disbursement to supplier and synchronize ledger.
     */
    public function makePayment(
        float $amountPaid,
        string $paymentMethod = 'cash',
        ?string $voucherNo = null,
        ?string $notes = null,
        ?int $performedBy = null
    ): SupplierLedger {
        if ($amountPaid <= 0) {
            throw new Exception(__('Payment amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($amountPaid, $paymentMethod, $voucherNo, $notes, $performedBy) {
            $supplier = static::where('id', $this->id)->lockForUpdate()->first();
            $userId = $performedBy ?? (auth()->id() ?? 1);

            $currentPayable = (float) $supplier->current_payable;
            $newPayable = round($currentPayable - $amountPaid, 2);

            $voucher = $voucherNo ?? sprintf('PV-%s-%04d', date('Ym'), SupplierLedger::where('transaction_type', 'supplier_payment')->count() + 1);

            $ledger = SupplierLedger::create([
                'supplier_id' => $supplier->id,
                'transaction_type' => 'supplier_payment',
                'reference_no' => $voucher,
                'debit_amount' => $amountPaid,
                'credit_amount' => 0.00,
                'running_payable_balance' => $newPayable,
                'payment_method' => $paymentMethod,
                'notes' => $notes ?? __('Payment disbursed to supplier'),
                'performed_by' => $userId,
                'created_at' => now(),
            ]);

            $supplier->current_payable = $newPayable;
            $supplier->save();

            $this->current_payable = $newPayable;

            return $ledger;
        });
    }

    protected static function booted(): void
    {
        static::created(function (Supplier $supplier) {
            // Initial opening payable balance
            if ((float) $supplier->opening_payable > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->id,
                    'transaction_type' => 'opening_payable',
                    'reference_no' => 'OPENING-PAYABLE',
                    'debit_amount' => 0.00,
                    'credit_amount' => (float) $supplier->opening_payable,
                    'running_payable_balance' => (float) $supplier->opening_payable,
                    'notes' => __('Initial opening debt owed to supplier on registration'),
                    'performed_by' => auth()->id() ?? 1,
                    'created_at' => now(),
                ]);

                $supplier->current_payable = $supplier->opening_payable;
                $supplier->saveQuietly();
            }
        });
    }
}
