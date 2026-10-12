<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\ThrPayment;
use App\Models\ThrSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\ThrCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);

    $this->company = Company::factory()->create();

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');

    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->position = Position::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
    ]);

    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('THR Settings Access', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get(route('thr-settings.index'));

        $response->assertRedirect('/login');
    });

    it('allows admin to access thr settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('thr-settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('thr-settings.index');
    });

    it('displays existing settings', function () {
        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('thr-settings.index'));

        $response->assertStatus(200);
    });
});

describe('THR Settings Update', function () {
    it('creates thr settings successfully', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('thr-settings.update'), [
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'prorata_formula' => 'months_worked_per_12',
            'include_allowances' => true,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
        ]);

        $response->assertRedirect(route('thr-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('thr_settings', [
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
        ]);
    });

    it('updates existing thr settings', function () {
        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('thr-settings.update'), [
            'calculation_method' => 'prorata',
            'min_service_months' => 3,
            'prorata_formula' => 'months_worked_per_12',
            'include_allowances' => false,
            'religious_holiday' => 'christmas',
            'payment_days_before' => 14,
        ]);

        $response->assertRedirect(route('thr-settings.index'));

        $this->assertDatabaseHas('thr_settings', [
            'company_id' => $this->company->id,
            'calculation_method' => 'prorata',
            'min_service_months' => 3,
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('thr-settings.update'), []);

        $response->assertSessionHasErrors([
            'calculation_method',
            'min_service_months',
            'religious_holiday',
            'payment_days_before',
        ]);
    });

    it('validates calculation method options', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('thr-settings.update'), [
            'calculation_method' => 'invalid_method',
            'min_service_months' => 1,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
        ]);

        $response->assertSessionHasErrors(['calculation_method']);
    });
});

describe('THR Index Page', function () {
    it('displays thr payments list', function () {
        ThrPayment::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('thr.index'));

        $response->assertStatus(200);
        $response->assertViewIs('thr.index');
    });

    it('filters by year', function () {
        ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'year' => 2025,
        ]);
        ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'year' => 2026,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('thr.index', ['year' => 2026]));

        $response->assertStatus(200);
    });

    it('filters by status', function () {
        ThrPayment::factory()->pending()->create([
            'company_id' => $this->company->id,
        ]);
        ThrPayment::factory()->paid()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('thr.index', ['status' => 'pending']));

        $response->assertStatus(200);
    });

    it('filters by religious holiday', function () {
        ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'religious_holiday' => 'idul_fitri',
        ]);
        ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'religious_holiday' => 'christmas',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('thr.index', ['religious_holiday' => 'idul_fitri']));

        $response->assertStatus(200);
    });
});

describe('THR Calculation Page', function () {
    it('displays calculate page', function () {
        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('thr.calculate'));

        $response->assertStatus(200);
        $response->assertViewIs('thr.calculate');
    });

    it('returns error when settings not configured', function () {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('thr.do-calculate'), [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    });

    it('calculates thr for eligible employees', function () {
        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'include_allowances' => false,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('thr.do-calculate'), [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data',
            'setting',
        ]);
    });
});

describe('THR Process', function () {
    it('creates thr payments for selected employees', function () {
        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.process'), [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
            'employee_ids' => [$employee->id],
            'payment_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('thr.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('thr_payments', [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
            'status' => 'pending',
        ]);
    });

    it('skips employees who already have thr for same year and holiday', function () {
        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'is_active' => true,
        ]);

        // Existing THR payment
        ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.process'), [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
            'employee_ids' => [$employee->id],
            'payment_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('thr.index'));
        $response->assertSessionHas('warning');
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('thr.process'), []);

        $response->assertSessionHasErrors([
            'year',
            'religious_holiday',
            'employee_ids',
            'payment_date',
        ]);
    });

    it('returns error when settings not configured', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.process'), [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
            'employee_ids' => [$employee->id],
            'payment_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHas('error');
    });
});

