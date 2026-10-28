<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\Employee;
use App\Models\SalaryAdvance;
use App\Services\PayrollService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AdvancesRelationManager extends RelationManager
{
    protected static string $relationship = 'advances';

    protected static ?string $title = 'Salary Advances (অগ্রিম বেতন ও হাওলাত)';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label(__('Advance Amount / অগ্রিম টাকা (৳)'))
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->minValue(1),

                Forms\Components\DatePicker::make('advance_date')
                    ->label(__('Date (তারিখ)'))
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

                Forms\Components\Textarea::make('reason')
                    ->label(__('Reason / Purpose (কারণ)'))
                    ->placeholder(__('e.g. Medical emergency, family festival expense...'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('advance_no')
            ->columns([
                Tables\Columns\TextColumn::make('advance_no')
                    ->label(__('Advance # (অগ্রিম ভাউচার)'))
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('advance_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount (টাকা)'))
                    ->money('BDT')
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(35),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label(__('Approved By')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Disburse Cash Advance (অগ্রিম প্রদান)'))
                    ->using(function (array $data, RelationManager $livewire): SalaryAdvance {
                        /** @var Employee $employee */
                        $employee = $livewire->getOwnerRecord();

                        return PayrollService::recordAdvance(
                            employee: $employee,
                            amount: (float) $data['amount'],
                            reason: $data['reason'] ?? null,
                            paymentMethod: $data['payment_method'] ?? 'cash',
                            userId: auth()->id() ?? 1
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (SalaryAdvance $record): void {
                        PayrollService::deleteAdvance($record);

                        Notification::make()
                            ->success()
                            ->title(__('Advance Deleted'))
                            ->body(__('Advance due balance reverted on employee record.'))
                            ->send();
                    }),
            ]);
    }
}
