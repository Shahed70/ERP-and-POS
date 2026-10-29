<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * Enforce that only Google Gmail (@gmail.com) accounts are allowed to log in.
     */
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->rules([
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
            ])
            ->validationMessages([
                'regex' => 'Only Google Gmail (@gmail.com) accounts are allowed to log in.',
            ]);
    }
}
