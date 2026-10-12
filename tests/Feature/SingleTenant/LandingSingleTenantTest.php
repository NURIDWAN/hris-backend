<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Landing page in single-tenant mode', function () {

    it('redirects root to login when single_mode and guest', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    });

    it('redirects root to dashboard when single_mode and authenticated admin', function () {
        $company = Company::factory()->create();
        config([
            'tenant.single_mode' => true,
            'tenant.company_id' => $company->id,
        ]);
        createStandardRoles($company->id);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect(route('dashboard'));
    });

    it('renders SaaS landing when multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('pages.landing');
    });

    it('aborts pricing page in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/pricing');

        $response->assertNotFound();
    });

    it('renders pricing page in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/pricing');

        $response->assertOk();
    });

    it('aborts subscription-expired in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/subscription-expired');

        $response->assertNotFound();
    });
});
