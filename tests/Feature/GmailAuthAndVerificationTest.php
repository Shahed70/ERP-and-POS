<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Filament\Notifications\Auth\VerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GmailAuthAndVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
    }

    public function test_primary_admin_can_authenticate_with_designated_credentials(): void
    {
        $admin = User::where('email', 'amaremail234@gmail.com')->first();
        $this->assertNotNull($admin, 'Primary admin account amaremail234@gmail.com must exist');

        $this->assertTrue(
            Hash::check('shahed@456', $admin->password),
            'Admin password must match shahed@456'
        );

        $this->assertTrue(
            Auth::attempt(['email' => 'amaremail234@gmail.com', 'password' => 'shahed@456']),
            'Auth::attempt must succeed for primary admin'
        );

        $this->assertTrue($admin->isAdmin(), 'User must have Admin role');
        $this->assertTrue($admin->hasVerifiedEmail(), 'Primary admin email must be pre-verified');
    }

    public function test_login_rejects_non_gmail_email_addresses(): void
    {
        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'invalid.user@yahoo.com',
                'password' => 'secret123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email' => 'regex']);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'user@hotmail.com',
                'password' => 'secret123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email' => 'regex']);
    }

    public function test_login_allows_valid_gmail_address(): void
    {
        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'amaremail234@gmail.com',
                'password' => 'shahed@456',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_non_gmail_email_addresses(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Non Gmail User',
                'email' => 'test@outlook.com',
                'password' => 'Password123#',
                'passwordConfirmation' => 'Password123#',
            ])
            ->call('register')
            ->assertHasFormErrors(['email' => 'regex']);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@outlook.com',
        ]);
    }

    public function test_registration_accepts_gmail_and_triggers_email_verification(): void
    {
        Notification::fake();

        $gmail = 'newregistered.' . uniqid() . '@gmail.com';

        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'New Feed Buyer',
                'email' => $gmail,
                'password' => 'SecurePass123#',
                'passwordConfirmation' => 'SecurePass123#',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::where('email', $gmail)->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Viewer'), 'New registrations must be assigned Viewer role');
        $this->assertFalse($user->hasVerifiedEmail(), 'New registered user email must not be verified initially');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_is_redirected_to_email_verification_prompt(): void
    {
        $unverifiedUser = User::factory()->unverified()->create([
            'email' => 'pending.' . uniqid() . '@gmail.com',
        ]);
        $unverifiedUser->assignRole('Viewer');

        $this->actingAs($unverifiedUser)
            ->get('/admin')
            ->assertRedirect('/admin/email-verification/prompt');

        $this->actingAs($unverifiedUser)
            ->get('/admin/email-verification/prompt')
            ->assertSuccessful();
    }

    public function test_verified_user_can_access_dashboard_directly(): void
    {
        $verifiedAdmin = User::where('email', 'amaremail234@gmail.com')->first();

        $this->actingAs($verifiedAdmin)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_user_management_in_admin_panel_enforces_gmail_address(): void
    {
        $admin = User::where('email', 'amaremail234@gmail.com')->first();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Manager X',
                'email' => 'manager@otherdomain.com',
                'password' => 'Manager123#',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'regex']);

        $this->assertDatabaseMissing('users', [
            'email' => 'manager@otherdomain.com',
        ]);
    }
}
