<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Services\PurchaseService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => __('Please add at least one feed item to this purchase consignment.'),
            ]);
        }

        try {
            return PurchaseService::createPurchase($data, $items, auth()->id() ?? 1);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('Consignment Recording Failed'))
                ->body($e->getMessage())
                ->send();

            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }
    }
}
