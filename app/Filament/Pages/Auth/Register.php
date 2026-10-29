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

    /**
     * Send email verification notification synchronously with error resilience.
     */
    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail || $user->hasVerifiedEmail()) {
            return;
        }

        try {
            $notification = app(\App\Notifications\Auth\VerifyEmail::class);
            $notification->url = \Filament\Facades\Filament::getVerifyEmailUrl($user);

            // Send immediately (synchronously)
            $user->notifyNow($notification);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Email verification delivery error: ' . $e->getMessage(), [
                'recipient' => $user->email,
            ]);

            \Filament\Notifications\Notification::make()
                ->title('ইমেইল পাঠানো সম্ভব হয়নি / Email Delivery Warning')
                ->body('আপনার অ্যাকাউন্ট তৈরি হয়েছে। তবে মেইল সার্ভার সংযোগের কারণে ভেরিফিকেশন ইমেইলটি পাঠানো যায়নি। অনুগ্রহ করে অ্যাডমিনের সাথে যোগাযোগ করুন অথবা পরে আবার চেষ্টা করুন।')
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
