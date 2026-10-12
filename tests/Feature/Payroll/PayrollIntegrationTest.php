<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\OvertimeSetting;
use App\Models\Payroll;
use App\Models\PayrollSetting;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    // Create payroll settings
    $this->payrollSetting = PayrollSetting::create([
        'company_id' => $this->company->id,
        'cutoff_day' => 1,
        'default_working_days' => 22,
        'auto_deduct_absent' => true,
        'auto_deduct_late' => true,
        'late_penalty_threshold' => 1,
        'late_penalty_amount' => 50000,
        'late_penalty_type' => 'fixed',
        'absent_deduction_type' => 'daily_rate',
        'absent_deduction_amount' => 0,
        'include_overtime_in_payroll' => true,
    ]);

    // Create overtime settings
    $this->overtimeSetting = OvertimeSetting::create([
        'company_id' => $this->company->id,
        'weekday_rate_first_hour' => 1.5,
        'weekday_rate_next_hours' => 2.0,
        'weekend_rate' => 2.0,
        'holiday_rate' => 3.0,
        'working_hours_per_month' => 173,
        'is_active' => true,
    ]);
});

describe('Overtime Integration', function () {
    it('includes overtime in payroll calculation', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        // Create payroll
        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance with overtime
        for ($day = 1; $day <= 22; $day++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => 'present',
                'overtime_minutes' => 60, // 1 hour overtime each day
            ]);
        }

        // Process payroll
        $response = $this->post(route('payrolls.process', $payroll));

        $response->assertRedirect(route('payrolls.show', $payroll));

        $payroll->refresh();
        expect($payroll->status)->toBe('calculated');

        $payrollItem = $payroll->items->first();
        expect($payrollItem)->not->toBeNull();
        expect($payrollItem->overtime_hours)->toBeGreaterThan(0);

        // Check overtime detail exists
        $overtimeDetail = $payrollItem->details->where('component_code', 'OVERTIME')->first();
        expect($overtimeDetail)->not->toBeNull();
        expect($overtimeDetail->type)->toBe('earning');
        expect((float) $overtimeDetail->amount)->toBeGreaterThan(0);
    });
});

describe('Late Penalty Integration', function () {
    it('applies late penalty when threshold exceeded', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance with late arrivals
        for ($day = 1; $day <= 20; $day++) {
            $status = $day <= 5 ? 'late' : 'present';
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => $status,
                'late_minutes' => $day <= 5 ? 30 : 0,
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        expect($payrollItem->late_days)->toBe(5);

        // Check late penalty detail
        $latePenalty = $payrollItem->details->where('component_code', 'LATE-PEN')->first();
        expect($latePenalty)->not->toBeNull();
        expect($latePenalty->type)->toBe('deduction');
        expect((float) $latePenalty->amount)->toBe(250000.0); // 5 days × 50000
    });
});

describe('Absent Deduction Integration', function () {
    it('applies absent deduction based on daily rate', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 11000000, // 11 juta / 22 = 500k per day
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance with 2 absents
        for ($day = 1; $day <= 20; $day++) {
            $status = $day <= 2 ? 'absent' : 'present';
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => $status,
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        expect($payrollItem->absent_days)->toBe(2);

        // Check absent deduction detail
        $absentDeduction = $payrollItem->details->where('component_code', 'ABSENT-DED')->first();
        expect($absentDeduction)->not->toBeNull();
        expect($absentDeduction->type)->toBe('deduction');
        // Daily rate = 11000000 / working_days * 2 days
        // February 2026 has 20 weekdays, so daily rate = 11000000/20 = 550000, 2 days = 1100000
        expect((float) $absentDeduction->amount)->toBe(1100000.0);
    });
});

