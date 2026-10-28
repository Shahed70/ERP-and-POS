<?php

namespace App\Services;

use App\Models\CreditLimitOverride;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Location;
use App\Models\PaymentCollection;
use App\Models\PaymentCollectionAllocation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Exception;
use Illuminate\Support\Facades\DB;

class SaleService
{
    /**
     * Atomically process and execute a Sale transaction (POS / Invoicing).
     *
     * @param array $saleData
     * @param array $itemsData
     * @param int|null $userId
     * @param bool $allowCreditOverride
     * @param string|null $overrideNotes
     * @return Sale
     * @throws Exception
     */
    public static function createSale(
        array $saleData,
        array $itemsData,
        ?int $userId = null,
        bool $allowCreditOverride = false,
        ?string $overrideNotes = null
    ): Sale {
        if (empty($itemsData)) {
            throw new Exception(__('A sales invoice must contain at least one feed item.'));
        }

        return DB::transaction(function () use (
            $saleData,
            $itemsData,
            $userId,
            $allowCreditOverride,
            $overrideNotes
        ) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $locationId = $saleData['location_id'] ?? (Location::getDefault()?->id ?? 1);

            // Fetch and lock customer row
            $customer = Customer::where('id', $saleData['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Auto-generate invoice number if needed
            $invoiceNo = ! empty($saleData['invoice_no'])
                ? $saleData['invoice_no']
                : Sale::generateInvoiceNo();

            if (Sale::where('invoice_no', $invoiceNo)->exists()) {
                $invoiceNo = Sale::generateInvoiceNo();
            }

            // Calculate item lines and weights
            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitType = $item['unit_type'] ?? 'bag';
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new Exception(__('Quantity must be greater than zero for product: :product', ['product' => $product->name]));
                }

                $packSize = (float) ($item['pack_size'] ?? $product->pack_size ?? 25.00);

                // Calculate weight in KG
                $deductedWeightKg = $unitType === 'bag'
                    ? round($quantity * $packSize, 2)
                    : round($quantity, 2);

                $unitPrice = (float) $item['unit_price'];
                if ($unitPrice < 0) {
                    throw new Exception(__('Unit price cannot be negative.'));
                }

                $lineTotal = round($quantity * $unitPrice, 2);
                $subtotal += $lineTotal;

                // Snapshot unit cost price
                $unitCost = $unitType === 'bag'
                    ? (float) $product->purchase_price
                    : ($packSize > 0 ? round((float) $product->purchase_price / $packSize, 2) : 0.00);

                $preparedItems[] = [
                    'product' => $product,
                    'unit_type' => $unitType,
                    'quantity' => $quantity,
                    'pack_size' => $packSize,
                    'deducted_weight_kg' => $deductedWeightKg,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'unit_cost_price' => $unitCost,
                ];
            }

            $discount = (float) ($saleData['discount_amount'] ?? 0.00);
            $grandTotal = max(0.00, round($subtotal - $discount, 2));

            $paidAmount = min($grandTotal, max(0.00, (float) ($saleData['paid_amount'] ?? 0.00)));
            $dueAmount = max(0.00, round($grandTotal - $paidAmount, 2));

            // Check Walk-in customer constraint: Walk-in customers cannot have unpaid credit
            $isWalkIn = (int) $customer->id === 1 || stripos($customer->name, 'Walk-in') !== false;
            if ($isWalkIn && $dueAmount > 0) {
                throw new Exception(__('Walk-in cash customers cannot purchase on credit. Full payment is required or please register a named customer.'));
            }

            // Enforce Credit Limit for named customers
            $previousDue = (float) $customer->current_due;
            $newDueProposed = round($previousDue + $dueAmount, 2);
            $isCreditOverridden = false;

            if ($dueAmount > 0 && $customer->hasCreditLimit()) {
                $creditLimit = (float) $customer->credit_limit;
                if ($newDueProposed > $creditLimit) {
                    if (! $allowCreditOverride) {
                        throw new Exception(
                            __("Credit limit exceeded for :customer! Limit: ৳:limit, Current Due: ৳:currentDue, Invoice Due: ৳:due. Total would reach ৳:total. Admin PIN / Override authorization required.", [
                                'customer' => $customer->name,
                                'limit' => number_format($creditLimit, 2),
                                'currentDue' => number_format($previousDue, 2),
                                'due' => number_format($dueAmount, 2),
                                'total' => number_format($newDueProposed, 2),
                            ])
                        );
                    }
                    $isCreditOverridden = true;
                }
            }

            $paymentStatus = 'due';
            if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            // 1. Create Sale Record
            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customer->id,
                'location_id' => $locationId,
                'sale_date' => $saleData['sale_date'] ?? now()->toDateString(),
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'unallocated_due' => $dueAmount,
                'previous_due' => $previousDue,
                'total_due_after_sale' => $newDueProposed,
                'payment_status' => $paymentStatus,
                'payment_method' => $paidAmount > 0 ? ($saleData['payment_method'] ?? 'cash') : null,
                'sale_type' => $saleData['sale_type'] ?? 'retail',
                'is_credit_overridden' => $isCreditOverridden,
                'notes' => $saleData['notes'] ?? null,
                'created_by' => $operatorId,
            ]);

