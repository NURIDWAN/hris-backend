<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    $this->company = Company::factory()->create();
    config(['tenant.company_id' => $this->company->id]);
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');
});

describe('Billing disabled in single_mode', function () {

    it('returns 404 for /settings/billing in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->actingAs($this->admin)->get('/settings/billing');

        $response->assertNotFound();
    });

    it('returns 404 for /settings/billing/upgrade in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->actingAs($this->admin)->get('/settings/billing/upgrade');

        $response->assertNotFound();
    });

    it('returns 404 for /subscription-expired in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/subscription-expired');

        $response->assertNotFound();
    });

    it('allows /settings/billing in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->actingAs($this->admin)->get('/settings/billing');

        $response->assertStatus(200);
    });

    it('allows /subscription-expired page in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/subscription-expired');

        $response->assertStatus(200);
    });
});
