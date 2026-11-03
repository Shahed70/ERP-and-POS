<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\PaymentCollection;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_offline@fishfeed.com'],
            ['name' => 'Offline Admin', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $this->admin->assignRole('Admin');

        $category = ProductCategory::firstOrCreate(['name' => 'Offline Test Feed Cat'], ['is_active' => true]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Offline Floating Feed ' . uniqid(),
            'brand' => 'Mega Feed',
            'feed_type' => 'floating',
            'unit' => 'KG',
            'pack_size' => 25.00,
            'purchase_price' => 1800.00,
            'sale_price' => 2200.00,
            'is_active' => true,
        ]);

        StockService::recordMovement(
            productId: $this->product->id,
            quantityKgChange: 500.00, // 20 bags
            transactionType: 'opening_stock',
            referenceNo: 'INIT-OFFLINE',
            unitCostPrice: 72.00,
            locationId: 1,
            performedBy: $this->admin->id
        );

        $this->customer = Customer::create([
            'name' => 'Offline Test Farmer ' . uniqid(),
            'phone' => '01719' . rand(100000, 999999),
            'opening_due' => 5000.00,
            'current_due' => 5000.00,
            'credit_limit' => 50000.00,
            'is_active' => true,
        ]);
    }

    public function test_offline_sync_data_endpoint_returns_catalog_snapshot(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/offline-sync/data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'products',
                'customers',
                'expense_categories',
                'payment_methods',
                'shop' => ['name', 'phone'],
            ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('products'));
        $this->assertNotEmpty($response->json('customers'));
    }

    public function test_offline_sync_ping_returns_online_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/offline-sync/ping');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'online',
            ]);
    }

    public function test_offline_pos_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/offline-pos');

        $response->assertStatus(200);
        $response->assertSee('Offline POS & Automatic Sync Manager');
    }

    public function test_offline_sync_processes_batch_of_sales_collections_and_expenses(): void
    {
        $payload = [
            'transactions' => [
                // 1. Offline Sale
                [
                    'offline_id' => 'off_sale_test_101',
                    'type' => 'sale',
                    'data' => [
                        'sale_data' => [
                            'customer_id' => $this->customer->id,
                            'sale_date' => now()->toDateString(),
                            'discount_amount' => 100.00,
                            'paid_amount' => 4300.00,
                            'payment_method' => 'cash',
                        ],
                        'items_data' => [
                            [
                                'product_id' => $this->product->id,
                                'unit_type' => 'bag',
                                'quantity' => 2,
                                'pack_size' => 25.00,
                                'unit_price' => 2200.00,
                            ],
                        ],
                        'offline_invoice_no' => 'OFF-20261103-8899',
                        'allow_credit_override' => true,
                    ],
                ],

                // 2. Offline Due Collection
                [
                    'offline_id' => 'off_coll_test_102',
                    'type' => 'collection',
                    'data' => [
                        'customer_id' => $this->customer->id,
                        'amount_paid' => 2000.00,
                        'payment_method' => 'bkash',
                        'transaction_reference' => 'BKASH-OFF-991',
                        'notes' => 'Offline collected due at counter',
                    ],
                ],

                // 3. Offline Operating Expense
                [
                    'offline_id' => 'off_exp_test_103',
                    'type' => 'expense',
                    'data' => [
                        'category' => Expense::CATEGORY_SNACK_EXPENSES,
                        'title' => 'Offline Counter Tea & Biscuits',
                        'amount' => 250.00,
                        'payment_method' => 'cash',
                        'paid_to' => 'Local Tea Stall',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/offline-sync/sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'synced_count' => 3,
            ]);

        // Verify Sale created in MySQL
        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'discount_amount' => 100.00,
            'paid_amount' => 4300.00,
        ]);

        // Verify Collection created in MySQL
        $this->assertDatabaseHas('payment_collections', [
            'customer_id' => $this->customer->id,
            'amount_paid' => 2000.00,
            'payment_method' => 'bkash',
        ]);

        // Verify Expense created in MySQL
        $this->assertDatabaseHas('expenses', [
            'category' => Expense::CATEGORY_SNACK_EXPENSES,
            'title' => 'Offline Counter Tea & Biscuits',
            'amount' => 250.00,
        ]);
    }

    public function test_offline_sync_handles_item_errors_without_crashing_entire_batch(): void
    {
        $payload = [
            'transactions' => [
                // Good item
                [
                    'offline_id' => 'off_exp_valid',
                    'type' => 'expense',
                    'data' => [
                        'category' => Expense::CATEGORY_OTHERS,
                        'title' => 'Valid Offline Expense',
                        'amount' => 120.00,
                        'payment_method' => 'cash',
                    ],
                ],
                // Bad item with invalid customer ID
                [
                    'offline_id' => 'off_coll_invalid',
                    'type' => 'collection',
                    'data' => [
                        'customer_id' => 9999999, // non-existent customer
                        'amount_paid' => 500.00,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/offline-sync/sync', $payload);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('synced_count'));
        $this->assertCount(1, $response->json('errors'));
        $this->assertEquals('off_coll_invalid', $response->json('errors.0.offline_id'));

        $this->assertDatabaseHas('expenses', [
            'title' => 'Valid Offline Expense',
            'amount' => 120.00,
        ]);
    }
}
