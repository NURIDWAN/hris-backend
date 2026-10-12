<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionEmployeeSeeder extends Seeder
{
    /**
     * Seeder untuk membuat akun karyawan di server production.
     * Jalankan: php artisan db:seed --class=ProductionEmployeeSeeder
     */
    public function run(): void
    {
        $this->command->info('Creating production employee accounts for gajipro.com...');

        // Get or create demo company
        $company = Company::firstOrCreate(
            ['slug' => 'pt-gajipro'],
            [
                'name' => 'PT GajiPro Indonesia',
                'email' => 'hr@gajipro.com',
                'phone' => '021-12345678',
                'address' => 'Jakarta, Indonesia',
                'subscription_plan' => 'professional',
                'max_employees' => 100,
                'is_active' => true,
            ]
        );

        $this->command->info("Company: {$company->name} (ID: {$company->id})");

        // Create or get department
        $department = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'ENG'],
            [
                'name' => 'Engineering',
                'description' => 'Software Engineering Department',
            ]
        );

        // Create or get position
        $position = Position::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'STF'],
            [
                'name' => 'Staff',
                'level' => 1,
                'base_salary' => 8000000,
            ]
        );

        // Create or get work schedule
        $workSchedule = WorkSchedule::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Regular'],
            [
                'code' => 'REG',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5], // Mon-Fri
                'late_tolerance' => 15,
                'early_leave_tolerance' => 15,
                'is_flexible' => false,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // Create or get office location
        $officeLocation = OfficeLocation::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'HQ'],
            [
                'name' => 'Kantor Pusat',
                'address' => 'Jakarta, Indonesia',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_active' => true,
                'is_headquarters' => true,
            ]
        );

        // Create employee accounts
        $employees = [
            [
                'email' => 'employee@gajipro.com',
                'name' => 'Karyawan Demo',
                'first_name' => 'Karyawan',
                'last_name' => 'Demo',
                'phone' => '081234567890',
                'role' => 'employee',
            ],
            [
                'email' => 'budi@gajipro.com',
                'name' => 'Budi Santoso',
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'phone' => '081234567891',
                'role' => 'employee',
            ],
            [
                'email' => 'siti@gajipro.com',
                'name' => 'Siti Rahayu',
                'first_name' => 'Siti',
                'last_name' => 'Rahayu',
                'phone' => '081234567892',
                'role' => 'employee',
            ],
            [
                'email' => 'manager@gajipro.com',
                'name' => 'Manager Demo',
                'first_name' => 'Manager',
                'last_name' => 'Demo',
                'phone' => '081234567893',
                'role' => ['employee', 'hr-manager'],
            ],
            [
                'email' => 'admin@gajipro.com',
                'name' => 'Admin Demo',
                'first_name' => 'Admin',
                'last_name' => 'Demo',
                'phone' => '081234567894',
                'role' => ['employee', 'admin'],
            ],
        ];

        foreach ($employees as $empData) {
            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $empData['email']],
                [
                    'name' => $empData['name'],
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'email_verified_at' => now(),
                ]
            );

            // Assign roles
            $roles = is_array($empData['role']) ? $empData['role'] : [$empData['role']];
            $user->syncRoles($roles);

            // Create or update employee record
            $employee = Employee::updateOrCreate(
                ['email' => $empData['email'], 'company_id' => $company->id],
                [
                    'user_id' => $user->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'work_schedule_id' => $workSchedule->id,
                    'first_name' => $empData['first_name'],
                    'last_name' => $empData['last_name'],
                    'phone' => $empData['phone'],
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'male',
                    'marital_status' => 'single',
                    'religion' => 'islam',
                    'identity_number' => '1234567890'.rand(100000, 999999),
                    'address' => 'Jakarta, Indonesia',
                    'city' => 'Jakarta',
                    'province' => 'DKI Jakarta',
                    'postal_code' => '12345',
                    'hire_date' => '2024-01-01',
                    'employment_status' => 'permanent',
                    'base_salary' => $position->base_salary ?? 8000000,
                    'bank_name' => 'BCA',
                    'bank_account_number' => '1234567890',
                    'bank_account_name' => $empData['name'],
                    'tax_status' => 'TK/0',
                    'is_active' => true,
                ]
            );

            // Assign office location
            $employee->officeLocations()->syncWithoutDetaching([
                $officeLocation->id => ['is_primary' => true],
            ]);

            $this->command->info("Created: {$empData['email']} (User ID: {$user->id}, Employee ID: {$employee->id})");
        }

        $this->command->newLine();
        $this->command->info('=================================================');
        $this->command->info('Production employee accounts created successfully!');
        $this->command->info('=================================================');
        $this->command->newLine();
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['employee@gajipro.com', 'password', 'employee'],
                ['budi@gajipro.com', 'password', 'employee'],
                ['siti@gajipro.com', 'password', 'employee'],
                ['manager@gajipro.com', 'password', 'employee, hr-manager'],
                ['admin@gajipro.com', 'password', 'employee, admin'],
            ]
        );
    }
}
