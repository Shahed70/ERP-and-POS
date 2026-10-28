<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\CustomerLedger;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Customer Management';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Customers (ক্রেতা ও বাকির খাতা)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Section::make(__('Customer Profile (ক্রেতার প্রোফাইল)'))
                            ->description(__('Personal contact and fish farming operation details.'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Farmer / Customer Name (নাম)'))
                                    ->placeholder(__('e.g. Md. Rafiqul Islam / মোঃ রফিকুল ইসলাম'))
                                    ->required()
                                    ->maxLength(150)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('phone')
                                    ->label(__('Mobile Number (মোবাইল নম্বর)'))
                                    ->placeholder(__('017XXXXXXXX'))
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(Customer::class, 'phone', ignoreRecord: true)
                                    ->regex('/^(?:\+8801|01)[3-9]\d{8}$/')
                                    ->validationMessages([
                                        'regex' => __('Please enter a valid 11-digit Bangladeshi mobile number (e.g. 017XXXXXXXX).'),
                                    ])
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('alternative_phone')
                                    ->label(__('Alternative Phone (বিকল্প নম্বর)'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('farm_details')
                                    ->label(__('Fish Farm / Pond Details (ঘের বা পুকুরের বিবরণ)'))
                                    ->placeholder(__('e.g. 3 Ponds in Trishal - Rui & Pangas farming'))
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\Textarea::make('address')
                                    ->label(__('Address / Village (ঠিকানা বা গ্রাম)'))
                                    ->placeholder(__('Village, Union, Post Office, Upazila, District'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 12, 'lg' => 7]),

                        Forms\Components\Section::make(__('Credit & Opening Debt (বাকি ও ক্রেডিট লিমিট)'))
                            ->description(__('Credit policies and baseline debt tracking.'))
                            ->schema([
                                Forms\Components\TextInput::make('credit_limit')
                                    ->label(__('Credit Limit / বাকি সীমা (৳)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->default(0.00)
                                    ->helperText(__('Maximum allowable due balance. Enter 0 for unlimited credit.')),

                                Forms\Components\TextInput::make('opening_due')
                                    ->label(__('Opening Due / প্রারম্ভিক বাকি (৳)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->default(0.00)
                                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                                    ->dehydrated()
                                    ->helperText(__('Previous unsettled balance. Automatically creates an opening ledger entry.')),

                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('Active Status (সক্রিয়)'))
                                    ->default(true)
                                    ->helperText(__('Inactive customers will be excluded from new sales invoicing.')),
                            ])
                            ->columnSpan(['default' => 12, 'lg' => 5]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Customer Name (নাম)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record): ?string => $record->farm_details),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone (মোবাইল)'))
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('total_purchases')
                    ->label(__('Purchases (মোট ক্রয়)'))
                    ->money('BDT')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
                            return $query->withSum('sales', 'grand_total')->orderBy('sales_sum_grand_total', $direction);
                        }
                        return $query;
                    }),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label(__('Paid (পরিশোধিত)'))
                    ->money('BDT')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('opening_due')
                    ->label(__('Opening Due (প্রারম্ভিক বাকি)'))
                    ->money('BDT')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('current_due')
                    ->label(__('Total Due (মোট বাকি)'))
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->badge(),

                Tables\Columns\TextColumn::make('purchased_products_list')
                    ->label(__('Feeds Bought (ক্রয়কৃত খাদ্য)'))
                    ->limit(35)
                    ->tooltip(fn (Customer $record): string => $record->purchased_products_list)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('credit_limit')
                    ->label(__('Credit Limit'))
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '৳' . number_format($state, 2) : __('Unlimited'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('address')
                    ->label(__('Address'))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Tables\Filters\Filter::make('has_due')
                    ->label(__('Customers with Due (বাকিদার ক্রেতা)'))
                    ->query(fn (Builder $query): Builder => $query->where('current_due', '>', 0)),

                Tables\Filters\Filter::make('zero_due')
                    ->label(__('Zero Due / Fully Settled (পরিশোধিত)'))
                    ->query(fn (Builder $query): Builder => $query->where('current_due', '<=', 0)),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active Status')),
            ])
            ->actions([
                Tables\Actions\Action::make('collect_due')
                    ->label(__('Collect Due (বাকি আদায়)'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Customer $record): bool => (float) $record->current_due > 0)
                    ->modalHeading(fn (Customer $record): string => __("Collect Due from :name", ['name' => $record->name]))
                    ->modalDescription(fn (Customer $record): string => __("Outstanding Due Balance: ৳:due", ['due' => number_format($record->current_due, 2)]))
                    ->form([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label(__('Payment Amount (আদায়ের পরিমাণ)'))
                            ->numeric()
                            ->prefix('৳')
                            ->required()
                            ->minValue(1)
                            ->default(fn (Customer $record): float => (float) $record->current_due)
                            ->helperText(fn (Customer $record): string => __("Max payable against current due: ৳:due", ['due' => number_format($record->current_due, 2)])),

                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method (পদ্ধতি)'))
                            ->options([
                                'cash' => __('Cash / নগদ টাকা'),
                                'bkash' => __('bKash / বিকাশ'),
                                'nagad' => __('Nagad / নগদ'),
                                'bank_cheque' => __('Bank Cheque / ট্রান্সফার'),
                            ])
                            ->default('cash')
                            ->required(),

                        Forms\Components\TextInput::make('receipt_no')
                            ->label(__('Money Receipt No (রসিদ নং)'))
                            ->placeholder(__('Leave blank for auto-generation'))
                            ->helperText(__('Auto-generates format: MR-YYYYMM-XXXX')),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes / Reference (মন্তব্য / TrxID)'))
                            ->placeholder(__('e.g. bKash TrxID or Cheque details')),
                    ])
                    ->action(function (Tables\Actions\Action $action, Customer $record, array $data): void {
                        $amount = (float) $data['amount_paid'];

                        try {
                            $ledger = $record->collectDuePayment(
                                amountPaid: $amount,
                                paymentMethod: $data['payment_method'],
                                receiptNo: ! empty($data['receipt_no']) ? $data['receipt_no'] : null,
                                notes: $data['notes'] ?? null,
                                performedBy: auth()->id() ?? 1,
                            );

                            Notification::make()
                                ->success()
                                ->title(__('Due Payment Collected Successfully'))
                                ->body(__("Collected ৳:amount under Receipt :receipt. New outstanding due: ৳:newDue", [
                                    'amount' => number_format($amount, 2),
                                    'receipt' => $ledger->reference_no,
                                    'newDue' => number_format($record->fresh()->current_due, 2),
                                ]))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Payment Collection Failed'))
                                ->body($e->getMessage())
                                ->send();

                            $action->halt();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete Customer Profile'))
                    ->modalDescription(__('Are you sure you want to delete this customer?'))
                    ->before(function (Tables\Actions\DeleteAction $action, Customer $record) {
                        if ((float) $record->current_due > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot Delete Customer'))
                                ->body(__('This customer has an unsettled due balance of ৳:due. Please collect or settle dues first.', [
                                    'due' => number_format($record->current_due, 2),
                                ]))
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle(__('Customer deleted successfully')),
            ])
            ->defaultSort('current_due', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Financial Quick Check & Due Breakdown (আর্থিক সারসংক্ষেপ ও বাকির হিসাব)'))
                    ->description(__('Comprehensive overview of lifetime purchases, payments, and outstanding balances.'))
                    ->schema([
                        Infolists\Components\TextEntry::make('total_purchases')
                            ->label(__('Total Purchases / মোট ক্রয় (৳)'))
                            ->money('BDT')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->helperText(__('Total value of all feeds purchased.')),

                        Infolists\Components\TextEntry::make('total_paid')
                            ->label(__('Initial & Due Paid / পরিশোধিত (৳)'))
                            ->money('BDT')
                            ->color('success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->helperText(__('Total money paid at sale + collected.')),

                        Infolists\Components\TextEntry::make('opening_due')
                            ->label(__('Opening Due / প্রারম্ভিক বাকি (৳)'))
                            ->money('BDT')
                            ->color('warning')
                            ->helperText(__('Debt prior to software deployment.')),

                        Infolists\Components\TextEntry::make('current_due')
                            ->label(__('Total Net Due / সর্বমোট বাকি (৳)'))
                            ->money('BDT')
                            ->badge()
                            ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->helperText(__('Current payable balance.')),

                        Infolists\Components\TextEntry::make('credit_limit')
                            ->label(__('Credit Limit (বাকি সীমা)'))
                            ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '৳' . number_format($state, 2) : __('Unlimited (অসীম)')),

                        Infolists\Components\TextEntry::make('last_purchase_date')
                            ->label(__('Last Purchase Date (সর্বশেষ ক্রয়ের তারিখ)'))
                            ->placeholder(__('No purchases yet')),

                        Infolists\Components\TextEntry::make('is_active')
                            ->label(__('Account Status'))
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state): string => $state ? __('Active') : __('Inactive')),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),

                Infolists\Components\Section::make(__('Feeds & Products Bought (ক্রয়কৃত মাছের খাদ্যের বিবরণী)'))
                    ->schema([
                        Infolists\Components\TextEntry::make('purchased_products_list')
                            ->label(__('Purchased Products Catalog'))
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make(__('Personal & Farm Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('Name (নাম)')),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('Mobile (মোবাইল)'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('alternative_phone')
                            ->label(__('Alternative Phone'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('farm_details')
                            ->label(__('Farm / Gher Details (ঘের / পুকুর)'))
                            ->placeholder(__('N/A')),

                        Infolists\Components\TextEntry::make('address')
                            ->label(__('Address (ঠিকানা)'))
                            ->placeholder(__('N/A'))
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'sm' => 2]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CustomerResource\RelationManagers\LedgersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
