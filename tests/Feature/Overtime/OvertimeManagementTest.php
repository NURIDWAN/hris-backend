<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\OvertimeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);

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

    $this->employeeUser = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employeeUser->assignRole('employee');

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'work_schedule_id' => $this->workSchedule->id,
        'user_id' => $this->employeeUser->id,
        'is_active' => true,
    ]);

    EmployeeSalary::factory()->create([
        'employee_id' => $this->employee->id,
        'basic_salary' => 10000000,
        'is_active' => true,
    ]);
});

describe('Overtime Settings Access', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get(route('overtime-settings.index'));

        $response->assertRedirect('/login');
    });

    it('allows admin to access overtime settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('overtime-settings.index');
    });

    it('displays existing settings', function () {
        OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-settings.index'));

        $response->assertStatus(200);
    });
});

describe('Overtime Settings Update', function () {
    it('creates overtime settings successfully', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('overtime-settings.update'), [
            'weekday_rate_first_hour' => 1.5,
            'weekday_rate_next_hours' => 2.0,
            'weekend_rate' => 2.0,
            'holiday_rate' => 3.0,
            'max_overtime_hours_per_day' => 4,
            'max_overtime_hours_per_week' => 14,
            'min_overtime_minutes' => 30,
            'requires_approval' => true,
            'auto_calculate_from_attendance' => false,
        ]);

        $response->assertRedirect(route('overtime-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('overtime_settings', [
            'company_id' => $this->company->id,
            'weekday_rate_first_hour' => 1.5,
            'weekday_rate_next_hours' => 2.0,
        ]);
    });

    it('updates existing overtime settings', function () {
        OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
            'weekday_rate_first_hour' => 1.5,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('overtime-settings.update'), [
            'weekday_rate_first_hour' => 1.75,
            'weekday_rate_next_hours' => 2.5,
            'weekend_rate' => 2.0,
            'holiday_rate' => 3.0,
            'max_overtime_hours_per_day' => 3,
            'max_overtime_hours_per_week' => 12,
            'min_overtime_minutes' => 60,
            'requires_approval' => true,
            'auto_calculate_from_attendance' => true,
        ]);

        $response->assertRedirect(route('overtime-settings.index'));

        $this->assertDatabaseHas('overtime_settings', [
            'company_id' => $this->company->id,
            'weekday_rate_first_hour' => 1.75,
            'max_overtime_hours_per_day' => 3,
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('overtime-settings.update'), []);

        $response->assertSessionHasErrors([
            'weekday_rate_first_hour',
            'weekday_rate_next_hours',
            'weekend_rate',
            'holiday_rate',
            'max_overtime_hours_per_day',
            'max_overtime_hours_per_week',
        ]);
    });

    it('validates rate ranges', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('overtime-settings.update'), [
            'weekday_rate_first_hour' => 0, // Invalid - min 0.1
            'weekday_rate_next_hours' => 15, // Invalid - max 10
            'weekend_rate' => 2.0,
            'holiday_rate' => 3.0,
            'max_overtime_hours_per_day' => 4,
            'max_overtime_hours_per_week' => 14,
        ]);

        $response->assertSessionHasErrors(['weekday_rate_first_hour', 'weekday_rate_next_hours']);
    });
});

describe('Overtime Request Index', function () {
    it('displays overtime requests list for admin', function () {
        OvertimeRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-requests.index'));

        $response->assertStatus(200);
        $response->assertViewIs('overtime-requests.index');
    });

    it('filters by status', function () {
        OvertimeRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
        ]);
        OvertimeRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-requests.index', ['status' => 'pending']));

        $response->assertStatus(200);
    });

    it('filters by date range', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-requests.index', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
    });

    it('searches by employee name', function () {
        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-requests.index', [
            'search' => $this->employee->first_name,
        ]));

        $response->assertStatus(200);
    });
});

