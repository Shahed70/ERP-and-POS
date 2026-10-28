<?php

namespace App\Filament\Resources\PaymentCollectionResource\Pages;

use App\Filament\Resources\PaymentCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCollections extends ListRecords
{
    protected static string $resource = PaymentCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('New Money Receipt (নতুন বাকি আদায় রসিদ)')),
        ];
    }
}
