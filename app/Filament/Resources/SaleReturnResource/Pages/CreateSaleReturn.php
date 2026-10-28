<?php

namespace App\Filament\Resources\SaleReturnResource\Pages;

use App\Filament\Resources\SaleReturnResource;
use App\Services\SaleReturnService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSaleReturn extends CreateRecord
{
    protected static string $resource = SaleReturnResource::class;

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
                'items' => __('Please add at least one returned feed item.'),
            ]);
        }

        try {
            return SaleReturnService::createReturn(
                returnData: $data,
                itemsData: $items,
                userId: auth()->id() ?? 1
            );
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('Return Claim Recording Failed'))
                ->body($e->getMessage())
                ->send();

            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }
    }
}
