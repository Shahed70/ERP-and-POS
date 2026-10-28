<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('make_payment')
                ->label(__('Pay Supplier (পাওনা পরিশোধ)'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => (float) $this->getRecord()->current_payable > 0)
                ->modalHeading(fn (): string => __("Pay Supplier: :company", ['company' => $this->getRecord()->company_name]))
                ->form([
                    Forms\Components\TextInput::make('amount_paid')
                        ->label(__('Payment Amount (পরিশোধের পরিমাণ)'))
                        ->numeric()
                        ->prefix('৳')
                        ->required()
                        ->minValue(1)
                        ->default(fn (): float => (float) $this->getRecord()->current_payable),

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
                        ->placeholder(__('Auto-generated if blank')),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('Reference / Cheque Details (মন্তব্য / চেক বিবরণী)')),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
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
                    }
                }),

            Actions\EditAction::make(),
        ];
    }
}
