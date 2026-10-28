<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Services\PurchaseService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('Void / Delete Purchase Consignment'))
                ->modalDescription(__('Deleting this consignment will reverse the stock addition and undo the supplier payable ledger entry. This cannot be undone.'))
                ->action(function (Actions\DeleteAction $action): void {
                    $record = $this->getRecord();
                    try {
                        PurchaseService::deletePurchase($record, auth()->id() ?? 1);

                        Notification::make()
                            ->success()
                            ->title(__('Purchase Consignment Deleted'))
                            ->body(__('Inventory and supplier payable balances have been safely reversed.'))
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot Delete Consignment'))
                            ->body($e->getMessage())
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
