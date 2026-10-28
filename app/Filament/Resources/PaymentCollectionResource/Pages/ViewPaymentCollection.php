<?php

namespace App\Filament\Resources\PaymentCollectionResource\Pages;

use App\Filament\Resources\PaymentCollectionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentCollection extends ViewRecord
{
    protected static string $resource = PaymentCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
