<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\Employee;
use App\Models\Salary;
use App\Services\PayrollService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalariesRelationManager extends RelationManager
{
    protected static string $relationship = 'salaries';

    protected static ?string $title = 'Salary Payslips & Payments (মাসিক বেতন বিবরণী)';

    public function isReadOnly(): bool
    {
        return false;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('salary_month')
                    ->label(__('Salary Month (মাস - YYYY-MM)'))
                    ->default(date('Y-m'))
                    ->required()
                    ->placeholder('2026-10')
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\TextInput::make('base_salary')
                    ->label(__('Base Salary / মূল বেতন (৳)'))
                    ->numeric()
                    ->prefix('৳')
                    ->default(fn (RelationManager $livewire): float => (float) $livewire->getOwnerRecord()->base_salary)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\TextInput::make('bonus_amount')
                    ->label(__('Bonus / উৎসব বোনাস (৳)'))
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
                    ->helperText(fn (RelationManager $livewire): string => __("Current unpaid advance: ৳:due", ['due' => number_format($livewire->getOwnerRecord()->current_advance_due, 2)]))
                    ->live()
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\TextInput::make('other_deductions')
                    ->label(__('Other Deductions / জরিমানা বা কর্তন (৳)'))
                    ->numeric()
                    ->prefix('৳')
                    ->default(0.00)
                    ->live()
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateNetPayable($get, $set))
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\TextInput::make('net_payable')
                    ->label(__('Net Payable / প্রদেয় মোট বেতন (৳)'))
                    ->numeric()
                    ->prefix('৳')
                    ->readOnly()
                    ->dehydrated()
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\TextInput::make('paid_amount')
                    ->label(__('Paid Amount / পরিশোধিত টাকা (৳)'))
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $net = (float) ($get('net_payable') ?? 0);
                        $paid = (float) $state;
                        $set('due_amount', number_format(max(0.00, round($net - $paid, 2)), 2, '.', ''));
                    })
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\TextInput::make('due_amount')
                    ->label(__('Due Amount / বকেয়া বেতন (৳)'))
                    ->numeric()
                    ->prefix('৳')
                    ->readOnly()
                    ->dehydrated()
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\Select::make('payment_method')
                    ->label(__('Payment Method (পদ্ধতি)'))
                    ->options([
                        'cash' => __('Cash / নগদ'),
                        'bkash' => __('bKash'),
                        'nagad' => __('Nagad'),
                        'bank_transfer' => __('Bank Transfer'),
                    ])
                    ->default('cash')
                    ->required()
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\DatePicker::make('disbursement_date')
                    ->label(__('Disbursement Date (পরিশোধের তারিখ)'))
                    ->default(now())
                    ->required()
                    ->columnSpan(['default' => 12, 'sm' => 6]),

                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes (মন্তব্য)'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('salary_slip_no')
            ->columns([
                Tables\Columns\TextColumn::make('salary_slip_no')
                    ->label(__('Slip #'))
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('salary_month')
                    ->label(__('Month (মাস)'))
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label(__('Base'))
                    ->money('BDT'),

                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label(__('Bonus'))
                    ->money('BDT')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('advance_deduction')
                    ->label(__('Advance Deducted'))
                    ->money('BDT')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('net_payable')
                    ->label(__('Net Payable'))
                    ->money('BDT')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('Paid (পরিশোধিত)'))
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
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Generate & Disburse Salary (বেতন প্রদান)'))
                    ->using(function (array $data, RelationManager $livewire): Salary {
                        /** @var Employee $employee */
                        $employee = $livewire->getOwnerRecord();
                        $data['employee_id'] = $employee->id;

                        return PayrollService::processSalary($data, auth()->id() ?? 1);
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (Salary $record): void {
                        PayrollService::deleteSalary($record);

                        Notification::make()
                            ->success()
                            ->title(__('Salary Deleted'))
                            ->body(__('Deducted advances restored to employee.'))
                            ->send();
                    }),
            ]);
    }
}
