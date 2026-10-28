<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Record a new Purchase consignment atomically.
     *
     * @param array $purchaseData
     * @param array $itemsData
     * @param int|null $userId
     * @return Purchase
     * @throws Exception
     */
    public static function createPurchase(array $purchaseData, array $itemsData, ?int $userId = null): Purchase
    {
        if (empty($itemsData)) {
            throw new Exception(__('A purchase consignment must contain at least one feed product item.'));
        }

        return DB::transaction(function () use ($purchaseData, $itemsData, $userId) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $locationId = $purchaseData['location_id'] ?? (Location::getDefault()?->id ?? 1);

            // Fetch and lock supplier
            $supplier = Supplier::where('id', $purchaseData['supplier_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Auto-generate invoice number if needed
            $invoiceNo = ! empty($purchaseData['purchase_invoice_no'])
                ? $purchaseData['purchase_invoice_no']
                : Purchase::generateInvoiceNo();

            // Validate uniqueness
            if (Purchase::where('purchase_invoice_no', $invoiceNo)->exists()) {
                $invoiceNo = Purchase::generateInvoiceNo();
            }

            // Calculate item totals
            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qtyBags = (int) $item['quantity_bags'];
                if ($qtyBags <= 0) {
                    throw new Exception(__('Quantity of bags must be greater than zero for product: :product', ['product' => $product->name]));
                }

                $packSize = (float) ($item['pack_size'] ?? $product->pack_size ?? 25.00);
                $ratePerBag = (float) $item['purchase_rate_per_bag'];
                if ($ratePerBag < 0) {
                    throw new Exception(__('Purchase rate per bag cannot be negative.'));
                }

                $totalWeightKg = round($qtyBags * $packSize, 2);
                $lineTotal = round($qtyBags * $ratePerBag, 2);
                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'product' => $product,
                    'quantity_bags' => $qtyBags,
                    'pack_size' => $packSize,
                    'total_weight_kg' => $totalWeightKg,
                    'purchase_rate_per_bag' => $ratePerBag,
                    'line_total' => $lineTotal,
                ];
            }

            $transportLabor = (float) ($purchaseData['transport_fare_labor_cost'] ?? 0.00);
            $discount = (float) ($purchaseData['discount_amount'] ?? 0.00);
            $grandTotal = max(0.00, round($subtotal + $transportLabor - $discount, 2));

            $paidAmount = min($grandTotal, max(0.00, (float) ($purchaseData['paid_amount'] ?? 0.00)));
            $dueAmount = max(0.00, round($grandTotal - $paidAmount, 2));

            $paymentStatus = 'due';
            if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            // 1. Create Purchase record
            $purchase = Purchase::create([
                'purchase_invoice_no' => $invoiceNo,
                'supplier_challan_no' => $purchaseData['supplier_challan_no'] ?? null,
                'supplier_id' => $supplier->id,
                'location_id' => $locationId,
                'purchase_date' => $purchaseData['purchase_date'] ?? now()->toDateString(),
                'subtotal_amount' => $subtotal,
                'transport_fare_labor_cost' => $transportLabor,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paidAmount > 0 ? ($purchaseData['payment_method'] ?? 'cash') : null,
                'notes' => $purchaseData['notes'] ?? null,
                'created_by' => $operatorId,
            ]);

            // 2. Create Items, Increment Stock & Update Pivot
            foreach ($preparedItems as $prep) {
                $product = $prep['product'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity_bags' => $prep['quantity_bags'],
                    'pack_size' => $prep['pack_size'],
                    'total_weight_kg' => $prep['total_weight_kg'],
                    'purchase_rate_per_bag' => $prep['purchase_rate_per_bag'],
                    'line_total' => $prep['line_total'],
                ]);

                // Atomically increment stock in KG
                $costPerKg = $prep['pack_size'] > 0 ? round($prep['purchase_rate_per_bag'] / $prep['pack_size'], 2) : 0.00;
                StockService::recordMovement(
                    productId: $product->id,
                    quantityKgChange: $prep['total_weight_kg'],
                    transactionType: 'purchase',
                    referenceNo: $purchase->purchase_invoice_no,
                    referenceId: $purchase->id,
                    unitCostPrice: $costPerKg,
                    notes: __("Purchased :bags Bags (:kg KG) @ ৳:rate/bag from :supplier", [
                        'bags' => $prep['quantity_bags'],
                        'kg' => $prep['total_weight_kg'],
                        'rate' => number_format($prep['purchase_rate_per_bag'], 2),
                        'supplier' => $supplier->company_name,
                    ]),
                    locationId: $locationId,
                    performedBy: $operatorId
                );

                // Update product catalog purchase price to latest rate
                $product->purchase_price = $prep['purchase_rate_per_bag'];
                $product->saveQuietly();

                // Synchronize supplier's pivot product catalog
                $supplier->products()->syncWithoutDetaching([
                    $product->id => [
                        'last_purchase_price' => $prep['purchase_rate_per_bag'],
                        'last_purchased_at' => now(),
                    ],
                ]);
            }

            // 3. Update Supplier Ledger & Current Payable Balance
            $currentPayable = (float) $supplier->current_payable;
            $newPayableAfterBill = round($currentPayable + $grandTotal, 2);

            // Record Bill Credit in Supplier Ledger
            SupplierLedger::create([
                'supplier_id' => $supplier->id,
                'transaction_type' => 'purchase_bill',
                'reference_no' => $purchase->purchase_invoice_no,
                'debit_amount' => 0.00,
                'credit_amount' => $grandTotal,
                'running_payable_balance' => $newPayableAfterBill,
                'notes' => __('Purchase Challan Bill: :invoice', ['invoice' => $purchase->purchase_invoice_no]) .
                    ($purchase->supplier_challan_no ? " (Challan: {$purchase->supplier_challan_no})" : ''),
                'performed_by' => $operatorId,
                'created_at' => now(),
            ]);

            $finalPayable = $newPayableAfterBill;

            // If initial payment was made at time of purchase, record payment debit
            if ($paidAmount > 0) {
                $finalPayable = round($newPayableAfterBill - $paidAmount, 2);
                SupplierLedger::create([
                    'supplier_id' => $supplier->id,
                    'transaction_type' => 'supplier_payment',
                    'reference_no' => $purchase->purchase_invoice_no . '-PAY',
                    'debit_amount' => $paidAmount,
                    'credit_amount' => 0.00,
                    'running_payable_balance' => $finalPayable,
                    'payment_method' => $purchase->payment_method ?? 'cash',
                    'notes' => __('Initial disbursement for Purchase :invoice', ['invoice' => $purchase->purchase_invoice_no]),
                    'performed_by' => $operatorId,
                    'created_at' => now(),
                ]);
            }

            // Save updated payable on supplier
            $supplier->current_payable = $finalPayable;
            $supplier->save();

            return $purchase;
        });
    }

    /**
     * Delete/void a purchase and safely revert inventory & supplier ledger.
     *
     * @param Purchase $purchase
     * @param int|null $userId
     * @return void
     * @throws Exception
     */
    public static function deletePurchase(Purchase $purchase, ?int $userId = null): void
    {
        DB::transaction(function () use ($purchase, $userId) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $supplier = Supplier::where('id', $purchase->supplier_id)->lockForUpdate()->firstOrFail();

            // 1. Revert inventory (decrement stock). StockService checks for negative stock!
            foreach ($purchase->items as $item) {
                StockService::recordMovement(
                    productId: $item->product_id,
                    quantityKgChange: -$item->total_weight_kg,
                    transactionType: 'damage_wastage', // Or adjustment
                    referenceNo: $purchase->purchase_invoice_no . '-VOID',
                    referenceId: $purchase->id,
                    notes: __("Purchase :invoice Voided/Deleted. Reverted :kg KG", [
                        'invoice' => $purchase->purchase_invoice_no,
                        'kg' => $item->total_weight_kg,
                    ]),
                    locationId: $purchase->location_id,
                    performedBy: $operatorId
                );
            }

            // 2. Revert Supplier Balance
            // If bill added grand_total and paid deducted paid_amount, net effect was +due_amount
            $currentPayable = (float) $supplier->current_payable;
            $netEffect = round((float) $purchase->grand_total - (float) $purchase->paid_amount, 2);
            $newPayable = max(0.00, round($currentPayable - $netEffect, 2));

            SupplierLedger::create([
                'supplier_id' => $supplier->id,
                'transaction_type' => 'purchase_return',
                'reference_no' => $purchase->purchase_invoice_no . '-VOID',
                'debit_amount' => (float) $purchase->grand_total,
                'credit_amount' => (float) $purchase->paid_amount,
                'running_payable_balance' => $newPayable,
                'notes' => __('Purchase :invoice was voided/deleted. Supplier payable reversed.', ['invoice' => $purchase->purchase_invoice_no]),
                'performed_by' => $operatorId,
                'created_at' => now(),
            ]);

            $supplier->current_payable = $newPayable;
            $supplier->save();

            // 3. Delete purchase
            $purchase->delete();
        });
    }
}
