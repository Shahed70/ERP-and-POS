<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    public function test_category_can_be_created_with_auto_slug(): void
    {
        $category = ProductCategory::create([
            'name' => 'Floating Feed',
            'description' => 'Commercial floating fish feed',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Floating Feed',
            'slug' => 'floating-feed',
            'is_active' => true,
        ]);

        // Clean up
        $category->delete();
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
}
