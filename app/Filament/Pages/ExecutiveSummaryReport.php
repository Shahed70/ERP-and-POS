<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ExecutiveSummaryReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Reports & Analytics (প্রতিবেদন ও লাভ-ক্ষতি)';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.executive-summary-report';

    public ?string $from_date = null;
    public ?string $to_date = null;

    public function mount(): void
    {
        $this->from_date = now()->startOfMonth()->toDateString();
        $this->to_date = now()->toDateString();
        $this->form->fill([
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\DatePicker::make('from_date')
                            ->label(__('From Date (শুরুর তারিখ)'))
                            ->default(now()->startOfMonth())
                            ->required()
                            ->columnSpan(['default' => 12, 'sm' => 4]),

                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('To Date (শেষ তারিখ)'))
                            ->default(now())
                            ->required()
                            ->columnSpan(['default' => 12, 'sm' => 4]),
                    ]),
            ]);
    }

    public function applyFilter(): void
    {
        $data = $this->form->getState();
        $this->from_date = $data['from_date'];
        $this->to_date = $data['to_date'];
    }

    public function getExecutiveDataProperty(): array
    {
        return ReportService::getExecutiveSummary($this->from_date, $this->to_date);
    }

    public static function getNavigationLabel(): string
    {
        return __('Executive P&L Summary (লাভ-ক্ষতি ও সারসংক্ষেপ)');
    }

    public function getTitle(): string
    {
        return __('Monthly Executive Summary & Profit & Loss Statement');
    }
}
