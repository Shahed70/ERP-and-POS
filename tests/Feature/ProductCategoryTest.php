<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductCategoryResource\Pages\ListProductCategories;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_category_can_be_created_with_auto_slug(): void
    {
        $uniqueSuffix = uniqid();
        $category = ProductCategory::create([
            'name' => 'Floating Feed ' . $uniqueSuffix,
            'description' => 'Commercial floating fish feed',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Floating Feed ' . $uniqueSuffix,
            'slug' => 'floating-feed-' . $uniqueSuffix,
            'is_active' => true,
        ]);
    }

    public function test_category_resource_page_is_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin/product-categories');
        $response->assertSuccessful();

        $createResponse = $this->actingAs($admin)->get('/admin/product-categories/create');
        $createResponse->assertSuccessful();
    }

    public function test_category_can_be_deleted_via_filament_table_action(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $category = ProductCategory::create([
            'name' => 'Temporary Category ' . uniqid(),
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ListProductCategories::class)
            ->callTableAction('delete', $category);

        $this->assertDatabaseMissing('product_categories', [
            'id' => $category->id,
        ]);
    }
}
