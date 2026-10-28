<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get Executive Financial Summary (P&L, Balances, Inventory Valuation).
     *
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public static function getExecutiveSummary(?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        // 1. Sales Revenue & Cash Collections
        $salesQuery = Sale::whereBetween('sale_date', [$from->toDateString(), $to->toDateString()]);
        $grossSales = (float) $salesQuery->sum('subtotal_amount');
        $discounts = (float) $salesQuery->sum('discount_amount');
        $netSalesRevenue = (float) $salesQuery->sum('grand_total');
        $counterCashCollected = (float) $salesQuery->sum('paid_amount');
        $salesDueAccrued = (float) $salesQuery->sum('due_amount');

        // 2. Cost of Goods Sold (COGS)
        $saleIds = $salesQuery->pluck('id');
        $cogs = (float) SaleItem::whereIn('sale_id', $saleIds)
            ->selectRaw('SUM(CASE WHEN unit_type = "bag" THEN (quantity * unit_cost_price) ELSE (deducted_weight_kg * unit_cost_price) END) as total_cogs')
            ->value('total_cogs') ?? 0.00;

        // 3. Gross Profit
        $grossProfit = round($netSalesRevenue - $cogs, 2);
        $grossMarginPercent = $netSalesRevenue > 0 ? round(($grossProfit / $netSalesRevenue) * 100, 2) : 0.00;

        // 4. Operating Expenses
        // A. Staff Salaries Paid
        $salariesPaid = (float) Salary::whereBetween('disbursement_date', [$from->toDateString(), $to->toDateString()])
            ->sum('paid_amount');

        // B. Inward Consignment Transport & Labor Freight
        $purchaseFreight = (float) Purchase::whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->sum('transport_fare_labor_cost');

        // C. Damage & Wastage Cost
        $wastageCost = (float) StockAdjustment::where('stock_adjustments.type', 'damage_wastage')
            ->whereBetween('stock_adjustments.created_at', [$from, $to])
            ->join('products', 'stock_adjustments.product_id', '=', 'products.id')
            ->selectRaw('SUM(stock_adjustments.quantity_kg * (products.purchase_price / COALESCE(products.pack_size, 25))) as wastage_val')
            ->value('wastage_val') ?? 0.00;

        $totalOperatingExpenses = round($salariesPaid + $purchaseFreight + $wastageCost, 2);

        // 5. Net Profit
        $netProfit = round($grossProfit - $totalOperatingExpenses, 2);

        // 6. Balance Sheet Positions (Current)
        $totalCustomerDues = (float) Customer::sum('current_due');
        $totalCustomerAdvance = (float) Customer::sum('advance_balance');
        $totalSupplierPayables = (float) Supplier::sum('current_payable');
        $totalStaffAdvanceDue = (float) Employee::sum('current_advance_due');

        // 7. Closing Inventory Valuation
        $inventoryValuation = 0.00;
        $totalStockKg = 0.00;
        $products = Product::with('stockBalances')->get();
        foreach ($products as $p) {
            $kg = $p->total_stock_kg;
            $totalStockKg += $kg;
            $costPerKg = (float) $p->pack_size > 0 ? ((float) $p->purchase_price / (float) $p->pack_size) : 0.00;
            $inventoryValuation += ($kg * $costPerKg);
        }
        $inventoryValuation = round($inventoryValuation, 2);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'formatted' => $from->format('d M Y') . ' — ' . $to->format('d M Y'),
            ],
            'revenue' => [
                'gross_sales' => $grossSales,
                'discounts' => $discounts,
                'net_sales' => $netSalesRevenue,
                'cash_collected' => $counterCashCollected,
                'due_accrued' => $salesDueAccrued,
            ],
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin_percent' => $grossMarginPercent,
            'expenses' => [
                'salaries_paid' => $salariesPaid,
                'purchase_freight' => $purchaseFreight,
                'wastage_cost' => round($wastageCost, 2),
                'total_expenses' => $totalOperatingExpenses,
            ],
            'net_profit' => $netProfit,
            'balances' => [
                'customer_dues' => $totalCustomerDues,
                'customer_advance' => $totalCustomerAdvance,
                'supplier_payables' => $totalSupplierPayables,
                'staff_advance_due' => $totalStaffAdvanceDue,
                'inventory_valuation' => $inventoryValuation,
                'total_stock_kg' => round($totalStockKg, 1),
            ],
        ];
    }

    /**
     * Get Customer Aging Report (>30, >60, >90 days).
     *
     * @return Collection
     */
    public static function getCustomerAgingReport(): Collection
    {
        $customers = Customer::where('current_due', '>', 0)
            ->orderBy('current_due', 'desc')
            ->get();

        $now = Carbon::now();

        return $customers->map(function (Customer $customer) use ($now) {
            $bucket0to30 = 0.00;
            $bucket31to60 = 0.00;
            $bucket61to90 = 0.00;
            $bucketOver90 = 0.00;

            // Group unpaid invoices into aging buckets
            $unpaidSales = Sale::where('customer_id', $customer->id)
                ->where('unallocated_due', '>', 0)
                ->get();

            $totalInvoiceDueAllocated = 0.00;

            foreach ($unpaidSales as $sale) {
                $days = Carbon::parse($sale->sale_date)->diffInDays($now);
                $due = (float) $sale->unallocated_due;
                $totalInvoiceDueAllocated += $due;

                if ($days <= 30) {
                    $bucket0to30 += $due;
                } elseif ($days <= 60) {
                    $bucket31to60 += $due;
                } elseif ($days <= 90) {
                    $bucket61to90 += $due;
                } else {
                    $bucketOver90 += $due;
                }
            }

            // If there is opening debt not tied to sales invoices, put into >90 days
            $unlinkedDue = (float) $customer->current_due - $totalInvoiceDueAllocated;
            if ($unlinkedDue > 0) {
                $bucketOver90 += $unlinkedDue;
            }

            $creditLimit = (float) $customer->credit_limit;
            $isRiskExceeded = $creditLimit > 0 && ((float) $customer->current_due > $creditLimit);

            return [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'total_due' => (float) $customer->current_due,
                'credit_limit' => $creditLimit,
                'is_risk_exceeded' => $isRiskExceeded,
                'bucket_0_30' => round($bucket0to30, 2),
                'bucket_31_60' => round($bucket31to60, 2),
                'bucket_61_90' => round($bucket61to90, 2),
                'bucket_over_90' => round($bucketOver90, 2),
            ];
        });
    }

    /**
     * Get Product Sales Velocity & Profit Margin Report.
     *
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return Collection
     */
    public static function getProductMovementReport(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        $products = Product::with('category')->get();

        return $products->map(function (Product $product) use ($from, $to) {
            $items = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', function ($q) use ($from, $to) {
                    $q->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()]);
                })
                ->get();

            $bagsSold = (int) $items->where('unit_type', 'bag')->sum('quantity');
            $looseKgSold = (float) $items->where('unit_type', 'kg')->sum('quantity');
            $totalKgSold = (float) $items->sum('deducted_weight_kg');

            $revenue = (float) $items->sum('line_total');

            $packSize = (float) ($product->pack_size ?: 25.00);
            $costPerKg = $packSize > 0 ? ((float) $product->purchase_price / $packSize) : 0.00;
            $cost = round($totalKgSold * $costPerKg, 2);

            $profit = round($revenue - $cost, 2);
            $marginPercent = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.00;

            $currentStockKg = $product->total_stock_kg;
            $currentBags = (int) floor($currentStockKg / $packSize);
            $currentStockValue = round($currentStockKg * $costPerKg, 2);

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'category' => $product->category?->name ?? 'Uncategorized',
                'pack_size' => $packSize,
                'bags_sold' => $bagsSold,
                'loose_kg_sold' => round($looseKgSold, 2),
                'total_kg_sold' => round($totalKgSold, 2),
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin_percent' => $marginPercent,
                'stock_kg' => round($currentStockKg, 1),
                'stock_bags' => $currentBags,
                'stock_value' => $currentStockValue,
            ];
        })->sortByDesc('revenue')->values();
    }

    /**
     * Get Supplier Purchases & Payables Report.
     *
     * @return Collection
     */
    public static function getSupplierLedgerReport(): Collection
    {
        $suppliers = Supplier::with('products')->get();

        return $suppliers->map(function (Supplier $supplier) {
            $totalBilled = (float) $supplier->total_purchases;
            $totalPaid = (float) $supplier->total_paid;
            $currentPayable = (float) $supplier->current_payable;
            $lastPurchase = Purchase::where('supplier_id', $supplier->id)->latest('purchase_date')->first();

            return [
                'supplier_id' => $supplier->id,
                'company_name' => $supplier->company_name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'feeds_count' => $supplier->products->count(),
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'current_payable' => $currentPayable,
                'last_purchase_date' => $lastPurchase?->purchase_date?->format('d M Y') ?? __('Never'),
                'is_active' => $supplier->is_active,
            ];
        })->sortByDesc('current_payable')->values();
    }
}
