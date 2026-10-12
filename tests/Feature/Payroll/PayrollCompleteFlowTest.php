<?php

/**
 * Comprehensive Payroll Flow Test for March 2026
 *
 * Tests the complete payroll calculation including:
 * - PPh 21 (TER method and Progressive method)
 * - BPJS Kesehatan
 * - BPJS Ketenagakerjaan (JHT, JP, JKK, JKM)
 * - Overtime calculation
 * - Late penalty
 * - Absent deduction
 * - Reimbursement
 * - Attendance-based salary components
 */

use App\Models\Attendance;
use App\Models\BpjsKesSetting;
use App\Models\BpjsTkSetting;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\OvertimeSetting;
use App\Models\Payroll;
use App\Models\PayrollSetting;
use App\Models\Pph21Setting;
use App\Models\Pph21TerRate;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\SalaryComponent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();

    // Create standard roles with proper team context
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    // Setup PayrollSetting for March 2026
    PayrollSetting::create([
        'company_id' => $this->company->id,
        'cycle_type' => 'monthly',
        'cutoff_day' => 1,
        'default_working_days' => 22,
        'auto_deduct_absent' => true,
        'auto_deduct_late' => true,
        'late_penalty_threshold' => 3,
        'late_penalty_amount' => 50000,
        'late_penalty_type' => 'fixed',
        'absent_deduction_type' => 'daily_rate',
        'absent_deduction_amount' => 0,
        'prorate_new_employees' => true,
        'prorate_resigned_employees' => true,
        'include_overtime_in_payroll' => true,
        'require_overtime_approval' => false,
        'include_reimbursement_in_payroll' => true,
        'payment_day' => 1,
        'is_active' => true,
    ]);

    // Setup OvertimeSetting
    OvertimeSetting::create([
        'company_id' => $this->company->id,
        'weekday_rate_multiplier' => 1.5,
        'weekend_rate_multiplier' => 2.0,
        'holiday_rate_multiplier' => 3.0,
        'max_daily_hours' => 4,
        'max_monthly_hours' => 40,
        'minimum_minutes' => 30,
        'round_to_minutes' => 30,
        'require_approval' => false,
        'is_active' => true,
    ]);
});

describe('PPh 21 Calculation Cases', function () {
    it('calculates PPh21 using TER method for Category A (TK/0)', function () {
        // Setup TER rates
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);

        // TER rate for Category A, income 10-15 million: 2.5%
        Pph21TerRate::create([
            'company_id' => $this->company->id,
            'category' => 'A',
            'ptkp_status' => 'TK/0',
            'min_income' => 10000000,
            'max_income' => 15000000,
            'rate' => 2.5,
            'year' => 2026,
            'is_active' => true,
        ]);

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jkk_rate' => 0.24,
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'TK/0',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 12000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance for weekdays in March 2026
        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);
        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'present',
                ]);
            }
            $date->addDay();
        }

        $response = $this->post(route('payrolls.process', $payroll));
        $response->assertRedirect();

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created. Payroll status: '.$payroll->status);
        // PPh21 with TER: 12,000,000 * 2.5% = 300,000
        expect((float) $payrollItem->tax_amount)->toBe(300000.0);

        // Check BPJS Kes employee portion: 12M * 1% = 120,000
        expect((float) $payrollItem->details->where('component_code', 'BPJS-KES')->first()?->amount ?? 0)->toBe(120000.0);
    });

    it('calculates PPh21 using TER method for Category B (K/1)', function () {
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);

        // TER rate for Category B, income 10-15 million: 1.5%
        Pph21TerRate::create([
            'company_id' => $this->company->id,
            'category' => 'B',
            'ptkp_status' => 'K/1',
            'min_income' => 10000000,
            'max_income' => 15000000,
            'rate' => 1.5,
            'year' => 2026,
            'is_active' => true,
        ]);

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'K/1',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 12000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);
        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'present',
                ]);
            }
            $date->addDay();
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created');
        // PPh21 with TER Category B: 12,000,000 * 1.5% = 180,000
        expect((float) $payrollItem->tax_amount)->toBe(180000.0);
    });

    it('applies 20% penalty for employee without NPWP', function () {
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);

        Pph21TerRate::create([
            'company_id' => $this->company->id,
            'category' => 'A',
            'ptkp_status' => 'TK/0',
            'min_income' => 10000000,
            'max_income' => 15000000,
            'rate' => 2.5,
            'year' => 2026,
            'is_active' => true,
        ]);

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_employee_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'TK/0',
            'npwp' => null, // No NPWP
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 12000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);
        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'present',
                ]);
            }
            $date->addDay();
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created');
        // PPh21 with TER + 20% penalty: 12,000,000 * 2.5% * 1.2 = 360,000
        expect((float) $payrollItem->tax_amount)->toBe(360000.0);
    });

    it('uses progressive calculation when TER rate not found', function () {
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true, // TER enabled but no rates configured
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_employee_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'TK/0',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);
        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'present',
                ]);
            }
            $date->addDay();
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created');
        // Progressive calculation:
        // PTKP TK/0 monthly: 54,000,000 / 12 = 4,500,000
        // PKP monthly: 10,000,000 - 4,500,000 = 5,500,000
        // PKP annual: 5,500,000 * 12 = 66,000,000
        // Tax: (60,000,000 * 5%) + (6,000,000 * 15%) = 3,000,000 + 900,000 = 3,900,000 annual
        // Monthly: 3,900,000 / 12 = 325,000
        expect((float) $payrollItem->tax_amount)->toBe(325000.0);
    });
});

