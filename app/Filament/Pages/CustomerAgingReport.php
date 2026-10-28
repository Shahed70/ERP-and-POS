<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CustomerAgingReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Reports & Analytics (প্রতিবেদন ও লাভ-ক্ষতি)';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.customer-aging-report';

    public static function getNavigationLabel(): string
    {
        return __('Customer Aging Due Report (বকেয়া বয়সভিত্তিক খতিয়ান)');
    }

    public function getTitle(): string
    {
        return __('Customer Debt Aging Analysis (>30, >60, >90 Days)');
    }

    public function getAgingDataProperty(): Collection
    {
        return ReportService::getCustomerAgingReport();
    }
}
