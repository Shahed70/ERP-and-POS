<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseResource;
use App\Filament\Resources\PurchaseResource\Pages\CreatePurchase;
use App\Filament\Resources\PurchaseResource\Pages\ListPurchases;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\StockService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use DatabaseTransactions;

    protected function createProduct(string $name, float $packSize = 25.00, float $cost = 2000.00): Product
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Feed Test Cat'],
            ['is_active' => true]
        );

        return Product::create([
            'category_id' => $category->id,
            'name' => $name . ' ' . uniqid(),
            'brand' => 'Mega Feed Mills',
            'feed_type' => 'floating',
            'unit' => 'KG',
            'pack_size' => $packSize,
            'purchase_price' => $cost,
            'sale_price' => $cost + 200.00,
            'is_active' => true,
        ]);
    }

    protected function createSupplier(): Supplier
    {
        return Supplier::create([
            'company_name' => 'Mega Feed Mills Ltd. ' . uniqid(),
            'phone' => '01711' . rand(100000, 999999),
            'opening_payable' => 50000.00,
            'is_active' => true,
        ]);
    }

    public function test_multi_product_purchase_calculates_totals_and_updates_stock_and_ledger(): void
    {
        $supplier = $this->createSupplier();
        $starter = $this->createProduct('Starter 1.5mm', 25.00, 2200.00);
        $grower = $this->createProduct('Grower 3.0mm', 25.00, 2150.00);
        $defaultLoc = Location::getDefault();

        $initialStarterStock = $starter->total_stock_kg;
        $initialGrowerStock = $grower->total_stock_kg;
        $initialPayable = (float) $supplier->current_payable; // 50,000

        // Purchase Consignment:
        // Item 1: Starter - 100 bags @ 2,200 Tk = 220,000 Tk (2,500 KG)
        // Item 2: Grower - 200 bags @ 2,150 Tk = 430,000 Tk (5,000 KG)
        // Subtotal = 650,000 Tk
        // Transport = 5,000 Tk
        // Discount = 10,000 Tk
        // Grand Total = 645,000 Tk
        // Paid Amount = 200,000 Tk
        // Net Due Added = 445,000 Tk
        $purchase = PurchaseService::createPurchase(
            purchaseData: [
                'supplier_id' => $supplier->id,
                'supplier_challan_no' => 'MEGA-CH-9912',
                'location_id' => $defaultLoc->id,
                'purchase_date' => now()->toDateString(),
                'transport_fare_labor_cost' => 5000.00,
                'discount_amount' => 10000.00,
                'paid_amount' => 200000.00,
                'payment_method' => 'bank_transfer',
                'notes' => 'Truck delivery consignment #1',
            ],
            itemsData: [
                [
                    'product_id' => $starter->id,
                    'quantity_bags' => 100,
                    'pack_size' => 25.00,
                    'purchase_rate_per_bag' => 2200.00,
                ],
                [
                    'product_id' => $grower->id,
                    'quantity_bags' => 200,
                    'pack_size' => 25.00,
                    'purchase_rate_per_bag' => 2150.00,
                ],
            ],
            userId: 1
        );

        // 1. Verify Purchase Record Calculation
        $this->assertEquals(650000.00, (float) $purchase->subtotal_amount);
        $this->assertEquals(5000.00, (float) $purchase->transport_fare_labor_cost);
        $this->assertEquals(10000.00, (float) $purchase->discount_amount);
        $this->assertEquals(645000.00, (float) $purchase->grand_total);
        $this->assertEquals(200000.00, (float) $purchase->paid_amount);
        $this->assertEquals(445000.00, (float) $purchase->due_amount);
        $this->assertEquals('partial', $purchase->payment_status);
        $this->assertStringStartsWith('PUR-', $purchase->purchase_invoice_no);

        // 2. Verify Individual Items
        $this->assertCount(2, $purchase->items);
        $item1 = $purchase->items->where('product_id', $starter->id)->first();
        $this->assertEquals(100, $item1->quantity_bags);
        $this->assertEquals(2500.00, (float) $item1->total_weight_kg);
        $this->assertEquals(220000.00, (float) $item1->line_total);

        $item2 = $purchase->items->where('product_id', $grower->id)->first();
        $this->assertEquals(200, $item2->quantity_bags);
        $this->assertEquals(5000.00, (float) $item2->total_weight_kg);
        $this->assertEquals(430000.00, (float) $item2->line_total);

        // 3. Verify Inventory Stock Addition
        $this->assertEquals($initialStarterStock + 2500.00, $starter->fresh()->total_stock_kg);
        $this->assertEquals($initialGrowerStock + 5000.00, $grower->fresh()->total_stock_kg);

        // Verify Stock Ledger Audit Trail
        $starterStockLedger = StockLedger::where('reference_no', $purchase->purchase_invoice_no)
            ->where('product_id', $starter->id)
            ->first();
        $this->assertNotNull($starterStockLedger);
        $this->assertEquals(2500.00, (float) $starterStockLedger->quantity_kg_change);
        $this->assertEquals('purchase', $starterStockLedger->transaction_type);

        // 4. Verify Supplier Ledger & Balances
        $supplier->refresh();
        $expectedPayable = $initialPayable + 445000.00;
        $this->assertEquals($expectedPayable, (float) $supplier->current_payable);

        // Verify Bill Credit in Supplier Ledger
        $billLedger = SupplierLedger::where('supplier_id', $supplier->id)
            ->where('transaction_type', 'purchase_bill')
            ->where('reference_no', $purchase->purchase_invoice_no)
            ->first();
        $this->assertNotNull($billLedger);
        $this->assertEquals(645000.00, (float) $billLedger->credit_amount);

        // Verify Payment Debit in Supplier Ledger
        $payLedger = SupplierLedger::where('supplier_id', $supplier->id)
            ->where('transaction_type', 'supplier_payment')
            ->where('reference_no', $purchase->purchase_invoice_no . '-PAY')
            ->first();
        $this->assertNotNull($payLedger);
        $this->assertEquals(200000.00, (float) $payLedger->debit_amount);
        $this->assertEquals('bank_transfer', $payLedger->payment_method);

        // 5. Verify Supplier Product Pivot Update
        $this->assertTrue($supplier->products->contains($starter));
        $this->assertEquals(2200.00, (float) $supplier->products->find($starter->id)->pivot->last_purchase_price);
        $this->assertEquals(2150.00, (float) $supplier->products->find($grower->id)->pivot->last_purchase_price);
    }

    public function test_purchase_via_filament_create_page(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $supplier = $this->createSupplier();
        $product = $this->createProduct('Finisher 5.0mm', 25.00, 2050.00);
        $defaultLoc = Location::getDefault();

        Livewire::actingAs($admin)
            ->test(CreatePurchase::class)
            ->fillForm([
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'supplier_challan_no' => 'TEST-CH-01',
                'location_id' => $defaultLoc->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_bags' => 50,
                        'pack_size' => 25.00,
                        'purchase_rate_per_bag' => 2050.00,
                        'total_weight_kg' => 1250.00,
                        'line_total' => 102500.00,
                    ],
                ],
                'transport_fare_labor_cost' => 1500.00,
                'discount_amount' => 500.00,
                'subtotal_amount' => 102500.00,
                'grand_total' => 103500.00,
                'paid_amount' => 50000.00,
                'due_amount' => 53500.00,
                'payment_method' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(PurchaseResource::getUrl('index'));

        $this->assertDatabaseHas('purchases', [
            'supplier_id' => $supplier->id,
            'supplier_challan_no' => 'TEST-CH-01',
            'grand_total' => 103500.00,
            'paid_amount' => 50000.00,
            'due_amount' => 53500.00,
            'payment_status' => 'partial',
        ]);
    }

    public function test_purchase_voiding_reverses_stock_and_payable(): void
    {
        $supplier = $this->createSupplier();
        $product = $this->createProduct('Sinking Carp Feed', 25.00, 1900.00);
        $initialStock = $product->total_stock_kg;
        $initialPayable = (float) $supplier->current_payable;

        $purchase = PurchaseService::createPurchase(
            purchaseData: [
                'supplier_id' => $supplier->id,
                'paid_amount' => 10000.00,
                'payment_method' => 'cash',
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'quantity_bags' => 40, // 1,000 KG
                    'pack_size' => 25.00,
                    'purchase_rate_per_bag' => 1900.00, // 76,000 Tk
                ],
            ],
            userId: 1
        );

        $this->assertEquals($initialStock + 1000.00, $product->fresh()->total_stock_kg);
        $this->assertEquals($initialPayable + 66000.00, (float) $supplier->fresh()->current_payable);

        // Now void/delete purchase
        PurchaseService::deletePurchase($purchase, 1);

        // Verify stock is reversed back to initial
        $this->assertEquals($initialStock, $product->fresh()->total_stock_kg);

        // Verify supplier payable is reversed back to initial
        $this->assertEquals($initialPayable, (float) $supplier->fresh()->current_payable);

        // Verify purchase was deleted
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
    }

    public function test_purchase_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $supplier = $this->createSupplier();
        $product = $this->createProduct('Nursery Feed', 20.00, 2500.00);

        $purchase = PurchaseService::createPurchase(
            purchaseData: [
                'supplier_id' => $supplier->id,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'quantity_bags' => 10,
                    'pack_size' => 20.00,
                    'purchase_rate_per_bag' => 2500.00,
                ],
            ],
            userId: $admin->id
        );

        $this->actingAs($admin)->get('/admin/purchases')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/purchases/create')->assertSuccessful();
        $this->actingAs($admin)->get("/admin/purchases/{$purchase->id}")->assertSuccessful();
    }
}