describe('THR Payment Actions', function () {
    it('marks thr as paid', function () {
        $payment = ThrPayment::factory()->pending()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.pay', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        expect($payment->status)->toBe('paid');
        expect($payment->paid_at)->not->toBeNull();
    });

    it('prevents paying already paid thr', function () {
        $payment = ThrPayment::factory()->paid()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.pay', $payment));

        $response->assertSessionHas('error');
    });

    it('cancels pending thr', function () {
        $payment = ThrPayment::factory()->pending()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.cancel', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        expect($payment->status)->toBe('cancelled');
    });

    it('prevents cancelling paid thr', function () {
        $payment = ThrPayment::factory()->paid()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.cancel', $payment));

        $response->assertSessionHas('error');
    });

    it('prevents accessing other company thr', function () {
        $otherCompany = Company::factory()->create();
        $payment = ThrPayment::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('thr.pay', $payment));

        $response->assertStatus(404);
    });
});

describe('THR Calculation Service', function () {
    it('calculates one month salary correctly', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'include_allowances' => false,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(12),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'is_active' => true,
        ]);

        $service = app(ThrCalculationService::class);
        $result = $service->calculateForEmployee($employee, $setting);

        expect($result['eligible'])->toBeTrue();
        expect($result['thr_amount'])->toEqual(10000000);
    });

    it('calculates prorata correctly', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'prorata',
            'prorata_formula' => 'months_worked_per_12',
            'min_service_months' => 1,
            'include_allowances' => false,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 12000000,
            'is_active' => true,
        ]);

        $service = app(ThrCalculationService::class);
        $result = $service->calculateForEmployee($employee, $setting);

        expect($result['eligible'])->toBeTrue();
        // 6/12 * 12,000,000 = 6,000,000
        expect($result['thr_amount'])->toEqual(6000000);
    });

    it('rejects employees with insufficient service months', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 12,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'is_active' => true,
        ]);

        $service = app(ThrCalculationService::class);
        $result = $service->calculateForEmployee($employee, $setting);

        expect($result['eligible'])->toBeFalse();
        expect($result['reason'])->toContain('kurang dari 12 bulan');
    });

    it('rejects employees without salary data', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
            'hire_date' => now()->subMonths(12),
            'is_active' => true,
        ]);

        $service = app(ThrCalculationService::class);
        $result = $service->calculateForEmployee($employee, $setting);

        expect($result['eligible'])->toBeFalse();
        expect($result['reason'])->toContain('data gaji');
    });
});

describe('THR Payment Model', function () {
    it('returns correct status labels', function () {
        $pending = ThrPayment::factory()->pending()->create(['company_id' => $this->company->id]);
        $paid = ThrPayment::factory()->paid()->create(['company_id' => $this->company->id]);
        $cancelled = ThrPayment::factory()->cancelled()->create(['company_id' => $this->company->id]);

        expect($pending->status_label)->toBe('Menunggu');
        expect($paid->status_label)->toBe('Dibayar');
        expect($cancelled->status_label)->toBe('Dibatalkan');
    });

    it('formats amount correctly', function () {
        $payment = ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'amount' => 10000000,
        ]);

        expect($payment->formatted_amount)->toBe('Rp 10.000.000');
    });

    it('returns correct religious holiday label', function () {
        $payment = ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'religious_holiday' => 'idul_fitri',
        ]);

        expect($payment->religious_holiday_label)->toBe('Idul Fitri');
    });

    it('has status check methods', function () {
        $pending = ThrPayment::factory()->pending()->create(['company_id' => $this->company->id]);
        $paid = ThrPayment::factory()->paid()->create(['company_id' => $this->company->id]);
        $cancelled = ThrPayment::factory()->cancelled()->create(['company_id' => $this->company->id]);

        expect($pending->isPending())->toBeTrue();
        expect($pending->isPaid())->toBeFalse();

        expect($paid->isPaid())->toBeTrue();
        expect($paid->isPending())->toBeFalse();

        expect($cancelled->isCancelled())->toBeTrue();
    });
});

describe('THR Setting Model', function () {
    it('returns correct calculation method label', function () {
        $oneMonth = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
        ]);

        $prorata = ThrSetting::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'calculation_method' => 'prorata',
        ]);

        expect($oneMonth->calculation_method_label)->toBe('1 Bulan Gaji');
        expect($prorata->calculation_method_label)->toBe('Prorata');
    });

    it('returns correct religious holiday label', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'religious_holiday' => 'idul_fitri',
        ]);

        expect($setting->religious_holiday_label)->toBe('Idul Fitri');
    });
});
