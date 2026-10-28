<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\SaleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

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
                'items' => __('Please add at least one feed product to this sale invoice.'),
            ]);
        }

        $allowOverride = (bool) ($data['allow_credit_override'] ?? false);
        $overrideNotes = $data['override_notes'] ?? null;
        unset($data['allow_credit_override'], $data['override_notes']);

        try {
            return SaleService::createSale(
                saleData: $data,
                itemsData: $items,
                userId: auth()->id() ?? 1,
                allowCreditOverride: $allowOverride,
                overrideNotes: $overrideNotes
            );
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('Sale Invoice Processing Failed'))
                ->body($e->getMessage())
                ->send();

            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }
    }
}
