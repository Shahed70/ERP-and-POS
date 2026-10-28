<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Stock & Inventory';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Stock Adjustment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock Adjustments (স্টক সমন্বয়)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Stock Adjustment Details'))
                    ->description(__('Record physical stock write-offs (damage, rat bite, loss) or surplus adjustments.'))
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->label(__('Inventory Location'))
                            ->options(Location::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Location::getDefault()?->id ?? 1)
                            ->required()
                            ->columnSpan(['default' => 12, 'sm' => 6]),

                        Forms\Components\Select::make('product_id')
                            ->label(__('Select Feed Product (পণ্য নির্বাচন)'))
                            ->options(Product::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText(function (Forms\Get $get): ?string {
                                $productId = $get('product_id');
                                if (! $productId) {
                                    return null;
                                }
                                $product = Product::find($productId);
                                return $product ? __("Current Available Stock: :stock (Pack: :pack KG)", [
                                    'stock' => $product->stock_display,
                                    'pack' => $product->pack_size,
                                ]) : null;
                            })
                            ->columnSpan(['default' => 12, 'sm' => 6]),

                        Forms\Components\Select::make('type')
                            ->label(__('Adjustment Reason / Type (কারন)'))
                            ->options([
                                'damage' => __('Damaged Bags / নষ্ট বস্তা (Decrease)'),
                                'rat_bite' => __('Rat Bite / ইঁদুরে কাটা বস্তা (Decrease)'),
                                'expired' => __('Expired Feed / মেয়াদোত্তীর্ণ ফীড (Decrease)'),
                                'theft' => __('Theft or Missing / চুরি বা ঘাটতি (Decrease)'),
                                'weighing_loss' => __('Weighing Loss / Drying / শুকানো ঘাটতি (Decrease)'),
                                'surplus_found' => __('Surplus Stock Found / বাড়তি স্টক (Increase)'),
                                'opening_stock' => __('Initial Opening Stock / প্রারম্ভিক স্টক (Increase)'),
                            ])
                            ->default('damage')
                            ->required()
                            ->live()
                            ->columnSpan(['default' => 12, 'sm' => 4]),

                        Forms\Components\Select::make('unit_type')
                            ->label(__('Unit (পরিমাপ একক)'))
                            ->options([
                                'bag' => __('Full Bags / বস্তা'),
                                'kg' => __('Loose KG / কেজি'),
                            ])
                            ->default('bag')
                            ->required()
                            ->live()
                            ->columnSpan(['default' => 12, 'sm' => 4]),

                        Forms\Components\TextInput::make('input_quantity')
                            ->label(__('Quantity (পরিমাণ)'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->live(onBlur: true)
                            ->helperText(function (Forms\Get $get): ?string {
                                $productId = $get('product_id');
                                $qty = (float) $get('input_quantity');
                                if (! $productId || $qty <= 0) {
                                    return null;
                                }
                                $product = Product::find($productId);
                                if (! $product) {
                                    return null;
                                }
                                $unit = $get('unit_type');
                                $kg = $unit === 'bag' ? ($qty * (float) $product->pack_size) : $qty;
                                return __("Equivalent: :kg KG", ['kg' => number_format($kg, 2)]);
                            })
                            ->columnSpan(['default' => 12, 'sm' => 4]),

                        Forms\Components\Textarea::make('reason')
                            ->label(__('Explanation & Audit Note (বিস্তারিত কারণ)'))
                            ->placeholder(__('Specify reason, e.g. 2 sacks torn by transport labour, moisture damage, etc.'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_number')
                    ->label(__('Adjustment #'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('Product (পণ্য)'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (StockAdjustment $record): string => "{$record->product->brand}"),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'damage' => __('Damaged Bags'),
                        'rat_bite' => __('Rat Bite'),
                        'expired' => __('Expired'),
                        'theft' => __('Theft/Loss'),
                        'weighing_loss' => __('Weighing Loss'),
                        'surplus_found' => __('Surplus Found'),
                        'opening_stock' => __('Opening Stock'),
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => in_array($state, ['surplus_found', 'opening_stock']) ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('mode')
                    ->label(__('Action'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'increase' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'increase' ? '+ Stock In' : '- Stock Out'),

                Tables\Columns\TextColumn::make('input_quantity')
                    ->label(__('Quantity'))
                    ->formatStateUsing(fn (StockAdjustment $record): string => "{$record->input_quantity} " . strtoupper($record->unit_type) . " ({$record->quantity_kg} KG)")
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('authorizer.name')
                    ->label(__('Authorized By'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date & Time'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Adjustment Type'))
                    ->options([
                        'damage' => __('Damaged Bags'),
                        'rat_bite' => __('Rat Bite'),
                        'expired' => __('Expired'),
                        'theft' => __('Theft/Loss'),
                        'weighing_loss' => __('Weighing Loss'),
                        'surplus_found' => __('Surplus Found'),
                        'opening_stock' => __('Opening Stock'),
                    ]),

                Tables\Filters\SelectFilter::make('mode')
                    ->label(__('Mode'))
                    ->options([
                        'increase' => __('Stock In (+)'),
                        'decrease' => __('Stock Out (-)'),
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
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
        ];
    }
}
