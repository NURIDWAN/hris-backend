<?php

use App\Models\Activity;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('Activity Model', function () {
    it('can create an activity log entry', function () {
        $activity = Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Test activity',
            'causer_type' => User::class,
            'causer_id' => $this->user->id,
            'event' => 'created',
        ]);

        expect($activity)->toBeInstanceOf(Activity::class);
        expect($activity->log_name)->toBe('default');
        expect($activity->description)->toBe('Test activity');
        expect($activity->company_id)->toBe($this->company->id);
    });

    it('belongs to a company', function () {
        $activity = Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Test activity',
        ]);

        expect($activity->company)->toBeInstanceOf(Company::class);
        expect($activity->company->id)->toBe($this->company->id);
    });

    it('can have a causer (user who performed the action)', function () {
        $activity = Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'User created something',
            'causer_type' => User::class,
            'causer_id' => $this->user->id,
            'event' => 'created',
        ]);

        expect($activity->causer)->toBeInstanceOf(User::class);
        expect($activity->causer->id)->toBe($this->user->id);
    });

    it('can have a subject (model that was affected)', function () {
        $workSchedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $workSchedule->id,
        ]);

        $activity = Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Employee was updated',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'causer_type' => User::class,
            'causer_id' => $this->user->id,
            'event' => 'updated',
        ]);

        expect($activity->subject)->toBeInstanceOf(Employee::class);
        expect($activity->subject->id)->toBe($employee->id);
    });

    it('can store properties as JSON', function () {
        $properties = [
            'old' => ['name' => 'Old Name'],
            'attributes' => ['name' => 'New Name'],
        ];

        $activity = Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Name was changed',
            'properties' => $properties,
            'event' => 'updated',
        ]);

        expect($activity->properties)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($activity->properties['old']['name'])->toBe('Old Name');
        expect($activity->properties['attributes']['name'])->toBe('New Name');
    });
});

describe('Activity - Tenant Isolation', function () {
    it('auto-sets company_id from tenant context', function () {
        // Simulate tenant binding
        app()->instance('tenant', $this->company);

        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'Auto tenant activity',
            'event' => 'created',
        ]);

        expect($activity->company_id)->toBe($this->company->id);
    });

    it('can filter activities by company', function () {
        $otherCompany = Company::factory()->create();

        // Clear any auto-logged activities
        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Company 1 activity',
        ]);

        Activity::create([
            'company_id' => $otherCompany->id,
            'log_name' => 'default',
            'description' => 'Company 2 activity',
        ]);

        $companyActivities = Activity::forCompany($this->company->id)->get();

        expect($companyActivities)->toHaveCount(1);
        expect($companyActivities->first()->description)->toBe('Company 1 activity');
    });
});

describe('Activity - Scopes', function () {
    it('can filter by log name', function () {
        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'employee',
            'description' => 'Employee log',
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'attendance',
            'description' => 'Attendance log',
        ]);

        $employeeLogs = Activity::inLog('employee')->get();

        expect($employeeLogs)->toHaveCount(1);
        expect($employeeLogs->first()->log_name)->toBe('employee');
    });

    it('can filter by causer', function () {
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'User 1 action',
            'causer_type' => User::class,
            'causer_id' => $this->user->id,
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'User 2 action',
            'causer_type' => User::class,
            'causer_id' => $otherUser->id,
        ]);

        $userActivities = Activity::causedBy($this->user)->get();

        expect($userActivities)->toHaveCount(1);
        expect($userActivities->first()->description)->toBe('User 1 action');
    });

    it('can filter by subject', function () {
        $workSchedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $workSchedule->id,
        ]);

        // Clear auto-logged activities
        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Employee action',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Other action',
        ]);

        $employeeActivities = Activity::forSubject($employee)->get();

        expect($employeeActivities)->toHaveCount(1);
        expect($employeeActivities->first()->description)->toBe('Employee action');
    });

    it('can filter by event type', function () {
        // Clear any auto-logged activities
        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Created something',
            'event' => 'created',
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Updated something',
            'event' => 'updated',
        ]);

        $createdEvents = Activity::forEvent('created')->get();

        expect($createdEvents)->toHaveCount(1);
        expect($createdEvents->first()->event)->toBe('created');
    });
});

describe('Activity - Accessors', function () {
    it('returns human readable event label', function () {
        $activity = new Activity(['event' => 'created']);
        expect($activity->event_label)->toBe('Dibuat');

        $activity = new Activity(['event' => 'updated']);
        expect($activity->event_label)->toBe('Diperbarui');

        $activity = new Activity(['event' => 'deleted']);
        expect($activity->event_label)->toBe('Dihapus');

        $activity = new Activity(['event' => 'login']);
        expect($activity->event_label)->toBe('Login');
    });

    it('returns human readable subject type label', function () {
        $activity = new Activity(['subject_type' => 'App\\Models\\Employee']);
        expect($activity->subject_type_label)->toBe('Karyawan');

        $activity = new Activity(['subject_type' => 'App\\Models\\Department']);
        expect($activity->subject_type_label)->toBe('Departemen');

        $activity = new Activity(['subject_type' => 'App\\Models\\Attendance']);
        expect($activity->subject_type_label)->toBe('Absensi');

        $activity = new Activity(['subject_type' => null]);
        expect($activity->subject_type_label)->toBe('-');
    });

    it('returns changes summary from properties', function () {
        $activity = new Activity([
            'properties' => [
                'old' => ['name' => 'John', 'email' => 'john@test.com'],
                'attributes' => ['name' => 'Jane', 'email' => 'john@test.com'],
            ],
        ]);

        $changes = $activity->changes_summary;

        expect($changes)->toBeArray();
        expect($changes)->toHaveKey('name');
        expect($changes['name']['old'])->toBe('John');
        expect($changes['name']['new'])->toBe('Jane');
        // email tidak berubah, jadi tidak ada di changes
        expect($changes)->not->toHaveKey('email');
    });

    it('returns null for changes summary when no properties', function () {
        $activity = new Activity(['properties' => null]);
        expect($activity->changes_summary)->toBeNull();

        $activity = new Activity(['properties' => []]);
        expect($activity->changes_summary)->toBeNull();
    });
});
