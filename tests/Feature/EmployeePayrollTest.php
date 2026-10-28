<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\SalaryResource;
use App\Filament\Resources\SalaryResource\Pages\CreateSalary;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\SalaryAdvance;
use App\Models\User;
use App\Services\PayrollService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeePayrollTest extends TestCase
{
    use DatabaseTransactions;

    protected function createEmployee(string $name = 'Abdur Razzaq', float $baseSalary = 15000.00): Employee
    {
        return Employee::create([
            'name' => $name . ' ' . uniqid(),
            'phone' => '01714' . rand(100000, 999999),
            'designation' => 'Store In-Charge / ম্যানেজার',
            'joining_date' => now()->subMonths(6)->toDateString(),
            'base_salary' => $baseSalary,
            'is_active' => true,
        ]);
    }

    public function test_can_disburse_cash_advance_to_employee(): void
    {
        $employee = $this->createEmployee('Loader Jamal', 12000.00);
        $this->assertEquals(0.00, (float) $employee->current_advance_due);

        // Disburse 3,500 Tk cash advance
        $advance = PayrollService::recordAdvance(
            employee: $employee,
            amount: 3500.00,
            reason: 'Medical expense for child',
            paymentMethod: 'cash',
            userId: 1
        );

        $this->assertStringStartsWith('ADV-', $advance->advance_no);
        $this->assertEquals(3500.00, (float) $advance->amount);

        $employee->refresh();
        $this->assertEquals(3500.00, (float) $employee->current_advance_due);
    }

    public function test_monthly_payroll_calculates_net_payable_and_deducts_advance(): void
    {
        $employee = $this->createEmployee('Sales Exec Shakil', 18000.00);

        // Employee takes 4,000 Tk advance
        PayrollService::recordAdvance($employee, 4000.00, 'Festival advance');
        $employee->refresh();
        $this->assertEquals(4000.00, (float) $employee->current_advance_due);

        // Process Salary for month 2026-10:
        // Base: 18,000 Tk
        // Bonus: 2,000 Tk
        // Advance Deduction: 2,500 Tk (leaving 1,500 Tk advance due)
        // Other Deductions: 500 Tk
        // Net Payable = 18,000 + 2,000 - 2,500 - 500 = 17,000 Tk
        // Paid Amount: 17,000 Tk
        // Due Amount: 0.00 Tk
        $salary = PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10',
            'base_salary' => 18000.00,
            'bonus_amount' => 2000.00,
            'advance_deduction' => 2500.00,
            'other_deductions' => 500.00,
            'paid_amount' => 17000.00,
            'payment_method' => 'bank_transfer',
            'disbursement_date' => now()->toDateString(),
        ], 1);

        // 1. Verify Salary Slip Calculations
        $this->assertStringStartsWith('SAL-', $salary->salary_slip_no);
        $this->assertEquals(18000.00, (float) $salary->base_salary);
        $this->assertEquals(2000.00, (float) $salary->bonus_amount);
        $this->assertEquals(2500.00, (float) $salary->advance_deduction);
        $this->assertEquals(500.00, (float) $salary->other_deductions);
        $this->assertEquals(17000.00, (float) $salary->net_payable);
        $this->assertEquals(17000.00, (float) $salary->paid_amount);
        $this->assertEquals(0.00, (float) $salary->due_amount);
        $this->assertEquals('paid', $salary->payment_status);

        // 2. Verify Employee Advance Debt Decreased (4,000 - 2,500 = 1,500)
        $employee->refresh();
        $this->assertEquals(1500.00, (float) $employee->current_advance_due);
    }

    public function test_advance_deduction_cannot_exceed_employee_current_advance_due(): void
    {
        $employee = $this->createEmployee('Van Driver Karim', 14000.00);

        // Employee owes 1,000 Tk advance
        PayrollService::recordAdvance($employee, 1000.00, 'Minor advance');
        $employee->refresh();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Advance deduction .* cannot exceed/i');

        // Attempting to deduct 2,500 Tk when only 1,000 Tk is owed
        PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10',
            'base_salary' => 14000.00,
            'advance_deduction' => 2500.00,
        ], 1);
    }

    public function test_cannot_process_duplicate_salary_for_same_employee_and_month(): void
    {
        $employee = $this->createEmployee('Manager Monir', 20000.00);

        PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10',
            'base_salary' => 20000.00,
            'paid_amount' => 20000.00,
        ], 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/already been generated/i');

        // Second attempt for the same month must be rejected
        PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10',
            'base_salary' => 20000.00,
        ], 1);
    }

    public function test_deleting_salary_restores_deducted_advance_balance(): void
    {
        $employee = $this->createEmployee('Staff Member', 15000.00);
        PayrollService::recordAdvance($employee, 3000.00);

        $salary = PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10',
            'base_salary' => 15000.00,
            'advance_deduction' => 2000.00,
            'paid_amount' => 13000.00,
        ], 1);

        $this->assertEquals(1000.00, (float) $employee->fresh()->current_advance_due);

        // Delete salary slip
        PayrollService::deleteSalary($salary);

        // Advance due should be restored to 3,000 Tk
        $this->assertEquals(3000.00, (float) $employee->fresh()->current_advance_due);
    }

    public function test_employee_creation_via_filament_form(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();

        Livewire::actingAs($admin)
            ->test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Md. Faruk Hossain',
                'phone' => '01715' . rand(100000, 999999),
                'designation' => 'Senior Sales Officer',
                'joining_date' => now()->toDateString(),
                'base_salary' => 16000.00,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EmployeeResource::getUrl('index'));

        $this->assertDatabaseHas('employees', [
            'name' => 'Md. Faruk Hossain',
            'base_salary' => 16000.00,
        ]);
    }

    public function test_employee_and_salary_pages_are_accessible_by_admin(): void
    {
        $admin = User::where('email', 'admin@fishfeed.com')->first();
        $employee = $this->createEmployee();

        $salary = PayrollService::processSalary([
            'employee_id' => $employee->id,
            'salary_month' => '2026-09',
            'base_salary' => 15000.00,
            'paid_amount' => 15000.00,
        ], $admin->id);

        $this->actingAs($admin)->get('/admin/employees')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/employees/create')->assertSuccessful();
        $this->actingAs($admin)->get("/admin/employees/{$employee->id}")->assertSuccessful();

        $this->actingAs($admin)->get('/admin/salaries')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/salaries/create')->assertSuccessful();
        $this->actingAs($admin)->get("/admin/salaries/{$salary->id}")->assertSuccessful();
    }
}