describe('Overtime Request Creation', function () {
    beforeEach(function () {
        OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
            'requires_approval' => true,
        ]);
    });

    it('displays create form', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('overtime-requests.create'));

        $response->assertStatus(200);
        $response->assertViewIs('overtime-requests.create');
    });

    it('creates overtime request successfully', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.store'), [
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => 'weekday',
            'reason' => 'Project deadline',
        ]);

        $response->assertRedirect(route('overtime-requests.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('overtime_requests', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.store'), []);

        $response->assertSessionHasErrors([
            'employee_id',
            'date',
            'start_time',
            'end_time',
            'overtime_type',
            'reason',
        ]);
    });

    it('validates end time after start time', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.store'), [
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '21:00',
            'end_time' => '18:00', // Before start
            'overtime_type' => 'weekday',
            'reason' => 'Test',
        ]);

        $response->assertSessionHasErrors(['end_time']);
    });

    it('validates overtime type options', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.store'), [
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => 'invalid_type',
            'reason' => 'Test',
        ]);

        $response->assertSessionHasErrors(['overtime_type']);
    });

    it('prevents duplicate overtime request for same employee and date', function () {
        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(1)->toDateString(),
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.store'), [
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => 'weekday',
            'reason' => 'Test',
        ]);

        $response->assertSessionHasErrors();
    });
});

describe('Overtime Request Approval', function () {
    beforeEach(function () {
        OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);
    });

    it('approves pending overtime request', function () {
        $request = OvertimeRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.approve', $request));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();
        expect($request->status)->toBe('approved');
        expect($request->approved_by)->toBe($this->admin->id);
        expect($request->approved_at)->not->toBeNull();
    });

    it('rejects pending overtime request', function () {
        $request = OvertimeRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.reject', $request), [
            'rejection_reason' => 'Not necessary',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();
        expect($request->status)->toBe('rejected');
        expect($request->rejection_reason)->toBe('Not necessary');
    });

    it('requires rejection reason when rejecting', function () {
        $request = OvertimeRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.reject', $request), []);

        $response->assertSessionHasErrors(['rejection_reason']);
    });

    it('prevents approving already approved request', function () {
        $request = OvertimeRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.approve', $request));

        $response->assertSessionHas('error');
    });

    it('prevents approving other company request', function () {
        $otherCompany = Company::factory()->create();
        $request = OvertimeRequest::factory()->pending()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('overtime-requests.approve', $request));

        $response->assertStatus(404);
    });
});

describe('Overtime Calculation Service', function () {
    beforeEach(function () {
        $this->setting = OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
            'weekday_rate_first_hour' => 1.5,
            'weekday_rate_next_hours' => 2.0,
            'weekend_rate' => 2.0,
            'holiday_rate' => 3.0,
        ]);
    });

    it('calculates weekday overtime correctly', function () {
        // Salary: 10,000,000 / 173 (jam kerja per bulan) = ~57,803 per jam
        // First hour: 57,803 * 1.5 = 86,705
        // Next 2 hours: 57,803 * 2.0 * 2 = 231,214
        // Total: 317,919

        $service = app(OvertimeCalculationService::class);
        $result = $service->calculate(
            employee: $this->employee,
            overtimeHours: 3,
            overtimeType: 'weekday',
            setting: $this->setting
        );

        expect($result['hourly_rate'])->toBeGreaterThan(0);
        expect($result['overtime_hours'])->toBe(3.0);
        expect($result['overtime_amount'])->toBeGreaterThan(0);
        expect($result['calculation_method'])->toBe('weekday');
    });

    it('calculates weekend overtime correctly', function () {
        $service = app(OvertimeCalculationService::class);
        $result = $service->calculate(
            employee: $this->employee,
            overtimeHours: 4,
            overtimeType: 'weekend',
            setting: $this->setting
        );

        expect($result['overtime_hours'])->toBe(4.0);
        expect($result['calculation_method'])->toBe('weekend');
        // Weekend uses flat rate 2.0 for all hours
    });

    it('calculates holiday overtime correctly', function () {
        $service = app(OvertimeCalculationService::class);
        $result = $service->calculate(
            employee: $this->employee,
            overtimeHours: 2,
            overtimeType: 'holiday',
            setting: $this->setting
        );

        expect($result['overtime_hours'])->toBe(2.0);
        expect($result['calculation_method'])->toBe('holiday');
        // Holiday uses flat rate 3.0 for all hours
    });

    it('returns zero for employee without salary', function () {
        $employeeNoSalary = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $service = app(OvertimeCalculationService::class);
        $result = $service->calculate(
            employee: $employeeNoSalary,
            overtimeHours: 3,
            overtimeType: 'weekday',
            setting: $this->setting
        );

        expect($result['overtime_amount'])->toBe(0.0);
        expect($result['error'])->not->toBeNull();
    });
});

