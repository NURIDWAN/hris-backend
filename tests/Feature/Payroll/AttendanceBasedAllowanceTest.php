<?php

use App\Models\Company;
use App\Models\PayrollSetting;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('PayrollSetting period calculation', function () {
    it('calculates standard monthly period (cutoff day 1)', function () {
        $setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'cutoff_day' => 1,
        ]);

        $period = $setting->calculatePeriodDates(2026, 2);

        expect($period['start']->format('Y-m-d'))->toBe('2026-02-01');
        expect($period['end']->format('Y-m-d'))->toBe('2026-02-28');
    });

    it('calculates custom period with cutoff day 25', function () {
        $setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'cutoff_day' => 25,
        ]);

        // February 2026 payroll: Jan 25, 2026 to Feb 24, 2026
        $period = $setting->calculatePeriodDates(2026, 2);

        expect($period['start']->format('Y-m-d'))->toBe('2026-01-25');
        expect($period['end']->format('Y-m-d'))->toBe('2026-02-24');
    });

    it('calculates custom period with cutoff day 15', function () {
        $setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'cutoff_day' => 15,
        ]);

        // March 2026 payroll: Feb 15, 2026 to Mar 14, 2026
        $period = $setting->calculatePeriodDates(2026, 3);

        expect($period['start']->format('Y-m-d'))->toBe('2026-02-15');
        expect($period['end']->format('Y-m-d'))->toBe('2026-03-14');
    });

    it('calculates custom period with cutoff day 20', function () {
        $setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'cutoff_day' => 20,
        ]);

        // January 2026 payroll: Dec 20, 2025 to Jan 19, 2026
        $period = $setting->calculatePeriodDates(2026, 1);

        expect($period['start']->format('Y-m-d'))->toBe('2025-12-20');
        expect($period['end']->format('Y-m-d'))->toBe('2026-01-19');
    });

    it('calculates daily rate from monthly salary', function () {
        $setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'default_working_days' => 22,
        ]);

        $dailyRate = $setting->calculateDailyRate(11000000);

        expect($dailyRate)->toBe(500000.0);
    });
});

describe('SalaryComponent attendance-based calculation', function () {
    it('calculates daily meal allowance based on present days', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Uang Makan',
            'code' => 'MEAL',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'is_attendance_based' => true,
            'attendance_calculation' => 'daily',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);

        $attendanceData = [
            'present_days' => 20,
            'absent_days' => 2,
            'late_days' => 0,
            'half_days' => 0,
            'working_days' => 22,
        ];

        $amount = $component->calculateAttendanceBasedAmount($attendanceData);

        // 20 days × Rp 50,000 = Rp 1,000,000
        expect($amount)->toBe(1000000.0);
    });

    it('calculates monthly allowance with proportional deduction', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Tunjangan Transport',
            'code' => 'TRANSPORT',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 500000,
            'is_attendance_based' => true,
            'attendance_calculation' => 'monthly',
            'is_active' => true,
        ]);

        $attendanceData = [
            'present_days' => 20,
            'absent_days' => 2,
            'late_days' => 0,
            'half_days' => 0,
            'working_days' => 22,
        ];

        $amount = $component->calculateAttendanceBasedAmount($attendanceData, 500000);

        // Rp 500,000 - (Rp 500,000 / 22 × 2 absent days) = Rp 454,545.45
        expect(round($amount))->toBe(454545.0);
    });

    it('includes half days as 0.5 when configured', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Uang Makan',
            'code' => 'MEAL',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'is_attendance_based' => true,
            'attendance_calculation' => 'daily',
            'daily_rate' => 50000,
            'include_half_days' => true,
            'is_active' => true,
        ]);

        $attendanceData = [
            'present_days' => 18,
            'absent_days' => 2,
            'late_days' => 0,
            'half_days' => 2,
            'working_days' => 22,
        ];

        $amount = $component->calculateAttendanceBasedAmount($attendanceData);

        // (18 + 2*0.5) days × Rp 50,000 = 19 × 50,000 = Rp 950,000
        expect($amount)->toBe(950000.0);
    });

    it('deducts late days when configured', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Uang Makan',
            'code' => 'MEAL',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'is_attendance_based' => true,
            'attendance_calculation' => 'daily',
            'daily_rate' => 50000,
            'deduct_for_late' => true,
            'is_active' => true,
        ]);

        $attendanceData = [
            'present_days' => 20,
            'absent_days' => 0,
            'late_days' => 3,
            'half_days' => 0,
            'working_days' => 22,
        ];

        $amount = $component->calculateAttendanceBasedAmount($attendanceData);

        // (20 - 3 late) days × Rp 50,000 = 17 × 50,000 = Rp 850,000
        expect($amount)->toBe(850000.0);
    });

    it('returns full amount for non-attendance-based component', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Gaji Pokok',
            'code' => 'BASIC',
            'type' => 'earning',
            'category' => 'fixed',
            'calculation_type' => 'fixed',
            'default_amount' => 5000000,
            'is_attendance_based' => false,
            'is_active' => true,
        ]);

        $attendanceData = [
            'present_days' => 15,
            'absent_days' => 5,
            'late_days' => 2,
            'half_days' => 0,
            'working_days' => 22,
        ];

        $amount = $component->calculateAttendanceBasedAmount($attendanceData, 5000000);

        // Non-attendance based, should return full amount
        expect($amount)->toBe(5000000.0);
    });

    it('prevents negative amount', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Uang Makan',
            'code' => 'MEAL',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'is_attendance_based' => true,
            'attendance_calculation' => 'daily',
            'daily_rate' => 50000,
            'deduct_for_late' => true,
            'is_active' => true,
        ]);

        // Edge case: more late days than present days
        $attendanceData = [
            'present_days' => 5,
            'absent_days' => 15,
            'late_days' => 10,
            'half_days' => 0,
            'working_days' => 22,
        ];

        $amount = $component->calculateAttendanceBasedAmount($attendanceData);

        // Should not be negative (5 - 10 = -5, capped at 0)
        expect($amount)->toBe(0.0);
    });
});

describe('PayrollSetting UI', function () {
    it('displays payroll settings page', function () {
        $response = $this->get(route('payroll-settings.edit'));

        $response->assertOk();
        $response->assertViewIs('payroll-settings.edit');
    });

    it('creates default settings on first visit', function () {
        expect(PayrollSetting::where('company_id', $this->company->id)->exists())->toBeFalse();

        $this->get(route('payroll-settings.edit'));

        expect(PayrollSetting::where('company_id', $this->company->id)->exists())->toBeTrue();
    });

    it('updates payroll settings', function () {
        PayrollSetting::create([
            'company_id' => $this->company->id,
            'cutoff_day' => 1,
        ]);

        $response = $this->put(route('payroll-settings.update'), [
            'cycle_type' => 'monthly',
            'cutoff_day' => 25,
            'default_working_days' => 22,
            'auto_deduct_absent' => true,
            'late_penalty_threshold' => 3,
            'late_penalty_amount' => 50000,
            'late_penalty_type' => 'fixed',
            'absent_deduction_type' => 'daily_rate',
            'absent_deduction_amount' => 0,
            'payment_day' => 1,
        ]);

        $response->assertRedirect(route('payroll-settings.edit'));
        $response->assertSessionHas('success');

        $setting = PayrollSetting::where('company_id', $this->company->id)->first();
        expect($setting->cutoff_day)->toBe(25);
    });
});
