<?php

namespace Tests\Feature;

use App\Filament\Resources\SaleReturnResource;
use App\Filament\Resources\SaleReturnResource\Pages\CreateSaleReturn;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\SaleReturnService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SaleReturnTest extends TestCase
{
    use DatabaseTransactions;

    protected function createProduct(string $name, float $packSize = 25.00, float $price = 2400.00, float $initialStockKg = 100.00): Product
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Return Test Cat'],
            ['is_active' => true]
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name . ' ' . uniqid(),
            'brand' => 'Quality Feeds',
            'feed_type' => 'floating',
            'unit' => 'KG',
            'pack_size' => $packSize,
            'purchase_price' => 2000.00,
            'sale_price' => $price,
            'is_active' => true,
        ]);

        if ($initialStockKg > 0) {
            StockService::recordMovement(
                productId: $product->id,
                quantityKgChange: $initialStockKg,
                transactionType: 'opening_stock',
                referenceNo: 'INIT-RET-STOCK',
                locationId: 1,
                performedBy: 1
            );
        }

        return $product;
    }

    protected function createCustomer(float $openingDue = 5000.00): Customer
    {
        return Customer::create([
            'name' => 'Return Farmer ' . uniqid(),
            'phone' => '01713' . rand(100000, 999999),
            'credit_limit' => 50000.00,
            'opening_due' => $openingDue,
            'is_active' => true,
        ]);
    }

    public function test_credit_first_settlement_reduces_customer_debt_and_restores_intact_stock(): void
    {
        $customer = $this->createCustomer(5000.00); // 5,000 Tk existing due
        $product = $this->createProduct('Sealed Grower', 25.00, 2400.00, 100.00); // 100 KG initial stock

        // Customer returns 1 intact sealed bag (25 KG) worth 2,400 Tk
        $saleReturn = SaleReturnService::createReturn(
            returnData: [
                'customer_id' => $customer->id,
                'location_id' => 1,
                'return_date' => now()->toDateString(),
                'reason' => 'Ordered extra bags, returned sealed',
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'condition' => 'intact_sealed_bag',
                    'unit_price' => 2400.00,
                ],
            ],
            userId: 1
        );

        // 1. Verify Return Calculation
        $this->assertEquals(2400.00, (float) $saleReturn->total_return_value);
        $this->assertEquals(2400.00, (float) $saleReturn->due_deduction_amount);
        $this->assertEquals(0.00, (float) $saleReturn->cash_refund_amount);
        $this->assertEquals(0.00, (float) $saleReturn->advance_credited_amount);

        // 2. Verify Customer Debt Reduced (5,000 - 2,400 = 2,600)
        $customer->refresh();
        $this->assertEquals(2600.00, (float) $customer->current_due);

        // Verify Customer Ledger Credit Entry
        $ledger = CustomerLedger::where('customer_id', $customer->id)
            ->where('transaction_type', 'sale_return_credit')
            ->where('reference_no', $saleReturn->return_no)
            ->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(2400.00, (float) $ledger->credit_amount);
        $this->assertEquals(2600.00, (float) $ledger->running_due_balance);

        // 3. Verify Physical Inventory Restored (100 KG + 25 KG = 125 KG)
        $this->assertEquals(125.00, $product->fresh()->total_stock_kg);

        // Verify Stock Ledger Audit
        $stockLedger = StockLedger::where('reference_no', $saleReturn->return_no)->first();
        $this->assertNotNull($stockLedger);
        $this->assertEquals('sale_return', $stockLedger->transaction_type);
        $this->assertEquals(25.00, (float) $stockLedger->quantity_kg_change);
    }

    public function test_excess_return_value_credits_to_customer_advance_balance(): void
    {
        $customer = $this->createCustomer(1000.00); // 1,000 Tk existing due
        $product = $this->createProduct('Advance Feed', 25.00, 2400.00, 100.00);

        // Returns 1 bag (2,400 Tk) with preference to credit excess as advance deposit
        $saleReturn = SaleReturnService::createReturn(
            returnData: [
                'customer_id' => $customer->id,
                'refund_preference' => 'advance',
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'condition' => 'intact_sealed_bag',
                    'unit_price' => 2400.00,
                ],
            ],
            userId: 1
        );

        $customer->refresh();
        // Debt cleared to zero
        $this->assertEquals(1000.00, (float) $saleReturn->due_deduction_amount);
        $this->assertEquals(0.00, (float) $customer->current_due);

        // Remaining 1,400 Tk credited to advance deposit balance
        $this->assertEquals(1400.00, (float) $saleReturn->advance_credited_amount);
        $this->assertEquals(1400.00, (float) $customer->advance_balance);
    }

    public function test_excess_return_value_refunded_in_cash(): void
    {
        $customer = $this->createCustomer(0.00); // Zero due
        $product = $this->createProduct('Cash Refund Feed', 25.00, 2000.00, 100.00);

        // Returns 1 bag with cash refund preference
        $saleReturn = SaleReturnService::createReturn(
            returnData: [
                'customer_id' => $customer->id,
                'refund_preference' => 'cash',
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'condition' => 'intact_sealed_bag',
                    'unit_price' => 2000.00,
                ],
            ],
            userId: 1
        );

        $this->assertEquals(0.00, (float) $saleReturn->due_deduction_amount);
        $this->assertEquals(2000.00, (float) $saleReturn->cash_refund_amount);
        $this->assertEquals(0.00, (float) $saleReturn->advance_credited_amount);
        $this->assertEquals(0.00, (float) $customer->fresh()->current_due);
    }

    public function test_damaged_goods_do_not_increase_active_stock_and_route_to_damage_wastage(): void
    {
        $customer = $this->createCustomer(3000.00);
        $product = $this->createProduct('Damaged Feed Bag', 25.00, 2200.00, 100.00); // 100 KG initial

        // Customer returns a damaged/torn bag
        $saleReturn = SaleReturnService::createReturn(
            returnData: [
                'customer_id' => $customer->id,
                'reason' => 'Bag was torn during rain, wet feed',
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'condition' => 'opened_damaged',
                    'unit_price' => 2200.00,
                ],
            ],
            userId: 1
        );

        // 1. Salable active inventory must NOT increase (must remain 100 KG)
        $this->assertEquals(100.00, $product->fresh()->total_stock_kg);

        // 2. Must be routed to StockAdjustment as damage_wastage
        $wastage = StockAdjustment::where('product_id', $product->id)
            ->where('type', 'damage_wastage')
            ->where('reason', 'like', '%' . $saleReturn->return_no . '%')
            ->first();

        $this->assertNotNull($wastage);
        $this->assertEquals(25.00, (float) $wastage->quantity_kg);
        $this->assertEquals('deduct', $wastage->mode);
    }

    public function test_sale_return_via_filament_create_page(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $customer = $this->createCustomer(4000.00);
        $product = $this->createProduct('Filament Return Feed', 25.00, 2400.00, 100.00);
        $defaultLoc = Location::getDefault();

        Livewire::actingAs($admin)
            ->test(CreateSaleReturn::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'return_date' => now()->toDateString(),
                'location_id' => $defaultLoc->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'unit_type' => 'bag',
                        'quantity' => 1,
                        'pack_size' => 25.00,
                        'condition' => 'intact_sealed_bag',
                        'unit_price' => 2400.00,
                        'line_total' => 2400.00,
                    ],
                ],
                'total_return_value' => 2400.00,
                'due_deduction_amount' => 2400.00,
                'refund_preference' => 'advance',
                'reason' => 'Wrong pellet size ordered by farmer',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(SaleReturnResource::getUrl('index'));

        $this->assertDatabaseHas('sale_returns', [
            'customer_id' => $customer->id,
            'total_return_value' => 2400.00,
            'due_deduction_amount' => 2400.00,
        ]);
    }

    public function test_sale_return_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $customer = $this->createCustomer();
        $product = $this->createProduct('Accessible Return', 25.00, 2400.00, 100.00);

        $saleReturn = SaleReturnService::createReturn(
            returnData: [
                'customer_id' => $customer->id,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'condition' => 'intact_sealed_bag',
                    'unit_price' => 2400.00,
                ],
            ],
            userId: $admin->id
        );

        $this->actingAs($admin)->get('/admin/sale-returns')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/sale-returns/create')->assertSuccessful();
        $this->actingAs($admin)->get("/admin/sale-returns/{$saleReturn->id}")->assertSuccessful();
    }
}
