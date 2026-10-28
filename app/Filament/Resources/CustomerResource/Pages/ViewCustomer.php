<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('collect_due')
                ->label(__('Collect Due (বাকি আদায়)'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => (float) $this->getRecord()->current_due > 0)
                ->modalHeading(fn (): string => __("Collect Due from :name", ['name' => $this->getRecord()->name]))
                ->form([
                    Forms\Components\TextInput::make('amount_paid')
                        ->label(__('Payment Amount (আদায়ের পরিমাণ)'))
                        ->numeric()
                        ->prefix('৳')
                        ->required()
                        ->minValue(1)
                        ->default(fn (): float => (float) $this->getRecord()->current_due),

                    Forms\Components\Select::make('payment_method')
                        ->label(__('Payment Method (পদ্ধতি)'))
                        ->options([
                            'cash' => __('Cash / নগদ'),
                            'bkash' => __('bKash / বিকাশ'),
                            'nagad' => __('Nagad / নগদ'),
                            'bank_cheque' => __('Bank Cheque / ট্রান্সফার'),
                        ])
                        ->default('cash')
                        ->required(),

                    Forms\Components\TextInput::make('receipt_no')
                        ->label(__('Money Receipt No (রসিদ নং)'))
                        ->placeholder(__('Auto-generated if blank')),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('Notes / Reference (মন্তব্য / TrxID)')),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
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
                            ->body(__("Collected ৳:amount under Receipt :receipt. New balance: ৳:newDue", [
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
                    }
                }),

            Actions\EditAction::make(),
        ];
    }
}
