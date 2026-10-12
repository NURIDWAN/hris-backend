<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create super-admin as a global role (no team context)
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Tenant Middleware', function () {

    it('sets tenant context from authenticated user company', function () {
        $company = Company::factory()->create();
        createStandardRoles($company->id);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);

        // Tenant should be set in app container
        expect(app()->bound('tenant'))->toBeTrue()
            ->and(app('tenant')->id)->toBe($company->id);
    });

    it('does not set tenant for super admin', function () {
        setPermissionsTeamId(null);
        $superAdmin = User::factory()->create(['company_id' => null]);
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);

        // Tenant should not be set for super admin
        expect(app()->bound('tenant'))->toBeFalse();
    });

    it('blocks access if company subscription expired', function () {
        $company = Company::factory()->create([
            'subscription_ends_at' => now()->subDay(),
            'is_active' => true,
        ]);
        createStandardRoles($company->id);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertRedirect('/subscription-expired');
    });

    it('allows access if company subscription is valid', function () {
        $company = Company::factory()->create([
            'subscription_ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);
        createStandardRoles($company->id);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
    });

});
