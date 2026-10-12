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

describe('Sidebar in single_mode', function () {

    it('hides Billing menu in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('settings.billing.index'));
    });

    it('shows Billing menu in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('settings.billing.index'));
    });
});
