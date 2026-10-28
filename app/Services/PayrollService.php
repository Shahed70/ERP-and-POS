<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\SalaryAdvance;
use Exception;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Record a cash salary advance given to an employee.
     *
     * @param Employee $employee
     * @param float $amount
     * @param string|null $reason
     * @param string $paymentMethod
     * @param int|null $userId
     * @return SalaryAdvance
     * @throws Exception
     */
    public static function recordAdvance(
        Employee $employee,
        float $amount,
        ?string $reason = null,
        string $paymentMethod = 'cash',
        ?int $userId = null
    ): SalaryAdvance {
        if ($amount <= 0) {
            throw new Exception(__('Advance amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($employee, $amount, $reason, $paymentMethod, $userId) {
            $lockedEmployee = Employee::where('id', $employee->id)->lockForUpdate()->firstOrFail();
            $operatorId = $userId ?? (auth()->id() ?? 1);

            $advance = SalaryAdvance::create([
                'advance_no' => SalaryAdvance::generateAdvanceNo(),
                'employee_id' => $lockedEmployee->id,
                'advance_date' => now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reason' => $reason,
                'approved_by' => $operatorId,
            ]);

            $lockedEmployee->current_advance_due = round((float) $lockedEmployee->current_advance_due + $amount, 2);
            $lockedEmployee->save();

            return $advance;
        });
    }

    /**
     * Delete an advance disbursement and safely revert employee's advance due balance.
     *
     * @param SalaryAdvance $advance
     * @return void
     */
    public static function deleteAdvance(SalaryAdvance $advance): void
    {
        DB::transaction(function () use ($advance) {
            $employee = Employee::where('id', $advance->employee_id)->lockForUpdate()->firstOrFail();
            $employee->current_advance_due = max(0.00, round((float) $employee->current_advance_due - (float) $advance->amount, 2));
            $employee->save();

            $advance->delete();
        });
    }

    /**
     * Atomically calculate and disburse a monthly employee salary.
     *
     * @param array $salaryData
     * @param int|null $userId
     * @return Salary
     * @throws Exception
     */
    public static function processSalary(array $salaryData, ?int $userId = null): Salary
    {
        return DB::transaction(function () use ($salaryData, $userId) {
            $operatorId = $userId ?? (auth()->id() ?? 1);
            $employee = Employee::where('id', $salaryData['employee_id'])->lockForUpdate()->firstOrFail();

            $month = $salaryData['salary_month']; // YYYY-MM
            if (Salary::where('employee_id', $employee->id)->where('salary_month', $month)->exists()) {
                throw new Exception(__("Salary for employee ':name' for month :month has already been generated.", [
                    'name' => $employee->name,
                    'month' => $month,
                ]));
            }

            $baseSalary = (float) ($salaryData['base_salary'] ?? $employee->base_salary);
            $bonus = (float) ($salaryData['bonus_amount'] ?? 0.00);
            $advanceDeduction = (float) ($salaryData['advance_deduction'] ?? 0.00);
            $otherDeductions = (float) ($salaryData['other_deductions'] ?? 0.00);

            // Validate that advance deduction does not exceed what the employee actually owes
            $currentAdvanceDue = (float) $employee->current_advance_due;
            if ($advanceDeduction > $currentAdvanceDue) {
                throw new Exception(
                    __("Advance deduction (৳:deduction) cannot exceed employee's current unpaid advance balance (৳:due).", [
                        'deduction' => number_format($advanceDeduction, 2),
                        'due' => number_format($currentAdvanceDue, 2),
                    ])
                );
            }

            $netPayable = max(0.00, round($baseSalary + $bonus - $advanceDeduction - $otherDeductions, 2));
            $paidAmount = min($netPayable, max(0.00, (float) ($salaryData['paid_amount'] ?? $netPayable)));
            $dueAmount = max(0.00, round($netPayable - $paidAmount, 2));

            $paymentStatus = 'pending';
            if ($paidAmount >= $netPayable && $netPayable > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            $salary = Salary::create([
                'salary_slip_no' => Salary::generateSlipNo(),
                'employee_id' => $employee->id,
                'salary_month' => $month,
                'base_salary' => $baseSalary,
                'bonus_amount' => $bonus,
                'advance_deduction' => $advanceDeduction,
                'other_deductions' => $otherDeductions,
                'net_payable' => $netPayable,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'disbursement_date' => $salaryData['disbursement_date'] ?? now()->toDateString(),
                'payment_method' => $salaryData['payment_method'] ?? 'cash',
                'payment_status' => $paymentStatus,
                'notes' => $salaryData['notes'] ?? null,
                'processed_by' => $operatorId,
            ]);

            // Deduct advance from employee's running advance debt
            if ($advanceDeduction > 0) {
                $employee->current_advance_due = max(0.00, round($currentAdvanceDue - $advanceDeduction, 2));
                $employee->save();
            }

            return $salary;
        });
    }

    /**
     * Delete a salary slip and safely restore deducted advance back to employee.
     *
     * @param Salary $salary
     * @return void
     */
    public static function deleteSalary(Salary $salary): void
    {
        DB::transaction(function () use ($salary) {
            $employee = Employee::where('id', $salary->employee_id)->lockForUpdate()->firstOrFail();

            if ((float) $salary->advance_deduction > 0) {
                $employee->current_advance_due = round((float) $employee->current_advance_due + (float) $salary->advance_deduction, 2);
                $employee->save();
            }

            $salary->delete();
        });
    }
}
