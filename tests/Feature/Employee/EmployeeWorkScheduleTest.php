<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeWeeklySchedule;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();

    createStandardRoles($this->company->id);
    setPermissionsTeamId($this->company->id);

    $this->department = Department::factory()->create(['company_id' => $this->company->id]);
    $this->position = Position::factory()->create(['company_id' => $this->company->id]);
    $this->workSchedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);

    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');
});

describe('Employee Work Schedule Assignment', function () {

    it('shows work schedule dropdown on create form', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('employees.create'));

        $response->assertStatus(200);
        $response->assertSee($this->workSchedule->name);
        $response->assertSee('Jadwal Kerja');
    });

    it('can assign work schedule when creating employee', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('employees.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-01-01',
            'employment_status' => 'permanent',
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $response->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'work_schedule_id' => $this->workSchedule->id,
        ]);
    });

    it('can create employee without work schedule', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('employees.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-01-01',
            'employment_status' => 'permanent',
            'work_schedule_id' => null,
        ]);

        $response->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'first_name' => 'Jane',
            'work_schedule_id' => null,
        ]);
    });

    it('shows work schedule dropdown on edit form', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $response = $this->get(route('employees.edit', $employee));

        $response->assertStatus(200);
        $response->assertSee($this->workSchedule->name);
        $response->assertSee('Jadwal Kerja');
    });

    it('can update work schedule on existing employee', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        $newSchedule = WorkSchedule::factory()->morning()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'employment_status' => $employee->employment_status,
            'work_schedule_id' => $newSchedule->id,
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        expect($employee->work_schedule_id)->toBe($newSchedule->id);
    });

    it('displays work schedule on show page', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee($this->workSchedule->name);
        $response->assertSee(\Carbon\Carbon::parse($this->workSchedule->start_time)->format('H:i'));
        $response->assertSee(\Carbon\Carbon::parse($this->workSchedule->end_time)->format('H:i'));
    });

    it('shows "Belum diatur" when no work schedule assigned', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Belum diatur');
    });

    it('cannot assign work schedule from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherSchedule = WorkSchedule::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->post(route('employees.store'), [
            'first_name' => 'Test',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-01-01',
            'employment_status' => 'permanent',
            'work_schedule_id' => $otherSchedule->id,
        ]);

        $response->assertSessionHasErrors('work_schedule_id');
    });

    it('does not show inactive work schedules in create form', function () {
        $this->actingAs($this->admin);

        $inactiveSchedule = WorkSchedule::factory()->inactive()->create([
            'company_id' => $this->company->id,
            'name' => 'InactiveScheduleXYZ',
        ]);

        $response = $this->get(route('employees.create'));

        $response->assertStatus(200);
        $response->assertDontSee('InactiveScheduleXYZ');
    });
});

