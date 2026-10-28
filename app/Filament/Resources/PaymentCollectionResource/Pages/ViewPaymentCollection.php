<?php

namespace App\Filament\Resources\PaymentCollectionResource\Pages;

use App\Filament\Resources\PaymentCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentCollection extends ViewRecord
{
    protected static string $resource = PaymentCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label(__('Print Receipt (রসিদ প্রিন্ট)'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('payment-collections.print', ['collection' => $this->getRecord(), 'autoprint' => 1]))
                ->openUrlInNewTab(),
        ];
    }
}
