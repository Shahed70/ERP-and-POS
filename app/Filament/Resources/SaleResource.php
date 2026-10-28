<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SaleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales & POS (বিক্রয় ও পিওএস)';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Sales Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sales Invoices & Counter POS (বিক্রয় চালান)');
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0.00;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $rate = (float) ($item['unit_price'] ?? 0);
            $subtotal += ($qty * $rate);
        }

        $discount = (float) ($get('discount_amount') ?? 0);
        $grandTotal = max(0.00, round($subtotal - $discount, 2));

        $paid = (float) ($get('paid_amount') ?? 0);
        $due = max(0.00, round($grandTotal - $paid, 2));

        $set('subtotal_amount', number_format($subtotal, 2, '.', ''));
        $set('grand_total', number_format($grandTotal, 2, '.', ''));
        $set('due_amount', number_format($due, 2, '.', ''));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        // Left Section: Customer & Product Items
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Customer & Invoice Info (ক্রেতা ও চালানের তথ্য)'))
                                ->schema([
                                    Forms\Components\Select::make('customer_id')
                                        ->label(__('Select Customer (ক্রেতার নাম)'))
                                        ->relationship('customer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->helperText(function (Forms\Get $get): ?string {
                                            $customerId = $get('customer_id');
                                            if (! $customerId) {
                                                return null;
                                            }
                                            $c = Customer::find($customerId);
                                            if (! $c) {
                                                return null;
                                            }
                                            $limit = (float) $c->credit_limit > 0 ? '৳' . number_format($c->credit_limit, 2) : __('Unlimited');
                                            return __("Phone: :phone | Current Due: ৳:due | Credit Limit: :limit", [
                                                'phone' => $c->phone,
                                                'due' => number_format($c->current_due, 2),
                                                'limit' => $limit,
                                            ]);
                                        })
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\DatePicker::make('sale_date')
                                        ->label(__('Sale Date (তারিখ)'))
                                        ->default(now())
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('sale_type')
                                        ->label(__('Sale Type (বিক্রয়ের ধরন)'))
                                        ->options([
                                            'retail' => __('Retail / খুচরা'),
                                            'wholesale' => __('Wholesale / পাইকারি'),
                                        ])
                                        ->default('retail')
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('location_id')
                                        ->label(__('Selling Godown / Shop (দোকান / গোডাউন)'))
                                        ->relationship('location', 'name')
                                        ->default(Location::getDefault()?->id ?? 1)
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),
                                ])
                                ->columns(12),

                            Forms\Components\Section::make(__('Feed Items (খাদ্য পণ্যের তালিকা ও বিক্রয়)'))
                                ->description(__('Supports Dual-Unit: sell in intact sealed Bags or loose per KG.'))
                                ->schema([
                                    Forms\Components\Repeater::make('items')
                                        ->label(__('Sale Line Items'))
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
                                                            $weight = $unitType === 'bag' ? round($qty * $packSize, 2) : round($qty, 2);
                                                            $set('deducted_weight_kg', $weight);
                                                            $set('line_total', round($qty * $rate, 2));
                                                        }
                                                    }
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 12, 'md' => 4]),

                                            Forms\Components\Select::make('unit_type')
                                                ->label(__('Unit (একক)'))
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
                                                            $weight = $state === 'bag' ? round($qty * $packSize, 2) : round($qty, 2);
                                                            $set('deducted_weight_kg', $weight);
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
                                                    $unitType = $get('unit_type') ?: 'bag';
                                                    $packSize = (float) ($get('pack_size') ?: 25.00);
                                                    $rate = (float) ($get('unit_price') ?: 0.00);

                                                    $weight = $unitType === 'bag' ? round($qty * $packSize, 2) : round($qty, 2);
                                                    $set('deducted_weight_kg', $weight);
                                                    $set('line_total', round($qty * $rate, 2));
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\TextInput::make('deducted_weight_kg')
                                                ->label(__('Total KG'))
                                                ->numeric()
                                                ->suffix('KG')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(['default' => 6, 'md' => 1]),

                                            Forms\Components\TextInput::make('unit_price')
                                                ->label(__('Rate (দর/একক)'))
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
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\TextInput::make('line_total')
                                                ->label(__('Total (৳)'))
                                                ->numeric()
                                                ->prefix('৳')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(['default' => 12, 'md' => 1]),

                                            Forms\Components\Hidden::make('pack_size')->default(25.00),
                                        ])
                                        ->columns(12)
                                        ->defaultItems(1)
                                        ->addActionLabel(__('Add Another Feed Item (আরেকটি পণ্য যোগ করুন)'))
                                        ->live()
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set)),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 8]),

                        // Right Section: Bill Summary, Paid Amount, Due & Override
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Invoice Settlement (বিল ও মূল্য পরিশোধ)'))
                                ->schema([
                                    Forms\Components\TextInput::make('subtotal_amount')
                                        ->label(__('Subtotal / পণ্যের মোট মূল্য (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('discount_amount')
                                        ->label(__('Special Discount / ছাড় (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set)),

                                    Forms\Components\TextInput::make('grand_total')
                                        ->label(__('Net Payable / মোট বিল (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('paid_amount')
                                        ->label(__('Paid Amount / নগদ আদায় (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set))
                                        ->helperText(__('Amount received at counter now.')),

                                    Forms\Components\TextInput::make('due_amount')
                                        ->label(__('Due Balance / বাকী দেনা (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\Select::make('payment_method')
                                        ->label(__('Payment Method (পদ্ধতি)'))
                                        ->options([
                                            'cash' => __('Cash / নগদ'),
                                            'bkash' => __('bKash'),
                                            'nagad' => __('Nagad'),
                                            'bank_cheque' => __('Bank / Cheque'),
                                        ])
                                        ->default('cash')
                                        ->visible(fn (Forms\Get $get): bool => (float) $get('paid_amount') > 0)
                                        ->required(fn (Forms\Get $get): bool => (float) $get('paid_amount') > 0),

                                    Forms\Components\Toggle::make('allow_credit_override')
                                        ->label(__('Authorize Credit Limit Override (বাকী সীমা ছাড়ের অনুমতি)'))
                                        ->helperText(__('Enable this if customer exceeds debt ceiling.')),

                                    Forms\Components\Textarea::make('override_notes')
                                        ->label(__('Override Reason (অনুমোদনের কারণ)'))
                                        ->placeholder(__('e.g. Fish harvest due next week, approved by owner'))
                                        ->visible(fn (Forms\Get $get): bool => (bool) $get('allow_credit_override'))
                                        ->rows(2),

                                    Forms\Components\Textarea::make('notes')
                                        ->label(__('Notes / Delivery Info (মন্তব্য)'))
                                        ->placeholder(__('Van driver name, phone, etc.'))
                                        ->rows(2),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 4]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_no')
                    ->label(__('Invoice # (চালান নং)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Sale $record): string => $record->sale_type === 'wholesale' ? 'Wholesale' : 'Retail'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer (ক্রেতা)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Sale $record): ?string => $record->customer?->phone),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_weight_kg')
                    ->label(__('Weight'))
                    ->formatStateUsing(fn (Sale $record): string => number_format($record->items->sum('deducted_weight_kg'), 1) . ' KG')
                    ->color('info')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('Billed (মোট বিল)'))
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('Paid (নগদ)'))
                    ->money('BDT')
                    ->color('success'),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label(__('Due (বাকী)'))
                    ->money('BDT')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => __('Paid (পরিশোধিত)'),
                        'partial' => __('Partial Due (আংশিক দেনা)'),
                        'due' => __('Unpaid / Due (বকেয়া)'),
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'due' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_credit_overridden')
                    ->label(__('Override'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('Payment Status'))
                    ->options([
                        'paid' => __('Paid'),
                        'partial' => __('Partial Due'),
                        'due' => __('Unpaid / Due'),
                    ]),

                Tables\Filters\SelectFilter::make('sale_type')
                    ->label(__('Sale Type'))
                    ->options([
                        'retail' => __('Retail'),
                        'wholesale' => __('Wholesale'),
                    ]),

                Tables\Filters\Filter::make('sale_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('From Date')),
                        Forms\Components\DatePicker::make('until')->label(__('Until Date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('sale_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('sale_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('Void / Delete Sales Invoice'))
                    ->modalDescription(__('Voiding this invoice will restore the sold feed stock back to inventory and cancel the customer debt. This cannot be undone.'))
                    ->action(function (Tables\Actions\DeleteAction $action, Sale $record): void {
                        try {
                            SaleService::voidSale($record, auth()->id() ?? 1);

                            Notification::make()
                                ->success()
                                ->title(__('Sales Invoice Voided'))
                                ->body(__('Inventory restored and customer balance adjusted.'))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot Void Invoice'))
                                ->body($e->getMessage())
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Invoice Summary (চালান সারসংক্ষেপ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_no')
                            ->label(__('Invoice #'))
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label(__('Customer Name'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label(__('Customer Phone')),

                        Infolists\Components\TextEntry::make('sale_date')
                            ->label(__('Sale Date'))
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('sale_type')
                            ->label(__('Sale Type'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('payment_status')
                            ->label(__('Payment Status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'partial' => 'warning',
                                'due' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(['default' => 2, 'md' => 3]),

                Infolists\Components\Section::make(__('Financial Breakdown & Debt Impact (হিসাব ও দেনার প্রভাব)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal_amount')
                            ->label(__('Feed Subtotal'))
                            ->money('BDT'),

                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label(__('Discount (ছাড়)'))
                            ->money('BDT')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('grand_total')
                            ->label(__('Net Bill Total'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label(__('Amount Paid At Counter'))
                            ->money('BDT')
                            ->color('success')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('due_amount')
                            ->label(__('Invoice Due (চালানের বাকী)'))
                            ->money('BDT')
                            ->badge()
                            ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('previous_due')
                            ->label(__('Previous Customer Due'))
                            ->money('BDT')
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('total_due_after_sale')
                            ->label(__('Total Customer Due After Sale'))
                            ->money('BDT')
                            ->weight('bold')
                            ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),

                Infolists\Components\Section::make(__('Sold Feed Items (বিক্রয়কৃত খাদ্য পণ্য)'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label(__('Line Items'))
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label(__('Product Name'))
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('unit_type')
                                    ->label(__('Unit Type'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state === 'bag' ? __('Bag') : __('KG')),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label(__('Sold Quantity'))
                                    ->formatStateUsing(fn ($record): string => $record->formatted_quantity),

                                Infolists\Components\TextEntry::make('deducted_weight_kg')
                                    ->label(__('Deducted Stock (KG)'))
                                    ->formatStateUsing(fn ($state): string => number_format($state, 2) . ' KG'),

                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label(__('Rate / Unit'))
                                    ->money('BDT'),

                                Infolists\Components\TextEntry::make('line_total')
                                    ->label(__('Line Total'))
                                    ->money('BDT')
                                    ->weight('bold'),
                            ])
                            ->columns(['default' => 2, 'md' => 6]),
                    ]),

                Infolists\Components\Section::make(__('Payment & Audit Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Payment Method'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('is_credit_overridden')
                            ->label(__('Credit Ceiling Overridden'))
                            ->badge()
                            ->color(fn ($state): string => $state ? 'danger' : 'gray')
                            ->formatStateUsing(fn ($state): string => $state ? __('YES (Authorized)') : __('NO')),

                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('Notes'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('creator.name')
                            ->label(__('Cashier / Performer'))
                            ->placeholder(__('System')),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}
