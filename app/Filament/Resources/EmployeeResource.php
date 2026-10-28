<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Services\PayrollService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Staff & Payroll (কর্মচারী ও বেতন)';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Staff Employee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Employees & Staff (কর্মচারী তালিকা)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Section::make(__('Personal & Role Details (ব্যক্তিগত ও পদবি বিবরণ)'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Employee Full Name (কর্মচারীর নাম)'))
                                    ->required()
                                    ->maxLength(150)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('phone')
                                    ->label(__('Mobile Number (মোবাইল নম্বর)'))
                                    ->tel()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('designation')
                                    ->label(__('Designation / Role (পদবি)'))
                                    ->placeholder(__('e.g. Sales Manager, Delivery Van Driver, Godown Loader'))
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\DatePicker::make('joining_date')
                                    ->label(__('Joining Date (যোগদানের তারিখ)'))
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('nid')
                                    ->label(__('National ID / NID (জাতীয় পরিচয়পত্র)'))
                                    ->maxLength(30)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('emergency_contact')
                                    ->label(__('Emergency Contact / জরুরি যোগাযোগ'))
                                    ->maxLength(100)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\Textarea::make('address')
                                    ->label(__('Residential Address (ঠিকানা)'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 12, 'lg' => 7]),

                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Salary & Remuneration (বেতন ও স্থিতি)'))
                                ->schema([
                                    Forms\Components\TextInput::make('base_salary')
                                        ->label(__('Monthly Base Salary / মূল বেতন (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required()
                                        ->minValue(1),

                                    Forms\Components\TextInput::make('current_advance_due')
                                        ->label(__('Current Advance Due / অগ্রিম দেনা (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText(__('Managed automatically via cash advances & payroll deductions.')),

                                    Forms\Components\Textarea::make('bank_or_mfs_details')
                                        ->label(__('Salary Payment Account (ব্যাংক / bKash নম্বর)'))
                                        ->placeholder(__('bKash Personal: 017XXXXX or DBBL A/C #123456'))
                                        ->rows(2),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('Active Staff Member (কর্মরত)'))
                                        ->default(true),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 5]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Staff Member (কর্মচারী)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Employee $record): string => $record->designation),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone (মোবাইল)'))
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label(__('Monthly Salary'))
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('current_advance_due')
                    ->label(__('Advance Due (হাওলাত/অগ্রিম)'))
                    ->money('BDT')
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'warning' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid_salaries')
                    ->label(__('Total Paid'))
                    ->money('BDT')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('joining_date')
                    ->label(__('Joined'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active Status')),

                Tables\Filters\Filter::make('has_advance')
                    ->label(__('Has Advance Balance (অগ্রিম বকেয়া আছে)'))
                    ->query(fn ($query) => $query->where('current_advance_due', '>', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('give_advance')
                    ->label(__('Give Advance (অগ্রিম প্রদান)'))
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->modalHeading(fn (Employee $record): string => __("Disburse Cash Advance to :name", ['name' => $record->name]))
                    ->modalDescription(fn (Employee $record): string => __("Current Advance Balance: ৳:due", ['due' => number_format($record->current_advance_due, 2)]))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Advance Amount / অগ্রিম টাকা (৳)'))
                            ->numeric()
                            ->prefix('৳')
                            ->required()
                            ->minValue(100),

                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method (পদ্ধতি)'))
                            ->options([
                                'cash' => __('Cash / নগদ'),
                                'bkash' => __('bKash'),
                                'nagad' => __('Nagad'),
                                'bank_transfer' => __('Bank Transfer'),
                            ])
                            ->default('cash')
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label(__('Reason (কারণ)'))
                            ->placeholder(__('Emergency family expense, medical, etc.')),
                    ])
                    ->action(function (Employee $record, array $data): void {
                        try {
                            $advance = PayrollService::recordAdvance(
                                employee: $record,
                                amount: (float) $data['amount'],
                                reason: $data['reason'] ?? null,
                                paymentMethod: $data['payment_method'] ?? 'cash',
                                userId: auth()->id() ?? 1
                            );

                            Notification::make()
                                ->success()
                                ->title(__('Cash Advance Disbursed'))
                                ->body(__("Disbursed ৳:amount under :adv. New advance balance: ৳:due", [
                                    'amount' => number_format($data['amount'], 2),
                                    'adv' => $advance->advance_no,
                                    'due' => number_format($record->fresh()->current_advance_due, 2),
                                ]))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Advance Disbursement Failed'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Remuneration & Financial Status (বেতন ও আর্থিক স্থিতি)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('base_salary')
                            ->label(__('Monthly Base Salary (মূল বেতন)'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('current_advance_due')
                            ->label(__('Current Advance Due (বকেয়া অগ্রিম)'))
                            ->money('BDT')
                            ->badge()
                            ->color(fn ($state) => (float) $state > 0 ? 'warning' : 'success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('total_paid_salaries')
                            ->label(__('Lifetime Salaries Paid (মোট বেতন পরিশোধ)'))
                            ->money('BDT')
                            ->color('success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('joining_date')
                            ->label(__('Joining Date'))
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('is_active')
                            ->label(__('Employment Status'))
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state) => $state ? __('Active Staff') : __('Inactive / Resigned')),
                    ])
                    ->columns(['default' => 2, 'md' => 5]),

                Infolists\Components\Section::make(__('Personal & Contact Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('Full Name'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('Phone / Mobile'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('designation')
                            ->label(__('Designation')),

                        Infolists\Components\TextEntry::make('nid')
                            ->label(__('NID Number'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('emergency_contact')
                            ->label(__('Emergency Contact'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('bank_or_mfs_details')
                            ->label(__('Salary Payment Account'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('address')
                            ->label(__('Address'))
                            ->placeholder(__('N/A'))
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 2, 'md' => 3]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AdvancesRelationManager::class,
            RelationManagers\SalariesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
