<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Location;
use App\Models\Product;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockAdjustment;
use Exception;
use Illuminate\Support\Facades\DB;

class SaleReturnService
{
    /**
     * Atomically process a customer sale return with strict credit-first settlement
     * and condition-based inventory routing (intact vs. damaged).
     *
     * @param array $returnData
     * @param array $itemsData
     * @param int|null $userId
     * @return SaleReturn
     * @throws Exception
     */
    public static function createReturn(array $returnData, array $itemsData, ?int $userId = null): SaleReturn
    {
        if (empty($itemsData)) {
            throw new Exception(__('A return claim must contain at least one feed item.'));
        }

        return DB::transaction(function () use ($returnData, $itemsData, $userId) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $locationId = $returnData['location_id'] ?? (Location::getDefault()?->id ?? 1);

            // Fetch and lock customer
            $customer = Customer::where('id', $returnData['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Auto-generate return number
            $returnNo = ! empty($returnData['return_no'])
                ? $returnData['return_no']
                : SaleReturn::generateReturnNo();

            if (SaleReturn::where('return_no', $returnNo)->exists()) {
                $returnNo = SaleReturn::generateReturnNo();
            }

            // Calculate item values and weights
            $totalReturnValue = 0.00;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitType = $item['unit_type'] ?? 'bag';
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new Exception(__('Return quantity must be greater than zero for product: :product', ['product' => $product->name]));
                }

                $packSize = (float) ($item['pack_size'] ?? $product->pack_size ?? 25.00);
                $condition = $item['condition'] ?? 'intact_sealed_bag';

                $weightKg = $unitType === 'bag'
                    ? round($quantity * $packSize, 2)
                    : round($quantity, 2);

                $unitPrice = (float) $item['unit_price'];
                if ($unitPrice < 0) {
                    throw new Exception(__('Return rate cannot be negative.'));
                }

                $lineTotal = round($quantity * $unitPrice, 2);
                $totalReturnValue += $lineTotal;

                $preparedItems[] = [
                    'product' => $product,
                    'unit_type' => $unitType,
                    'quantity' => $quantity,
                    'pack_size' => $packSize,
                    'condition' => $condition,
                    'weight_kg' => $weightKg,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            // Business Rule 1: Credit-First Settlement
            $currentDue = (float) $customer->current_due;
            $dueDeduction = 0.00;
            $remainder = $totalReturnValue;

            if ($currentDue > 0) {
                $dueDeduction = min($totalReturnValue, $currentDue);
                $remainder = round($totalReturnValue - $dueDeduction, 2);
            }

            // Remainder Handling: Cash refund vs. Advance deposit
            $cashRefund = 0.00;
            $advanceCredited = 0.00;

            if ($remainder > 0) {
                $preference = $returnData['refund_preference'] ?? 'advance';
                if ($preference === 'cash') {
                    $cashRefund = $remainder;
                } else {
                    $advanceCredited = $remainder;
                    $customer->advance_balance = round((float) $customer->advance_balance + $advanceCredited, 2);
                }
            }

            // 1. Create SaleReturn Master Record
            $saleReturn = SaleReturn::create([
                'return_no' => $returnNo,
                'sale_id' => $returnData['sale_id'] ?? null,
                'customer_id' => $customer->id,
                'location_id' => $locationId,
                'return_date' => $returnData['return_date'] ?? now()->toDateString(),
                'total_return_value' => $totalReturnValue,
                'due_deduction_amount' => $dueDeduction,
                'cash_refund_amount' => $cashRefund,
                'advance_credited_amount' => $advanceCredited,
                'authorized_by' => $operatorId,
                'reason' => $returnData['reason'] ?? null,
            ]);

            // 2. Process Line Items & Inventory Condition Logic
            foreach ($preparedItems as $prep) {
                $product = $prep['product'];

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $product->id,
                    'unit_type' => $prep['unit_type'],
                    'quantity' => $prep['quantity'],
                    'pack_size' => $prep['pack_size'],
                    'condition' => $prep['condition'],
                    'unit_price' => $prep['unit_price'],
                    'line_total' => $prep['line_total'],
                ]);

                // Business Rule 3: Physical Stock Integrity
                if ($prep['condition'] === 'intact_sealed_bag') {
                    // Intact sealed bags: return to active inventory
                    StockService::recordMovement(
                        productId: $product->id,
                        quantityKgChange: $prep['weight_kg'], // Positive (stock increment)
                        transactionType: 'sale_return',
                        referenceNo: $saleReturn->return_no,
                        referenceId: $saleReturn->id,
                        unitCostPrice: (float) $product->purchase_price,
                        notes: __("Sale Return: Intact sealed bags returned by :customer", ['customer' => $customer->name]),
                        locationId: $locationId,
                        performedBy: $operatorId
                    );
                } else {
                    // Opened / Damaged goods: Do NOT add to active inventory. Route directly to damage wastage!
                    $datePrefix = date('Ym');
                    $count = StockAdjustment::whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'))->count() + 1;
                    $adjNumber = sprintf('ADJ-%s-%04d', $datePrefix, $count);

                    StockAdjustment::create([
                        'adjustment_number' => $adjNumber,
                        'location_id' => $locationId,
                        'product_id' => $product->id,
                        'type' => 'damage_wastage',
                        'mode' => 'deduct',
                        'unit_type' => $prep['unit_type'],
                        'input_quantity' => $prep['quantity'],
                        'quantity_kg' => $prep['weight_kg'],
                        'reason' => __("Wastage from Return :ret: Damaged/opened packaging - :reason", [
                            'ret' => $saleReturn->return_no,
                            'reason' => $returnData['reason'] ?? __('Damaged goods'),
                        ]),
                        'authorized_by' => $operatorId,
                    ]);
                }
            }

            // 3. Update Customer Debt & Customer Ledger
            if ($dueDeduction > 0) {
                $newDue = round($currentDue - $dueDeduction, 2);

                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'transaction_type' => 'sale_return_credit',
                    'reference_id' => $saleReturn->id,
                    'reference_no' => $saleReturn->return_no,
                    'debit_amount' => 0.00,
                    'credit_amount' => $dueDeduction,
                    'running_due_balance' => $newDue,
                    'notes' => __('Sale return credit :ret applied against outstanding debt', ['ret' => $saleReturn->return_no]),
                    'performed_by' => $operatorId,
                    'created_at' => now(),
                ]);

                $customer->current_due = $newDue;
            }

            if ($advanceCredited > 0) {
                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'transaction_type' => 'refund_adjustment',
                    'reference_id' => $saleReturn->id,
                    'reference_no' => $saleReturn->return_no . '-ADV',
                    'debit_amount' => 0.00,
                    'credit_amount' => $advanceCredited,
                    'running_due_balance' => (float) $customer->current_due,
                    'notes' => __('Excess return value ৳:amt credited to customer advance deposit', ['amt' => number_format($advanceCredited, 2)]),
                    'performed_by' => $operatorId,
                    'created_at' => now(),
                ]);
            }

            $customer->save();

            return $saleReturn;
        });
    }
}
