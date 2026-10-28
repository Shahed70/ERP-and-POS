<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products (পণ্য তালিকা)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        // Left / Primary Column
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Basic Information (পণ্যের সাধারণ তথ্য)'))
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('Product Name (পণ্যের নাম)'))
                                        ->placeholder(__('e.g. Mega Floating Grower 3.0mm'))
                                        ->required()
                                        ->maxLength(200)
                                        ->columnSpan(['default' => 12, 'sm' => 8]),

                                    Forms\Components\TextInput::make('sku')
                                        ->label(__('SKU / Barcode (এসকেইউ)'))
                                        ->placeholder(__('Auto-generated if empty'))
                                        ->maxLength(50)
                                        ->unique(Product::class, 'sku', ignoreRecord: true)
                                        ->columnSpan(['default' => 12, 'sm' => 4]),

                                    Forms\Components\TextInput::make('brand')
                                        ->label(__('Brand / Company (ব্র্যান্ড / কোম্পানি)'))
                                        ->placeholder(__('e.g. Mega, Quality, ACI, CP, Nourish'))
                                        ->required()
                                        ->maxLength(100)
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('category_id')
                                        ->label(__('Category (ক্যাটাগরি)'))
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('feed_type')
                                        ->label(__('Feed Type (ফীডের ধরন)'))
                                        ->options([
                                            'floating' => __('Floating (ভাসমান)'),
                                            'sinking' => __('Sinking (ডুবন্ত)'),
                                            'powder' => __('Powder / Mash (গুঁড়া)'),
                                            'medicine' => __('Medicine & Supplement (ঔষধ ও পুষ্টি)'),
                                        ])
                                        ->native(false)
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\TextInput::make('pellet_size')
                                        ->label(__('Pellet / Granule Size (দানার সাইজ)'))
                                        ->placeholder(__('e.g. 0.5mm, 1.0mm, 2.0mm, 3.0mm'))
                                        ->maxLength(20)
                                        ->columnSpan(['default' => 12, 'sm' => 6]),
                                ])
                                ->columns(12),

                            Forms\Components\Section::make(__('Packaging & Units (প্যাকেজিং ও বিক্রয় একক)'))
                                ->schema([
                                    Forms\Components\TextInput::make('unit')
                                        ->label(__('Base Measurement Unit'))
                                        ->default('KG')
                                        ->readOnly()
                                        ->helperText(__('Base unit is strictly stored in KG for precise inventory tracking.'))
                                        ->columnSpan(['default' => 12, 'sm' => 4]),

                                    Forms\Components\TextInput::make('pack_size')
                                        ->label(__('Pack / Bag Size (ব্যাগের ওজন)'))
                                        ->numeric()
                                        ->default(25.00)
                                        ->suffix('KG')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                            $salePrice = (float) $get('sale_price');
                                            $packSize = (float) $state;
                                            if ($packSize > 0 && $salePrice > 0 && $get('allow_loose_sale')) {
                                                $set('loose_sale_price_per_kg', round($salePrice / $packSize, 2));
                                            }
                                        })
                                        ->columnSpan(['default' => 12, 'sm' => 4]),

                                    Forms\Components\Toggle::make('allow_loose_sale')
                                        ->label(__('Allow Loose Sale (খুচরা কেজি বিক্রয়)'))
                                        ->helperText(__('Allow opening sealed bags to sell loose KG.'))
                                        ->default(true)
                                        ->live()
                                        ->columnSpan(['default' => 12, 'sm' => 4]),
                                ])
                                ->columns(12),

                            Forms\Components\Section::make(__('Pricing & Valuation (মূল্য নির্ধারণ - টাকা ৳)'))
                                ->schema([
                                    Forms\Components\TextInput::make('purchase_price')
                                        ->label(__('Purchase Cost per Bag (ক্রয়মূল্য / বস্তা)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\TextInput::make('sale_price')
                                        ->label(__('Retail Price per Bag (খুচরা বিক্রয়মূল্য / বস্তা)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                            $packSize = (float) $get('pack_size');
                                            $salePrice = (float) $state;
                                            if ($packSize > 0 && $salePrice > 0 && $get('allow_loose_sale')) {
                                                $set('loose_sale_price_per_kg', round($salePrice / $packSize, 2));
                                            }
                                        })
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\TextInput::make('loose_sale_price_per_kg')
                                        ->label(__('Loose Price per KG (খুচরা কেজি দর)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->suffix('/ KG')
                                        ->visible(fn (Forms\Get $get): bool => (bool) $get('allow_loose_sale'))
                                        ->helperText(__('Auto-defaults to Bag Price / Pack Size; manual override allowed.'))
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\TextInput::make('wholesale_price')
                                        ->label(__('Wholesale Price per Bag (পাইকারি মূল্য / বস্তা)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->nullable()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),
                                ])
                                ->columns(12),
                        ])->columnSpan(['default' => 12, 'lg' => 8]),

                        // Right / Meta Column
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Status & Alerts (স্ট্যাটাস ও সতর্কতা)'))
                                ->schema([
                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('Active Status (সক্রিয়)'))
                                        ->default(true),

                                    Forms\Components\TextInput::make('reorder_level_bags')
                                        ->label(__('Low Stock Alert Level (কম স্টক অ্যালার্ট)'))
                                        ->numeric()
                                        ->default(10)
                                        ->suffix(__('Bags'))
                                        ->required()
                                        ->helperText(__('Alert triggers when stock drops below this number of bags.')),
                                ]),

                            Forms\Components\Section::make(__('Product Image (পণ্যের ছবি)'))
                                ->schema([
                                    Forms\Components\FileUpload::make('image_path')
                                        ->label(__('Feed Bag Photo'))
                                        ->image()
                                        ->directory('products')
                                        ->imageEditor()
                                        ->maxSize(2048)
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 4]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label(__('Image'))
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/favicon.ico')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Product (পণ্য)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => "{$record->brand} | SKU: {$record->sku}"),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Category (ক্যাটাগরি)'))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('feed_type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'floating' => 'success',
                        'sinking' => 'info',
                        'medicine' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'floating' => 'Floating',
                        'sinking' => 'Sinking',
                        'powder' => 'Powder',
                        'medicine' => 'Medicine',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('pack_size')
                    ->label(__('Pack'))
                    ->formatStateUsing(fn ($state): string => "{$state} KG")
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label(__('Cost (ক্রয়)'))
                    ->money('BDT')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label(__('Retail / Bag (খুচরা)'))
                    ->money('BDT')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('loose_sale_price_per_kg')
                    ->label(__('Loose / KG (কেজি দর)'))
                    ->formatStateUsing(fn (Product $record): string => $record->allow_loose_sale
                        ? "৳" . number_format($record->effective_loose_price_per_kg, 2)
                        : __('N/A'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reorder_level_bags')
                    ->label(__('Alert At'))
                    ->suffix(' bags')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('brand')
                    ->label(__('Brand'))
                    ->options(fn (): array => Product::distinct()->pluck('brand', 'brand')->filter()->toArray()),

                Tables\Filters\SelectFilter::make('feed_type')
                    ->label(__('Feed Type'))
                    ->options([
                        'floating' => __('Floating'),
                        'sinking' => __('Sinking'),
                        'powder' => __('Powder'),
                        'medicine' => __('Medicine'),
                    ]),

                Tables\Filters\TernaryFilter::make('allow_loose_sale')
                    ->label(__('Allow Loose Sale')),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active Status')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete Product'))
                    ->modalDescription(__('Are you sure you want to delete this product?'))
                    ->successNotificationTitle(__('Product deleted successfully')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
