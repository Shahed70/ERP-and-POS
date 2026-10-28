<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseTransactions;

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'name' => 'Floating Feeds ' . uniqid(),
            'description' => 'Test feed category',
            'is_active' => true,
        ]);
    }

    public function test_product_can_be_created_with_auto_sku_and_loose_price(): void
    {
        $category = $this->createCategory();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Mega Floating Grower 3.0mm',
            'brand' => 'Mega Feed',
            'feed_type' => 'floating',
            'pellet_size' => '3.0mm',
            'unit' => 'KG',
            'pack_size' => 25.00,
            'allow_loose_sale' => true,
            'purchase_price' => 2200.00,
            'sale_price' => 2500.00,
            // loose_sale_price_per_kg intentionally omitted to test auto-calculation
            'reorder_level_bags' => 15,
            'is_active' => true,
        ]);

        $this->assertNotEmpty($product->sku);
        // 2500 / 25 = 100.00
        $this->assertEquals(100.00, (float) $product->loose_sale_price_per_kg);
        $this->assertEquals('Mega Feed', $product->brand);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_resource_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin/products');
        $response->assertSuccessful();

        $createResponse = $this->actingAs($admin)->get('/admin/products/create');
        $createResponse->assertSuccessful();
    }

    public function test_product_can_be_deleted_via_table_action(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $category = $this->createCategory();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Product to Delete',
            'brand' => 'TestBrand',
            'unit' => 'KG',
            'pack_size' => 25.00,
            'purchase_price' => 1000.00,
            'sale_price' => 1200.00,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ListProducts::class)
            ->callTableAction('delete', $product);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}
