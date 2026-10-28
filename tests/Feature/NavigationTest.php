<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    }

    public function test_sidebar_is_configured_collapsible_on_desktop_and_collapsible_groups(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertTrue($panel->hasCollapsibleNavigationGroups());
    }

    public function test_dashboard_renders_with_collapsible_sidebar_classes(): void
    {
        $admin = User::first() ?? User::factory()->create();
        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSuccessful();

        // Check for presence of sidebar collapse and expand Alpine actions
        $response->assertSee('$store.sidebar.close()', false);
        $response->assertSee('$store.sidebar.open()', false);
    }
}
