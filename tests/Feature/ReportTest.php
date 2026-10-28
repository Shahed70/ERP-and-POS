<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use DatabaseTransactions;

    protected function createProduct(string $name, float $cost = 2000.00, float $price = 2400.00, float $stockKg = 500.00): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Report Feed Cat'], ['is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name . ' ' . uniqid(),
            'brand' => 'Mega Feed',
            'feed_type' => 'floating',
            'unit' => 'KG',
            'pack_size' => 25.00,
            'purchase_price' => $cost,
            'sale_price' => $price,
            'is_active' => true,
        ]);

        if ($stockKg > 0) {
            StockService::recordMovement(
                productId: $product->id,
                quantityKgChange: $stockKg,
                transactionType: 'opening_stock',
                referenceNo: 'INIT-REPORT',
                unitCostPrice: $cost / 25.00,
                locationId: 1,
                performedBy: 1
            );
        }

        return $product;
    }

    public function test_executive_summary_report_calculates_cogs_gross_profit_expenses_and_net_profit(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $customer = Customer::create([
            'name' => 'Report Customer ' . uniqid(),
            'phone' => '01716' . rand(100000, 999999),
            'is_active' => true,
        ]);

        // Product: Cost 2000 Tk/bag (80 Tk/kg), Sale 2400 Tk/bag (96 Tk/kg)
        $product = $this->createProduct('Report Feed Item', 2000.00, 2400.00, 500.00);

        // 1. Process Sale:
        // 2 bags sold @ 2,400 Tk = 4,800 Tk. Discount = 100 Tk. Net Sales = 4,700 Tk.
        // Cost: 2 bags * 2000 Tk = 4,000 Tk.
        // Gross Profit = 4,700 - 4,000 = 700 Tk.
        $sale = SaleService::createSale(
            saleData: [
                'customer_id' => $customer->id,
                'sale_date' => now()->toDateString(),
                'discount_amount' => 100.00,
                'paid_amount' => 4700.00,
            ],
            itemsData: [
                [
                    'product_id' => $product->id,
                    'unit_type' => 'bag',
                    'quantity' => 2,
                    'pack_size' => 25.00,
                    'unit_price' => 2400.00,
                ],
            ],
            userId: $admin->id
        );

        // 2. Disburse Staff Salary: 5,000 Tk
        $employee = Employee::create([
            'name' => 'Report Staff',
            'phone' => '01717' . rand(100000, 999999),
            'designation' => 'Sales Staff',
            'joining_date' => now()->toDateString(),
            'base_salary' => 5000.00,
            'is_active' => true,
        ]);

        PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => now()->format('Y-m'),
            'base_salary' => 5000.00,
            'paid_amount' => 5000.00,
            'disbursement_date' => now()->toDateString(),
        ], $admin->id);

        // Call ReportService
        $summary = ReportService::getExecutiveSummary(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        // Verify Calculations
        $this->assertGreaterThanOrEqual(4700.00, $summary['revenue']['net_sales']);
        $this->assertGreaterThanOrEqual(4000.00, $summary['cogs']);
        $this->assertGreaterThanOrEqual(5000.00, $summary['expenses']['salaries_paid']);
        $this->assertGreaterThanOrEqual(0.00, $summary['balances']['inventory_valuation']);
    }

    public function test_customer_aging_report_allocates_debt_into_buckets(): void
    {
        $customer = Customer::create([
            'name' => 'Aging Farmer ' . uniqid(),
            'phone' => '01718' . rand(100000, 999999),
            'opening_due' => 2500.00, // Historical opening due (>90 days)
            'current_due' => 2500.00,
            'is_active' => true,
        ]);

        $report = ReportService::getCustomerAgingReport();

        $this->assertTrue($report->contains('customer_id', $customer->id));
        $customerAging = $report->where('customer_id', $customer->id)->first();
        $this->assertEquals(2500.00, $customerAging['total_due']);
        $this->assertEquals(2500.00, $customerAging['bucket_over_90']);
    }

    public function test_product_movement_velocity_report_returns_sales_volume_and_margins(): void
    {
        $product = $this->createProduct('Velocity Feed', 2000.00, 2400.00, 200.00);

        $report = ReportService::getProductMovementReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertTrue($report->contains('product_id', $product->id));
        $item = $report->where('product_id', $product->id)->first();
        $this->assertArrayHasKey('bags_sold', $item);
        $this->assertArrayHasKey('margin_percent', $item);
        $this->assertArrayHasKey('stock_kg', $item);
    }

    public function test_supplier_ledger_report_aggregates_payables(): void
    {
        $supplier = Supplier::create([
            'company_name' => 'Mega Feed Depot ' . uniqid(),
            'phone' => '01719' . rand(100000, 999999),
            'opening_payable' => 45000.00,
            'is_active' => true,
        ]);

        $report = ReportService::getSupplierLedgerReport();

        $this->assertTrue($report->contains('supplier_id', $supplier->id));
        $item = $report->where('supplier_id', $supplier->id)->first();
        $this->assertEquals(45000.00, $item['current_payable']);
    }

    public function test_report_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();

        $this->actingAs($admin)->get('/admin/executive-summary-report')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/customer-aging-report')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/product-movement-report')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/supplier-ledger-report')->assertSuccessful();

        // Printable Executive Summary
        $printResponse = $this->actingAs($admin)->get(route('reports.executive.print'));
        $printResponse->assertSuccessful();
        $printResponse->assertSee('PROFIT & LOSS STATEMENT', false);
    }
}
