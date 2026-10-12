<?php

use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'is_superadmin' => true,
        'company_id' => null,
        'is_active' => true,
    ]);

    $this->regularUser = User::factory()->create([
        'is_superadmin' => false,
        'is_active' => true,
    ]);

    // Clear any activity logs created by factory setup
    Activity::query()->delete();
});

describe('Audit Logs Viewer', function () {
    it('can access audit logs index as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs');

        $response->assertOk()
            ->assertViewIs('superadmin.system.audit-logs.index')
            ->assertViewHas('activities')
            ->assertViewHas('stats');
    });

    it('displays activity logs from all companies', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        Activity::create([
            'log_name' => 'default',
            'description' => 'created',
            'event' => 'created',
            'subject_type' => 'App\\Models\\Employee',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => $this->superadmin->id,
            'company_id' => $company1->id,
            'properties' => [],
        ]);

        Activity::create([
            'log_name' => 'default',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => 'App\\Models\\Department',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => $this->superadmin->id,
            'company_id' => $company2->id,
            'properties' => [],
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs');

        $response->assertOk();
        $activities = $response->viewData('activities');
        expect($activities->total())->toBe(2);
    });

    it('can filter by company', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        Activity::create([
            'log_name' => 'default',
            'description' => 'created',
            'event' => 'created',
            'company_id' => $company1->id,
            'properties' => [],
        ]);

        Activity::create([
            'log_name' => 'default',
            'description' => 'updated',
            'event' => 'updated',
            'company_id' => $company2->id,
            'properties' => [],
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs?company_id='.$company1->id);

        $response->assertOk();
        $activities = $response->viewData('activities');
        expect($activities->total())->toBe(1);
    });

    it('can filter by event type', function () {
        Activity::create([
            'log_name' => 'default',
            'description' => 'created',
            'event' => 'created',
            'properties' => [],
        ]);

        Activity::create([
            'log_name' => 'default',
            'description' => 'deleted',
            'event' => 'deleted',
            'properties' => [],
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs?event=created');

        $response->assertOk();
        $activities = $response->viewData('activities');
        expect($activities->total())->toBe(1);
    });

    it('can filter by date range', function () {
        Activity::create([
            'log_name' => 'default',
            'description' => 'old record',
            'event' => 'created',
            'properties' => [],
            'created_at' => now()->subDays(10),
        ]);

        Activity::create([
            'log_name' => 'default',
            'description' => 'recent record',
            'event' => 'created',
            'properties' => [],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs?date_from='.now()->subDay()->toDateString().'&date_to='.now()->toDateString());

        $response->assertOk();
        $activities = $response->viewData('activities');
        expect($activities->total())->toBe(1);
    });

    it('displays correct stats', function () {
        Activity::create(['log_name' => 'default', 'description' => 'created', 'event' => 'created', 'properties' => []]);
        Activity::create(['log_name' => 'default', 'description' => 'updated', 'event' => 'updated', 'properties' => []]);
        Activity::create(['log_name' => 'default', 'description' => 'deleted', 'event' => 'deleted', 'properties' => []]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs');

        $stats = $response->viewData('stats');
        expect($stats['total'])->toBe(3)
            ->and($stats['created'])->toBe(1)
            ->and($stats['updated'])->toBe(1)
            ->and($stats['deleted'])->toBe(1);
    });

    it('can view audit log detail', function () {
        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'created',
            'event' => 'created',
            'subject_type' => 'App\\Models\\Employee',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => $this->superadmin->id,
            'properties' => ['attributes' => ['name' => 'Test']],
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/audit-logs/'.$activity->id);

        $response->assertOk()
            ->assertViewIs('superadmin.system.audit-logs.show')
            ->assertViewHas('activity');
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/audit-logs');

        $response->assertRedirect('/superadmin/login');
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->get('/superadmin/system/audit-logs');

        $response->assertRedirect('/login');
    });
});
