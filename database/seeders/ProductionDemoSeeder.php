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

/**
 * Production-safe seeder that creates/updates demo accounts without affecting existing data.
 * Uses updateOrCreate to safely update existing records or create new ones.
 */
class ProductionDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin (no company)
        $superAdmin = User::withTrashed()->where('email', 'superadmin@gajipro.com')->first();

        if ($superAdmin) {
            if ($superAdmin->trashed()) {
                $superAdmin->restore();
            }
            $superAdmin->update([
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_superadmin' => true,
                'company_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        } else {
            $superAdmin = User::create([
                'email' => 'superadmin@gajipro.com',
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_superadmin' => true,
                'company_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
        $this->command->info('✓ Super Admin created/updated');

        // 2. Demo Company
        $company = Company::withTrashed()->where('slug', 'demo-gajipro')->first();

        if ($company) {
            if ($company->trashed()) {
                $company->restore();
            }
            $company->update([
                'name' => 'PT Demo GajiPro',
                'email' => 'demo@gajipro.com',
                'phone' => '021-12345678',
                'address' => 'Jl. Demo No. 123, Jakarta, DKI Jakarta',
                'subscription_plan' => 'professional',
                'subscription_ends_at' => now()->addYear(),
                'max_employees' => 100,
                'is_active' => true,
                'enable_face_recognition' => true,
                'enable_gps_validation' => true,
                'face_match_threshold' => 0.6,
            ]);
        } else {
            $company = Company::create([
                'slug' => 'demo-gajipro',
                'name' => 'PT Demo GajiPro',
                'email' => 'demo@gajipro.com',
                'phone' => '021-12345678',
                'address' => 'Jl. Demo No. 123, Jakarta, DKI Jakarta',
                'subscription_plan' => 'professional',
                'subscription_ends_at' => now()->addYear(),
                'max_employees' => 100,
                'is_active' => true,
                'enable_face_recognition' => true,
                'enable_gps_validation' => true,
                'face_match_threshold' => 0.6,
            ]);
        }
        $this->command->info('✓ Demo Company created/updated');

        // 3. Departments
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Mengelola sumber daya manusia'],
            ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Pengembangan produk dan teknologi'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Pemasaran dan promosi'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Keuangan dan akuntansi'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operasional perusahaan'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['company_id' => $company->id, 'code' => $dept['code']],
                $dept
            );
        }
        $this->command->info('✓ Departments created/updated');

        // 4. Positions
        $positions = [
            ['name' => 'Manager', 'code' => 'MGR', 'level' => 3, 'base_salary' => 25000000],
            ['name' => 'Senior Staff', 'code' => 'SST', 'level' => 2, 'base_salary' => 15000000],
            ['name' => 'Staff', 'code' => 'STF', 'level' => 1, 'base_salary' => 8000000],
            ['name' => 'Junior Staff', 'code' => 'JST', 'level' => 1, 'base_salary' => 5000000],
            ['name' => 'Intern', 'code' => 'INT', 'level' => 0, 'base_salary' => 2500000],
        ];

        foreach ($positions as $pos) {
            Position::updateOrCreate(
                ['company_id' => $company->id, 'code' => $pos['code']],
                $pos
            );
        }
        $this->command->info('✓ Positions created/updated');

        // 5. Work Schedule
        $workSchedule = WorkSchedule::updateOrCreate(
            ['company_id' => $company->id, 'code' => 'REG-001'],
            [
                'name' => 'Regular Office Hours',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_tolerance' => 15,
                'early_leave_tolerance' => 15,
                'is_flexible' => false,
                'is_default' => true,
                'is_active' => true,
            ]
        );
        $this->command->info('✓ Work Schedule created/updated');

        // 6. Office Locations
        $officeHQ = OfficeLocation::updateOrCreate(
            ['company_id' => $company->id, 'code' => 'HQ-001'],
            [
                'name' => 'Kantor Pusat Jakarta',
                'address' => 'Jl. Sudirman No. 1, Jakarta Pusat',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => true,
            ]
        );
        $this->command->info('✓ Office Locations created/updated');

        // Get references
        $hrDept = Department::where('company_id', $company->id)->where('code', 'HR')->first();
        $engDept = Department::where('company_id', $company->id)->where('code', 'ENG')->first();
        $mgrPos = Position::where('company_id', $company->id)->where('code', 'MGR')->first();
        $staffPos = Position::where('company_id', $company->id)->where('code', 'STF')->first();

        // 7. Demo Users
        $demoUsers = [
            [
                'email' => 'admin@demo.gajipro.com',
                'name' => 'Admin Demo',
                'role' => 'admin',
                'department' => $hrDept,
                'position' => $mgrPos,
                'first_name' => 'Admin',
                'last_name' => 'Demo',
                'employee_id' => 'DEMO'.date('Y').'001',
            ],
            [
                'email' => 'hr@demo.gajipro.com',
                'name' => 'HR Manager Demo',
                'role' => 'hr-manager',
                'department' => $hrDept,
                'position' => $mgrPos,
                'first_name' => 'HR',
                'last_name' => 'Manager',
                'employee_id' => 'DEMO'.date('Y').'002',
            ],
            [
                'email' => 'karyawan@demo.gajipro.com',
                'name' => 'Karyawan Demo',
                'role' => 'employee',
                'department' => $engDept,
                'position' => $staffPos,
                'first_name' => 'Karyawan',
                'last_name' => 'Demo',
                'employee_id' => 'DEMO'.date('Y').'003',
            ],
        ];

        foreach ($demoUsers as $userData) {
            // Find user including soft-deleted to handle restore
            $user = User::withTrashed()->where('email', $userData['email'])->first();

            if ($user) {
                // Restore if soft-deleted
                if ($user->trashed()) {
                    $user->restore();
                }

                // Update existing user
                $user->update([
                    'company_id' => $company->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'email' => $userData['email'],
                    'company_id' => $company->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }

            // Sync role (remove old roles, assign new)
            $user->syncRoles([$userData['role']]);

            // Find existing employee by email OR employee_id (include soft-deleted to avoid unique constraint violation)
            $employee = Employee::withTrashed()
                ->where('company_id', $company->id)
                ->where(function ($query) use ($userData) {
                    $query->where('email', $userData['email'])
                        ->orWhere('employee_id', $userData['employee_id']);
                })
                ->first();

            if ($employee) {
                // Restore if soft-deleted
                if ($employee->trashed()) {
                    $employee->restore();
                }

                // Update existing employee
                $employee->update([
                    'user_id' => $user->id,
                    'department_id' => $userData['department']->id,
                    'position_id' => $userData['position']->id,
                    'work_schedule_id' => $workSchedule->id,
                    'employee_id' => $userData['employee_id'],
                    'email' => $userData['email'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'is_active' => true,
                ]);
            } else {
                // Create new employee
                $employee = Employee::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'department_id' => $userData['department']->id,
                    'position_id' => $userData['position']->id,
                    'work_schedule_id' => $workSchedule->id,
                    'employee_id' => $userData['employee_id'],
                    'email' => $userData['email'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'phone' => '08123456789'.substr($userData['employee_id'], -1),
                    'hire_date' => now()->subMonths(6),
                    'employment_status' => 'permanent',
                    'is_active' => true,
                ]);
            }

            // Sync office location
            $employee->officeLocations()->syncWithoutDetaching([
                $officeHQ->id => ['is_primary' => true],
            ]);

            $this->command->info("✓ {$userData['name']} ({$userData['email']}) created/updated");
        }

        $this->command->newLine();
        $this->command->info('=== Production Demo Seeder Complete ===');
        $this->command->newLine();
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'superadmin@gajipro.com', 'password'],
                ['Admin', 'admin@demo.gajipro.com', 'password'],
                ['HR Manager', 'hr@demo.gajipro.com', 'password'],
                ['Karyawan', 'karyawan@demo.gajipro.com', 'password'],
            ]
        );
    }
}
