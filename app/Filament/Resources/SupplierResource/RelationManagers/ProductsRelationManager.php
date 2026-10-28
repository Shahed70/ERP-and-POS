<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Supplied Products & Last Purchase Rates (সরবরাহকৃত খাদ্য ও ক্রয়দর)';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('last_purchase_price')
                    ->label(__('Last Purchase Price per Bag (সর্বশেষ ক্রয়দর / বস্তা)'))
                    ->numeric()
                    ->prefix('৳')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Product Name (পণ্যের নাম)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): string => "{$record->brand} | SKU: {$record->sku}"),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->badge(),

                Tables\Columns\TextColumn::make('pack_size')
                    ->label(__('Pack Size'))
                    ->formatStateUsing(fn ($state): string => "{$state} KG"),

                Tables\Columns\TextColumn::make('pivot.last_purchase_price')
                    ->label(__('Last Invoiced Rate (দর / বস্তা)'))
                    ->money('BDT')
                    ->weight('bold')
                    ->color('primary')
                    ->placeholder(fn (Product $record): string => "৳" . number_format($record->purchase_price, 2)),

                Tables\Columns\TextColumn::make('pivot.last_purchased_at')
                    ->label(__('Last Purchased Date'))
                    ->dateTime('d M Y')
                    ->placeholder(__('Not purchased yet')),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label(__('Link Supplied Product'))
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('Select Feed Product'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('last_purchase_price', $product->purchase_price);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('last_purchase_price')
                            ->label(__('Negotiated / Last Purchase Price per Bag (৳)'))
                            ->numeric()
                            ->prefix('৳')
                            ->helperText(__('Default purchase rate from product catalog or negotiate a special rate.'))
                            ->default(fn (Forms\Get $get) => Product::find($get('recordId'))?->purchase_price),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit Rate')),
                Tables\Actions\DetachAction::make()
                    ->label(__('Unlink')),
            ]);
    }
}
