<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Company profile lock in single_mode', function () {

    it('prevents creating a second company when single_mode is enabled', function () {
        config(['tenant.single_mode' => true, 'tenant.company_id' => 1]);

        // First company is the locked one
        Company::factory()->create(['id' => 1, 'name' => 'PT Gemilang Sari Husada']);

        expect(fn () => Company::factory()->create(['name' => 'PT Another']))
            ->toThrow(\RuntimeException::class, 'single-tenant');
    });

    it('allows multiple companies when single_mode is disabled', function () {
        config(['tenant.single_mode' => false]);

        Company::factory()->create(['name' => 'PT Alpha']);
        $second = Company::factory()->create(['name' => 'PT Beta']);

        expect($second->exists)->toBeTrue();
        expect(Company::count())->toBe(2);
    });

    it('allows creating the locked company when no company exists yet', function () {
        config(['tenant.single_mode' => true, 'tenant.company_id' => 1]);

        $company = Company::factory()->create(['id' => 1, 'name' => 'PT Gemilang Sari Husada']);

        expect($company->exists)->toBeTrue();
        expect($company->id)->toBe(1);
    });

    it('allows updating the existing locked company in single_mode', function () {
        config(['tenant.single_mode' => true, 'tenant.company_id' => 1]);

        $company = Company::factory()->create(['id' => 1, 'name' => 'PT Gemilang Sari Husada']);

        $company->update(['phone' => '021-12345678']);

        expect($company->fresh()->phone)->toBe('021-12345678');
    });
});
