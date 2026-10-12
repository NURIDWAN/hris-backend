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

describe('Single Tenant Config', function () {

    it('has single_mode disabled by default in test env', function () {
        // In testing, default should be multi-tenant (false) unless explicitly overridden.
        config(['tenant.single_mode' => false]);
        expect(single_tenant_mode())->toBeFalse();
    });

    it('has locked company_id default of 1', function () {
        expect(config('tenant.company_id'))->toBe(1);
    });

    it('exposes single_mode flag via helper', function () {
        expect(function_exists('single_tenant_mode'))->toBeTrue();
    });

    it('returns false from single_tenant_mode() when disabled', function () {
        config(['tenant.single_mode' => false]);
        expect(single_tenant_mode())->toBeFalse();
    });

    it('returns true from single_tenant_mode() when enabled', function () {
        config(['tenant.single_mode' => true]);
        expect(single_tenant_mode())->toBeTrue();
    });
});

describe('Single Tenant Middleware Behavior', function () {

    it('skips subscription expired check in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $company = Company::factory()->create([
            'subscription_ends_at' => now()->subYear(), // expired
            'is_active' => true,
        ]);
        config(['tenant.company_id' => $company->id]);
        createStandardRoles($company->id);

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        // Should NOT redirect to /subscription-expired in single_mode
        $response->assertStatus(200);
    });

    it('still enforces subscription check in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $company = Company::factory()->create([
            'subscription_ends_at' => now()->subYear(),
            'is_active' => true,
        ]);
        createStandardRoles($company->id);

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertRedirect('/subscription-expired');
    });

    it('sets tenant context in single_mode for logged-in user', function () {
        config(['tenant.single_mode' => true]);

        $company = Company::factory()->create();
        config(['tenant.company_id' => $company->id]);
        createStandardRoles($company->id);

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);
        $this->get('/dashboard');

        expect(app()->bound('tenant'))->toBeTrue()
            ->and(app('tenant')->id)->toBe($company->id);
    });
});
