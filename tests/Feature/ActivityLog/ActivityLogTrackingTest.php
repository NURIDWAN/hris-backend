<?php

use App\Models\Activity;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Simulate tenant binding
    app()->instance('tenant', $this->company);
});

describe('Activity - Auto Track Employee', function () {
    it('logs activity when employee is created', function () {
        $this->actingAs($this->admin);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'hire_date' => now(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $activity = Activity::forSubject($employee)->first();

        expect($activity)->not->toBeNull();
        expect($activity->event)->toBe('created');
        expect($activity->causer_id)->toBe($this->admin->id);
        expect($activity->company_id)->toBe($this->company->id);
    });

    it('logs activity when employee is updated', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'first_name' => 'John',
        ]);

        // Clear previous activities
        Activity::query()->delete();

        $employee->update(['first_name' => 'Jane']);

        $activity = Activity::forSubject($employee)->first();

        expect($activity)->not->toBeNull();
        expect($activity->event)->toBe('updated');
        expect($activity->properties['old']['first_name'])->toBe('John');
        expect($activity->properties['attributes']['first_name'])->toBe('Jane');
    });

    it('logs activity when employee is deleted', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);
        $employeeId = $employee->id;

        // Clear previous activities
        Activity::query()->delete();

        $employee->delete();

        $activity = Activity::where('subject_id', $employeeId)
            ->where('subject_type', Employee::class)
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->event)->toBe('deleted');
    });
});

describe('Activity - Auto Track Department', function () {
    it('logs activity when department is created', function () {
        $this->actingAs($this->admin);

        $department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'IT Department',
            'code' => 'IT',
            'is_active' => true,
        ]);

        $activity = Activity::forSubject($department)->first();

        expect($activity)->not->toBeNull();
        expect($activity->event)->toBe('created');
        expect($activity->log_name)->toBe('department');
    });

    it('logs activity when department is updated', function () {
        $this->actingAs($this->admin);

        $department = Department::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        Activity::query()->delete();

        $department->update(['name' => 'New Name']);

        $activity = Activity::forSubject($department)->first();

        expect($activity)->not->toBeNull();
        expect($activity->event)->toBe('updated');
    });
});

describe('Activity - Auto Track Position', function () {
    it('logs activity when position is created', function () {
        $this->actingAs($this->admin);

        $department = Department::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $position = Position::create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
            'name' => 'Software Engineer',
            'code' => 'SE',
            'level' => 3,
            'is_active' => true,
        ]);

        $activity = Activity::forSubject($position)->first();

        expect($activity)->not->toBeNull();
        expect($activity->event)->toBe('created');
        expect($activity->log_name)->toBe('position');
    });
});

describe('Activity - Custom Log Names', function () {
    it('uses model-specific log names', function () {
        $this->actingAs($this->admin);

        $department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $activity = Activity::forSubject($department)->first();
        expect($activity->log_name)->toBe('department');

        Activity::query()->delete();

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.com',
            'hire_date' => now(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $activity = Activity::forSubject($employee)->first();
        expect($activity->log_name)->toBe('employee');
    });
});

describe('Activity - Tenant Isolation with Tracking', function () {
    it('automatically assigns company_id to logged activities', function () {
        $this->actingAs($this->admin);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'test@example.com',
            'hire_date' => now(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $activity = Activity::forSubject($employee)->first();

        expect($activity->company_id)->toBe($this->company->id);
    });

    it('does not show activities from other companies', function () {
        $this->actingAs($this->admin);

        // Clear any pre-existing activities
        Activity::query()->delete();

        $otherCompany = Company::factory()->create();
        $otherWorkSchedule = WorkSchedule::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Create employee in current company
        Employee::create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'first_name' => 'Company1',
            'last_name' => 'Employee',
            'email' => 'emp1@test.com',
            'hire_date' => now(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        // Simulate other company context
        app()->instance('tenant', $otherCompany);

        Employee::create([
            'company_id' => $otherCompany->id,
            'work_schedule_id' => $otherWorkSchedule->id,
            'first_name' => 'Company2',
            'last_name' => 'Employee',
            'email' => 'emp2@test.com',
            'hire_date' => now(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        // Query activities for company 1 only
        $company1Activities = Activity::forCompany($this->company->id)->get();

        expect($company1Activities)->toHaveCount(1);
        expect($company1Activities->first()->company_id)->toBe($this->company->id);
    });
});

describe('Activity - Only Log Dirty Attributes', function () {
    it('only logs changed attributes on update', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
        ]);

        Activity::query()->delete();

        // Only update first_name
        $employee->update(['first_name' => 'Jane']);

        $activity = Activity::forSubject($employee)->first();

        expect($activity)->not->toBeNull();
        expect($activity->properties['attributes'])->toHaveKey('first_name');
        // Should only log changed attributes
        expect($activity->properties['old'])->toHaveKey('first_name');
    });
});
