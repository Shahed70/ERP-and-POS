<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockBalanceResource\Pages;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Services\StockService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockBalanceResource extends Resource
{
    protected static ?string $model = StockBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Stock & Inventory';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Stock Balance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Current Stock (বর্তমান স্টক)');
    }

    public static function canCreate(): bool
    {
        // Stock is managed via Purchases, Sales, or Adjustments
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product.image_path')
                    ->label(__('Image'))
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/favicon.ico')),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('Product (পণ্য)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (StockBalance $record): string => "{$record->product->brand} | SKU: {$record->product->sku}"),

                Tables\Columns\TextColumn::make('product.category.name')
                    ->label(__('Category'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_weight_kg')
                    ->label(__('Total Weight (মোট ওজন)'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' KG')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('formatted_stock')
                    ->label(__('Packaging Stock (বস্তা ও কেজি)'))
                    ->badge()
                    ->color(fn (StockBalance $record): string => match (true) {
                        $record->is_out_of_stock => 'danger',
                        $record->is_low_stock => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label(__('Status'))
                    ->badge()
                    ->state(fn (StockBalance $record): string => match (true) {
                        $record->is_out_of_stock => __('Out of Stock'),
                        $record->is_low_stock => __('Low Stock Alert'),
                        default => __('Normal'),
                    })
                    ->color(fn (StockBalance $record): string => match (true) {
                        $record->is_out_of_stock => 'danger',
                        $record->is_low_stock => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('cost_valuation')
                    ->label(__('Cost Value (ক্রয় মূল্য)'))
                    ->money('BDT')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('total_weight_kg', $direction);
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('retail_valuation')
                    ->label(__('Retail Value (বিক্রয় মূল্য)'))
                    ->money('BDT')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product.category_id')
                    ->label(__('Category'))
                    ->relationship('product.category', 'name'),

                Tables\Filters\Filter::make('low_stock')
                    ->label(__('Low Stock Alert Only'))
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('product', function ($q) {
                            $q->whereRaw('stock_balances.total_weight_kg <= (products.reorder_level_bags * products.pack_size)')
                              ->where('stock_balances.total_weight_kg', '>', 0);
                        });
                    }),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label(__('Out of Stock Only'))
                    ->query(fn (Builder $query): Builder => $query->where('total_weight_kg', '<=', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('quick_adjust')
                    ->label(__('Adjust Stock'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->modalHeading(fn (StockBalance $record): string => __("Adjust Stock for ':product'", ['product' => $record->product->name]))
                    ->modalDescription(fn (StockBalance $record): string => __("Current Stock: :stock", ['stock' => $record->formatted_stock]))
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label(__('Adjustment Type (কারন)'))
                            ->options([
                                'damage' => __('Damaged Bags / নষ্ট বস্তা (Decrease)'),
                                'rat_bite' => __('Rat Bite / ইঁদুরে কাটা (Decrease)'),
                                'expired' => __('Expired / মেয়াদোত্তীর্ণ (Decrease)'),
                                'theft' => __('Theft or Lost / ঘাটতি (Decrease)'),
                                'weighing_loss' => __('Weighing Loss / Drying / শুকানো ঘাটতি (Decrease)'),
                                'surplus_found' => __('Surplus Stock Found / উদ্বৃত্ত স্টক (Increase)'),
                            ])
                            ->required()
                            ->default('damage'),

                        Forms\Components\Select::make('unit_type')
                            ->label(__('Unit (একক)'))
                            ->options([
                                'bag' => __('Full Bags / বস্তা'),
                                'kg' => __('Loose KG / কেজি'),
                            ])
                            ->default('bag')
                            ->required(),

                        Forms\Components\TextInput::make('input_quantity')
                            ->label(__('Quantity (পরিমাণ)'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01),

                        Forms\Components\Textarea::make('reason')
                            ->label(__('Reason / Notes (বিস্তারিত কারণ)'))
                            ->required(),
                    ])
                    ->action(function (StockBalance $record, array $data): void {
                        $product = $record->product;
                        $packSize = (float) ($product->pack_size ?: 25.00);
                        $inputQty = (float) $data['input_quantity'];

                        $kgChange = $data['unit_type'] === 'bag' ? ($inputQty * $packSize) : $inputQty;
                        $isIncrease = $data['type'] === 'surplus_found';
                        $signedKg = $isIncrease ? $kgChange : -$kgChange;

                        try {
                            $adj = StockAdjustment::create([
                                'location_id' => $record->location_id,
                                'product_id' => $record->product_id,
                                'type' => $data['type'],
                                'mode' => $isIncrease ? 'increase' : 'decrease',
                                'unit_type' => $data['unit_type'],
                                'input_quantity' => $inputQty,
                                'quantity_kg' => $kgChange,
                                'reason' => $data['reason'],
                                'authorized_by' => auth()->id() ?? 1,
                            ]);

                            StockService::recordMovement(
                                productId: $record->product_id,
                                quantityKgChange: $signedKg,
                                transactionType: $isIncrease ? 'manual_adjustment_surplus' : 'damage_wastage',
                                referenceNo: $adj->adjustment_number,
                                referenceId: $adj->id,
                                notes: $data['reason'],
                                locationId: $record->location_id,
                                performedBy: auth()->id() ?? 1,
                            );

                            Notification::make()
                                ->success()
                                ->title(__('Stock Adjusted Successfully'))
                                ->body(__("New stock for :product is :stock", [
                                    'product' => $product->name,
                                    'stock' => $record->fresh()->formatted_stock,
                                ]))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Adjustment Failed'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('total_weight_kg', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockBalances::route('/'),
        ];
    }
}
