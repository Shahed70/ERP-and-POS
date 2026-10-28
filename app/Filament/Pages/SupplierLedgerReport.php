<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SupplierLedgerReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Reports & Analytics (প্রতিবেদন ও লাভ-ক্ষতি)';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.supplier-ledger-report';

    public static function getNavigationLabel(): string
    {
        return __('Supplier Purchases & Payables (সরবরাহকারী পাওনা খতিয়ান)');
    }

    public function getTitle(): string
    {
        return __('Supplier Procurement & Outstanding Payable Summary');
    }

    public function getSupplierDataProperty(): Collection
    {
        return ReportService::getSupplierLedgerReport();
    }
}
