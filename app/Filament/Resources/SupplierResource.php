<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Supplier & Procurement';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Supplier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Suppliers (সরবরাহকারী তালিকা)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Section::make(__('Company & Contact Details (কোম্পানি ও যোগাযোগ)'))
                            ->description(__('Feed manufacturer mill, regional depot, or distributor details.'))
                            ->schema([
                                Forms\Components\TextInput::make('company_name')
                                    ->label(__('Company / Mill Name (কোম্পানির নাম)'))
                                    ->placeholder(__('e.g. Mega Feed Mills Ltd. / মেগা ফীড মিলস'))
                                    ->required()
                                    ->maxLength(150)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('contact_person')
                                    ->label(__('Contact Person / Representative (প্রতিনিধি)'))
                                    ->placeholder(__('e.g. ASM Md. Harun / সেলস ম্যানেজার'))
                                    ->maxLength(100)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('phone')
                                    ->label(__('Phone / Mobile (ফোন নম্বর)'))
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('alternative_phone')
                                    ->label(__('Alternative Phone (বিকল্প নম্বর)'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email Address (ইমেইল)'))
                                    ->email()
                                    ->maxLength(100)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\Textarea::make('address')
                                    ->label(__('Depot / Factory Address (ঠিকানা)'))
                                    ->placeholder(__('Mill address, Regional Depot location, etc.'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 12, 'lg' => 7]),

                        Forms\Components\Group::make([
                            Forms\Components\Section::make(__('Bank & Settlement (ব্যাংক হিসাব)'))
                                ->schema([
                                    Forms\Components\Textarea::make('bank_details')
                                        ->label(__('Bank Account Details (ব্যাংক তথ্য)'))
                                        ->placeholder(__('Bank Name, Branch, Account No, Routing No'))
                                        ->rows(3),
                                ]),

                            Forms\Components\Section::make(__('Payable & Status (পাওনা ও স্থিতি)'))
                                ->schema([
                                    Forms\Components\TextInput::make('opening_payable')
                                        ->label(__('Opening Payable / প্রারম্ভিক দেনা (৳)'))
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00)
                                        ->disabled(fn (string $operation): bool => $operation === 'edit')
                                        ->dehydrated()
                                        ->helperText(__('Previous unsettled balance owed to this supplier.')),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('Active Supplier (সক্রিয়)'))
                                        ->default(true),
                                ]),
                        ])->columnSpan(['default' => 12, 'lg' => 5]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('Supplier (কোম্পানি)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Supplier $record): ?string => $record->contact_person),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone (মোবাইল)'))
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('total_purchases')
                    ->label(__('Billed (মোট ক্রয় বিল)'))
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label(__('Paid (পরিশোধিত)'))
                    ->money('BDT')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('opening_payable')
                    ->label(__('Opening (প্রারম্ভিক)'))
                    ->money('BDT')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('current_payable')
                    ->label(__('Total Payable (মোট পাওনা)'))
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->badge(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('Feeds Supplied'))
                    ->counts('products')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Registered'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_payable')
                    ->label(__('Suppliers with Payable (বকেয়া দেনা)'))
                    ->query(fn (Builder $query): Builder => $query->where('current_payable', '>', 0)),

                Tables\Filters\Filter::make('zero_payable')
                    ->label(__('Zero Balance / Fully Settled (পরিশোধিত)'))
                    ->query(fn (Builder $query): Builder => $query->where('current_payable', '<=', 0)),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active Status')),
            ])
            ->actions([
                Tables\Actions\Action::make('make_payment')
                    ->label(__('Pay Supplier (পাওনা পরিশোধ)'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Supplier $record): bool => (float) $record->current_payable > 0)
                    ->modalHeading(fn (Supplier $record): string => __("Pay Supplier: :company", ['company' => $record->company_name]))
                    ->modalDescription(fn (Supplier $record): string => __("Current Payable Balance: ৳:amount", ['amount' => number_format($record->current_payable, 2)]))
                    ->form([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label(__('Payment Amount (পরিশোধের পরিমাণ)'))
                            ->numeric()
                            ->prefix('৳')
                            ->required()
                            ->minValue(1)
                            ->default(fn (Supplier $record): float => (float) $record->current_payable)
                            ->helperText(fn (Supplier $record): string => __("Current outstanding payable: ৳:payable", ['payable' => number_format($record->current_payable, 2)])),

                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method (পদ্ধতি)'))
                            ->options([
                                'cash' => __('Cash / নগদ'),
                                'bank_transfer' => __('Bank Transfer / ব্যাংক ট্রান্সফার'),
                                'cheque' => __('Bank Cheque / চেক'),
                                'mfs_bkash_nagad' => __('bKash / Nagad'),
                            ])
                            ->default('bank_transfer')
                            ->required(),

                        Forms\Components\TextInput::make('voucher_no')
                            ->label(__('Payment Voucher No (ভাউচার নং)'))
                            ->placeholder(__('Leave blank for auto-generation'))
                            ->helperText(__('Format: PV-YYYYMM-XXXX')),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('Reference Notes / Cheque Details (মন্তব্য / চেক নং)'))
                            ->placeholder(__('e.g. Cheque No: 4892182, Dutch-Bangla Bank')),
                    ])
                    ->action(function (Tables\Actions\Action $action, Supplier $record, array $data): void {
                        $amount = (float) $data['amount_paid'];

                        try {
                            $ledger = $record->makePayment(
                                amountPaid: $amount,
                                paymentMethod: $data['payment_method'],
                                voucherNo: ! empty($data['voucher_no']) ? $data['voucher_no'] : null,
                                notes: $data['notes'] ?? null,
                                performedBy: auth()->id() ?? 1,
                            );

                            Notification::make()
                                ->success()
                                ->title(__('Supplier Payment Recorded Successfully'))
                                ->body(__("Disbursed ৳:amount under Voucher :voucher. Remaining payable: ৳:newPayable", [
                                    'amount' => number_format($amount, 2),
                                    'voucher' => $ledger->reference_no,
                                    'newPayable' => number_format($record->fresh()->current_payable, 2),
                                ]))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Payment Failed'))
                                ->body($e->getMessage())
                                ->send();

                            $action->halt();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete Supplier Profile'))
                    ->modalDescription(__('Are you sure you want to delete this supplier?'))
                    ->before(function (Tables\Actions\DeleteAction $action, Supplier $record) {
                        if ((float) $record->current_payable > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot Delete Supplier'))
                                ->body(__('This supplier has an unsettled payable balance of ৳:amount. Settle payables before deleting.', [
                                    'amount' => number_format($record->current_payable, 2),
                                ]))
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle(__('Supplier deleted successfully')),
            ])
            ->defaultSort('current_payable', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Financial Payable Overview (আর্থিক স্থিতি ও দেনা)'))
                    ->description(__('Current payable obligations and historical payments.'))
                    ->schema([
                        Infolists\Components\TextEntry::make('total_purchases')
                            ->label(__('Total Purchases Billed (মোট ক্রয় চালান)'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('total_paid')
                            ->label(__('Total Payments Made (মোট পরিশোধ)'))
                            ->money('BDT')
                            ->color('success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('opening_payable')
                            ->label(__('Opening Payable (প্রারম্ভিক দেনা)'))
                            ->money('BDT')
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('current_payable')
                            ->label(__('Current Net Payable (মোট বকেয়া দেনা)'))
                            ->money('BDT')
                            ->badge()
                            ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('is_active')
                            ->label(__('Account Status'))
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state): string => $state ? __('Active') : __('Inactive')),
                    ])
                    ->columns(['default' => 2, 'md' => 5]),

                Infolists\Components\Section::make(__('Company & Contact Details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('company_name')
                            ->label(__('Company Name (কোম্পানি)')),

                        Infolists\Components\TextEntry::make('contact_person')
                            ->label(__('Representative (যোগাযোগকারী ব্যক্তি)'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('Phone (মোবাইল)'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('alternative_phone')
                            ->label(__('Alternative Phone'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('email')
                            ->label(__('Email'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('address')
                            ->label(__('Address (ঠিকানা)'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('bank_details')
                            ->label(__('Bank Settlement Details (ব্যাংক হিসাব)'))
                            ->placeholder(__('N/A'))
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'sm' => 2]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SupplierResource\RelationManagers\ProductsRelationManager::class,
            SupplierResource\RelationManagers\LedgersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
