<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class SalesTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Last 7 Days Sales Trend (গত ৭ দিনের বিক্রয় প্রবাহ)';

    protected static ?string $description = 'Daily turnover across floating & sinking feed sales';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 7,
    ];

    protected static ?string $maxHeight = '280px';

    protected static string $view = 'filament.widgets.sales-trend-chart-widget';

    public function getCategoryBreakdown(): array
    {
        $products = \App\Models\Product::with(['category', 'stockBalances'])->get();
        $categories = \App\Models\ProductCategory::with('products.stockBalances')
            ->where('is_active', true)
            ->get();

        $totalBagsAll = $products->sum('total_bags');

        $breakdown = [];
        foreach ($categories->take(4) as $cat) {
            $catBags = $cat->products->sum('total_bags');
            $share = $totalBagsAll > 0 ? round(($catBags / $totalBagsAll) * 100) : 0;
            $breakdown[] = [
                'name' => $cat->name,
                'bags' => number_format($catBags),
                'share' => $share,
            ];
        }

        return $breakdown;
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D (d M)');
            $data[] = (float) Sale::whereDate('sale_date', $date->toDateString())->sum('grand_total');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Feed Sales (৳ BDT)',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(16, 185, 129, 0.92)', // Today highlighted in vibrant green
                    ],
                    'borderColor' => 'rgba(13, 148, 136, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
