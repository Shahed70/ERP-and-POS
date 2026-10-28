<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSalesTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Sales Transactions (সর্বশেষ বিক্রয় লেনদেন)';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->with(['customer'])
                    ->latest('id')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_no')
                    ->label(__('Invoice #'))
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer / Gher (ক্রেতা)'))
                    ->description(fn (Sale $record): string => $record->customer->phone ?? '')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label(__('Date (তারিখ)'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('Total (মোট)'))
                    ->money('BDT')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('Paid (নগদ পরিশোধ)'))
                    ->money('BDT')
                    ->color('success'),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label(__('Due (বকেয়া দেনা)'))
                    ->money('BDT')
                    ->color(fn (Sale $record): string => $record->due_amount > 0 ? 'danger' : 'gray')
                    ->weight(fn (Sale $record): string => $record->due_amount > 0 ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Status (অবস্থা)'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'due' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label(__('Print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Sale $record): string => route('sales.print', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Sale $record): string => "/admin/sales/{$record->id}"),
            ])
            ->paginated(false);
    }
}
