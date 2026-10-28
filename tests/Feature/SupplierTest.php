<?php

namespace Tests\Feature;

use App\Filament\Resources\SupplierResource\Pages\ViewSupplier;
use App\Filament\Resources\SupplierResource\RelationManagers\ProductsRelationManager;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use DatabaseTransactions;

    protected function createProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Supplier Test Category ' . uniqid(),
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'CP Sinking Finisher ' . uniqid(),
            'brand' => 'CP Bangladesh',
            'feed_type' => 'sinking',
            'unit' => 'KG',
            'pack_size' => 25.00,
            'purchase_price' => 2150.00,
            'sale_price' => 2400.00,
            'is_active' => true,
        ]);
    }

    public function test_supplier_creation_with_opening_payable_creates_initial_ledger(): void
    {
        $supplier = Supplier::create([
            'company_name' => 'Mega Feed Mills Ltd. ' . uniqid(),
            'contact_person' => 'Md. Harun',
            'phone' => '01711223344',
            'address' => 'Valuka, Mymensingh',
            'opening_payable' => 45000.00,
            'is_active' => true,
        ]);

        $this->assertEquals(45000.00, (float) $supplier->current_payable);

        // Verify initial ledger entry
        $ledger = SupplierLedger::where('supplier_id', $supplier->id)
            ->where('transaction_type', 'opening_payable')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(45000.00, (float) $ledger->credit_amount);
        $this->assertEquals(0.00, (float) $ledger->debit_amount);
        $this->assertEquals(45000.00, (float) $ledger->running_payable_balance);
    }

    public function test_supplier_payment_updates_payable_and_creates_debit_ledger(): void
    {
        $supplier = Supplier::create([
            'company_name' => 'Quality Feeds Ltd. ' . uniqid(),
            'phone' => '01811223344',
            'opening_payable' => 30000.00,
            'is_active' => true,
        ]);

        $this->assertEquals(30000.00, (float) $supplier->current_payable);

        // Make payment of 12,000 Tk via bank transfer
        $ledger = $supplier->makePayment(
            amountPaid: 12000.00,
            paymentMethod: 'bank_transfer',
            notes: 'Dutch-Bangla Bank Wire #98231',
        );

        $this->assertEquals(18000.00, (float) $supplier->current_payable);
        $this->assertEquals(12000.00, (float) $ledger->debit_amount);
        $this->assertEquals(0.00, (float) $ledger->credit_amount);
        $this->assertEquals(18000.00, (float) $ledger->running_payable_balance);
        $this->assertEquals('bank_transfer', $ledger->payment_method);
    }

    public function test_supplier_tracks_supplied_products_and_last_purchase_price(): void
    {
        $supplier = Supplier::create([
            'company_name' => 'ACI Godrej Agrovet ' . uniqid(),
            'phone' => '01911223344',
            'is_active' => true,
        ]);

        $product = $this->createProduct();

        // Attach product with last purchase price
        $supplier->products()->attach($product->id, [
            'last_purchase_price' => 2100.00,
            'last_purchased_at' => now(),
        ]);

        $this->assertTrue($supplier->products->contains($product));
        $this->assertEquals(2100.00, (float) $supplier->products->first()->pivot->last_purchase_price);
    }

    public function test_can_attach_product_via_relation_manager(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $supplier = Supplier::create([
            'company_name' => 'CP Bangladesh ' . uniqid(),
            'phone' => '01511223344',
            'is_active' => true,
        ]);
        $product = $this->createProduct();

        Livewire::actingAs($admin)
            ->test(ProductsRelationManager::class, [
                'ownerRecord' => $supplier,
                'pageClass' => ViewSupplier::class,
            ])
            ->callTableAction('attach', data: [
                'recordId' => $product->id,
                'last_purchase_price' => 2200.00,
            ]);

        $this->assertTrue($supplier->fresh()->products->contains($product));
    }

    public function test_supplier_resource_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $this->assertNotNull($admin);

        $supplier = Supplier::create([
            'company_name' => 'Spectra Hexa Feeds ' . uniqid(),
            'phone' => '01611223344',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/suppliers');
        $response->assertSuccessful();

        $createResponse = $this->actingAs($admin)->get('/admin/suppliers/create');
        $createResponse->assertSuccessful();

        $viewResponse = $this->actingAs($admin)->get("/admin/suppliers/{$supplier->id}");
        $viewResponse->assertSuccessful();
    }
}
