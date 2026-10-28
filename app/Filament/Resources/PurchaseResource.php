<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Supplier & Procurement';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Purchase Consignment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Purchases / Inward Challans (ক্রয় চালান)');
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0.00;

        foreach ($items as $item) {
            $qty = (int) ($item['quantity_bags'] ?? 0);
            $rate = (float) ($item['purchase_rate_per_bag'] ?? 0);
            $subtotal += ($qty * $rate);
        }

        $transport = (float) ($get('transport_fare_labor_cost') ?? 0);
        $discount = (float) ($get('discount_amount') ?? 0);
        $grandTotal = max(0.00, round($subtotal + $transport - $discount, 2));

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
                        // Left Section: Consignment Details & Items Repeater
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Consignment & Supplier Info (চালান ও সরবরাহকারী)'))
                                ->description(__('Select feed supplier company, delivery date, and mill challan.'))
                                ->schema([
                                    Forms\Components\Select::make('supplier_id')
                                        ->label(__('Supplier Company (ফিড সরবরাহকারী)'))
                                        ->relationship('supplier', 'company_name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\DatePicker::make('purchase_date')
                                        ->label(__('Purchase / Challan Date (চালানের তারিখ)'))
                                        ->default(now())
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\TextInput::make('supplier_challan_no')
                                        ->label(__('Mill Challan / Gate Pass # (কোম্পানির চালান নং)'))
                                        ->placeholder(__('e.g. MEGA-CH-87421'))
                                        ->maxLength(100)
                                        ->columnSpan(['default' => 12, 'sm' => 6]),

                                    Forms\Components\Select::make('location_id')
                                        ->label(__('Receiving Godown / Shop (গুদাম / দোকান)'))
                                        ->relationship('location', 'name')
                                        ->default(Location::getDefault()?->id ?? 1)
                                        ->required()
                                        ->columnSpan(['default' => 12, 'sm' => 6]),
                                ])
                                ->columns(12),

                            Forms\Components\Section::make(__('Purchased Feed Items (ক্রয়কৃত ফিড পণ্যের তালিকা)'))
                                ->description(__('Add multiple feed items received in this truck / consignment.'))
                                ->schema([
                                    Forms\Components\Repeater::make('items')
                                        ->label(__('Feed Line Items'))
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

                                                            // Check supplier pivot last purchase rate
                                                            $supplierId = $get('../../supplier_id');
                                                            $rate = null;
                                                            if ($supplierId) {
                                                                $supplier = Supplier::find($supplierId);
                                                                $pivot = $supplier?->products()->where('products.id', $state)->first()?->pivot;
                                                                if ($pivot && (float) $pivot->last_purchase_price > 0) {
                                                                    $rate = (float) $pivot->last_purchase_price;
                                                                }
                                                            }

                                                            $rate = $rate ?: (float) $product->purchase_price;
                                                            $set('purchase_rate_per_bag', $rate);

                                                            $qty = (int) ($get('quantity_bags') ?: 1);
                                                            $set('total_weight_kg', round($qty * $packSize, 2));
                                                            $set('line_total', round($qty * $rate, 2));
                                                        }
                                                    }
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 12, 'md' => 4]),

                                            Forms\Components\TextInput::make('pack_size')
                                                ->label(__('Pack'))
                                                ->numeric()
                                                ->suffix('KG')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->default(25.00)
                                                ->columnSpan(['default' => 6, 'md' => 1]),

                                            Forms\Components\TextInput::make('quantity_bags')
                                                ->label(__('Bags (বস্তা)'))
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    $qty = (int) $state;
                                                    $packSize = (float) ($get('pack_size') ?: 25.00);
                                                    $rate = (float) ($get('purchase_rate_per_bag') ?: 0.00);

                                                    $set('total_weight_kg', round($qty * $packSize, 2));
                                                    $set('line_total', round($qty * $rate, 2));
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\TextInput::make('total_weight_kg')
                                                ->label(__('Weight (কেজি)'))
                                                ->numeric()
                                                ->suffix('KG')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\TextInput::make('purchase_rate_per_bag')
                                                ->label(__('Rate / Bag (দর/বস্তা)'))
                                                ->numeric()
                                                ->prefix('৳')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    $qty = (int) ($get('quantity_bags') ?: 0);
                                                    $rate = (float) $state;

                                                    $set('line_total', round($qty * $rate, 2));
                                                    static::updateTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 6, 'md' => 2]),

                                            Forms\Components\TextInput::make('line_total')
                                                ->label(__('Line Total'))
                                                ->numeric()
                                                ->prefix('৳')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(['default' => 12, 'md' => 1]),
                                        ])
                                        ->columns(12)
                                        ->defaultItems(1)
                                        ->addActionLabel(__('Add Another Feed Item (আরেকটি পণ্য যোগ করুন)'))
                                        ->live()
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set)),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 8]),

                        // Right Section: Settlement, Freight & Balances
                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Consignment Settlement (বিল ও পরিশোধ)'))
                                ->schema([
                                    Forms\Components\TextInput::make('subtotal_amount')
                                        ->label(__('Subtotal / পণ্যের মূল্য (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('transport_fare_labor_cost')
                                        ->label(__('Transport & Labor / গাড়িভাড়া (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set))
                                        ->helperText(__('Truck freight and labour loading/unloading.')),

                                    Forms\Components\TextInput::make('discount_amount')
                                        ->label(__('Mill Rebate / Discount (ছাড়) (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set)),

                                    Forms\Components\TextInput::make('grand_total')
                                        ->label(__('Grand Total Bill / মোট চালান (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\TextInput::make('paid_amount')
                                        ->label(__('Paid Amount / পরিশোধ (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotals($get, $set))
                                        ->helperText(__('Cash or bank amount paid on delivery.')),

                                    Forms\Components\TextInput::make('due_amount')
                                        ->label(__('Due Balance / অবশিষ্ট দেনা (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0.00),

                                    Forms\Components\Select::make('payment_method')
                                        ->label(__('Payment Method (পদ্ধতি)'))
                                        ->options([
                                            'cash' => __('Cash / নগদ'),
                                            'bank_transfer' => __('Bank Transfer / ব্যাংক'),
                                            'cheque' => __('Bank Cheque / চেক'),
                                            'mfs_bkash_nagad' => __('bKash / Nagad'),
                                        ])
                                        ->default('cash')
                                        ->visible(fn (Forms\Get $get): bool => (float) $get('paid_amount') > 0)
                                        ->required(fn (Forms\Get $get): bool => (float) $get('paid_amount') > 0),

                                    Forms\Components\Textarea::make('notes')
                                        ->label(__('Notes / Remarks (মন্তব্য)'))
                                        ->placeholder(__('Driver name, truck number, payment notes...'))
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
                Tables\Columns\TextColumn::make('purchase_invoice_no')
                    ->label(__('Invoice # (চালান নং)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Purchase $record): ?string => $record->supplier_challan_no ? "Mill Challan: {$record->supplier_challan_no}" : null),

                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label(__('Supplier (কোম্পানি)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('Feeds'))
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_bags')
                    ->label(__('Total Bags'))
                    ->formatStateUsing(fn (Purchase $record): string => $record->items->sum('quantity_bags') . ' Bags')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_weight_kg')
                    ->label(__('Total Weight'))
                    ->formatStateUsing(fn (Purchase $record): string => number_format($record->items->sum('total_weight_kg'), 1) . ' KG')
                    ->color('info'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('Billed (মোট বিল)'))
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('Paid (পরিশোধিত)'))
                    ->money('BDT')
                    ->color('success'),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label(__('Due (বকেয়া দেনা)'))
                    ->money('BDT')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => __('Fully Paid (পরিশোধিত)'),
                        'partial' => __('Partial Due (আংশিক দেনা)'),
                        'due' => __('Unpaid / Due (সম্পূর্ণ বকেয়া)'),
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'due' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label(__('Supplier'))
                    ->relationship('supplier', 'company_name'),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('Payment Status'))
                    ->options([
                        'paid' => __('Fully Paid'),
                        'partial' => __('Partial Due'),
                        'due' => __('Unpaid / Due'),
                    ]),

                Tables\Filters\Filter::make('purchase_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('From Date')),
                        Forms\Components\DatePicker::make('until')->label(__('Until Date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('purchase_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('purchase_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('Void / Delete Purchase Consignment'))
                    ->modalDescription(__('Deleting this consignment will reverse the stock addition and undo the supplier payable ledger entry. This cannot be undone.'))
                    ->action(function (Tables\Actions\DeleteAction $action, Purchase $record): void {
                        try {
                            PurchaseService::deletePurchase($record, auth()->id() ?? 1);

                            Notification::make()
                                ->success()
                                ->title(__('Purchase Consignment Deleted'))
                                ->body(__('Inventory and supplier payable balances have been safely reversed.'))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot Delete Consignment'))
                                ->body($e->getMessage())
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->defaultSort('purchase_date', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Consignment Summary (চালান সারসংক্ষেপ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('purchase_invoice_no')
                            ->label(__('Invoice #'))
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('supplier.company_name')
                            ->label(__('Supplier Company'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('supplier_challan_no')
                            ->label(__('Mill Challan #'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('purchase_date')
                            ->label(__('Purchase Date'))
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('location.name')
                            ->label(__('Receiving Location')),

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

                Infolists\Components\Section::make(__('Financial Breakdown (মূল্য ও দেনা বিবরণ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal_amount')
                            ->label(__('Feed Subtotal (পণ্যের মূল্য)'))
                            ->money('BDT'),

                        Infolists\Components\TextEntry::make('transport_fare_labor_cost')
                            ->label(__('Transport & Labor (ভাড়া)'))
                            ->money('BDT'),

                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label(__('Discount (ছাড়)'))
                            ->money('BDT')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('grand_total')
                            ->label(__('Grand Total Bill (মোট বিল)'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label(__('Paid Amount (পরিশোধিত)'))
                            ->money('BDT')
                            ->color('success')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('due_amount')
                            ->label(__('Outstanding Due (বকেয়া দেনা)'))
                            ->money('BDT')
                            ->badge()
                            ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),
                    ])
                    ->columns(['default' => 2, 'md' => 3]),

                Infolists\Components\Section::make(__('Purchased Feed Products (পণ্য বিবরণ ও পরিমাণ)'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label(__('Feed Items'))
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label(__('Product Name'))
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('quantity_bags')
                                    ->label(__('Quantity (Bags)'))
                                    ->formatStateUsing(fn ($state): string => "{$state} Bags"),

                                Infolists\Components\TextEntry::make('pack_size')
                                    ->label(__('Pack Size'))
                                    ->formatStateUsing(fn ($state): string => "{$state} KG"),

                                Infolists\Components\TextEntry::make('total_weight_kg')
                                    ->label(__('Total Weight'))
                                    ->formatStateUsing(fn ($state): string => number_format($state, 2) . " KG"),

                                Infolists\Components\TextEntry::make('purchase_rate_per_bag')
                                    ->label(__('Rate / Bag'))
                                    ->money('BDT'),

                                Infolists\Components\TextEntry::make('line_total')
                                    ->label(__('Line Total'))
                                    ->money('BDT')
                                    ->weight('bold'),
                            ])
                            ->columns(['default' => 2, 'md' => 6]),
                    ]),

                Infolists\Components\Section::make(__('Additional Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Payment Method'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('Notes'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('creator.name')
                            ->label(__('Recorded By'))
                            ->placeholder(__('System')),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Created At'))
                            ->dateTime('d M Y, h:i A'),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
        ];
    }
}
