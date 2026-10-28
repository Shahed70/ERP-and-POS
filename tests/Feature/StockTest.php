<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\StockService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockTest extends TestCase
{
    use DatabaseTransactions;

    protected function createProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Stock Test Category ' . uniqid(),
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Quality Grower 25kg ' . uniqid(),
            'brand' => 'Quality Feed',
            'feed_type' => 'floating',
            'unit' => 'KG',
            'pack_size' => 25.00,
            'purchase_price' => 2000.00,
            'sale_price' => 2300.00,
            'reorder_level_bags' => 5,
            'is_active' => true,
        ]);
    }

    public function test_stock_service_records_movement_and_calculates_dual_packaging(): void
    {
        $product = $this->createProduct();

        // Add 62.50 KG (2 Bags @ 25kg + 12.50 KG loose)
        $stock = StockService::recordMovement(
            productId: $product->id,
            quantityKgChange: 62.50,
            transactionType: 'opening_stock',
            referenceNo: 'INIT-001',
            notes: 'Test initial inventory',
        );

        $this->assertEquals(62.50, (float) $stock->total_weight_kg);
        $this->assertEquals(2, $stock->full_bags);
        $this->assertEquals(12.50, $stock->loose_kg);
        $this->assertEquals('2 Bags + 12.5 KG', $stock->formatted_stock);

        // Verify ledger record
        $ledger = StockLedger::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(62.50, (float) $ledger->quantity_kg_change);
        $this->assertEquals(62.50, (float) $ledger->running_balance_kg);
        $this->assertEquals('INIT-001', $ledger->reference_no);
    }

    public function test_stock_service_enforces_zero_negative_stock_policy(): void
    {
        $product = $this->createProduct();

        // Seed with 25 KG (1 Bag)
        StockService::recordMovement(
            productId: $product->id,
            quantityKgChange: 25.00,
            transactionType: 'opening_stock',
        );

        // Attempting to deduct 30 KG (exceeding 25 KG) MUST throw an Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        StockService::recordMovement(
            productId: $product->id,
            quantityKgChange: -30.00,
            transactionType: 'damage_wastage',
        );
    }

    public function test_stock_filament_resources_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $this->assertNotNull($admin);

        // Current Stock list
        $response = $this->actingAs($admin)->get('/admin/stock-balances');
        $response->assertSuccessful();

        // Stock Adjustments list & create
        $adjResponse = $this->actingAs($admin)->get('/admin/stock-adjustments');
        $adjResponse->assertSuccessful();

        $createAdjResponse = $this->actingAs($admin)->get('/admin/stock-adjustments/create');
        $createAdjResponse->assertSuccessful();

        // Stock Movement Ledger list
        $ledgerResponse = $this->actingAs($admin)->get('/admin/stock-ledgers');
        $ledgerResponse->assertSuccessful();
    }
}
