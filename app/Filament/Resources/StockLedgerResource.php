<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockLedgerResource\Pages;
use App\Models\StockLedger;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockLedgerResource extends Resource
{
    protected static ?string $model = StockLedger::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Stock & Inventory';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('Stock Ledger');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock Movement Ledger (মুভমেন্ট খতিয়ান)');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date & Time'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('Product (পণ্য)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (StockLedger $record): string => "{$record->product->brand}"),

                Tables\Columns\TextColumn::make('transaction_type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'purchase' => __('Purchase (ক্রয়)'),
                        'sale' => __('Sale (বিক্রয়)'),
                        'sale_return' => __('Sale Return (ফেরত)'),
                        'purchase_return' => __('Purchase Return'),
                        'damage_wastage' => __('Damage / Wastage (ঘাটতি)'),
                        'manual_adjustment_surplus' => __('Surplus Adjusted'),
                        'opening_stock' => __('Opening Stock'),
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'purchase', 'sale_return', 'manual_adjustment_surplus', 'opening_stock' => 'success',
                        'sale', 'damage_wastage', 'purchase_return' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reference_no')
                    ->label(__('Ref #'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity_kg_change')
                    ->label(__('Change (ওজন পরিবর্তন)'))
                    ->formatStateUsing(fn (StockLedger $record): string => ($record->quantity_kg_change > 0 ? '+' : '') . number_format($record->quantity_kg_change, 2) . ' KG')
                    ->color(fn (StockLedger $record): string => $record->quantity_kg_change > 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('running_balance_kg')
                    ->label(__('Running Balance (বর্তমান ব্যালেন্স)'))
                    ->formatStateUsing(function (StockLedger $record): string {
                        $packSize = (float) ($record->product->pack_size ?: 25.00);
                        $bags = (int) floor((float) $record->running_balance_kg / $packSize);
                        $loose = round(fmod((float) $record->running_balance_kg, $packSize), 2);
                        return number_format($record->running_balance_kg, 2) . " KG ({$bags} Bags + {$loose} KG)";
                    })
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes_or_reason')
                    ->label(__('Notes / Reason'))
                    ->limit(35)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('performer.name')
                    ->label(__('Staff'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label(__('Product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->options([
                        'purchase' => __('Purchase (ক্রয়)'),
                        'sale' => __('Sale (বিক্রয়)'),
                        'sale_return' => __('Sale Return (বিক্রয় ফেরত)'),
                        'purchase_return' => __('Purchase Return'),
                        'damage_wastage' => __('Damage / Wastage (ঘাটতি)'),
                        'manual_adjustment_surplus' => __('Surplus Adjusted'),
                        'opening_stock' => __('Opening Stock'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLedgers::route('/'),
        ];
    }
}
