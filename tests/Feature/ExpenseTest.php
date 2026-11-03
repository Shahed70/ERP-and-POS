<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Viewer']);
    }

    public function test_expense_auto_generates_expense_number_and_persists(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_test@fishfeed.com'],
            ['name' => 'Admin Tester', 'password' => bcrypt('password')]
        );
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $expense = Expense::create([
            'category' => Expense::CATEGORY_SHOP_RENT,
            'title' => 'November Shop Rent Test',
            'amount' => 25000.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank',
            'paid_to' => 'Landlord',
        ]);

        $this->assertNotEmpty($expense->expense_number);
        $this->assertStringStartsWith('EXP-', $expense->expense_number);
        $this->assertEquals(25000.00, $expense->amount);
        $this->assertEquals(Expense::CATEGORY_SHOP_RENT, $expense->category);
        $this->assertEquals($admin->id, $expense->created_by);
    }

    public function test_operating_expenses_include_shop_rent_electricity_warehouse_snack_and_others_in_executive_summary(): void
    {
        $testDate = '2026-11-03';

        Expense::create([
            'category' => Expense::CATEGORY_SHOP_RENT,
            'title' => 'Shop Rent Expense Test',
            'amount' => 10000.00,
            'expense_date' => $testDate,
        ]);

        Expense::create([
            'category' => Expense::CATEGORY_ELECTRICITY_BILL,
            'title' => 'Electricity Bill Test',
            'amount' => 3000.00,
            'expense_date' => $testDate,
        ]);

        Expense::create([
            'category' => Expense::CATEGORY_WAREHOUSE_RENT,
            'title' => 'Warehouse Rent Test',
            'amount' => 7000.00,
            'expense_date' => $testDate,
        ]);

        Expense::create([
            'category' => Expense::CATEGORY_SNACK_EXPENSES,
            'title' => 'Snack Expenses Test',
            'amount' => 1500.00,
            'expense_date' => $testDate,
        ]);

        Expense::create([
            'category' => Expense::CATEGORY_OTHERS,
            'title' => 'Other Office Expenses Test',
            'amount' => 500.00,
            'expense_date' => $testDate,
        ]);

        $summary = ReportService::getExecutiveSummary($testDate, $testDate);

        $expenses = $summary['expenses'];
        $this->assertArrayHasKey('shop_rent', $expenses);
        $this->assertArrayHasKey('electricity_bill', $expenses);
        $this->assertArrayHasKey('warehouse_rent', $expenses);
        $this->assertArrayHasKey('snack_expenses', $expenses);
        $this->assertArrayHasKey('other_expenses', $expenses);
        $this->assertArrayHasKey('general_expenses_total', $expenses);

        $this->assertGreaterThanOrEqual(10000.00, $expenses['shop_rent']);
        $this->assertGreaterThanOrEqual(3000.00, $expenses['electricity_bill']);
        $this->assertGreaterThanOrEqual(7000.00, $expenses['warehouse_rent']);
        $this->assertGreaterThanOrEqual(1500.00, $expenses['snack_expenses']);
        $this->assertGreaterThanOrEqual(500.00, $expenses['other_expenses']);
        $this->assertGreaterThanOrEqual(22000.00, $expenses['general_expenses_total']);
        $this->assertGreaterThanOrEqual(22000.00, $expenses['total_expenses']);
    }

    public function test_admin_can_access_expense_list_and_create_pages(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_exp@fishfeed.com'],
            ['name' => 'Admin Expense', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/admin/expenses');
        $response->assertStatus(200);

        $createResponse = $this->actingAs($admin)->get('/admin/expenses/create');
        $createResponse->assertStatus(200);
    }

    public function test_viewer_has_read_only_access_to_expenses(): void
    {
        $viewer = User::firstOrCreate(
            ['email' => 'viewer_exp@fishfeed.com'],
            ['name' => 'Viewer Expense', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $viewer->assignRole('Viewer');

        // Viewer can view the index list
        $response = $this->actingAs($viewer)->get('/admin/expenses');
        $response->assertStatus(200);

        // Viewer is forbidden from create page
        $createResponse = $this->actingAs($viewer)->get('/admin/expenses/create');
        $createResponse->assertStatus(403);
    }

    public function test_executive_summary_report_page_renders_operating_expenses_breakdown(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_pnl@fishfeed.com'],
            ['name' => 'Admin Pnl', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/admin/executive-summary-report');
        $response->assertStatus(200);
        $response->assertSee('Operating Expenses');
        $response->assertSee('Shop Rent (দোকান ভাড়া)');
        $response->assertSee('Electricity Bill (বিদ্যুৎ বিল)');
        $response->assertSee('Warehouse Rent (গোডাউন ভাড়া)');
        $response->assertSee('Snack Expenses (আপ্যায়ন / নাস্তা খরচ)');
        $response->assertSee('Other Operating Expenses (অন্যান্য খরচ)');
    }
}