describe('Employee Portal Overtime', function () {
    beforeEach(function () {
        OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
            'requires_approval' => true,
        ]);
    });

    it('displays overtime history for employee', function () {
        OvertimeRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get(route('portal.overtime.index'));

        $response->assertStatus(200);
        $response->assertViewIs('portal.overtime.index');
    });

    it('allows employee to submit overtime request', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('portal.overtime.store'), [
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => 'weekday',
            'reason' => 'Urgent project work',
        ]);

        $response->assertRedirect(route('portal.overtime.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);
    });

    it('allows employee to cancel pending overtime request', function () {
        $request = OvertimeRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post(route('portal.overtime.cancel', $request));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();
        expect($request->status)->toBe('cancelled');
    });

    it('prevents employee from cancelling approved overtime', function () {
        $request = OvertimeRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post(route('portal.overtime.cancel', $request));

        $response->assertSessionHas('error');
    });

    it('prevents employee from accessing other employee overtime', function () {
        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $request = OvertimeRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post(route('portal.overtime.cancel', $request));

        $response->assertStatus(403);
    });
});

describe('Overtime Request Model', function () {
    it('returns correct status labels', function () {
        $pending = OvertimeRequest::factory()->pending()->create(['company_id' => $this->company->id]);
        $approved = OvertimeRequest::factory()->approved()->create(['company_id' => $this->company->id]);
        $rejected = OvertimeRequest::factory()->rejected()->create(['company_id' => $this->company->id]);
        $cancelled = OvertimeRequest::factory()->cancelled()->create(['company_id' => $this->company->id]);

        expect($pending->status_label)->toBe('Menunggu');
        expect($approved->status_label)->toBe('Disetujui');
        expect($rejected->status_label)->toBe('Ditolak');
        expect($cancelled->status_label)->toBe('Dibatalkan');
    });

    it('calculates overtime hours correctly', function () {
        $request = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '18:00',
            'end_time' => '21:30',
        ]);

        expect($request->calculated_overtime_hours)->toBe(3.5);
    });

    it('returns correct overtime type label', function () {
        $weekday = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'overtime_type' => 'weekday',
        ]);
        $weekend = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'overtime_type' => 'weekend',
        ]);
        $holiday = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'overtime_type' => 'holiday',
        ]);

        expect($weekday->overtime_type_label)->toBe('Hari Kerja');
        expect($weekend->overtime_type_label)->toBe('Akhir Pekan');
        expect($holiday->overtime_type_label)->toBe('Hari Libur');
    });

    it('has status check methods', function () {
        $pending = OvertimeRequest::factory()->pending()->create(['company_id' => $this->company->id]);
        $approved = OvertimeRequest::factory()->approved()->create(['company_id' => $this->company->id]);

        expect($pending->isPending())->toBeTrue();
        expect($pending->isApproved())->toBeFalse();

        expect($approved->isApproved())->toBeTrue();
        expect($approved->isPending())->toBeFalse();
    });

    it('formats overtime amount correctly', function () {
        $request = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'overtime_amount' => 500000,
        ]);

        expect($request->formatted_overtime_amount)->toBe('Rp 500.000');
    });
});

describe('Overtime Setting Model', function () {
    it('has correct default values', function () {
        $setting = OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        expect($setting->weekday_rate_first_hour)->toBeGreaterThan(0);
        expect($setting->weekday_rate_next_hours)->toBeGreaterThan(0);
        expect($setting->requires_approval)->toBeBool();
    });

    it('calculates hourly rate from monthly salary', function () {
        $setting = OvertimeSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Default working hours per month is 173
        $monthlySalary = 10000000;
        $expectedHourlyRate = $monthlySalary / 173;

        expect($setting->calculateHourlyRate($monthlySalary))->toEqual(round($expectedHourlyRate, 2));
    });
});
