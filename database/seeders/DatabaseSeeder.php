<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Single-tenant (on-premise) edition: seed only the Gemilang dataset.
        if (single_tenant_mode()) {
            $this->call(GemilangCompanySeeder::class);
            $this->call(GemilangInitialDataSeeder::class);

            return;
        }

        // Create Super Admin (no company - team context null)
        setPermissionsTeamId(null);
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gajipro.com',
            'company_id' => null,
            'is_superadmin' => true,
        ]);
        $superAdmin->assignRole('super-admin');

        // Create demo company
        $demoCompany = Company::factory()->create([
            'name' => 'PT Demo GajiPro',
            'slug' => 'demo-gajipro',
            'email' => 'hr@demo.gajipro.com',
            'subscription_plan' => 'professional',
            'max_employees' => 100,
        ]);

        // Set team context for company roles
        setPermissionsTeamId($demoCompany->id);

        // Create Testing User with ALL roles (for staging/testing)
        $testingUser = User::factory()->create([
            'name' => 'Testing User (All Roles)',
            'email' => 'testing@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $testingUser->assignRole(['admin', 'hr-manager', 'payroll-manager', 'employee']);

        // Create individual role users for testing specific roles
        // Admin only
        $adminOnly = User::factory()->create([
            'name' => 'Admin Only',
            'email' => 'admin-only@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $adminOnly->assignRole('admin');

        // HR Manager only
        $hrOnly = User::factory()->create([
            'name' => 'HR Manager Only',
            'email' => 'hr-only@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $hrOnly->assignRole('hr-manager');

        // Payroll Manager only
        $payrollOnly = User::factory()->create([
            'name' => 'Payroll Manager Only',
            'email' => 'payroll-only@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $payrollOnly->assignRole('payroll-manager');

        // Employee only
        $employeeOnly = User::factory()->create([
            'name' => 'Employee Only',
            'email' => 'employee-only@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $employeeOnly->assignRole('employee');

        // Create company admin (legacy - shown on login page)
        $companyAdmin = User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $companyAdmin->assignRole(['admin', 'employee']);

        // Create HR Manager (legacy - shown on login page)
        $hrManager = User::factory()->create([
            'name' => 'HR Manager Demo',
            'email' => 'hr@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $hrManager->assignRole(['hr-manager', 'employee']);

        // Create Payroll Manager (legacy)
        $payrollManager = User::factory()->create([
            'name' => 'Payroll Manager Demo',
            'email' => 'payroll@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $payrollManager->assignRole(['payroll-manager', 'employee']);

        // Create Employee (shown on login page as "Karyawan")
        $karyawan = User::factory()->create([
            'name' => 'Karyawan Demo',
            'email' => 'karyawan@demo.gajipro.com',
            'company_id' => $demoCompany->id,
        ]);
        $karyawan->assignRole('employee');

        // Create some employee users
        $employeeUsers = User::factory()->count(5)->create([
            'company_id' => $demoCompany->id,
        ]);

        foreach ($employeeUsers as $empUser) {
            $empUser->assignRole('employee');
        }

        // Create departments
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Mengelola sumber daya manusia'],
            ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Pengembangan produk dan teknologi'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Pemasaran dan promosi'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Keuangan dan akuntansi'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operasional perusahaan'],
        ];

        foreach ($departments as $dept) {
            Department::create(array_merge($dept, ['company_id' => $demoCompany->id]));
        }

        // Create positions
        $positions = [
            ['name' => 'Manager', 'code' => 'MGR', 'level' => 3, 'base_salary' => 25000000],
            ['name' => 'Senior Staff', 'code' => 'SST', 'level' => 2, 'base_salary' => 15000000],
            ['name' => 'Staff', 'code' => 'STF', 'level' => 1, 'base_salary' => 8000000],
            ['name' => 'Junior Staff', 'code' => 'JST', 'level' => 1, 'base_salary' => 5000000],
            ['name' => 'Intern', 'code' => 'INT', 'level' => 0, 'base_salary' => 2500000],
        ];

        foreach ($positions as $pos) {
            Position::create(array_merge($pos, ['company_id' => $demoCompany->id]));
        }

        // Get created departments and positions
        $deptIds = Department::where('company_id', $demoCompany->id)->pluck('id')->toArray();
        $posIds = Position::where('company_id', $demoCompany->id)->pluck('id')->toArray();

        // Link demo users to Employee records so portal works
        $demoUserEmployees = [
            ['user' => $karyawan, 'first_name' => 'Karyawan', 'last_name' => 'Demo'],
            ['user' => $employeeOnly, 'first_name' => 'Employee', 'last_name' => 'Only'],
            ['user' => $testingUser, 'first_name' => 'Testing', 'last_name' => 'User'],
            ['user' => $companyAdmin, 'first_name' => 'Admin', 'last_name' => 'Demo'],
            ['user' => $hrManager, 'first_name' => 'HR Manager', 'last_name' => 'Demo'],
            ['user' => $payrollManager, 'first_name' => 'Payroll Manager', 'last_name' => 'Demo'],
        ];

        foreach ($demoUserEmployees as $entry) {
            Employee::factory()->create([
                'company_id' => $demoCompany->id,
                'user_id' => $entry['user']->id,
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name'],
                'email' => $entry['user']->email,
                'department_id' => $deptIds[array_rand($deptIds)],
                'position_id' => $posIds[array_rand($posIds)],
            ]);
        }

        // Create sample employees (without user accounts)
        for ($i = 0; $i < 20; $i++) {
            Employee::factory()->create([
                'company_id' => $demoCompany->id,
                'department_id' => $deptIds[array_rand($deptIds)],
                'position_id' => $posIds[array_rand($posIds)],
            ]);
        }

        // Seed employee salaries
        $this->call(EmployeeSalarySeeder::class);

        // Seed subscription plans, demo subscription, invoices & payments
        $this->call(DemoBillingSeeder::class);

        // Seed payment gateway settings (Xendit, Midtrans, Manual)
        $this->call(PaymentGatewaySeeder::class);
    }
}
