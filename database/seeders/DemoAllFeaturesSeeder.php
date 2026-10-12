<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master Seeder untuk SEMUA fitur Demo PT GajiPro
 *
 * Seeder ini menjalankan semua seeder demo secara berurutan:
 *
 * 1. DemoDataSeeder - Data dasar (company, users, departments, positions)
 * 2. DemoOrganizationStructureSeeder - Struktur organisasi lengkap
 * 3. DemoEmployeeDataSeeder - Data karyawan realistis (52 aktif + 5 ex-employee)
 * 4. DemoOfficeLocationSeeder - Lokasi kantor dan assignment karyawan
 * 5. DemoTaxBpjsSettingsSeeder - Konfigurasi PPh21 dan BPJS
 * 6. DemoApprovalWorkflowSeeder - Workflow approval (cuti, lembur, expense, reimbursement)
 * 7. DemoBillingSeeder - Subscription plans, invoice, dan payment
 * 8. DemoAttendanceDataSeeder - Data absensi, cuti, dan lembur
 * 9. DemoPayrollDataSeeder - Data gaji, tunjangan, dan potongan
 *
 * Cara menjalankan:
 * php artisan db:seed --class=DemoAllFeaturesSeeder
 *
 * PERINGATAN: Seeder ini akan menghapus dan membuat ulang data demo!
 */
class DemoAllFeaturesSeeder extends Seeder
{
    /**
     * Daftar seeder yang akan dijalankan secara berurutan.
     * Urutan PENTING karena ada dependency antar seeder.
     */
    private array $seeders = [
        // 1. Data dasar - HARUS pertama
        DemoDataSeeder::class,

        // 2. Struktur organisasi (departments, positions hierarchy)
        DemoOrganizationStructureSeeder::class,

        // 3. Data karyawan lengkap (depends on: departments, positions)
        DemoEmployeeDataSeeder::class,

        // 4. Lokasi kantor dan assignment (depends on: employees)
        DemoOfficeLocationSeeder::class,

        // 5. Konfigurasi Pajak & BPJS
        DemoTaxBpjsSettingsSeeder::class,

        // 6. Approval Workflows
        DemoApprovalWorkflowSeeder::class,

        // 7. Billing & Subscription
        DemoBillingSeeder::class,

        // 8. Attendance data (depends on: employees, office locations)
        DemoAttendanceDataSeeder::class,

        // 9. Payroll data (depends on: employees, attendance)
        DemoPayrollDataSeeder::class,

        // 10. Bukti Potong 1721-A1 (depends on: employees, payroll)
        DemoTaxForm1721A1Seeder::class,

        // 11. SPT Tahunan 1721 (depends on: tax forms 1721-A1)
        DemoSpt1721Seeder::class,
    ];

    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║           DEMO ALL FEATURES SEEDER - PT DEMO GAJIPRO          ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        $startTime = microtime(true);
        $totalSeeders = count($this->seeders);
        $currentSeeder = 0;

        foreach ($this->seeders as $seederClass) {
            $currentSeeder++;
            $seederName = class_basename($seederClass);

            $this->command->newLine();
            $this->command->info('┌─────────────────────────────────────────────────────────────────┐');
            $this->command->info("│ [{$currentSeeder}/{$totalSeeders}] Running: {$seederName}");
            $this->command->info('└─────────────────────────────────────────────────────────────────┘');

            $seederStartTime = microtime(true);

            try {
                $this->call($seederClass);
                $seederDuration = round(microtime(true) - $seederStartTime, 2);
                $this->command->info("✓ Completed in {$seederDuration}s");
            } catch (\Exception $e) {
                $this->command->error("✗ Error: {$e->getMessage()}");
                $this->command->error("  File: {$e->getFile()}:{$e->getLine()}");

                // Ask to continue or abort
                if (! $this->command->confirm('Continue with remaining seeders?', false)) {
                    $this->command->error('Seeding aborted by user.');

                    return;
                }
            }
        }

