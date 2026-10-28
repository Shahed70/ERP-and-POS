<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\CustomerLedger;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LedgersRelationManager extends RelationManager
{
    protected static string $relationship = 'ledgers';

    protected static ?string $title = 'Statement of Account / Due Ledger (বাকির খতিয়ান)';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_no')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date & Time'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_type')
                    ->label(__('Transaction'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'opening_due' => __('Opening Debt (প্রারম্ভিক বাকি)'),
                        'sale_invoice' => __('Sale Invoice (বিক্রয় চালান)'),
                        'due_collection' => __('Due Payment (বাকি আদায়)'),
                        'sale_return_credit' => __('Return Credit (বিক্রয় ফেরত)'),
                        'refund_adjustment' => __('Refund / Adjustment'),
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'due_collection', 'sale_return_credit' => 'success',
                        'sale_invoice', 'opening_due' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reference_no')
                    ->label(__('Ref / Receipt #'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('debit_amount')
                    ->label(__('Debit (+) (বাকি বৃদ্ধি)'))
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '+৳' . number_format($state, 2) : '-')
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('credit_amount')
                    ->label(__('Credit (-) (বাকি পরিশোধ)'))
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '-৳' . number_format($state, 2) : '-')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('running_due_balance')
                    ->label(__('Balance Due (অবশিষ্ট বাকি)'))
                    ->money('BDT')
                    ->weight('bold')
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Method'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? strtoupper($state) : '-'),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(30),

                Tables\Columns\TextColumn::make('performer.name')
                    ->label(__('Received By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->options([
                        'opening_due' => __('Opening Debt'),
                        'sale_invoice' => __('Sale Invoice'),
                        'due_collection' => __('Due Payment'),
                        'sale_return_credit' => __('Return Credit'),
                    ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
