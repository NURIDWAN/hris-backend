<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions by module
        $permissions = [
            // Company Management (Super Admin only)
            'view companies',
            'create companies',
            'edit companies',
            'delete companies',
            'manage subscriptions',

            // Employee Management
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'import employees',
            'export employees',

            // Attendance Management
            'view attendance',
            'create attendance',
            'edit attendance',
            'approve attendance',
            'export attendance',

            // Leave Management
            'view leaves',
            'create leaves',
            'edit leaves',
            'approve leaves',
            'manage leave types',

            // Payroll Management
            'view payroll',
            'create payroll',
            'edit payroll',
            'process payroll',
            'approve payroll',
            'export payroll',

            // Salary Components
            'view salary components',
            'create salary components',
            'edit salary components',
            'delete salary components',

            // Reports
            'view reports',
            'export reports',

            // Settings
            'view settings',
            'edit settings',

            // Users & Roles
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view employees', 'create employees', 'edit employees', 'delete employees', 'import employees', 'export employees',
            'view attendance', 'create attendance', 'edit attendance', 'approve attendance', 'export attendance',
            'view leaves', 'create leaves', 'edit leaves', 'approve leaves', 'manage leave types',
            'view payroll', 'create payroll', 'edit payroll', 'process payroll', 'approve payroll', 'export payroll',
            'view salary components', 'create salary components', 'edit salary components', 'delete salary components',
            'view reports', 'export reports',
            'view settings', 'edit settings',
            'view users', 'create users', 'edit users', 'delete users', 'manage roles',
        ]);

        $hrManager = Role::firstOrCreate(['name' => 'hr-manager']);
        $hrManager->givePermissionTo([
            'view employees', 'create employees', 'edit employees', 'import employees', 'export employees',
            'view attendance', 'create attendance', 'edit attendance', 'approve attendance', 'export attendance',
            'view leaves', 'create leaves', 'edit leaves', 'approve leaves', 'manage leave types',
            'view reports', 'export reports',
        ]);

        $payrollManager = Role::firstOrCreate(['name' => 'payroll-manager']);
        $payrollManager->givePermissionTo([
            'view employees',
            'view attendance', 'export attendance',
            'view leaves',
            'view payroll', 'create payroll', 'edit payroll', 'process payroll', 'approve payroll', 'export payroll',
            'view salary components', 'create salary components', 'edit salary components', 'delete salary components',
            'view reports', 'export reports',
        ]);

        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->givePermissionTo([
            'view attendance',
            'create attendance',
            'view leaves',
            'create leaves',
        ]);
    }
}
