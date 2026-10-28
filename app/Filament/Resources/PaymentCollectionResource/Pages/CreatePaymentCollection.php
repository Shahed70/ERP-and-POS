<?php

namespace App\Filament\Resources\PaymentCollectionResource\Pages;

use App\Filament\Resources\PaymentCollectionResource;
use App\Models\Customer;
use App\Services\SaleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePaymentCollection extends CreateRecord
{
    protected static string $resource = PaymentCollectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $customer = Customer::findOrFail($data['customer_id']);
        $amount = (float) $data['amount_paid'];

        try {
            return SaleService::collectPayment(
                customer: $customer,
                amountPaid: $amount,
                paymentMethod: $data['payment_method'] ?? 'cash',
                reference: $data['transaction_reference'] ?? null,
                notes: $data['notes'] ?? null,
                userId: auth()->id() ?? 1
            );
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('Payment Collection Failed'))
                ->body($e->getMessage())
                ->send();

            throw ValidationException::withMessages([
                'amount_paid' => $e->getMessage(),
            ]);
        }
    }
}
