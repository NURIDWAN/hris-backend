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
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('ActivityLog - Index', function () {
    it('displays activity log list', function () {
        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'employee',
            'description' => 'Karyawan ditambahkan',
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index'));

        $response->assertOk();
        $response->assertViewIs('activity-logs.index');
        $response->assertViewHas('activities');
    });

    it('only shows activities for current company', function () {
        $otherCompany = Company::factory()->create();

        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'My company activity',
        ]);

        Activity::create([
            'company_id' => $otherCompany->id,
            'log_name' => 'default',
            'description' => 'Other company activity',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index'));

        $response->assertOk();
        expect($response->viewData('activities'))->toHaveCount(1);
    });

    it('can filter by log name', function () {
        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'employee',
            'description' => 'Employee activity',
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'attendance',
            'description' => 'Attendance activity',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index', ['log_name' => 'employee']));

        $response->assertOk();
        expect($response->viewData('activities'))->toHaveCount(1);
    });

    it('can filter by event type', function () {
        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Created',
            'event' => 'created',
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Updated',
            'event' => 'updated',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index', ['event' => 'created']));

        $response->assertOk();
        expect($response->viewData('activities'))->toHaveCount(1);
    });

    it('can filter by causer (user)', function () {
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Admin action',
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Other user action',
            'causer_type' => User::class,
            'causer_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index', ['causer_id' => $this->admin->id]));

        $response->assertOk();
        expect($response->viewData('activities'))->toHaveCount(1);
    });

    it('can filter by date range', function () {
        Activity::query()->delete();

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Today activity',
            'created_at' => now(),
        ]);

        Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'default',
            'description' => 'Old activity',
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index', [
                'date_from' => now()->subDay()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]));

        $response->assertOk();
        expect($response->viewData('activities'))->toHaveCount(1);
    });

    it('paginates results', function () {
        Activity::query()->delete();

        // Create 20 activities
        for ($i = 0; $i < 20; $i++) {
            Activity::create([
                'company_id' => $this->company->id,
                'log_name' => 'default',
                'description' => "Activity {$i}",
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.index'));

        $response->assertOk();
        expect($response->viewData('activities'))->toHaveCount(15); // Default per page
    });
});

describe('ActivityLog - Show', function () {
    it('displays activity detail', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        $activity = Activity::create([
            'company_id' => $this->company->id,
            'log_name' => 'employee',
            'description' => 'Karyawan ditambahkan',
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'properties' => [
                'attributes' => ['first_name' => 'John', 'last_name' => 'Doe'],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.show', $activity));

        $response->assertOk();
        $response->assertViewIs('activity-logs.show');
        $response->assertViewHas('activity');
    });

    it('restricts access to activities from other companies', function () {
        $otherCompany = Company::factory()->create();

        $activity = Activity::create([
            'company_id' => $otherCompany->id,
            'log_name' => 'default',
            'description' => 'Other company activity',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.activity-logs.show', $activity));

        $response->assertNotFound();
    });
});

describe('ActivityLog - Access Control', function () {
    it('requires authentication', function () {
        $response = $this->get(route('settings.activity-logs.index'));

        $response->assertRedirect(route('login'));
    });
});
