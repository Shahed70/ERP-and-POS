<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAndSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
    }

    public function test_auth_routes_are_accessible(): void
    {
        // 1. Register page
        $this->get('/admin/register')->assertSuccessful();

        // 2. Forgot Password Request page
        $this->get('/admin/password-reset/request')->assertSuccessful();
    }

    public function test_user_profile_is_accessible_by_logged_in_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)
            ->get('/admin/profile')
            ->assertSuccessful();
    }

    public function test_admin_has_full_access_to_settings_and_user_management(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get('/admin/shop-settings-page')
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertSuccessful();
    }

    public function test_viewer_is_forbidden_from_shop_settings_and_user_management(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->get('/admin/shop-settings-page')
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_role_based_policy_enforcement(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $product = Product::first() ?? Product::create([
            'category_id' => 1,
            'name' => 'Test Feed Policy',
            'sku' => 'TEST-FEED-POL',
            'product_type' => 'floating',
            'pack_size' => 25,
            'purchase_price' => 1500,
            'retail_price' => 1800,
            'wholesale_price' => 1700,
        ]);

        // Viewer permissions: Can view, CANNOT create/update/delete
        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', Product::class));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $product));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $product));
        $this->assertFalse(Gate::forUser($viewer)->allows('delete', $product));

        // Admin permissions: Full access
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Product::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $product));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $product));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $product));
    }

    public function test_shop_settings_singleton_and_customization(): void
    {
        $settings = ShopSetting::getSettings();
        $this->assertNotNull($settings);
        $this->assertNotEmpty($settings->shop_name);

        $settings->update([
            'shop_name' => 'মেঘনা ফিশ ফিড ট্রেডার্স',
            'shop_tagline' => 'সেরা মানের মাছের খাদ্য পরিবেশক',
            'phone' => '০১৮৯৯-১১২২৩৩',
            'address' => 'ময়মনসিংহ রোড, ত্রিশাল',
        ]);

        $fresh = ShopSetting::getSettings();
        $this->assertEquals('মেঘনা ফিশ ফিড ট্রেডার্স', $fresh->shop_name);
        $this->assertEquals('সেরা মানের মাছের খাদ্য পরিবেশক', $fresh->shop_tagline);
        $this->assertEquals('০১৮৯৯-১১২২৩৩', $fresh->phone);
    }
}
