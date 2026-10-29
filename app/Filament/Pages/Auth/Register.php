<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    /**
     * Enforce that only Google Gmail (@gmail.com) addresses are allowed for registration.
     */
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->rules([
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
            ])
            ->validationMessages([
                'regex' => 'Only Google Gmail (@gmail.com) addresses are allowed for registration.',
            ]);
    }

    /**
     * Handle user registration and assign read-only 'Viewer' role by default.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        // Assign 'Viewer' role to new signups so only Admin has write privileges
        $user->assignRole('Viewer');

        return $user;
    }
}
