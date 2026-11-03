<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Operating Expenses (পরিচালন ব্যয়)';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Operating Expense');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Operating Expenses (পরিচালন ব্যয়)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Expense Details (ব্যয়ের বিবরণ)'))
                    ->description(__('Record daily shop rent, electricity bills, godown charges, snacks and miscellaneous shop overheads.'))
                    ->schema([
                        Forms\Components\Grid::make(12)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label(__('Expense Category (ব্যয়ের খাত)'))
                                    ->options(Expense::getCategoryOptions())
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('title')
                                    ->label(__('Expense Title / Description (বিবরণ)'))
                                    ->placeholder(__('e.g. October 2026 Shop Rent / Monthly Electric Bill'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('amount')
                                    ->label(__('Amount (টাকার পরিমাণ)'))
                                    ->numeric()
                                    ->prefix('৳')
                                    ->minValue(0.01)
                                    ->required()
                                    ->columnSpan(['default' => 12, 'sm' => 4]),

                                Forms\Components\DatePicker::make('expense_date')
                                    ->label(__('Expense Date (তারিখ)'))
                                    ->default(now())
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(['default' => 12, 'sm' => 4]),

                                Forms\Components\Select::make('payment_method')
                                    ->label(__('Payment Method (পরিশোধের মাধ্যম)'))
                                    ->options(Expense::getPaymentMethodOptions())
                                    ->default('cash')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(['default' => 12, 'sm' => 4]),

                                Forms\Components\TextInput::make('paid_to')
                                    ->label(__('Paid To / Recipient (প্রাপক / ব্যক্তি / প্রতিষ্ঠান)'))
                                    ->placeholder(__('e.g. Haji Abdul Barek (Landlord) / Electric Office'))
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\TextInput::make('reference_no')
                                    ->label(__('Reference / Voucher # (রসিদ / বিল নং)'))
                                    ->placeholder(__('e.g. VOUCHER-104 / METER-8821'))
                                    ->maxLength(100)
                                    ->columnSpan(['default' => 12, 'sm' => 6]),

                                Forms\Components\Textarea::make('note')
                                    ->label(__('Additional Notes (মন্তব্য / বিস্তারিত)'))
                                    ->rows(3)
                                    ->columnSpan(12),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expense_number')
                    ->label(__('Voucher #'))
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('Category (খাত)'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Expense::getCategoryLabel($state))
                    ->color(fn (?string $state): string => Expense::getCategoryColor($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title / Description (বিবরণ)'))
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn (Expense $record): string => $record->title),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount (টাকা)'))
                    ->money('BDT')
                    ->weight('black')
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Method'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'cash'))
                    ->color(fn (?string $state): string => match ($state) {
                        'cash' => 'info',
                        'bank' => 'primary',
                        'bkash', 'nagad', 'rocket' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('paid_to')
                    ->label(__('Paid To'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference_no')
                    ->label(__('Ref #'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('Category (খাত)'))
                    ->options(Expense::getCategoryOptions()),

                Tables\Filters\Filter::make('expense_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')->label(__('From')),
                        Forms\Components\DatePicker::make('to_date')->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from_date'] ?? null, fn ($q, $date) => $q->whereDate('expense_date', '>=', $date))
                            ->when($data['to_date'] ?? null, fn ($q, $date) => $q->whereDate('expense_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
