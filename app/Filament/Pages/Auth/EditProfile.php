<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    /**
     * Enforce that only Google Gmail (@gmail.com) addresses can be saved in user profile.
     */
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->rules([
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
            ])
            ->validationMessages([
                'regex' => 'Only Google Gmail (@gmail.com) addresses are allowed.',
            ]);
    }
}
