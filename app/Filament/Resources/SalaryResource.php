<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Models\Employee;
use App\Models\Salary;
use App\Services\PayrollService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Staff & Payroll (কর্মচারী ও বেতন)';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Monthly Salary Slip');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Payroll & Salary Slips (মাসিক বেতন প্রদান)');
    }

    public static function updateNetPayable(Forms\Get $get, Forms\Set $set): void
    {
        $base = (float) ($get('base_salary') ?? 0);
        $bonus = (float) ($get('bonus_amount') ?? 0);
        $advance = (float) ($get('advance_deduction') ?? 0);
        $other = (float) ($get('other_deductions') ?? 0);

        $net = max(0.00, round($base + $bonus - $advance - $other, 2));
        $paid = (float) ($get('paid_amount') ?? $net);
        $due = max(0.00, round($net - $paid, 2));

        $set('net_payable', number_format($net, 2, '.', ''));
        $set('paid_amount', number_format($paid, 2, '.', ''));
        $set('due_amount', number_format($due, 2, '.', ''));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Section::make(__('Employee & Salary Period (কর্মচারী ও বেতনের মাস)'))
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label(__('Employee Name (কর্মচারীর নাম)'))
                                    ->relationship('employee', 'name', fn (Builder $query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $emp = Employee::find($state);
                                            if ($emp) {
                                                $set('base_salary', (float) $emp->base_salary);
                                                $set('current_advance_due_display', (float) $emp->current_advance_due);
                                                static::updateNetPayable($get, $set);
                                            }
                                        }
                                    })
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('salary_month')
                                    ->label(__('Salary Month / মাস (YYYY-MM)'))
                                    ->default(date('Y-m'))
                                    ->required()
                                    ->placeholder('2026-10')
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('base_salary')
                                    ->label(__('Base Salary / মূল বেতন (৳)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('bonus_amount')
                                    ->label(__('Festival / Bonus / বোনাস (৳)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->default(0.00)
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('advance_deduction')
                                    ->label(__('Advance Deduction / অগ্রিম কর্তন (৳)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->default(0.00)
                                    ->helperText(function (Forms\Get $get): ?string {
                                        $id = $get('employee_id');
                                        if (! $id) return null;
                                        $emp = Employee::find($id);
                                        return $emp ? __("Current Advance Due: ৳:due", ['due' => number_format($emp->current_advance_due, 2)]) : null;
                                    })
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('other_deductions')
                                    ->label(__('Other Deductions / জরিমানা বা অন্যান্য কর্তন (৳)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->default(0.00)
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                                    ->columnSpan(['default' => 12, 'sm' => 6]),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 12, 'lg' => 7]),

                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Disbursement & Payment (পরিশোধ বিবরণী)'))
                                ->schema([
                                    Forms\Components\TextInput::make('net_payable')
                                        ->label(__('Net Payable / নিট প্রদেয় বেতন (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('paid_amount')
                                        ->label(__('Paid Amount / প্রদত্ত অর্থ (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $net = (float) ($get('net_payable') ?? 0);
                                            $paid = (float) $state;
                                            $set('due_amount', number_format(max(0.00, round($net - $paid, 2)), 2, '.', ''));
                                        }),

                                    Forms\Components\TextInput::make('due_amount')
                                        ->label(__('Due Balance / বকেয়া বেতন (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\DatePicker::make('disbursement_date')
                                        ->label(__('Disbursement Date (পরিশোধের তারিখ)'))
                                        ->default(now())
                                        ->required(),

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

                                    Forms\Components\Textarea::make('notes')
                                        ->label(__('Notes (মন্তব্য)'))
                                        ->rows(2),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 5]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salary_slip_no')
                    ->label(__('Slip #'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label(__('Staff Member (কর্মচারী)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Salary $record): string => $record->employee?->designation ?? ''),

                Tables\Columns\TextColumn::make('salary_month')
                    ->label(__('Month (মাস)'))
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label(__('Base'))
                    ->money('BDT'),

                Tables\Columns\TextColumn::make('advance_deduction')
                    ->label(__('Advance Deducted'))
                    ->money('BDT')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('net_payable')
                    ->label(__('Net Payable'))
                    ->money('BDT')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('Paid (প্রদত্ত)'))
                    ->money('BDT')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label(__('Due (বকেয়া)'))
                    ->money('BDT')
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => __('Paid (পরিশোধিত)'),
                        'partial' => __('Partial Due (আংশিক দেনা)'),
                        'pending' => __('Pending (বকেয়া)'),
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label(__('Staff Employee'))
                    ->relationship('employee', 'name'),

                Tables\Filters\SelectFilter::make('salary_month')
                    ->label(__('Salary Month'))
                    ->options(fn () => Salary::distinct()->pluck('salary_month', 'salary_month')->toArray()),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'paid' => __('Paid'),
                        'partial' => __('Partial Due'),
                        'pending' => __('Pending'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (Salary $record): void {
                        PayrollService::deleteSalary($record);

                        Notification::make()
                            ->success()
                            ->title(__('Salary Slip Deleted'))
                            ->body(__('Advance deductions restored to employee balance.'))
                            ->send();
                    }),
            ])
            ->defaultSort('salary_month', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Salary Slip Summary (বেতন রসিদ সারসংক্ষেপ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('salary_slip_no')
                            ->label(__('Slip #'))
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('employee.name')
                            ->label(__('Staff Name'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('employee.designation')
                            ->label(__('Role')),

                        Infolists\Components\TextEntry::make('salary_month')
                            ->label(__('Salary Month'))
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('disbursement_date')
                            ->label(__('Disbursement Date'))
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('payment_status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid' => 'success',
                                'partial' => 'warning',
                                'pending' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(['default' => 2, 'md' => 3]),

                Infolists\Components\Section::make(__('Financial Breakdown (বেতন ও কর্তন হিসাব)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('base_salary')
                            ->label(__('Base Salary'))
                            ->money('BDT'),

                        Infolists\Components\TextEntry::make('bonus_amount')
                            ->label(__('Festival / Bonus'))
                            ->money('BDT')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('advance_deduction')
                            ->label(__('Advance Deducted'))
                            ->money('BDT')
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('other_deductions')
                            ->label(__('Other Deductions'))
                            ->money('BDT')
                            ->color('danger'),

                        Infolists\Components\TextEntry::make('net_payable')
                            ->label(__('Net Payable'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label(__('Paid Amount'))
                            ->money('BDT')
                            ->color('success')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('due_amount')
                            ->label(__('Remaining Due'))
                            ->money('BDT')
                            ->badge()
                            ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'view' => Pages\ViewSalary::route('/{record}'),
        ];
    }
}