            // If credit override was authorized, log audit record
            if ($isCreditOverridden) {
                CreditLimitOverride::create([
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'current_credit_limit' => $customer->credit_limit,
                    'existing_due' => $previousDue,
                    'attempted_invoice_amount' => $dueAmount,
                    'authorized_by' => $operatorId,
                    'authorized_at' => now(),
                    'notes' => $overrideNotes ?? __('Authorized sale exceeding customer credit limit'),
                ]);
            }

            // 2. Create Items & Deduct Physical Stock in KG
            foreach ($preparedItems as $prep) {
                $product = $prep['product'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'unit_type' => $prep['unit_type'],
                    'quantity' => $prep['quantity'],
                    'pack_size' => $prep['pack_size'],
                    'deducted_weight_kg' => $prep['deducted_weight_kg'],
                    'unit_price' => $prep['unit_price'],
                    'line_total' => $prep['line_total'],
                    'unit_cost_price' => $prep['unit_cost_price'],
                ]);

                // Atomically deduct inventory with zero negative stock enforcement
                StockService::recordMovement(
                    productId: $product->id,
                    quantityKgChange: -$prep['deducted_weight_kg'],
                    transactionType: 'sale',
                    referenceNo: $sale->invoice_no,
                    referenceId: $sale->id,
                    unitCostPrice: $prep['unit_cost_price'],
                    notes: __("Sold :qty :unit (:kg KG) to :customer", [
                        'qty' => $prep['quantity'],
                        'unit' => $prep['unit_type'] === 'bag' ? 'Bags' : 'KG',
                        'kg' => $prep['deducted_weight_kg'],
                        'customer' => $customer->name,
                    ]),
                    locationId: $locationId,
                    performedBy: $operatorId
                );
            }

            // 3. Update Customer Ledger & Outstanding Debt
            $runningDueAfterInvoice = round($previousDue + $grandTotal, 2);

            // Record Sale Invoice Debit in Customer Ledger
            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_type' => 'sale_invoice',
                'reference_id' => $sale->id,
                'reference_no' => $sale->invoice_no,
                'debit_amount' => $grandTotal,
                'credit_amount' => 0.00,
                'running_due_balance' => $runningDueAfterInvoice,
                'notes' => __('Sales Invoice :invoice', ['invoice' => $sale->invoice_no]) .
                    ($sale->sale_type === 'wholesale' ? ' (Wholesale)' : ''),
                'performed_by' => $operatorId,
                'created_at' => now(),
            ]);

            $finalDue = $runningDueAfterInvoice;

            // If initial cash payment was received at counter, record collection credit
            if ($paidAmount > 0) {
                $finalDue = round($runningDueAfterInvoice - $paidAmount, 2);

                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'transaction_type' => 'due_collection',
                    'reference_id' => $sale->id,
                    'reference_no' => $sale->invoice_no . '-PAY',
                    'debit_amount' => 0.00,
                    'credit_amount' => $paidAmount,
                    'running_due_balance' => $finalDue,
                    'payment_method' => $sale->payment_method ?? 'cash',
                    'notes' => __('Down payment on Invoice :invoice', ['invoice' => $sale->invoice_no]),
                    'performed_by' => $operatorId,
                    'created_at' => now(),
                ]);
            }

            // Update customer running due
            $customer->current_due = $finalDue;
            $customer->save();

            return $sale;
        });
    }

    /**
     * Collect Due Payment from customer and allocate across unpaid invoices via FIFO.
     *
     * @param Customer $customer
     * @param float $amountPaid
     * @param string $paymentMethod
     * @param string|null $receiptNo
     * @param string|null $reference
     * @param string|null $notes
     * @param int|null $userId
     * @return PaymentCollection
     * @throws Exception
     */
    public static function collectPayment(
        Customer $customer,
        float $amountPaid,
        string $paymentMethod = 'cash',
        ?string $receiptNo = null,
        ?string $reference = null,
        ?string $notes = null,
        ?int $userId = null
    ): PaymentCollection {
        if ($amountPaid <= 0) {
            throw new Exception(__('Payment collection amount must be greater than zero.'));
        }

        return DB::transaction(function () use (
            $customer,
            $amountPaid,
            $paymentMethod,
            $receiptNo,
            $reference,
            $notes,
            $userId
        ) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            $receipt = $receiptNo ?? PaymentCollection::generateReceiptNo();

            // 1. Create PaymentCollection
            $collection = PaymentCollection::create([
                'receipt_no' => $receipt,
                'customer_id' => $lockedCustomer->id,
                'payment_date' => now()->toDateString(),
                'amount_paid' => $amountPaid,
                'allocation_mode' => 'fifo_automatic',
                'payment_method' => $paymentMethod,
                'transaction_reference' => $reference,
                'collected_by' => $operatorId,
                'notes' => $notes,
            ]);

            // 2. FIFO Allocation across unpaid sales
            $remaining = $amountPaid;
            $unpaidSales = Sale::where('customer_id', $lockedCustomer->id)
                ->where('unallocated_due', '>', 0)
                ->orderBy('sale_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($unpaidSales as $sale) {
                if ($remaining <= 0) {
                    break;
                }

                $allocate = min($remaining, (float) $sale->unallocated_due);
                $newUnallocated = round((float) $sale->unallocated_due - $allocate, 2);

                PaymentCollectionAllocation::create([
                    'payment_collection_id' => $collection->id,
                    'sale_id' => $sale->id,
                    'allocated_amount' => $allocate,
                ]);

                $sale->unallocated_due = $newUnallocated;
                if ($newUnallocated <= 0) {
                    $sale->payment_status = 'paid';
                } else {
                    $sale->payment_status = 'partial';
                }
                $sale->save();

                $remaining = round($remaining - $allocate, 2);
            }

            // 3. Update Customer Balance & Record in Customer Ledger
            $currentDue = (float) $lockedCustomer->current_due;
            $newDue = round($currentDue - $amountPaid, 2);

            CustomerLedger::create([
                'customer_id' => $lockedCustomer->id,
                'transaction_type' => 'due_collection',
                'reference_id' => $collection->id,
                'reference_no' => $receipt,
                'debit_amount' => 0.00,
                'credit_amount' => $amountPaid,
                'running_due_balance' => $newDue,
                'payment_method' => $paymentMethod,
                'notes' => $notes ?? __('FIFO Due Payment Collection: :receipt', ['receipt' => $receipt]),
                'performed_by' => $operatorId,
                'created_at' => now(),
            ]);

            $lockedCustomer->current_due = $newDue;
            $lockedCustomer->save();

            return $collection;
        });
    }

    /**
     * Safely void/cancel a sales invoice, returning physical inventory and reversing customer debt.
     *
     * @param Sale $sale
     * @param int|null $userId
     * @return void
     * @throws Exception
     */
    public static function voidSale(Sale $sale, ?int $userId = null): void
    {
        DB::transaction(function () use ($sale, $userId) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $customer = Customer::where('id', $sale->customer_id)->lockForUpdate()->firstOrFail();

            // 1. Revert stock (add back deducted weight in KG)
            foreach ($sale->items as $item) {
                StockService::recordMovement(
                    productId: $item->product_id,
                    quantityKgChange: (float) $item->deducted_weight_kg,
                    transactionType: 'manual_adjustment_surplus',
                    referenceNo: $sale->invoice_no . '-VOID',
                    referenceId: $sale->id,
                    notes: __("Sales Invoice :invoice Voided. Restored :kg KG to stock", [
                        'invoice' => $sale->invoice_no,
                        'kg' => $item->deducted_weight_kg,
                    ]),
                    locationId: $sale->location_id,
                    performedBy: $operatorId
                );
            }

            // 2. Revert Customer Due
            // Net effect of the sale was +due_amount
            $currentDue = (float) $customer->current_due;
            $netEffect = (float) $sale->due_amount;
            $newDue = round($currentDue - $netEffect, 2);

            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_type' => 'refund_adjustment',
                'reference_id' => $sale->id,
                'reference_no' => $sale->invoice_no . '-VOID',
                'debit_amount' => (float) $sale->paid_amount,
                'credit_amount' => (float) $sale->grand_total,
                'running_due_balance' => $newDue,
                'notes' => __('Sales Invoice :invoice voided. Customer balance reversed.', ['invoice' => $sale->invoice_no]),
                'performed_by' => $operatorId,
                'created_at' => now(),
            ]);

            $customer->current_due = $newDue;
            $customer->save();

            // 3. Delete sale
            $sale->delete();
        });
    }
}
