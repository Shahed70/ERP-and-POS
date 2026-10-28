<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_creation_with_opening_due_creates_initial_ledger(): void
    {
        $uniquePhone = '017' . rand(10000000, 99999999);

        $customer = Customer::create([
            'name' => 'Md. Rafiqul Islam',
            'phone' => $uniquePhone,
            'address' => 'Trishal, Mymensingh',
            'farm_details' => '3 Rui Ponds',
            'credit_limit' => 50000.00,
            'opening_due' => 12500.00,
            'is_active' => true,
        ]);

        $this->assertEquals(12500.00, (float) $customer->current_due);

        // Verify initial ledger entry
        $ledger = CustomerLedger::where('customer_id', $customer->id)
            ->where('transaction_type', 'opening_due')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(12500.00, (float) $ledger->debit_amount);
        $this->assertEquals(0.00, (float) $ledger->credit_amount);
        $this->assertEquals(12500.00, (float) $ledger->running_due_balance);
    }

    public function test_customer_can_collect_due_and_update_ledger(): void
    {
        $uniquePhone = '018' . rand(10000000, 99999999);

        $customer = Customer::create([
            'name' => 'Al-Amin Fishery',
            'phone' => $uniquePhone,
            'opening_due' => 20000.00,
            'is_active' => true,
        ]);

        $this->assertEquals(20000.00, (float) $customer->current_due);

        // Collect 8,000 Tk
        $ledger = $customer->collectDuePayment(
            amountPaid: 8000.00,
            paymentMethod: 'bkash',
            notes: 'bKash TrxID: 9X87KLM',
        );

        $this->assertEquals(12000.00, (float) $customer->current_due);
        $this->assertEquals(8000.00, (float) $ledger->credit_amount);
        $this->assertEquals(12000.00, (float) $ledger->running_due_balance);
        $this->assertEquals('bkash', $ledger->payment_method);
    }

    public function test_customer_resource_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin/customers');
        $response->assertSuccessful();

        $createResponse = $this->actingAs($admin)->get('/admin/customers/create');
        $createResponse->assertSuccessful();

        $walkInCustomer = Customer::where('phone', '01000000000')->first();
        $this->assertNotNull($walkInCustomer);

        $viewResponse = $this->actingAs($admin)->get("/admin/customers/{$walkInCustomer->id}");
        $viewResponse->assertSuccessful();
    }
}
