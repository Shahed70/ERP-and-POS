<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\PaymentCollection;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // 1. Today's Sales
        $todaySalesAmount = (float) Sale::whereDate('sale_date', $today)->sum('grand_total');
        $yesterdaySales = (float) Sale::whereDate('sale_date', $yesterday)->sum('grand_total');
        $todayInvoicesCount = Sale::whereDate('sale_date', $today)->count();

        $diff = $todaySalesAmount - $yesterdaySales;
        $trendText = $yesterdaySales > 0
            ? (($diff >= 0 ? '+' : '') . number_format(($diff / $yesterdaySales) * 100, 1) . '% vs yesterday · ')
            : '';

        // 7-day sparkline trend
        $past7DaysSales = [];
        $past7DaysCollection = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $s = (float) Sale::whereDate('sale_date', $d)->sum('grand_total');
            $c = (float) Sale::whereDate('sale_date', $d)->sum('paid_amount') + (float) PaymentCollection::whereDate('payment_date', $d)->sum('amount_paid');
            $past7DaysSales[] = $s;
            $past7DaysCollection[] = $c;
        }

        // 2. Today's Cash Collection (POS sales cash paid + payment collections)
        $todaySaleCash = (float) Sale::whereDate('sale_date', $today)->sum('paid_amount');
        $todayPaymentCollection = (float) PaymentCollection::whereDate('payment_date', $today)->sum('amount_paid');
        $todayTotalCollection = $todaySaleCash + $todayPaymentCollection;

        // 3. Total Customer Due
        $totalCustomerDue = (float) Customer::sum('current_due');
        $activeDebtorCount = Customer::where('current_due', '>', 0)->count();

        // 4. Feed Mill Payables
        $totalSupplierPayable = (float) Supplier::sum('current_payable');
        $creditorCount = Supplier::where('current_payable', '>', 0)->count();

        // 5. Total Stock in Warehouse
        $totalStockKg = 0.00;
        $totalValuation = 0.00;
        $products = Product::with('stockBalances')->get();
        foreach ($products as $p) {
            $kg = $p->total_stock_kg;
            $totalStockKg += $kg;
            $costPerKg = (float) $p->pack_size > 0 ? ((float) $p->purchase_price / (float) $p->pack_size) : 0.00;
            $totalValuation += ($kg * $costPerKg);
        }

        return [
            Stat::make(__('TODAY\'S SALES (আজকের বিক্রয়)'), '৳' . number_format($todaySalesAmount, 2))
                ->description("{$trendText}{$todayInvoicesCount} " . __('invoices today'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($past7DaysSales)
                ->color('success'),

            Stat::make(__('TODAY\'S COLLECTION (নগদ আদায়)'), '৳' . number_format($todayTotalCollection, 2))
                ->description('Cash: ৳' . number_format($todaySaleCash) . ' · Due Recovery: ৳' . number_format($todayPaymentCollection))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($past7DaysCollection)
                ->color('primary'),

            Stat::make(__('CUSTOMER DUE (বাকী পাওনা)'), '৳' . number_format($totalCustomerDue, 2))
                ->description("{$activeDebtorCount} " . __('active fish farmers with balance'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger'),

            Stat::make(__('FEED MILLS PAYABLE (কোম্পানির দেনা)'), '৳' . number_format($totalSupplierPayable, 2))
                ->description("{$creditorCount} " . __('feed mill suppliers'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning'),

            Stat::make(__('GODOWN STOCK (মজুদ ফিড)'), '৳' . number_format($totalValuation, 2))
                ->description(number_format($totalStockKg, 1) . ' KG ' . __('total physical inventory'))
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),
        ];
    }
}
