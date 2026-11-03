<?php

namespace App\Filament\Pages;

use App\Models\ShopSetting;
use Filament\Pages\Page;

class OfflinePosPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?string $slug = 'offline-pos';

    protected static ?string $navigationGroup = 'Sales & POS (বিক্রয় ও পিওএস)';

    protected static ?string $navigationLabel = 'Offline POS & Sync (অফলাইন পিওএস)';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.offline-pos-page';

    public function getHeading(): string
    {
        return __('Offline POS & Automatic Sync Manager (অফলাইন বিক্রয় ও অটো-সিঙ্ক)');
    }

    public function getSubheading(): ?string
    {
        return __('Record sales, due collections, and expenses without internet. All data will automatically sync to the database when connection is restored.');
    }
}
