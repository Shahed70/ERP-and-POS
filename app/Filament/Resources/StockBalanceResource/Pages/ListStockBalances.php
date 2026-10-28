<?php

namespace App\Filament\Resources\StockBalanceResource\Pages;

use App\Filament\Resources\StockBalanceResource;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use Filament\Resources\Pages\ListRecords;

class ListStockBalances extends ListRecords
{
    protected static string $resource = StockBalanceResource::class;

    public function mount(): void
    {
        parent::mount();

        // Ensure every product in the catalog has a baseline stock record in the default location
        $defaultLocId = Location::getDefault()?->id ?? 1;
        $existingProductIds = StockBalance::where('location_id', $defaultLocId)->pluck('product_id')->toArray();
        $missingProductIds = Product::whereNotIn('id', $existingProductIds)->pluck('id');

        foreach ($missingProductIds as $pId) {
            StockBalance::firstOrCreate([
                'location_id' => $defaultLocId,
                'product_id' => $pId,
            ], [
                'total_weight_kg' => 0.00,
            ]);
        }
    }
}
