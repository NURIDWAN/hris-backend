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

describe('Brand configuration', function () {

    it('exposes tenant.brand.name config key', function () {
        expect(config()->has('tenant.brand.name'))->toBeTrue();
    });

    it('exposes tenant.brand.logo_path config key', function () {
        expect(config()->has('tenant.brand.logo_path'))->toBeTrue();
    });

    it('exposes tenant.brand.favicon_path config key', function () {
        expect(config()->has('tenant.brand.favicon_path'))->toBeTrue();
    });

    it('exposes tenant.brand.primary_color config key', function () {
        expect(config()->has('tenant.brand.primary_color'))->toBeTrue();
    });

    it('defaults brand.name to GajiPro when not in single_mode', function () {
        config(['tenant.single_mode' => false]);

        expect(brand_name())->toBe(config('app.name'));
    });

    it('uses tenant.brand.name when in single_mode', function () {
        config([
            'tenant.single_mode' => true,
            'tenant.brand.name' => 'HR Gemilang',
        ]);

        expect(brand_name())->toBe('HR Gemilang');
    });

    it('renders brand name on login page', function () {
        config([
            'tenant.single_mode' => true,
            'tenant.brand.name' => 'HR Gemilang',
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('HR Gemilang');
    });

    it('renders brand name on admin layout', function () {
        $company = Company::factory()->create();
        config([
            'tenant.company_id' => $company->id,
            'tenant.single_mode' => true,
            'tenant.brand.name' => 'HR Gemilang',
        ]);
        createStandardRoles($company->id);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('HR Gemilang');
    });
});
