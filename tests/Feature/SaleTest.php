<?php

namespace Tests\Feature;

use App\Filament\Resources\PaymentCollectionResource;
use App\Filament\Resources\SaleResource;
use App\Filament\Resources\SaleResource\Pages\CreateSale;
use App\Models\CreditLimitOverride;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Location;
use App\Models\PaymentCollection;
use App\Models\PaymentCollectionAllocation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\SaleService;
use App\Services\StockService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use DatabaseTransactions;

    protected function createProduct(string $name, float $packSize = 25.00, float $salePrice = 2400.00, float $initialStockKg = 500.00): Product
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Feed Test Cat'],
            ['is_active' => true]
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name . ' ' . uniqid(),
            'brand' => 'Mega Feed Mills',
            'feed_type' => 'floating',
            'unit' => 'KG',
            'pack_size' => $packSize,
            'purchase_price' => 2000.00,
            'sale_price' => $salePrice,
            'is_active' => true,
        ]);

        if ($initialStockKg > 0) {
            StockService::recordMovement(
                productId: $product->id,
                quantityKgChange: $initialStockKg,
                transactionType: 'opening_stock',
                referenceNo: 'INIT-STOCK',
                unitCostPrice: 2000.00 / $packSize,
                locationId: 1,
                performedBy: 1
            );
        }

        return $product;
    }

    protected function createCustomer(string $name = 'Farmer Rahim', float $creditLimit = 50000.00, float $openingDue = 0.00): Customer
    {
        return Customer::create([
            'name' => $name . ' ' . uniqid(),
            'phone' => '01712' . rand(100000, 999999),
            'credit_limit' => $creditLimit,
            'opening_due' => $openingDue,
            'is_active' => true,
        ]);
    }

    public function test_dual_unit_sale_calculates_totals_and_deducts_stock_accurately(): void
    {
        $customer = $this->createCustomer('Commercial Farmer', 100000.00);
        $grower = $this->createProduct('Grower 3.0mm', 25.00, 2400.00, 500.00); // 500 KG available
        $defaultLoc = Location::getDefault();

        // Farmer buys:
        // Item 1: 2 Bags (2 * 25 = 50 KG) @ 2,400 Tk/bag = 4,800 Tk
        // Item 2: 5 Loose KG @ 96 Tk/kg = 480 Tk
        // Total Weight = 55 KG
        // Subtotal = 5,280 Tk
        // Discount = 80 Tk
        // Grand Total = 5,200 Tk
        // Paid Amount = 2,000 Tk (cash)
        // Due Amount = 3,200 Tk
        $sale = SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'location_id' => $defaultLoc->id,
                'sale_date' => now()->toDateString(),
                'discount_amount' => 80.00,
                'paid_amount' => 2000.00,
                'payment_method' => 'cash',
                'sale_type' => 'retail',
                'notes' => 'Farmer counter sale',
            ],
            itemsData: [
                [
                    'product_id' => $grower->id,
                    'unit_type' => 'bag',
                    'quantity' => 2,
                    'pack_size' => 25.00,
                    'unit_price' => 2400.00,
                ],
                [
                    'product_id' => $grower->id,
                    'unit_type' => 'kg',
                    'quantity' => 5,
                    'pack_size' => 25.00,
                    'unit_price' => 96.00,
                ],
            ],
            userId: 1
        );

        // 1. Verify Sale Calculations
        $this->assertEquals(5280.00, (float) $sale->subtotal_amount);
        $this->assertEquals(80.00, (float) $sale->discount_amount);
        $this->assertEquals(5200.00, (float) $sale->grand_total);
        $this->assertEquals(2000.00, (float) $sale->paid_amount);
        $this->assertEquals(3200.00, (float) $sale->due_amount);
        $this->assertEquals(3200.00, (float) $sale->unallocated_due);
        $this->assertEquals('partial', $sale->payment_status);
        $this->assertStringStartsWith('INV-', $sale->invoice_no);

        // 2. Verify Line Items
        $this->assertCount(2, $sale->items);
        $bagItem = $sale->items->where('unit_type', 'bag')->first();
        $this->assertEquals(2.00, (float) $bagItem->quantity);
        $this->assertEquals(50.00, (float) $bagItem->deducted_weight_kg);
        $this->assertEquals(4800.00, (float) $bagItem->line_total);

        $kgItem = $sale->items->where('unit_type', 'kg')->first();
        $this->assertEquals(5.00, (float) $kgItem->quantity);
        $this->assertEquals(5.00, (float) $kgItem->deducted_weight_kg);
        $this->assertEquals(480.00, (float) $kgItem->line_total);

        // 3. Verify Physical Stock Deduction (500 KG - 55 KG = 445 KG)
        $this->assertEquals(445.00, $grower->fresh()->total_stock_kg);

        // Verify Stock Ledger Audit Trail
        $stockLedger = StockLedger::where('reference_no', $sale->invoice_no)->first();
        $this->assertNotNull($stockLedger);
        $this->assertEquals('sale', $stockLedger->transaction_type);

        // 4. Verify Customer Debt & Ledger
        $customer->refresh();
        $this->assertEquals(3200.00, (float) $customer->current_due);

        // Verify Invoice Debit entry
        $invoiceLedger = CustomerLedger::where('customer_id', $customer->id)
            ->where('transaction_type', 'sale_invoice')
            ->where('reference_no', $sale->invoice_no)
            ->first();
        $this->assertNotNull($invoiceLedger);
        $this->assertEquals(5200.00, (float) $invoiceLedger->debit_amount);

        // Verify Payment Credit entry
        $paymentLedger = CustomerLedger::where('customer_id', $customer->id)
            ->where('transaction_type', 'due_collection')
            ->where('reference_no', $sale->invoice_no . '-PAY')
            ->first();
        $this->assertNotNull($paymentLedger);
        $this->assertEquals(2000.00, (float) $paymentLedger->credit_amount);
    }

    public function test_sale_fails_if_insufficient_stock(): void
    {
        $customer = $this->createCustomer();
        // Product with only 10 KG stock
        $product = $this->createProduct('Limited Feed', 25.00, 2400.00, 10.00);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/i');

        // Attempt to buy 1 bag (25 KG)
        SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'paid_amount' => 2400.00,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'unit_price' => 2400.00,
                ],
            ],
            userId: 1
        );
    }

    public function test_credit_limit_enforced_and_override_logged(): void
    {
        // Customer with 10,000 Tk limit and 8,000 Tk existing due
        $customer = $this->createCustomer('Strict Limit Customer', 10000.00, 8000.00);
        $product = $this->createProduct('Finisher Feed', 25.00, 2500.00, 500.00);

        // Customer attempts to buy 2 bags (5,000 Tk) on full credit.
        // Total due would be 8,000 + 5,000 = 13,000 Tk (exceeds 10,000 limit).

        // Step 1: Without override -> Must throw exception
        $exceptionCaught = false;
        try {
            SaleService::createSale(
                saleData: [
                    'customer_id' => $customer->id,
                    'paid_amount' => 0.00,
                ],
                itemsData: [
                    [
                        'product_id' => $product->id,
                        'unit_type' => 'bag',
                        'quantity' => 2,
                        'pack_size' => 25.00,
                        'unit_price' => 2500.00,
                    ],
                ],
                userId: 1,
                allowCreditOverride: false
            );
        } catch (Exception $e) {
            $exceptionCaught = true;
            $this->assertStringContainsString('Credit limit exceeded', $e->getMessage());
        }
        $this->assertTrue($exceptionCaught);

        // Step 2: With override authorized -> Must succeed and log audit record
        $authorizedSale = SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'paid_amount' => 0.00,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 2,
                    'pack_size' => 25.00,
                    'unit_price' => 2500.00,
                ],
            ],
            userId: 1,
            allowCreditOverride: true,
            overrideNotes: 'Owner authorized harvest credit'
        );

        $this->assertTrue($authorizedSale->is_credit_overridden);

        $overrideLog = CreditLimitOverride::where('sale_id', $authorizedSale->id)->first();
        $this->assertNotNull($overrideLog);
        $this->assertEquals(10000.00, (float) $overrideLog->current_credit_limit);
        $this->assertEquals(8000.00, (float) $overrideLog->existing_due);
        $this->assertEquals(5000.00, (float) $overrideLog->attempted_invoice_amount);
    }

    public function test_walk_in_cash_customer_cannot_buy_on_credit(): void
    {
        $walkIn = Customer::firstOrCreate(
            ['phone' => '01000000000'],
            ['name' => 'Walk-in Cash Customer', 'credit_limit' => 0.00, 'is_active' => true]
        );
        $product = $this->createProduct('Counter Feed', 25.00, 2400.00, 200.00);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Walk-in cash customers cannot purchase on credit/i');

        SaleService::createSale(
            saleData: [
                'customer_id' => $walkIn->id,
                'paid_amount' => 1000.00, // Due would be 1,400 Tk
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'unit_price' => 2400.00,
                ],
            ],
            userId: 1
        );
    }

    public function test_fifo_due_collection_allocates_across_oldest_invoices(): void
    {
        $customer = $this->createCustomer('FIFO Test Customer', 100000.00);
        $product = $this->createProduct('FIFO Feed', 25.00, 2000.00, 500.00);

        // Create Invoice 1: 3,000 Tk due
        $sale1 = SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'sale_date' => now()->subDays(5)->toDateString(),
                'paid_amount' => 0.00,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'kg',
                    'quantity' => 30, // 3,000 Tk
                    'pack_size' => 25.00,
                    'unit_price' => 100.00,
                ],
            ],
            userId: 1
        );

        // Create Invoice 2: 4,000 Tk due
        $sale2 = SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'sale_date' => now()->subDays(2)->toDateString(),
                'paid_amount' => 0.00,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'kg',
                    'quantity' => 40, // 4,000 Tk
                    'pack_size' => 25.00,
                    'unit_price' => 100.00,
                ],
            ],
            userId: 1
        );

        $customer->refresh();
        $this->assertEquals(7000.00, (float) $customer->current_due);

        // Customer pays 5,000 Tk via Money Receipt collection
        $collection = SaleService::collectPayment(
            customer: $customer,
            amountPaid: 5000.00,
            paymentMethod: 'bkash',
            reference: 'BKASH-TRX-99881',
            notes: 'Harvest installment',
            userId: 1
        );

        // 1. Verify FIFO Allocations
        // Invoice 1 (3,000 Tk) should be fully paid
        $sale1->refresh();
        $this->assertEquals(0.00, (float) $sale1->unallocated_due);
        $this->assertEquals('paid', $sale1->payment_status);

        // Invoice 2 (4,000 Tk) should receive remaining 2,000 Tk
        $sale2->refresh();
        $this->assertEquals(2000.00, (float) $sale2->unallocated_due);
        $this->assertEquals('partial', $sale2->payment_status);

        // 2. Verify Allocation Records
        $this->assertCount(2, $collection->allocations);
        $alloc1 = $collection->allocations->where('sale_id', $sale1->id)->first();
        $this->assertEquals(3000.00, (float) $alloc1->allocated_amount);

        $alloc2 = $collection->allocations->where('sale_id', $sale2->id)->first();
        $this->assertEquals(2000.00, (float) $alloc2->allocated_amount);

        // 3. Verify Customer Debt
        $customer->refresh();
        $this->assertEquals(2000.00, (float) $customer->current_due);
    }

    public function test_sale_via_filament_create_page(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $customer = $this->createCustomer('Filament Buyer', 50000.00);
        $product = $this->createProduct('POS Product', 25.00, 2400.00, 200.00);
        $defaultLoc = Location::getDefault();

        Livewire::actingAs($admin)
            ->test(CreateSale::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'sale_date' => now()->toDateString(),
                'sale_type' => 'retail',
                'location_id' => $defaultLoc->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'unit_type' => 'bag',
                        'quantity' => 1,
                        'pack_size' => 25.00,
                        'unit_price' => 2400.00,
                        'deducted_weight_kg' => 25.00,
                        'line_total' => 2400.00,
                    ],
                ],
                'discount_amount' => 100.00,
                'subtotal_amount' => 2400.00,
                'grand_total' => 2300.00,
                'paid_amount' => 2300.00,
                'due_amount' => 0.00,
                'payment_method' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(SaleResource::getUrl('index'));

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'grand_total' => 2300.00,
            'paid_amount' => 2300.00,
            'due_amount' => 0.00,
            'payment_status' => 'paid',
        ]);
    }

    public function test_sale_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $customer = $this->createCustomer();
        $product = $this->createProduct('View Feed', 25.00, 2400.00, 100.00);

        $sale = SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'paid_amount' => 2400.00,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 1,
                    'pack_size' => 25.00,
                    'unit_price' => 2400.00,
                ],
            ],
            userId: $admin->id
        );

        $this->actingAs($admin)->get('/admin/sales')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/sales/create')->assertSuccessful();
        $this->actingAs($admin)->get("/admin/sales/{$sale->id}")->assertSuccessful();

        $this->actingAs($admin)->get('/admin/payment-collections')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/payment-collections/create')->assertSuccessful();
    }
}
