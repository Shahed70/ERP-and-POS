<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ProductMovementReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports & Analytics (প্রতিবেদন ও লাভ-ক্ষতি)';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.product-movement-report';

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
                            ->columnSpan(['default' => 12, 'sm' => 4]),

                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('To Date (শেষ তারিখ)'))
                            ->default(now())
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

    public function getMovementDataProperty(): Collection
    {
        return ReportService::getProductMovementReport($this->from_date, $this->to_date);
    }

    public static function getNavigationLabel(): string
    {
        return __('Product Sales & Movement Velocity (পণ্য বিক্রয় ও মার্জিন)');
    }

    public function getTitle(): string
    {
        return __('Feed Product Sales Velocity & Margin Analysis');
    }
}
