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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create or get demo company
        $company = Company::firstOrCreate(
            ['slug' => 'demo-gajipro'],
            [
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
            ]
        );

        // Set tenant context untuk Spatie Permission
        setPermissionsTeamId($company->id);

        // Create departments
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Mengelola sumber daya manusia'],
            ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Pengembangan produk dan teknologi'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Pemasaran dan promosi'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Keuangan dan akuntansi'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operasional perusahaan'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['company_id' => $company->id, 'code' => $dept['code']],
                ['name' => $dept['name'], 'description' => $dept['description']]
            );
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
            Position::firstOrCreate(
                ['company_id' => $company->id, 'code' => $pos['code']],
                ['name' => $pos['name'], 'level' => $pos['level'], 'base_salary' => $pos['base_salary']]
            );
        }

        // Create work schedule
        $workSchedule = WorkSchedule::firstOrCreate(
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

        // Create office locations
        $officeHQ = OfficeLocation::firstOrCreate(
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

        $officeBranch = OfficeLocation::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'BR-001'],
            [
                'name' => 'Kantor Cabang Bandung',
                'address' => 'Jl. Asia Afrika No. 10, Bandung',
                'latitude' => -6.917464,
                'longitude' => 107.619123,
                'radius' => 150,
                'is_active' => true,
            ]
        );

        // Get departments and positions
        $hrDept = Department::where('company_id', $company->id)->where('code', 'HR')->first();
        $engDept = Department::where('company_id', $company->id)->where('code', 'ENG')->first();
        $mgrPos = Position::where('company_id', $company->id)->where('code', 'MGR')->first();
        $staffPos = Position::where('company_id', $company->id)->where('code', 'STF')->first();

        // Create admin user with employee record
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.gajipro.com'],
            [
                'company_id' => $company->id,
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Assign role jika belum punya role apapun (cek langsung ke DB untuk handle company_id null)
        $hasRole = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $admin->id)
            ->exists();
        if (! $hasRole) {
            $admin->assignRole('admin');
        }

        // Create employee record for admin
        $adminEmployee = Employee::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'company_id' => $company->id,
                'department_id' => $hrDept->id,
                'position_id' => $mgrPos->id,
                'work_schedule_id' => $workSchedule->id,
                'employee_id' => 'DEMO'.date('Y').'001',
                'first_name' => 'Admin',
                'last_name' => 'Demo',
                'email' => 'admin@demo.gajipro.com',
                'phone' => '081234567890',
                'hire_date' => now()->subYears(2),
                'employment_status' => 'permanent',
                'is_active' => true,
            ]
        );

        // Assign offices to admin
        if (! $adminEmployee->officeLocations()->exists()) {
            $adminEmployee->officeLocations()->attach([
                $officeHQ->id => ['is_primary' => true],
                $officeBranch->id => ['is_primary' => false],
            ]);
        }

        // Create HR Manager user with employee record
        $hrManager = User::firstOrCreate(
            ['email' => 'hr@demo.gajipro.com'],
            [
                'company_id' => $company->id,
                'name' => 'HR Manager Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Assign role jika belum punya role apapun
        $hasRole = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $hrManager->id)
            ->exists();
        if (! $hasRole) {
            $hrManager->assignRole('hr-manager');
        }

        $hrEmployee = Employee::firstOrCreate(
            ['user_id' => $hrManager->id],
            [
                'company_id' => $company->id,
                'department_id' => $hrDept->id,
                'position_id' => $mgrPos->id,
                'work_schedule_id' => $workSchedule->id,
                'employee_id' => 'DEMO'.date('Y').'002',
                'first_name' => 'HR',
                'last_name' => 'Manager',
                'email' => 'hr@demo.gajipro.com',
                'phone' => '081234567891',
                'hire_date' => now()->subYears(1),
                'employment_status' => 'permanent',
                'is_active' => true,
            ]
        );

        if (! $hrEmployee->officeLocations()->exists()) {
            $hrEmployee->officeLocations()->attach($officeHQ->id, ['is_primary' => true]);
        }

        // Create regular employee user
        $employee = User::firstOrCreate(
            ['email' => 'karyawan@demo.gajipro.com'],
            [
                'company_id' => $company->id,
                'name' => 'Karyawan Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Assign role jika belum punya role apapun
        $hasRole = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $employee->id)
            ->exists();
        if (! $hasRole) {
            $employee->assignRole('employee');
        }

        $empRecord = Employee::firstOrCreate(
            ['user_id' => $employee->id],
            [
                'company_id' => $company->id,
                'department_id' => $engDept->id,
                'position_id' => $staffPos->id,
                'work_schedule_id' => $workSchedule->id,
                'employee_id' => 'DEMO'.date('Y').'003',
                'first_name' => 'Karyawan',
                'last_name' => 'Demo',
                'email' => 'karyawan@demo.gajipro.com',
                'phone' => '081234567892',
                'hire_date' => now()->subMonths(6),
                'employment_status' => 'permanent',
                'is_active' => true,
            ]
        );

        if (! $empRecord->officeLocations()->exists()) {
            $empRecord->officeLocations()->attach($officeHQ->id, ['is_primary' => true]);
        }

        // Create additional sample employees without user accounts
        $deptIds = Department::where('company_id', $company->id)->pluck('id')->toArray();
        $posIds = Position::where('company_id', $company->id)->pluck('id')->toArray();

        $existingCount = Employee::where('company_id', $company->id)->count();
        $toCreate = max(0, 25 - $existingCount);

        if ($toCreate > 0) {
            Employee::factory()->count($toCreate)->create([
                'company_id' => $company->id,
                'department_id' => fn () => $deptIds[array_rand($deptIds)],
                'position_id' => fn () => $posIds[array_rand($posIds)],
                'work_schedule_id' => $workSchedule->id,
            ]);
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@demo.gajipro.com', 'password'],
                ['HR Manager', 'hr@demo.gajipro.com', 'password'],
                ['Karyawan', 'karyawan@demo.gajipro.com', 'password'],
            ]
        );
    }
}
