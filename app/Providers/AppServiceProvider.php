<?php

namespace App\Providers;

use App\Models;
use App\Policies\AppReadOnlyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super-admin bypass: Admin role has full access to all abilities
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
                return true;
            }

            return null;
        });

        // Enforce Read-Only policy for Viewer role across all models
        $models = [
            Models\ProductCategory::class,
            Models\Location::class,
            Models\Product::class,
            Models\StockAdjustment::class,
            Models\StockBalance::class,
            Models\StockLedger::class,
            Models\Customer::class,
            Models\CustomerLedger::class,
            Models\CreditLimitOverride::class,
            Models\Supplier::class,
            Models\SupplierLedger::class,
            Models\Purchase::class,
            Models\PurchaseItem::class,
            Models\Sale::class,
            Models\SaleItem::class,
            Models\PaymentCollection::class,
            Models\SaleReturn::class,
            Models\SaleReturnItem::class,
            Models\Employee::class,
            Models\Salary::class,
            Models\SalaryAdvance::class,
            Models\User::class,
            Models\ShopSetting::class,
        ];

        foreach ($models as $modelClass) {
            Gate::policy($modelClass, AppReadOnlyPolicy::class);
        }
    }
}
