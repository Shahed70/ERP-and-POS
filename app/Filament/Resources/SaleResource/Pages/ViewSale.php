<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\SaleService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label(__('Print Invoice (চালান প্রিন্ট)'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('sales.print', ['sale' => $this->getRecord(), 'autoprint' => 1]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('Void / Delete Sales Invoice'))
                ->modalDescription(__('Voiding this invoice will restore the sold feed stock back to inventory and cancel the customer debt. This cannot be undone.'))
                ->action(function (Actions\DeleteAction $action): void {
                    $record = $this->getRecord();
                    try {
                        SaleService::voidSale($record, auth()->id() ?? 1);

                        Notification::make()
                            ->success()
                            ->title(__('Sales Invoice Voided'))
                            ->body(__('Inventory restored and customer balance adjusted.'))
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot Void Invoice'))
                            ->body($e->getMessage())
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
