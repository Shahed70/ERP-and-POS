<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
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
