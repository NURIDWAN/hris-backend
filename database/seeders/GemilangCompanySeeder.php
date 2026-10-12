<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed the single PT Gemilang Sari Husada company used by the on-premise edition.
 *
 * This seeder is idempotent and safe to run multiple times.
 */
class GemilangCompanySeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $company = Company::updateOrCreate(
            ['name' => 'PT Gemilang Sari Husada'],
            [
                'slug' => 'pt-gemilang-sari-husada',
                'email' => 'info@gemilang.test',
                'phone' => '021-0000000',
                'address' => 'Jakarta, Indonesia',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'is_demo_mode' => false,
                'subscription_plan' => 'onpremise',
                'max_employees' => 9999,
                'enable_gps_validation' => true,
            ]
        );

        $this->createCompanyRoles($company->id);

        $this->createAdminUser($company->id);
    }

    /**
     * Create per-company (team-scoped) roles required for the on-premise edition.
     */
    protected function createCompanyRoles(int $companyId): void
    {
        $roles = [
            'admin' => Permission::pluck('name')->all(),
            'hr-manager' => [
                'view employees', 'create employees', 'edit employees',
                'import employees', 'export employees',
                'view attendance', 'create attendance', 'edit attendance',
                'approve attendance', 'export attendance',
                'view leaves', 'create leaves', 'edit leaves',
                'approve leaves', 'manage leave types',
                'view reports', 'export reports',
            ],
            'payroll-manager' => [
                'view employees',
                'view attendance', 'export attendance',
                'view leaves',
                'view payroll', 'create payroll', 'edit payroll',
                'process payroll', 'approve payroll', 'export payroll',
                'view salary components', 'create salary components',
                'edit salary components', 'delete salary components',
                'view reports', 'export reports',
            ],
            'employee' => [
                'view attendance', 'create attendance',
                'view leaves', 'create leaves',
            ],
        ];

        setPermissionsTeamId($companyId);
        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');

        foreach ($roles as $roleName => $permissions) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->where($teamColumn, $companyId)
                ->first();

            if (! $role) {
                $role = Role::query()->create([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    $teamColumn => $companyId,
                ]);
            }

            if (! empty($permissions)) {
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * Create (or update) the default admin user for the company.
     */
    protected function createAdminUser(int $companyId): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@gemilang.test'],
            [
                'company_id' => $companyId,
                'name' => 'Admin Gemilang',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_superadmin' => false,
            ]
        );

        setPermissionsTeamId($companyId);
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