        $totalDuration = round(microtime(true) - $startTime, 2);

        $this->printFinalSummary($totalDuration);
    }

    private function printFinalSummary(float $totalDuration): void
    {
        $this->command->newLine(2);
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                    SEEDING COMPLETED!                         ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // Summary statistics
        $this->command->info('📊 DATA SUMMARY:');
        $this->command->line('─────────────────────────────────────────────────────────────────');

        $stats = $this->collectStatistics();

        $this->command->table(
            ['Category', 'Count', 'Details'],
            $stats
        );

        $this->command->newLine();
        $this->command->info('👤 LOGIN CREDENTIALS:');
        $this->command->line('─────────────────────────────────────────────────────────────────');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@demo.gajipro.com', 'password'],
                ['HR Manager', 'hr@demo.gajipro.com', 'password'],
                ['Payroll Manager', 'payroll@demo.gajipro.com', 'password'],
                ['Employee', 'karyawan@demo.gajipro.com', 'password'],
            ]
        );

        $this->command->newLine();
        $this->command->info("⏱️  Total execution time: {$totalDuration} seconds");
        $this->command->newLine();
        $this->command->info('🎉 Demo data untuk PT Demo GajiPro berhasil dibuat!');
        $this->command->newLine();
    }

    private function collectStatistics(): array
    {
        $stats = [];

        // Company
        $companyCount = DB::table('companies')->where('name', 'like', '%Demo%')->count();
        $stats[] = ['Company', $companyCount, 'PT Demo GajiPro'];

        // Users
        $userCount = DB::table('users')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Users', $userCount, 'Admin, HR, Payroll, Employees'];

        // Employees
        $employeeCount = DB::table('employees')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $activeCount = DB::table('employees')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->where('is_active', true)
            ->count();
        $stats[] = ['Employees', $employeeCount, "{$activeCount} active, ".($employeeCount - $activeCount).' inactive'];

        // Departments
        $deptCount = DB::table('departments')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Departments', $deptCount, 'Organization structure'];

        // Positions
        $posCount = DB::table('positions')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Positions', $posCount, 'Job positions'];

        // Office Locations
        $officeCount = DB::table('office_locations')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Office Locations', $officeCount, 'HQ + Branch offices'];

        // Attendance
        $attendanceCount = DB::table('attendances')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Attendance Records', $attendanceCount, 'Clock in/out data'];

        // Leave Requests
        $leaveCount = DB::table('leave_requests')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Leave Requests', $leaveCount, 'Cuti, izin, sakit'];

        // Overtime
        $overtimeCount = DB::table('overtime_requests')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Overtime Requests', $overtimeCount, 'Pengajuan lembur'];

        // Payroll
        $payrollCount = DB::table('payrolls')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Payroll Periods', $payrollCount, 'Periode penggajian'];

        // Salary Settings (employee_salaries table)
        $salaryCount = DB::table('employee_salaries')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Salary Settings', $salaryCount, 'Pengaturan gaji'];

        // Reimbursements
        $reimbCount = DB::table('reimbursements')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Reimbursements', $reimbCount, 'Pengajuan reimburse'];

        // Approval Workflows
        $workflowCount = DB::table('approval_workflows')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Approval Workflows', $workflowCount, 'Leave, overtime, expense, reimburse'];

        // Subscription Plans
        $planCount = DB::table('subscription_plans')->count();
        $stats[] = ['Subscription Plans', $planCount, 'Free, Starter, Pro, Enterprise'];

        // Exit Records
        $exitCount = DB::table('employee_exits')
            ->whereIn('company_id', DB::table('companies')->where('name', 'like', '%Demo%')->pluck('id'))
            ->count();
        $stats[] = ['Exit Records', $exitCount, 'Resign, PHK, pensiun'];

        return $stats;
    }
}