describe('BPJS Calculations', function () {
    beforeEach(function () {
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => false,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);
    });

    it('calculates BPJS Kesehatan with salary cap', function () {
        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_employee_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'TK/0',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true,
        ]);

        // Salary 20 million (above BPJS Kes cap of 12 million)
        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 20000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);
        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'present',
                ]);
            }
            $date->addDay();
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created');
        // BPJS Kes capped at 12M: 12,000,000 * 1% = 120,000
        $bpjsKes = $payrollItem->details->where('component_code', 'BPJS-KES')->first();
        expect((float) $bpjsKes?->amount ?? 0)->toBe(120000.0);
    });

    it('calculates BPJS JP with salary cap', function () {
        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'max_salary_basis' => 12000000,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300, // JP cap
            'jp_enabled' => true,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'TK/0',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true,
        ]);

        // Salary 15 million (above JP cap of 10,042,300)
        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 15000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);
        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'present',
                ]);
            }
            $date->addDay();
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created');

        // JHT: 15M * 2% = 300,000 (no cap)
        $bpjsJht = $payrollItem->details->where('component_code', 'BPJS-JHT')->first();
        expect((float) $bpjsJht?->amount ?? 0)->toBe(300000.0);

        // JP capped at 10,042,300: 10,042,300 * 1% = 100,423
        $bpjsJp = $payrollItem->details->where('component_code', 'BPJS-JP')->first();
        expect((float) $bpjsJp?->amount ?? 0)->toBe(100423.0);
    });
});

