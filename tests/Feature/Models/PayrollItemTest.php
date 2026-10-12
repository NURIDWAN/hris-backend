<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollItemDetail;
use App\Models\SalaryComponent;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $this->employeeSalary = EmployeeSalary::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
    ]);
    $this->payroll = Payroll::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->user->id,
    ]);
});

describe('PayrollItem Model', function () {
    test('can create payroll item', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        expect($payrollItem)->toBeInstanceOf(PayrollItem::class);
        expect($payrollItem->payroll_id)->toBe($this->payroll->id);
    });

    test('belongs to payroll', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        expect($payrollItem->payroll)->toBeInstanceOf(Payroll::class);
        expect($payrollItem->payroll->id)->toBe($this->payroll->id);
    });

    test('belongs to employee', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        expect($payrollItem->employee)->toBeInstanceOf(Employee::class);
        expect($payrollItem->employee->id)->toBe($this->employee->id);
    });

    test('has many details', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        $payrollItem->addDetail(null, 'Tunjangan Makan', 'TM', 'earning', 'allowance', 500000);
        $payrollItem->addDetail(null, 'BPJS', 'BPJS', 'deduction', 'insurance', 200000);

        expect($payrollItem->details)->toHaveCount(2);
    });
});

describe('PayrollItem Scopes', function () {
    test('pending scope filters pending items', function () {
        // Create separate employees for each payroll item
        $employee1 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee3 = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary1 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee1->id]);
        $salary2 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee2->id]);
        $salary3 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee3->id]);

        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee1->id,
            'employee_salary_id' => $salary1->id,
            'status' => 'pending',
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee2->id,
            'employee_salary_id' => $salary2->id,
            'status' => 'pending',
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee3->id,
            'employee_salary_id' => $salary3->id,
            'status' => 'calculated',
        ]);

        $pending = PayrollItem::where('payroll_id', $this->payroll->id)->pending()->get();

        expect($pending)->toHaveCount(2);
    });

    test('calculated scope filters calculated items', function () {
        $employee1 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee3 = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary1 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee1->id]);
        $salary2 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee2->id]);
        $salary3 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee3->id]);

        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee1->id,
            'employee_salary_id' => $salary1->id,
            'status' => 'pending',
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee2->id,
            'employee_salary_id' => $salary2->id,
            'status' => 'calculated',
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee3->id,
            'employee_salary_id' => $salary3->id,
            'status' => 'calculated',
        ]);

        $calculated = PayrollItem::where('payroll_id', $this->payroll->id)->calculated()->get();

        expect($calculated)->toHaveCount(2);
    });

    test('paid scope filters paid items', function () {
        $employee1 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee3 = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary1 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee1->id]);
        $salary2 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee2->id]);
        $salary3 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee3->id]);

        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee1->id,
            'employee_salary_id' => $salary1->id,
            'status' => 'calculated',
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee2->id,
            'employee_salary_id' => $salary2->id,
            'status' => 'paid',
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee3->id,
            'employee_salary_id' => $salary3->id,
            'status' => 'paid',
        ]);

        $paid = PayrollItem::where('payroll_id', $this->payroll->id)->paid()->get();

        expect($paid)->toHaveCount(2);
    });
});

describe('PayrollItem Status Checks', function () {
    test('isPending returns true for pending status', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'status' => 'pending',
        ]);

        expect($payrollItem->isPending())->toBeTrue();
        expect($payrollItem->isCalculated())->toBeFalse();
    });

    test('isCalculated returns true for calculated status', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'status' => 'calculated',
        ]);

        expect($payrollItem->isCalculated())->toBeTrue();
    });

    test('isApproved returns true for approved status', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'status' => 'approved',
        ]);

        expect($payrollItem->isApproved())->toBeTrue();
    });

    test('isPaid returns true for paid status', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'status' => 'paid',
        ]);

        expect($payrollItem->isPaid())->toBeTrue();
    });
});

