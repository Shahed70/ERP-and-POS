<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\Widget;

class LowStockAlertWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.low-stock-alert-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'default' => 12,
        'lg' => 5,
    ];

    public function getViewData(): array
    {
        $products = Product::with(['category', 'stockBalances'])
            ->where('is_active', true)
            ->get();

        // Filter products where total bags is below reorder level (or <= 20 bags)
        $lowStockProducts = $products->filter(function ($p) {
            $threshold = $p->reorder_level_bags ?? 20;
            return $p->total_bags <= $threshold;
        })->sortBy('total_bags')->take(5);

        return [
            'lowStockItems' => $lowStockProducts,
            'criticalCount' => $lowStockProducts->count(),
            'isAdmin' => auth()->user()?->hasRole('Admin') ?? false,
        ];
    }
}
