<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\GemilangCompanySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    // RolePermissionSeeder creates base permissions + global roles
    $this->seed(RolePermissionSeeder::class);
});

describe('GemilangCompanySeeder', function () {

    it('creates PT Gemilang Sari Husada company with locked company ID', function () {
        config(['tenant.company_id' => 1]);

        $this->seed(GemilangCompanySeeder::class);

        $company = Company::find(1);
        expect($company)->not->toBeNull()
            ->and($company->name)->toBe('PT Gemilang Sari Husada')
            ->and($company->is_active)->toBeTrue()
            ->and($company->timezone)->toBe('Asia/Jakarta');
    });

    it('creates company-scoped roles (admin, hr-manager, payroll-manager, employee)', function () {
        $this->seed(GemilangCompanySeeder::class);

        $company = Company::where('name', 'PT Gemilang Sari Husada')->first();

        setPermissionsTeamId($company->id);

        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');

        $expectedRoles = ['admin', 'hr-manager', 'payroll-manager', 'employee'];
        foreach ($expectedRoles as $name) {
            $role = Role::where('name', $name)
                ->where($teamColumn, $company->id)
                ->first();
            expect($role)->not->toBeNull("Role {$name} should exist for company {$company->id}");
        }
    });

    it('creates a default admin user for the company', function () {
        $this->seed(GemilangCompanySeeder::class);

        $company = Company::where('name', 'PT Gemilang Sari Husada')->first();

        $admin = User::where('company_id', $company->id)
            ->where('email', 'admin@gemilang.test')
            ->first();

        expect($admin)->not->toBeNull()
            ->and($admin->company_id)->toBe($company->id);

        setPermissionsTeamId($company->id);
        expect($admin->hasRole('admin'))->toBeTrue();
    });

    it('is idempotent - running twice does not duplicate data', function () {
        $this->seed(GemilangCompanySeeder::class);
        $this->seed(GemilangCompanySeeder::class);

        expect(Company::where('name', 'PT Gemilang Sari Husada')->count())->toBe(1);
        expect(User::where('email', 'admin@gemilang.test')->count())->toBe(1);
    });
});