describe('PayrollItem Helpers', function () {
    test('addDetail creates detail record', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        $detail = $payrollItem->addDetail(
            null,
            'Tunjangan Transport',
            'TT',
            'earning',
            'allowance',
            500000,
            true,
            'Test notes'
        );

        expect($detail)->toBeInstanceOf(PayrollItemDetail::class);
        expect($detail->component_name)->toBe('Tunjangan Transport');
        expect($detail->component_code)->toBe('TT');
        expect($detail->type)->toBe('earning');
        expect($detail->amount)->toBe('500000.00');
    });

    test('earnings returns only earning details', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        $payrollItem->addDetail(null, 'Tunjangan Makan', 'TM', 'earning', 'allowance', 500000);
        $payrollItem->addDetail(null, 'BPJS', 'BPJS', 'deduction', 'insurance', 200000);

        $earnings = $payrollItem->earnings;

        expect($earnings)->toHaveCount(1);
        expect($earnings->first()->component_name)->toBe('Tunjangan Makan');
    });

    test('deductions returns only deduction details', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        $payrollItem->addDetail(null, 'Tunjangan Makan', 'TM', 'earning', 'allowance', 500000);
        $payrollItem->addDetail(null, 'BPJS', 'BPJS', 'deduction', 'insurance', 200000);

        $deductions = $payrollItem->deductions;

        expect($deductions)->toHaveCount(1);
        expect($deductions->first()->component_name)->toBe('BPJS');
    });

    test('recalculate updates totals from details', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'basic_salary' => 5000000,
            'total_earnings' => 0,
            'total_deductions' => 0,
            'gross_salary' => 5000000,
            'net_salary' => 5000000,
            'status' => 'pending',
        ]);

        $payrollItem->addDetail(null, 'Tunjangan Makan', 'TM', 'earning', 'allowance', 500000);
        $payrollItem->addDetail(null, 'Tunjangan Transport', 'TT', 'earning', 'allowance', 300000);
        $payrollItem->addDetail(null, 'BPJS', 'BPJS', 'deduction', 'insurance', 200000);

        $payrollItem->recalculate();

        expect($payrollItem->total_earnings)->toBe('800000.00');
        expect($payrollItem->total_deductions)->toBe('200000.00');
        expect($payrollItem->gross_salary)->toBe('5800000.00');
        expect($payrollItem->net_salary)->toBe('5600000.00');
        expect($payrollItem->status)->toBe('calculated');
    });
});

describe('PayrollItem Accessors', function () {
    test('status_label returns Indonesian label', function () {
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary2 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee2->id]);

        $pending = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'status' => 'pending',
        ]);
        $paid = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee2->id,
            'employee_salary_id' => $salary2->id,
            'status' => 'paid',
        ]);

        expect($pending->status_label)->toBe('Menunggu');
        expect($paid->status_label)->toBe('Dibayar');
    });

    test('formatted_net_salary returns formatted currency', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'net_salary' => 5000000,
        ]);

        expect($payrollItem->formatted_net_salary)->toBe('Rp 5.000.000');
    });

    test('attendance_summary returns summary string', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'working_days' => 22,
            'present_days' => 20,
        ]);

        expect($payrollItem->attendance_summary)->toBe('Hadir: 20/22 hari');
    });

    test('payment_info returns payment details', function () {
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary2 = EmployeeSalary::factory()->create(['company_id' => $this->company->id, 'employee_id' => $employee2->id]);

        $transfer = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
            'payment_method' => 'transfer',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
        ]);
        $cash = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $employee2->id,
            'employee_salary_id' => $salary2->id,
            'payment_method' => 'cash',
        ]);

        expect($transfer->payment_info)->toBe('BCA - 1234567890');
        expect($cash->payment_info)->toBe('Tunai');
    });
});

describe('PayrollItem Soft Deletes', function () {
    test('can soft delete payroll item', function () {
        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $this->employee->id,
            'employee_salary_id' => $this->employeeSalary->id,
        ]);

        $payrollItem->delete();

        $this->assertSoftDeleted('payroll_items', ['id' => $payrollItem->id]);
        expect(PayrollItem::withTrashed()->find($payrollItem->id))->not->toBeNull();
    });
});
