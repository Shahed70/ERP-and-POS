<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleReturnResource\Pages;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\SaleReturnService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleReturnResource extends Resource
{
    protected static ?string $model = SaleReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'Sales & POS (বিক্রয় ও পিওএস)';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('Sale Return Claim');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sale Returns & Wastage (পণ্য ফেরত ও অপচয়)');
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $total = 0.00;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $rate = (float) ($item['unit_price'] ?? 0);
            $total += ($qty * $rate);
        }

        $customerId = $get('customer_id');
        $currentDue = 0.00;
        if ($customerId) {
            $customer = Customer::find($customerId);
            $currentDue = (float) ($customer?->current_due ?? 0);
        }

        $dueDeduction = 0.00;
        $remainder = $total;
        if ($currentDue > 0) {
            $dueDeduction = min($total, $currentDue);
            $remainder = max(0.00, round($total - $dueDeduction, 2));
        }

        $preference = $get('refund_preference') ?: 'advance';
        $cashRefund = $preference === 'cash' ? $remainder : 0.00;
        $advanceCredited = $preference === 'advance' ? $remainder : 0.00;

        $set('total_return_value', number_format($total, 2, '.', ''));
        $set('due_deduction_amount', number_format($dueDeduction, 2, '.', ''));
        $set('cash_refund_amount', number_format($cashRefund, 2, '.', ''));
        $set('advance_credited_amount', number_format($advanceCredited, 2, '.', ''));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        // Left Column: Customer, Sale & Items
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Return Claim & Customer Info (ফেরত দাবি ও ক্রেতা তথ্য)'))
                                ->schema([
                                    Forms\Components\Select::make('customer_id')
                                        ->label(__('Customer Name (ক্রেতার নাম)'))
                                        ->relationship('customer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set))
                                        ->helperText(function (Forms\Get $get): ?string {
                                            $id = $get('customer_id');
                                            if (! $id) {
                                                return null;
                                            }
                                            $c = Customer::find($id);
                                            return $c ? __("Current Outstanding Due: ৳:due | Advance Deposit: ৳:adv", [
                                                'due' => number_format($c->current_due, 2),
                                                'adv' => number_format($c->advance_balance, 2),
                                            ]) : null;
                                        })
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('sale_id')
                                        ->label(__('Linked Sale Invoice (চালান নং - ঐচ্ছিক)'))
                                        ->options(function (Forms\Get $get) {
                                            $customerId = $get('customer_id');
                                            if (! $customerId) {
                                                return Sale::latest('id')->take(20)->pluck('invoice_no', 'id');
                                            }
                                            return Sale::where('customer_id', $customerId)->latest('id')->take(20)->pluck('invoice_no', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\DatePicker::make('return_date')
                                        ->label(__('Return Date (তারিখ)'))
                                        ->default(now())
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('location_id')
                                        ->label(__('Godown / Shop (গুদাম / দোকান)'))
                                        ->relationship('location', 'name')
                                        ->default(Location::getDefault()?->id ?? 1)
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),
                                ])
                                ->columns(12),

                            Forms\Components\Section::make(__('Returned Feed Items & Condition (ফেরতকৃত পণ্য ও অবস্থা)'))
                                ->description(__('Sealed intact bags return to active stock; opened or damaged goods route to damage wastage.'))
                                ->schema([
                                    Forms\Components\Repeater::make('items')
                                        ->label(__('Returned Items'))
                                        ->schema([
                                            Forms\Components\Select::make('product_id')
                                                ->label(__('Feed Product (পণ্য)'))
                                                ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    if ($state) {
                                                        $product = Product::find($state);
                                                        if ($product) {
                                                            $packSize = (float) ($product->pack_size ?: 25.00);
                                                            $set('pack_size', $packSize);

                                                            $unitType = $get('unit_type') ?: 'bag';
                                                            $rate = $unitType === 'bag'
                                                                ? (float) $product->sale_price
                                                                : ($packSize > 0 ? round((float) $product->sale_price / $packSize, 2) : 0.00);

                                                            $set('unit_price', $rate);
                                                            $qty = (float) ($get('quantity') ?: 1);
                                                            $set('line_total', round($qty * $rate, 2));
                                                        }
                                                    }
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 12, 'md' => 4]),

                                            Forms\Components\Select::make('unit_type')
                                                ->label(__('Unit'))
                                                ->options([
                                                    'bag' => __('Bag (বস্তা)'),
                                                    'kg' => __('Loose KG (কেজি)'),
                                                ])
                                                ->default('bag')
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    $productId = $get('product_id');
                                                    if ($productId) {
                                                        $product = Product::find($productId);
                                                        if ($product) {
                                                            $packSize = (float) ($product->pack_size ?: 25.00);
                                                            $rate = $state === 'bag'
                                                                ? (float) $product->sale_price
                                                                : ($packSize > 0 ? round((float) $product->sale_price / $packSize, 2) : 0.00);

                                                            $set('unit_price', $rate);
                                                            $qty = (float) ($get('quantity') ?: 1);
                                                            $set('line_total', round($qty * $rate, 2));
                                                        }
                                                    }
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\TextInput::make('quantity')
                                                ->label(__('Qty (পরিমাণ)'))
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(0.1)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    $qty = (float) $state;
                                                    $rate = (float) ($get('unit_price') ?: 0.00);
                                                    $set('line_total', round($qty * $rate, 2));
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\Select::make('condition')
                                                ->label(__('Physical Condition (অবস্থা)'))
                                                ->options([
                                                    'intact_sealed_bag' => __('Intact Sealed (অক্ষত বস্তা -> স্টকে যুক্ত হবে)'),
                                                    'opened_damaged' => __('Damaged / Opened (ছেঁড়া/নষ্ট -> অপচয় হিসেবে নথিভুক্ত)'),
                                                ])
                                                ->default('intact_sealed_bag')
                                                ->required()
                                                ->columnSpan(['default' => 12, 'md' => 2]),

                                            Forms\Components\TextInput::make('unit_price')
                                                ->label(__('Rate (দর)'))
                                                ->numeric()
                                                ->prefix('৳')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    $qty = (float) ($get('quantity') ?: 0);
                                                    $rate = (float) $state;
                                                    $set('line_total', round($qty * $rate, 2));
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 6, 'md' => 1]),

                                            Forms\Components\TextInput::make('line_total')
                                                ->label(__('Total (৳)'))
                                                ->numeric()
                                                ->prefix('৳')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(['default' => 6, 'md' => 1]),

                                            Forms\Components\Hidden::make('pack_size')->default(25.00),
                                        ])
                                        ->columns(12)
                                        ->defaultItems(1)
                                        ->addActionLabel(__('Add Returned Item (আরেকটি পণ্য যোগ করুন)'))
                                        ->live()
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set)),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 8]),

                        // Right Column: Credit Settlement
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Settlement Breakdown (হিসাব সমন্বয়)'))
                                ->schema([
                                    Forms\Components\TextInput::make('total_return_value')
                                        ->label(__('Total Return Value (ফেরত মূল্য)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('due_deduction_amount')
                                        ->label(__('Deducted From Due / দেনা কর্তন (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00)
                                        ->helperText(__('Rule: Credited against customer due first.')),

                                    Forms\Components\Select::make('refund_preference')
                                        ->label(__('Excess Value Settlement (উদ্বৃত্ত সমন্বয়)'))
                                        ->options([
                                            'advance' => __('Credit to Advance Deposit (জমা হিসেবে রাখা)'),
                                            'cash' => __('Cash Refund (নগদ টাকা ফেরত)'),
                                        ])
                                        ->default('advance')
                                        ->live()
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set)),

                                    Forms\Components\TextInput::make('advance_credited_amount')
                                        ->label(__('Advance Credited / অগ্রিম জমা (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('cash_refund_amount')
                                        ->label(__('Cash Refunded / নগদ ফেরত (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\Textarea::make('reason')
                                        ->label(__('Reason for Return (ফেরতের কারণ)'))
                                        ->placeholder(__('e.g. Farmer ordered wrong feed specification / Torn sack during transport'))
                                        ->rows(3),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 4]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_no')
                    ->label(__('Return # (ফেরত নং)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer (ক্রেতা)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SaleReturn $record): ?string => $record->customer?->phone),

                Tables\Columns\TextColumn::make('return_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_return_value')
                    ->label(__('Total Value (মোট মূল্য)'))
                    ->money('BDT')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_deduction_amount')
                    ->label(__('Due Deducted (দেনা সমন্বয়)'))
                    ->money('BDT')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('advance_credited_amount')
                    ->label(__('Advance Credited (জমা)'))
                    ->money('BDT')
                    ->color('info'),

                Tables\Columns\TextColumn::make('cash_refund_amount')
                    ->label(__('Cash Refund (নগদ ফেরত)'))
                    ->money('BDT')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('authorizer.name')
                    ->label(__('Authorized By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('return_date', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Return Summary (ফেরত সারসংক্ষেপ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('return_no')
                            ->label(__('Return #'))
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label(__('Customer Name'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('sale.invoice_no')
                            ->label(__('Linked Sale Invoice'))
                            ->placeholder(__('General Return (No Invoice Linked)')),

                        Infolists\Components\TextEntry::make('return_date')
                            ->label(__('Return Date'))
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('location.name')
                            ->label(__('Godown / Shop')),

                        Infolists\Components\TextEntry::make('authorizer.name')
                            ->label(__('Authorized By'))
                            ->placeholder(__('System Staff')),
                    ])
                    ->columns(['default' => 2, 'md' => 3]),

                Infolists\Components\Section::make(__('Financial Settlement (আর্থিক সমন্বয় বিবরণ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('total_return_value')
                            ->label(__('Total Return Claim Value'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('due_deduction_amount')
                            ->label(__('Applied Against Customer Due'))
                            ->money('BDT')
                            ->color('success')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('advance_credited_amount')
                            ->label(__('Credited to Advance Deposit'))
                            ->money('BDT')
                            ->color('info')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('cash_refund_amount')
                            ->label(__('Cash Refunded to Customer'))
                            ->money('BDT')
                            ->color('warning')
                            ->weight('bold'),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),

                Infolists\Components\Section::make(__('Returned Items & Condition (ফেরতকৃত পণ্যের তালিকা)'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label(__('Feed Items'))
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label(__('Product Name'))
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('unit_type')
                                    ->label(__('Unit'))
                                    ->badge(),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label(__('Quantity')),

                                Infolists\Components\TextEntry::make('condition')
                                    ->label(__('Condition'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state === 'intact_sealed_bag' ? __('Intact Sealed (Returned to Stock)') : __('Damaged / Opened (Wastage Logged)'))
                                    ->color(fn ($state) => $state === 'intact_sealed_bag' ? 'success' : 'danger'),

                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label(__('Rate (৳)'))
                                    ->money('BDT'),

                                Infolists\Components\TextEntry::make('line_total')
                                    ->label(__('Line Total'))
                                    ->money('BDT')
                                    ->weight('bold'),
                            ])
                            ->columns(['default' => 2, 'md' => 6]),
                    ]),

                Infolists\Components\Section::make(__('Reason & Remarks'))
                    ->schema([
                        Infolists\Components\TextEntry::make('reason')
                            ->label(__('Return Reason'))
                            ->placeholder(__('No reason recorded')),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaleReturns::route('/'),
            'create' => Pages\CreateSaleReturn::route('/create'),
            'view' => Pages\ViewSaleReturn::route('/{record}'),
        ];
    }
}
