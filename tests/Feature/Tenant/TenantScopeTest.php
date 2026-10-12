<?php

use App\Models\Company;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Tenant Scope', function () {

    it('filters users by company when tenant is set', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        User::factory()->count(3)->create(['company_id' => $company1->id]);
        User::factory()->count(2)->create(['company_id' => $company2->id]);

        // Set tenant context to company1
        app()->instance('tenant', $company1);

        // Now querying should only return company1 users
        $users = User::query()->withGlobalScope('tenant', new TenantScope())->get();

        expect($users)->toHaveCount(3)
            ->and($users->pluck('company_id')->unique()->first())->toBe($company1->id);
    });

    it('does not filter when no tenant is set', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        User::factory()->count(3)->create(['company_id' => $company1->id]);
        User::factory()->count(2)->create(['company_id' => $company2->id]);

        // No tenant set
        $users = User::all();

        expect($users)->toHaveCount(5);
    });

    it('super admin can see all records', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        User::factory()->count(3)->create(['company_id' => $company1->id]);
        User::factory()->count(2)->create(['company_id' => $company2->id]);

        // Create super admin (no company)
        $superAdmin = User::factory()->create(['company_id' => null]);

        // When acting as super admin, should see all
        $this->actingAs($superAdmin);

        // Super admin should not have tenant restriction
        $users = User::all();

        expect($users)->toHaveCount(6); // 3 + 2 + 1 super admin
    });

});
