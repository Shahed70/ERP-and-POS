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
        'default' => 12,
        'lg' => 7,
    ];

    protected static ?string $maxHeight = '280px';

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
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(16, 185, 129, 0.85)', // Today in vibrant green
                    ],
                    'borderColor' => 'rgba(14, 165, 233, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
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
