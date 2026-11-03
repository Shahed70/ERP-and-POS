<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Determine whether the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Determine if user has administrator privileges.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    /**
     * Determine if user has viewer / read-only access.
     */
    public function isViewer(): bool
    {
        return ! $this->isAdmin();
    }

    /**
     * Send email verification notification using custom notification with error resilience.
     */
    public function sendEmailVerificationNotification(): void
    {
        try {
            $notification = app(\App\Notifications\Auth\VerifyEmail::class);
            $notification->url = \Filament\Facades\Filament::getVerifyEmailUrl($this);
            $this->notify($notification);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Email verification delivery failure: ' . $e->getMessage(), [
                'recipient' => $this->email,
            ]);
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