describe('Complete March 2026 Payroll with All Components', function () {
    it('calculates complete payroll with overtime, penalty, reimbursement', function () {
        // Setup all BPJS and tax settings
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);

        Pph21TerRate::create([
            'company_id' => $this->company->id,
            'category' => 'A',
            'ptkp_status' => 'TK/0',
            'min_income' => 10000000,
            'max_income' => 20000000,
            'rate' => 3.0,
            'year' => 2026,
            'is_active' => true,
        ]);

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jkk_rate' => 0.24,
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        // Create attendance-based meal allowance
        SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Tunjangan Makan',
            'code' => 'MEAL',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 50000,
            'is_taxable' => true,
            'is_active' => true,
            'is_mandatory' => true,
            'is_attendance_based' => true,
            'attendance_calculation' => 'daily',
            'daily_rate' => 50000,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'TK/0',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        // Create reimbursement
        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'is_active' => true,
        ]);

        Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'description' => 'Client visit transport',
            'expense_date' => '2026-03-10',
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => '2026-03-15',
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Create attendance using factory states
        $dayCount = 0;
        $date = Carbon::create(2026, 3, 1);
        $endDate = Carbon::create(2026, 3, 31);

        while ($date <= $endDate) {
            if ($date->isWeekday()) {
                $dayCount++;
                if ($dayCount <= 2) {
                    // First 2 days: absent
                    Attendance::factory()
                        ->absent()
                        ->create([
                            'company_id' => $this->company->id,
                            'employee_id' => $employee->id,
                            'date' => $date->format('Y-m-d'),
                        ]);
                } elseif ($dayCount <= 6) {
                    // Days 3-6: late (4 days late, exceeds threshold of 3)
                    $att = Attendance::factory()
                        ->late(30)
                        ->create([
                            'company_id' => $this->company->id,
                            'employee_id' => $employee->id,
                            'date' => $date->format('Y-m-d'),
                        ]);

                    // Add overtime to one of the late days
                    if ($dayCount === 6) {
                        $att->update([
                            'overtime_minutes' => 120, // 2 hours
                            'clock_out_status' => 'overtime',
                        ]);
                    }
                } else {
                    // Rest: normal present
                    Attendance::factory()->create([
                        'company_id' => $this->company->id,
                        'employee_id' => $employee->id,
                        'date' => $date->format('Y-m-d'),
                        'status' => 'present',
                    ]);
                }
            }
            $date->addDay();
        }

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $payrollItem = $payroll->items()->with('details')->first();

        expect($payrollItem)->not->toBeNull('PayrollItem should be created');

        // Verify attendance data
        expect($payrollItem->absent_days)->toBe(2);
        expect($payrollItem->late_days)->toBe(4);

        // Verify reimbursement included
        $reimburse = $payrollItem->details->where('component_code', 'REIMBURSE')->first();
        expect($reimburse)->not->toBeNull();
        expect((float) $reimburse->amount)->toBe(500000.0);

        // Verify late penalty (4 late days * 50,000 = 200,000)
        $latePenalty = $payrollItem->details->where('component_code', 'LATE-PEN')->first();
        expect($latePenalty)->not->toBeNull();
        expect((float) $latePenalty->amount)->toBe(200000.0);

        // Verify absent deduction exists
        $absentDeduction = $payrollItem->details->where('component_code', 'ABSENT-DED')->first();
        expect($absentDeduction)->not->toBeNull();
        expect((float) $absentDeduction->amount)->toBeGreaterThan(0);

        // Verify all BPJS components exist
        expect($payrollItem->details->where('component_code', 'BPJS-KES')->first())->not->toBeNull();
        expect($payrollItem->details->where('component_code', 'BPJS-JHT')->first())->not->toBeNull();
        expect($payrollItem->details->where('component_code', 'BPJS-JP')->first())->not->toBeNull();

        // Verify PPh21 is calculated
        expect((float) $payrollItem->tax_amount)->toBeGreaterThan(0);

        // Verify net salary is reasonable
        expect((float) $payrollItem->net_salary)->toBeGreaterThan(0);
        expect((float) $payrollItem->net_salary)->toBeLessThan((float) $payrollItem->gross_salary);
    });
});

describe('THR Calculation', function () {
    it('calculates THR for eligible employee with one month salary', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => Carbon::now()->subMonths(13), // 13 months service
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->subYear(),
            'is_active' => true,
        ]);

        $thrSetting = \App\Models\ThrSetting::create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'include_allowances' => false,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $service = new \App\Services\ThrCalculationService;
        $result = $service->calculateForEmployee($employee, $thrSetting);

        expect($result['eligible'])->toBeTrue();
        expect($result['thr_amount'])->toBe(10000000.0);
        expect($result['service_months'])->toBeGreaterThanOrEqual(12);
    });

    it('calculates THR with prorata for new employee', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => Carbon::now()->subMonths(6), // 6 months service
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 12000000,
            'effective_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        $thrSetting = \App\Models\ThrSetting::create([
            'company_id' => $this->company->id,
            'calculation_method' => 'prorata',
            'prorata_formula' => 'months_worked_per_12',
            'min_service_months' => 1,
            'include_allowances' => false,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $service = new \App\Services\ThrCalculationService;
        $result = $service->calculateForEmployee($employee, $thrSetting);

        expect($result['eligible'])->toBeTrue();
        // Prorata: (6/12) * 12,000,000 = 6,000,000
        expect($result['thr_amount'])->toBe(6000000.0);
    });

    it('rejects employee with insufficient service', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => Carbon::now()->subDays(15), // Less than 1 month
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now(),
            'is_active' => true,
        ]);

        $thrSetting = \App\Models\ThrSetting::create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'include_allowances' => false,
            'religious_holiday' => 'idul_fitri',
            'effective_year' => 2026,
            'is_active' => true,
        ]);

        $service = new \App\Services\ThrCalculationService;
        $result = $service->calculateForEmployee($employee, $thrSetting);

        expect($result['eligible'])->toBeFalse();
    });
});
