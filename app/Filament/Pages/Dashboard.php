<?php

namespace App\Filament\Pages;

use App\Models\ShopSetting;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getTitle(): string | Htmlable
    {
        $shop = ShopSetting::getSettings();
        return $shop->shop_name ?? __('Business Dashboard');
    }

    public function getSubheading(): string | Htmlable | null
    {
        $shop = ShopSetting::getSettings();
        return $shop->shop_tagline ?? __('Fish Feed Retail & Wholesale ERP');
    }

    public function getColumns(): int | string | array
    {
        return 12;
    }
}
