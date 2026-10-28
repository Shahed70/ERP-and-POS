<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLedger;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Atomically record an inventory stock movement.
     *
     * @param int $productId
     * @param float $quantityKgChange Signed value: positive for in, negative for out
     * @param string $transactionType e.g., 'purchase', 'sale', 'damage_wastage', 'manual_adjustment_surplus', 'opening_stock'
     * @param string|null $referenceNo e.g., 'INV-202610-001', 'ADJ-202610-0001'
     * @param int|null $referenceId
     * @param float $unitCostPrice
     * @param string|null $notes
     * @param int|null $locationId Default operational location if null
     * @param int|null $performedBy Auth user if null
     * @return StockBalance
     * @throws Exception
     */
    public static function recordMovement(
        int $productId,
        float $quantityKgChange,
        string $transactionType,
        ?string $referenceNo = null,
        ?int $referenceId = null,
        float $unitCostPrice = 0.0,
        ?string $notes = null,
        ?int $locationId = null,
        ?int $performedBy = null
    ): StockBalance {
        return DB::transaction(function () use (
            $productId,
            $quantityKgChange,
            $transactionType,
            $referenceNo,
            $referenceId,
            $unitCostPrice,
            $notes,
            $locationId,
            $performedBy
        ) {
            $locId = $locationId ?? (Location::getDefault()?->id ?? 1);
            $userId = $performedBy ?? (auth()->id() ?? 1);

            // Fetch product to verify existence and pack size
            $product = Product::findOrFail($productId);

            // Fetch or create stock balance row with pessimistic write lock
            $stockBalance = StockBalance::where('location_id', $locId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stockBalance) {
                $stockBalance = new StockBalance([
                    'location_id' => $locId,
                    'product_id' => $productId,
                    'total_weight_kg' => 0.00,
                ]);
            }

            $currentKg = (float) $stockBalance->total_weight_kg;
            $newKg = round($currentKg + $quantityKgChange, 2);

            // Strict Zero Negative Stock Enforcement
            if ($newKg < 0) {
                $deficitKg = abs($newKg);
                $availableBags = (int) floor($currentKg / ($product->pack_size ?: 25));
                $availableLoose = round(fmod($currentKg, ($product->pack_size ?: 25)), 2);

                throw new Exception(
                    __("Insufficient stock for product ':product'. Available: :bags Bags + :loose KG (:totalKg KG). Deficit: :deficitKg KG.", [
                        'product' => $product->name,
                        'bags' => $availableBags,
                        'loose' => $availableLoose,
                        'totalKg' => $currentKg,
                        'deficitKg' => $deficitKg,
                    ])
                );
            }

            // Update balance
            $stockBalance->total_weight_kg = $newKg;
            $stockBalance->save();

            // Record immutable audit ledger entry
            StockLedger::create([
                'location_id' => $locId,
                'product_id' => $productId,
                'transaction_type' => $transactionType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'quantity_kg_change' => $quantityKgChange,
                'running_balance_kg' => $newKg,
                'unit_cost_price' => $unitCostPrice > 0 ? $unitCostPrice : (float) $product->purchase_price,
                'notes_or_reason' => $notes,
                'performed_by' => $userId,
                'created_at' => now(),
            ]);

            return $stockBalance;
        });
    }
}
