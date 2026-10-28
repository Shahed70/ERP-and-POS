<?php

namespace Tests\Feature;

use App\Filament\Widgets\DashboardHeaderWidget;
use App\Filament\Widgets\DashboardStatsOverviewWidget;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\RecentSalesTableWidget;
use App\Filament\Widgets\SalesTrendChartWidget;
use App\Models\User;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
    }

    public function test_custom_dashboard_renders_for_admin_with_all_new_widgets(): void
    {
        $admin = User::first() ?? User::factory()->create();
        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSuccessful();

        // Ensure default widgets are NOT registered or rendered
        $response->assertDontSee('v3.3.57');
        $response->assertDontSeeLivewire(AccountWidget::class);
        $response->assertDontSeeLivewire(FilamentInfoWidget::class);

        // Ensure new widgets are rendered
        $response->assertSeeLivewire(DashboardHeaderWidget::class);
        $response->assertSeeLivewire(DashboardStatsOverviewWidget::class);
        $response->assertSeeLivewire(SalesTrendChartWidget::class);
        $response->assertSeeLivewire(LowStockAlertWidget::class);
        $response->assertSeeLivewire(RecentSalesTableWidget::class);

        // Header shows Admin badge and action buttons
        $response->assertSee('Admin / স্বত্বাধিকারী');
        $response->assertSee('+ New Sale (POS)');
        $response->assertSee('+ Purchase (ক্রয়)');
    }

    public function test_dashboard_renders_for_viewer_in_read_only_mode(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $response = $this->actingAs($viewer)->get('/admin');
        $response->assertSuccessful();

        // Header shows Viewer badge
        $response->assertSee('Viewer / পর্যবেক্ষক');

        // Quick create actions like "+ New Sale (POS)" and "+ Purchase" are hidden for viewer
        $response->assertDontSee('+ New Sale (POS)');
        $response->assertDontSee('+ Purchase (ক্রয়)');
    }
}
