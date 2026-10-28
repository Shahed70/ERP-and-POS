<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = Product::findOrFail($data['product_id']);
        $packSize = (float) ($product->pack_size ?: 25.00);
        $inputQty = (float) $data['input_quantity'];

        $isIncrease = in_array($data['type'], ['surplus_found', 'opening_stock'], true);
        $kgChange = $data['unit_type'] === 'bag' ? ($inputQty * $packSize) : $inputQty;
        $signedKg = $isIncrease ? $kgChange : -$kgChange;

        $data['mode'] = $isIncrease ? 'increase' : 'decrease';
        $data['quantity_kg'] = $kgChange;
        $data['authorized_by'] = auth()->id() ?? 1;

        // Perform stock balance mutation and ledger recording via StockService
        $adjustment = StockAdjustment::create($data);

        StockService::recordMovement(
            productId: $product->id,
            quantityKgChange: $signedKg,
            transactionType: $isIncrease ? ($data['type'] === 'opening_stock' ? 'opening_stock' : 'manual_adjustment_surplus') : 'damage_wastage',
            referenceNo: $adjustment->adjustment_number,
            referenceId: $adjustment->id,
            notes: $data['reason'],
            locationId: $data['location_id'],
            performedBy: auth()->id() ?? 1,
        );

        Notification::make()
            ->success()
            ->title(__('Stock Adjusted Successfully'))
            ->body(__(":mode of :qty (:kg KG) recorded for :product", [
                'mode' => $isIncrease ? 'Increase (+)' : 'Decrease (-)',
                'qty' => "{$inputQty} " . strtoupper($data['unit_type']),
                'kg' => number_format($kgChange, 2),
                'product' => $product->name,
            ]))
            ->send();

        return $adjustment;
    }
}
