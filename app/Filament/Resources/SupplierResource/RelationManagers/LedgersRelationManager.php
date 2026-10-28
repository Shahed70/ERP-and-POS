<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\SupplierLedger;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LedgersRelationManager extends RelationManager
{
    protected static string $relationship = 'ledgers';

    protected static ?string $title = 'Payable Statement of Account (পাওনা দেনার খতিয়ান)';

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
                        'opening_payable' => __('Opening Balance (প্রারম্ভিক দেনা)'),
                        'purchase_bill' => __('Purchase Challan / Bill (ক্রয় চালান)'),
                        'supplier_payment' => __('Payment Paid (পরিশোধ)'),
                        'purchase_return' => __('Purchase Return (ফেরত সমন্বয়)'),
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'supplier_payment', 'purchase_return' => 'success',
                        'purchase_bill', 'opening_payable' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reference_no')
                    ->label(__('Ref / Voucher #'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('debit_amount')
                    ->label(__('Payment Paid (-) (পরিশোধ)'))
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '-৳' . number_format($state, 2) : '-')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('credit_amount')
                    ->label(__('Billed Amount (+) (দেনা বৃদ্ধি)'))
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '+৳' . number_format($state, 2) : '-')
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('running_payable_balance')
                    ->label(__('Payable Balance (অবশিষ্ট দেনা)'))
                    ->money('BDT')
                    ->weight('bold')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Method'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? strtoupper(str_replace('_', ' ', $state)) : '-'),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(35),

                Tables\Columns\TextColumn::make('performer.name')
                    ->label(__('Staff'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->options([
                        'opening_payable' => __('Opening Balance'),
                        'purchase_bill' => __('Purchase Bill'),
                        'supplier_payment' => __('Supplier Payment'),
                        'purchase_return' => __('Purchase Return'),
                    ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