describe('Reimbursement Integration', function () {
    it('includes approved reimbursements in payroll', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'is_active' => true,
        ]);

        // Create approved reimbursements
        // Reimbursement from last month but approved this month
        $reimbursement1 = Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'description' => 'Taxi to client',
            'expense_date' => '2026-01-20', // Expense from last month
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => '2026-02-05', // Approved this month
        ]);

        // Reimbursement from this month
        $reimbursement2 = Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 300000,
            'description' => 'Parking fees',
            'expense_date' => '2026-02-20',
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => '2026-02-21',
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance
        for ($day = 1; $day <= 22; $day++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => 'present',
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        // Check reimbursement detail
        $reimburseDetail = $payrollItem->details->where('component_code', 'REIMBURSE')->first();
        expect($reimburseDetail)->not->toBeNull();
        expect($reimburseDetail->type)->toBe('earning');
        expect((float) $reimburseDetail->amount)->toBe(800000.0); // 500k + 300k

        // Check reimbursements are marked as paid
        $reimbursement1->refresh();
        $reimbursement2->refresh();

        expect($reimbursement1->status)->toBe(Reimbursement::STATUS_PAID);
        expect($reimbursement1->payroll_item_id)->toBe($payrollItem->id);
        expect($reimbursement2->status)->toBe(Reimbursement::STATUS_PAID);
        expect($reimbursement2->payroll_item_id)->toBe($payrollItem->id);
    });

    it('does not include already paid reimbursements', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'is_active' => true,
        ]);

        // Create already paid reimbursement
        Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'description' => 'Old expense',
            'expense_date' => '2026-02-10',
            'status' => Reimbursement::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => 'cash',
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance
        for ($day = 1; $day <= 22; $day++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => 'present',
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        // Check no reimbursement detail
        $reimburseDetail = $payrollItem->details->where('component_code', 'REIMBURSE')->first();
        expect($reimburseDetail)->toBeNull();
    });

    it('does not include reimbursements approved after payroll period', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'is_active' => true,
        ]);

        // Create reimbursement approved AFTER payroll period ends
        Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'description' => 'Future approved expense',
            'expense_date' => '2026-02-20',
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => '2026-03-05', // Approved in March, after Feb period
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance
        for ($day = 1; $day <= 22; $day++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => 'present',
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        // Should NOT include reimbursement approved after period
        $reimburseDetail = $payrollItem->details->where('component_code', 'REIMBURSE')->first();
        expect($reimburseDetail)->toBeNull();
    });

    it('includes reimbursements from previous months if still approved and unpaid', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'is_active' => true,
        ]);

        // Create old reimbursement from January, approved in January
        Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 750000,
            'description' => 'Old expense from January',
            'expense_date' => '2026-01-15',
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => '2026-01-20', // Approved in January
        ]);

        // February payroll
        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance
        for ($day = 1; $day <= 22; $day++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => 'present',
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        // Should include old reimbursement that was never paid
        $reimburseDetail = $payrollItem->details->where('component_code', 'REIMBURSE')->first();
        expect($reimburseDetail)->not->toBeNull();
        expect((float) $reimburseDetail->amount)->toBe(750000.0);
    });

    it('does not include reimbursements when toggle is disabled', function () {
        // Disable reimbursement in payroll settings
        PayrollSetting::updateOrCreate(
            ['company_id' => $this->company->id],
            ['include_reimbursement_in_payroll' => false]
        );

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'is_active' => true,
        ]);

        // Create approved reimbursement
        Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'description' => 'Taxi expense',
            'expense_date' => '2026-02-10',
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => '2026-02-15',
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Test Payroll',
            'period_month' => 2,
            'period_year' => 2026,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance
        for ($day = 1; $day <= 22; $day++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::create(2026, 2, $day)->format('Y-m-d'),
                'status' => 'present',
            ]);
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items->first();

        // Should NOT include reimbursement when toggle is disabled
        $reimburseDetail = $payrollItem->details->where('component_code', 'REIMBURSE')->first();
        expect($reimburseDetail)->toBeNull();

        // And the reimbursement should remain unpaid (payroll_item_id still null)
        $reimbursement = Reimbursement::where('employee_id', $employee->id)->first();
        expect($reimbursement->payroll_item_id)->toBeNull();
    });
});
