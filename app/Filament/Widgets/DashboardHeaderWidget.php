<?php

namespace App\Filament\Widgets;

use App\Models\ShopSetting;
use Filament\Widgets\Widget;

class DashboardHeaderWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.dashboard-header-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $user = auth()->user();
        $shop = ShopSetting::getSettings();

        return [
            'user' => $user,
            'shop' => $shop,
            'isAdmin' => $user?->hasRole('Admin') ?? false,
            'dateFormatted' => now()->format('l, d F Y'),
        ];
    }
}
