<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use App\Services\PayrollService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSalary extends CreateRecord
{
    protected static string $resource = SalaryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return PayrollService::processSalary($data, auth()->id() ?? 1);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('Salary Generation Failed'))
                ->body($e->getMessage())
                ->send();

            throw ValidationException::withMessages([
                'employee_id' => $e->getMessage(),
            ]);
        }
    }
}
