<?php

namespace App\Filament\Resources\SaleReturnResource\Pages;

use App\Filament\Resources\SaleReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSaleReturns extends ListRecords
{
    protected static string $resource = SaleReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('New Return Claim (নতুন পণ্য ফেরত নথিভুক্ত করুন)')),
        ];
    }
}