describe('Employee Weekly Schedule Pattern', function () {

    it('resolveScheduleForDate returns correct schedule per day', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        $morningSchedule = WorkSchedule::factory()->morning()->create([
            'company_id' => $this->company->id,
        ]);
        $afternoonSchedule = WorkSchedule::factory()->afternoon()->create([
            'company_id' => $this->company->id,
        ]);

        // Monday = morning, Tuesday = afternoon
        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'day_of_week' => 1,
            'work_schedule_id' => $morningSchedule->id,
        ]);
        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'day_of_week' => 2,
            'work_schedule_id' => $afternoonSchedule->id,
        ]);

        // Monday 2026-04-13 is Monday
        $monday = Carbon::parse('2026-04-13');
        $tuesday = Carbon::parse('2026-04-14');

        $result = $employee->resolveScheduleForDate($monday);
        expect($result)->not->toBeNull();
        expect($result->id)->toBe($morningSchedule->id);

        $result = $employee->resolveScheduleForDate($tuesday);
        expect($result)->not->toBeNull();
        expect($result->id)->toBe($afternoonSchedule->id);
    });

    it('returns null for day without assignment when weekly pattern exists', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Only Monday assigned
        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'day_of_week' => 1,
            'work_schedule_id' => $schedule->id,
        ]);

        // Sunday = no assignment = day off
        $sunday = Carbon::parse('2026-04-19'); // Sunday
        $result = $employee->resolveScheduleForDate($sunday);
        expect($result)->toBeNull();
    });

    it('falls back to work_schedule_id when no weekly pattern', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'working_days' => [1, 2, 3, 4, 5],
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $schedule->id,
        ]);

        // Monday = working day
        $monday = Carbon::parse('2026-04-13');
        $result = $employee->resolveScheduleForDate($monday);
        expect($result)->not->toBeNull();
        expect($result->id)->toBe($schedule->id);

        // Saturday = not a working day (Mon-Fri only)
        $saturday = Carbon::parse('2026-04-18');
        $result = $employee->resolveScheduleForDate($saturday);
        expect($result)->toBeNull();
    });

    it('returns null when no schedule at all', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        $monday = Carbon::parse('2026-04-13');
        $result = $employee->resolveScheduleForDate($monday);
        expect($result)->toBeNull();
    });

    it('hasWeeklySchedulePattern returns correct value', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        expect($employee->hasWeeklySchedulePattern())->toBeFalse();

        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'day_of_week' => 1,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $employee->unsetRelation('weeklySchedules');
        expect($employee->hasWeeklySchedulePattern())->toBeTrue();
    });

    it('can save weekly schedule via employee create form', function () {
        $this->actingAs($this->admin);

        $morningSchedule = WorkSchedule::factory()->morning()->create([
            'company_id' => $this->company->id,
        ]);
        $afternoonSchedule = WorkSchedule::factory()->afternoon()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post(route('employees.store'), [
            'first_name' => 'Weekly',
            'last_name' => 'Worker',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-01-01',
            'employment_status' => 'permanent',
            'schedule_mode' => 'weekly',
            'weekly_schedules' => [
                1 => $morningSchedule->id,
                2 => $afternoonSchedule->id,
                3 => $morningSchedule->id,
                4 => $afternoonSchedule->id,
                5 => $morningSchedule->id,
            ],
        ]);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::where('first_name', 'Weekly')->first();
        expect($employee)->not->toBeNull();
        expect($employee->work_schedule_id)->toBeNull();
        expect($employee->weeklySchedules)->toHaveCount(5);

        $mondaySchedule = $employee->weeklySchedules->firstWhere('day_of_week', 1);
        expect($mondaySchedule->work_schedule_id)->toBe($morningSchedule->id);
    });

    it('can update employee to weekly schedule mode', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $morningSchedule = WorkSchedule::factory()->morning()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'employment_status' => $employee->employment_status,
            'schedule_mode' => 'weekly',
            'weekly_schedules' => [
                1 => $morningSchedule->id,
                2 => $morningSchedule->id,
                3 => $morningSchedule->id,
            ],
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        expect($employee->work_schedule_id)->toBeNull();
        expect($employee->weeklySchedules)->toHaveCount(3);
    });

    it('clears weekly schedules when switching to default mode', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'day_of_week' => 1,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $response = $this->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'employment_status' => $employee->employment_status,
            'schedule_mode' => 'default',
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        expect($employee->work_schedule_id)->toBe($this->workSchedule->id);
        expect($employee->weeklySchedules)->toHaveCount(0);
    });

    it('prevents weekly schedule from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherSchedule = WorkSchedule::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->post(route('employees.store'), [
            'first_name' => 'Tenant',
            'last_name' => 'Test',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-01-01',
            'employment_status' => 'permanent',
            'schedule_mode' => 'weekly',
            'weekly_schedules' => [
                1 => $otherSchedule->id,
            ],
        ]);

        $response->assertSessionHasErrors('weekly_schedules.1');
    });

    it('displays weekly schedule pattern on show page', function () {
        $this->actingAs($this->admin);

        $morningSchedule = WorkSchedule::factory()->morning()->create([
            'company_id' => $this->company->id,
            'name' => 'Shift Pagi Test',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'work_schedule_id' => null,
        ]);

        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'day_of_week' => 1,
            'work_schedule_id' => $morningSchedule->id,
        ]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Jadwal Mingguan');
        $response->assertSee('Senin');
        $response->assertSee('Shift Pagi Test');
    });
});

describe('Employee Salary Info on Show Page', function () {

    it('displays salary information when employee has active salary', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'is_active' => true,
        ]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Informasi Gaji');
        $response->assertSee('10.000.000');
    });

    it('shows "Belum ada data gaji" when no salary configured', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Belum ada data gaji');
        $response->assertSee('Atur Gaji');
    });
});
