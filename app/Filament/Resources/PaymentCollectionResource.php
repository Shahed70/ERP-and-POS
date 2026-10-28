<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentCollectionResource\Pages;
use App\Models\Customer;
use App\Models\PaymentCollection;
use App\Services\SaleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentCollectionResource extends Resource
{
    protected static ?string $model = PaymentCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Sales & POS (বিক্রয় ও পিওএস)';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Due Payment Receipt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Money Receipts / Collections (বাকি আদায় রসিদ)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Money Receipt Details (বাকি আদায়ের রসিদ)'))
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('Customer Name (ক্রেতার নাম)'))
                            ->relationship('customer', 'name', fn ($query) => $query->where('current_due', '>', 0))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText(function (Forms\Get $get): ?string {
                                $id = $get('customer_id');
                                if (! $id) {
                                    return null;
                                }
                                $c = Customer::find($id);
                                return $c ? __("Current Outstanding Due: ৳:due", ['due' => number_format($c->current_due, 2)]) : null;
                            })
                            ->columnSpan(['default' => 12, 'sm' => 6]),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label(__('Amount Received / আদায়কৃত টাকা (৳)'))
                            ->numeric()
                            ->prefix('৳')
                            ->required()
                            ->minValue(1)
                            ->columnSpan(['default' => 12, 'sm' => 6]),

                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method (পদ্ধতি)'))
                            ->options([
                                'cash' => __('Cash / নগদ'),
                                'bkash' => __('bKash'),
                                'nagad' => __('Nagad'),
                                'bank_cheque' => __('Bank Transfer / Cheque'),
                            ])
                            ->default('cash')
                            ->required()
                            ->columnSpan(['default' => 12, 'sm' => 6]),

                        Forms\Components\TextInput::make('transaction_reference')
                            ->label(__('Reference / TrxID (রেফারেন্স নং)'))
                            ->placeholder(__('e.g. bKash TrxID or Cheque No'))
                            ->columnSpan(['default' => 12, 'sm' => 6]),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes (মন্তব্য)'))
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_no')
                    ->label(__('Receipt # (রসিদ নং)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer (ক্রেতা)'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PaymentCollection $record): ?string => $record->customer?->phone),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label(__('Collected (আদায়)'))
                    ->money('BDT')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Method'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('allocations_count')
                    ->label(__('Invoices Settled'))
                    ->counts('allocations')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('collector.name')
                    ->label(__('Collected By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship('customer', 'name'),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label(__('Method'))
                    ->options([
                        'cash' => 'Cash',
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'bank_cheque' => 'Bank / Cheque',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label(__('Print (প্রিন্ট)'))
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (PaymentCollection $record): string => route('payment-collections.print', ['collection' => $record, 'autoprint' => 1]))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Money Receipt Details (রসিদ বিবরণ)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('receipt_no')
                            ->label(__('Receipt #'))
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label(__('Customer Name'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label(__('Phone')),

                        Infolists\Components\TextEntry::make('payment_date')
                            ->label(__('Collection Date'))
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('amount_paid')
                            ->label(__('Total Amount Collected'))
                            ->money('BDT')
                            ->weight('bold')
                            ->color('success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Payment Method'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('transaction_reference')
                            ->label(__('Trx Reference'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('collector.name')
                            ->label(__('Collected By'))
                            ->placeholder(__('System')),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),

                Infolists\Components\Section::make(__('FIFO Invoices Settled (যেসব চালানের বিপরীতে জমা হলো)'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('allocations')
                            ->label(__('Settled Invoices'))
                            ->schema([
                                Infolists\Components\TextEntry::make('sale.invoice_no')
                                    ->label(__('Invoice #'))
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('sale.sale_date')
                                    ->label(__('Sale Date'))
                                    ->date('d M Y'),

                                Infolists\Components\TextEntry::make('sale.grand_total')
                                    ->label(__('Invoice Total'))
                                    ->money('BDT'),

                                Infolists\Components\TextEntry::make('allocated_amount')
                                    ->label(__('Amount Allocated'))
                                    ->money('BDT')
                                    ->color('success')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('sale.payment_status')
                                    ->label(__('Invoice Status'))
                                    ->badge()
                                    ->color(fn ($state) => $state === 'paid' ? 'success' : 'warning'),
                            ])
                            ->columns(['default' => 2, 'md' => 5]),
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
            'index' => Pages\ListPaymentCollections::route('/'),
            'create' => Pages\CreatePaymentCollection::route('/create'),
            'view' => Pages\ViewPaymentCollection::route('/{record}'),
        ];
    }
}
