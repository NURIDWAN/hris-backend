<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BpjsKesSetting;
use App\Models\BpjsTkSetting;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\Pph21Rate;
use App\Models\Pph21Setting;
use App\Models\PtkpSetting;
use App\Models\SalaryComponent;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\DB;

class DemoDataService
{
    protected Company $company;

    public function __construct() {}

    /**
     * Seed basic company setup data (departments, positions, schedules, etc.)
     * WITHOUT creating demo employees. Use this for new user registration.
     */
    public function seedCompanySetup(Company $company): void
    {
        $this->company = $company;

        DB::transaction(function () {
            $this->createDepartments();
            $this->createPositions();
            $this->createWorkSchedules();
            $this->createLeaveTypes();
            $this->createSalaryComponents();
            $this->createTaxSettings();
            $this->createBpjsSettings();
        });
    }

    /**
     * Seed demo transaction data for existing employees.
     * Creates attendance, leave balances, and payroll for registered user.
     */
    public function seedEmployeeTransactionData(Company $company): void
    {
        $this->company = $company;

        DB::transaction(function () {
            $this->createLeaveBalances();
            $this->createAttendances();
            $this->createPayroll();
        });
    }

    /**
     * Seed full demo data including demo employees for testing.
     * Use this for demo/seeder purposes.
     */
    public function seedDemoData(Company $company): void
    {
        $this->company = $company;

        DB::transaction(function () {
            $this->createOfficeLocation();
            $this->createDepartments();
            $this->createPositions();
            $this->createWorkSchedules();
            $this->createLeaveTypes();
            $this->createSalaryComponents();
            $this->createEmployees();
            $this->assignEmployeesToOfficeLocation();
            $this->createLeaveBalances();
            $this->createAttendances();
            $this->createLeaveRequests();
            $this->createTaxSettings();
            $this->createBpjsSettings();
            $this->createPayroll();
        });

        $company->update([
            'is_demo_mode' => true,
            'demo_started_at' => now(),
        ]);
    }

    public function resetToProduction(Company $company): void
    {
        $this->company = $company;

        DB::transaction(function () use ($company) {
            // Delete all company data in reverse order of dependencies
            PayrollItem::whereHas('payroll', fn ($q) => $q->where('company_id', $company->id))->delete();
            Payroll::where('company_id', $company->id)->delete();
            LeaveRequest::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->delete();
            LeaveBalance::where('company_id', $company->id)->delete();
            Attendance::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->delete();
            EmployeeSalary::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->delete();

            // Delete employee-office pivot records before deleting employees and office locations
            $employeeIds = Employee::where('company_id', $company->id)->pluck('id');
            DB::table('employee_office_locations')->whereIn('employee_id', $employeeIds)->delete();

            Employee::where('company_id', $company->id)->delete();
            OfficeLocation::where('company_id', $company->id)->delete();
            Position::where('company_id', $company->id)->delete();
            Department::where('company_id', $company->id)->delete();
            WorkSchedule::where('company_id', $company->id)->delete();
            LeaveType::where('company_id', $company->id)->delete();
            SalaryComponent::where('company_id', $company->id)->delete();
            Pph21Setting::where('company_id', $company->id)->delete();
            PtkpSetting::where('company_id', $company->id)->delete();
            Pph21Rate::where('company_id', $company->id)->delete();
            BpjsTkSetting::where('company_id', $company->id)->delete();
            BpjsKesSetting::where('company_id', $company->id)->delete();

            $company->update([
                'is_demo_mode' => false,
                'demo_started_at' => null,
            ]);
        });
    }

    protected function createOfficeLocation(): void
    {
        OfficeLocation::firstOrCreate(
            [
                'company_id' => $this->company->id,
                'code' => 'HQ',
            ],
            [
                'name' => 'Kantor Pusat',
                'address' => 'Jl. Sudirman No. 1',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_active' => true,
                'is_headquarters' => true,
            ]
        );
    }

    protected function createDepartments(): void
    {
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Pengelolaan SDM dan rekrutmen'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Keuangan dan akuntansi'],
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Pengembangan sistem dan infrastruktur'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Pemasaran dan branding'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operasional perusahaan'],
        ];

        foreach ($departments as $dept) {
            Department::create([
                'company_id' => $this->company->id,
                'name' => $dept['name'],
                'code' => $dept['code'],
                'description' => $dept['description'],
                'is_active' => true,
            ]);
        }
    }

    protected function createPositions(): void
    {
        $departments = Department::where('company_id', $this->company->id)->get();

        $positionsByDept = [
            'HR' => [
                ['name' => 'HR Manager', 'level' => 3, 'base_salary' => 15000000],
                ['name' => 'HR Staff', 'level' => 1, 'base_salary' => 6000000],
            ],
            'FIN' => [
                ['name' => 'Finance Manager', 'level' => 3, 'base_salary' => 18000000],
                ['name' => 'Accountant', 'level' => 2, 'base_salary' => 8000000],
                ['name' => 'Finance Staff', 'level' => 1, 'base_salary' => 5500000],
            ],
            'IT' => [
                ['name' => 'IT Manager', 'level' => 3, 'base_salary' => 20000000],
                ['name' => 'Senior Developer', 'level' => 2, 'base_salary' => 15000000],
                ['name' => 'Junior Developer', 'level' => 1, 'base_salary' => 7000000],
            ],
            'MKT' => [
                ['name' => 'Marketing Manager', 'level' => 3, 'base_salary' => 16000000],
                ['name' => 'Digital Marketing', 'level' => 2, 'base_salary' => 9000000],
                ['name' => 'Marketing Staff', 'level' => 1, 'base_salary' => 5500000],
            ],
            'OPS' => [
                ['name' => 'Operations Manager', 'level' => 3, 'base_salary' => 14000000],
                ['name' => 'Staff', 'level' => 1, 'base_salary' => 5000000],
            ],
        ];

        foreach ($departments as $dept) {
            $positions = $positionsByDept[$dept->code] ?? [];
            foreach ($positions as $pos) {
                Position::create([
                    'company_id' => $this->company->id,
                    'department_id' => $dept->id,
                    'name' => $pos['name'],
                    'code' => strtoupper(substr(str_replace(' ', '', $pos['name']), 0, 5)).rand(100, 999),
                    'level' => $pos['level'],
                    'base_salary' => $pos['base_salary'],
                    'description' => 'Posisi '.$pos['name'],
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function createWorkSchedules(): void
    {
        WorkSchedule::create([
            'company_id' => $this->company->id,
            'name' => 'Jam Kantor Normal',
            'code' => 'WS-NORMAL',
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
        ]);

        WorkSchedule::create([
            'company_id' => $this->company->id,
            'name' => 'Shift Pagi',
            'code' => 'WS-PAGI',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'break_start' => '10:00',
            'break_end' => '10:30',
            'break_duration' => 30,
            'working_hours' => 8,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'late_tolerance' => 10,
            'early_leave_tolerance' => 10,
            'is_flexible' => false,
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    protected function createLeaveTypes(): void
    {
        $types = [
            ['name' => 'Cuti Tahunan', 'code' => 'CT', 'default_days' => 12, 'is_paid' => true, 'is_annual' => true],
            ['name' => 'Cuti Sakit', 'code' => 'CS', 'default_days' => 14, 'is_paid' => true, 'is_annual' => false],
            ['name' => 'Cuti Melahirkan', 'code' => 'CM', 'default_days' => 90, 'is_paid' => true, 'is_annual' => false],
            ['name' => 'Izin Tidak Dibayar', 'code' => 'ITD', 'default_days' => 30, 'is_paid' => false, 'is_annual' => false],
            ['name' => 'Cuti Menikah', 'code' => 'CMK', 'default_days' => 3, 'is_paid' => true, 'is_annual' => false],
        ];

        foreach ($types as $type) {
            LeaveType::create([
                'company_id' => $this->company->id,
                'name' => $type['name'],
                'code' => $type['code'],
                'default_days' => $type['default_days'],
                'is_paid' => $type['is_paid'],
                'is_annual' => $type['is_annual'],
                'requires_approval' => true,
                'is_active' => true,
            ]);
        }
    }

    protected function createSalaryComponents(): void
    {
        $components = [
            ['name' => 'Tunjangan Makan', 'code' => 'TJ-MAKAN', 'type' => 'earning', 'category' => 'benefit', 'is_taxable' => true, 'default_amount' => 500000],
            ['name' => 'Tunjangan Transport', 'code' => 'TJ-TRANS', 'type' => 'earning', 'category' => 'benefit', 'is_taxable' => true, 'default_amount' => 500000],
            ['name' => 'Tunjangan Kesehatan', 'code' => 'TJ-KES', 'type' => 'earning', 'category' => 'benefit', 'is_taxable' => false, 'default_amount' => 300000],
            ['name' => 'Bonus Kinerja', 'code' => 'BONUS', 'type' => 'earning', 'category' => 'variable', 'is_taxable' => true, 'default_amount' => 0],
            ['name' => 'Potongan Pinjaman', 'code' => 'POT-PINJAM', 'type' => 'deduction', 'category' => 'loan', 'is_taxable' => false, 'default_amount' => 0],
        ];

        foreach ($components as $index => $comp) {
            SalaryComponent::create([
                'company_id' => $this->company->id,
                'name' => $comp['name'],
                'code' => $comp['code'],
                'type' => $comp['type'],
                'category' => $comp['category'],
                'calculation_type' => 'fixed',
                'default_amount' => $comp['default_amount'],
                'is_taxable' => $comp['is_taxable'],
                'is_active' => true,
                'is_mandatory' => false,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function createEmployees(): void
    {
        $positions = Position::where('company_id', $this->company->id)->with('department')->get();

        $demoEmployees = [
            ['first_name' => 'Budi', 'last_name' => 'Santoso', 'gender' => 'male', 'status' => 'permanent'],
            ['first_name' => 'Siti', 'last_name' => 'Nurhaliza', 'gender' => 'female', 'status' => 'permanent'],
            ['first_name' => 'Ahmad', 'last_name' => 'Wijaya', 'gender' => 'male', 'status' => 'permanent'],
            ['first_name' => 'Dewi', 'last_name' => 'Lestari', 'gender' => 'female', 'status' => 'contract'],
            ['first_name' => 'Rudi', 'last_name' => 'Hermawan', 'gender' => 'male', 'status' => 'probation'],
            ['first_name' => 'Maya', 'last_name' => 'Putri', 'gender' => 'female', 'status' => 'permanent'],
            ['first_name' => 'Eko', 'last_name' => 'Prasetyo', 'gender' => 'male', 'status' => 'permanent'],
            ['first_name' => 'Linda', 'last_name' => 'Sari', 'gender' => 'female', 'status' => 'contract'],
            ['first_name' => 'Joko', 'last_name' => 'Susilo', 'gender' => 'male', 'status' => 'permanent'],
            ['first_name' => 'Ratna', 'last_name' => 'Dewi', 'gender' => 'female', 'status' => 'probation'],
        ];

        $employeeNumber = 1;
        foreach ($demoEmployees as $index => $emp) {
            $position = $positions[$index % $positions->count()];
            $hireDate = now()->subMonths(rand(1, 36));

            Employee::create([
                'company_id' => $this->company->id,
                'department_id' => $position->department_id,
                'position_id' => $position->id,
                'employee_number' => 'EMP'.date('Y').str_pad($employeeNumber++, 4, '0', STR_PAD_LEFT),
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'email' => strtolower($emp['first_name'].'.'.$emp['last_name']).'@demo.gajipro.com',
                'phone' => '08'.rand(1000000000, 9999999999),
                'date_of_birth' => now()->subYears(rand(25, 45))->subDays(rand(0, 365)),
                'gender' => $emp['gender'],
                'marital_status' => fake()->randomElement(['single', 'married']),
                'religion' => fake()->randomElement(['Islam', 'Kristen', 'Katolik']),
                'identity_number' => fake()->numerify('################'),
                'address' => 'Jl. Demo No. '.rand(1, 100),
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '1'.rand(1000, 9999),
                'hire_date' => $hireDate,
                'employment_status' => $emp['status'],
                'contract_start_date' => $emp['status'] === 'contract' ? $hireDate : null,
                'contract_end_date' => $emp['status'] === 'contract' ? $hireDate->copy()->addYear() : null,
                'base_salary' => $position->base_salary,
                'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
                'bank_account_number' => fake()->numerify('##########'),
                'bank_account_name' => $emp['first_name'].' '.$emp['last_name'],
                'npwp' => fake()->numerify('##.###.###.#-###.###'),
                'tax_status' => fake()->randomElement(['TK/0', 'K/0', 'K/1', 'K/2']),
                'bpjs_kesehatan' => fake()->numerify('#############'),
                'bpjs_ketenagakerjaan' => fake()->numerify('#############'),
                'is_active' => true,
            ]);
        }
    }

    protected function assignEmployeesToOfficeLocation(): void
    {
        $officeLocation = OfficeLocation::where('company_id', $this->company->id)
            ->where('is_headquarters', true)
            ->first();

        if (! $officeLocation) {
            return;
        }

        $employees = Employee::where('company_id', $this->company->id)->get();

        foreach ($employees as $employee) {
            // Use syncWithoutDetaching to avoid duplicate entries
            $employee->officeLocations()->syncWithoutDetaching([
                $officeLocation->id => ['is_primary' => true],
            ]);
        }
    }

    protected function createLeaveBalances(): void
    {
        $employees = Employee::where('company_id', $this->company->id)->get();
        $leaveTypes = LeaveType::where('company_id', $this->company->id)->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                $entitledDays = $leaveType->default_days ?? 0;
                $usedDays = rand(0, min(3, $entitledDays));
                LeaveBalance::create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => (int) date('Y'),
                    'entitled_days' => $entitledDays,
                    'used_days' => $usedDays,
                    'pending_days' => 0,
                    'carried_forward_days' => 0,
                    'adjustment_days' => 0,
                ]);
            }
        }
    }

    protected function createAttendances(): void
    {
        $employees = Employee::where('company_id', $this->company->id)->get();
        $workSchedule = WorkSchedule::where('company_id', $this->company->id)->where('is_default', true)->first();

        if (! $workSchedule) {
            return;
        }

        // Create attendance for last 30 days (excluding today so user can test clock in/out)
        for ($i = 30; $i >= 1; $i--) {
            $date = now()->subDays($i);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($employees as $employee) {
                // 90% attendance rate
                if (rand(1, 100) > 90) {
                    continue;
                }

                $lateMinutes = rand(0, 100) > 80 ? rand(1, 30) : 0;
                $clockIn = $date->copy()->setTime(8, $lateMinutes, 0);
                $clockOut = $date->copy()->setTime(17, rand(0, 30), 0);
                $workingMinutes = $clockOut->diffInMinutes($clockIn) - 60; // minus break

                Attendance::create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule->id,
                    'date' => $date->toDateString(),
                    'scheduled_start' => $workSchedule->start_time,
                    'scheduled_end' => $workSchedule->end_time,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late_minutes' => $lateMinutes,
                    'early_leave_minutes' => 0,
                    'overtime_minutes' => 0,
                    'working_minutes' => $workingMinutes,
                    'status' => $lateMinutes > 0 ? 'late' : 'present',
                    'clock_in_status' => $lateMinutes > 0 ? ($lateMinutes > 15 ? 'very_late' : 'late') : 'on_time',
                    'clock_out_status' => 'on_time',
                    'is_manual_entry' => false,
                ]);
            }
        }
    }

    protected function createLeaveRequests(): void
    {
        $employees = Employee::where('company_id', $this->company->id)->take(3)->get();
        $leaveType = LeaveType::where('company_id', $this->company->id)->where('code', 'CT')->first();

        if (! $leaveType) {
            return;
        }

        foreach ($employees as $employee) {
            $startDate = now()->addDays(rand(7, 30));
            $totalDays = rand(1, 3);
            LeaveRequest::create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays($totalDays - 1),
                'total_days' => $totalDays,
                'reason' => 'Keperluan keluarga',
                'status' => fake()->randomElement(['pending', 'approved']),
            ]);
        }
    }

    protected function createTaxSettings(): void
    {
        $year = date('Y');

        // PPh21 Setting
        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => $year,
        ]);

        // PTKP
        $ptkpDefaults = PtkpSetting::getDefaultPtkp2024();
        foreach ($ptkpDefaults as $ptkp) {
            PtkpSetting::create([
                'company_id' => $this->company->id,
                'status_code' => $ptkp['status_code'],
                'description' => $ptkp['description'],
                'annual_amount' => $ptkp['annual_amount'],
                'year' => $year,
                'is_active' => true,
            ]);
        }

        // PPh21 Rates
        $rateDefaults = Pph21Rate::getDefaultRates2024();
        foreach ($rateDefaults as $rate) {
            Pph21Rate::create([
                'company_id' => $this->company->id,
                'min_amount' => $rate['min_amount'],
                'max_amount' => $rate['max_amount'],
                'rate' => $rate['rate'],
                'year' => $year,
                'is_active' => true,
            ]);
        }
    }

    protected function createBpjsSettings(): void
    {
        $year = date('Y');

        BpjsTkSetting::create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jkk_rate' => 0.24,
            'jkk_risk_level' => 'very_low',
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => $year,
            'is_active' => true,
        ]);

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
            'effective_year' => $year,
            'is_active' => true,
        ]);
    }

    protected function createPayroll(): void
    {
        // Create payroll for previous month
        $lastMonth = now()->subMonth();

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => 'Payroll '.$lastMonth->translatedFormat('F Y'),
            'period_month' => $lastMonth->month,
            'period_year' => $lastMonth->year,
            'period_start' => $lastMonth->copy()->startOfMonth(),
            'period_end' => $lastMonth->copy()->endOfMonth(),
            'payment_date' => $lastMonth->copy()->endOfMonth(),
            'status' => 'paid',
            'total_employees' => 0,
            'total_earnings' => 0,
            'total_deductions' => 0,
            'total_net_salary' => 0,
            'notes' => 'Demo payroll - Gaji bulan '.$lastMonth->translatedFormat('F Y'),
        ]);

        $employees = Employee::where('company_id', $this->company->id)->where('is_active', true)->get();

        $totalEarnings = 0;
        $totalDeductions = 0;
        $totalNet = 0;
        $employeeCount = 0;

        foreach ($employees as $employee) {
            $baseSalary = $employee->base_salary ?? 5000000;
            $allowances = 1000000; // Tunjangan
            $grossSalary = $baseSalary + $allowances;

            // Calculate using PayrollCalculationService
            $calculationService = app(\App\Services\PayrollCalculationService::class);
            $calculation = $calculationService->calculate($employee, [
                'gross_salary' => $grossSalary,
                'basic_salary' => $baseSalary,
                'total_earnings' => $allowances,
                'taxable_earnings' => $allowances,
                'total_deductions' => 0,
            ], $this->company->id);

            $taxAmount = $calculation['pph21'];
            $bpjsDeductions = $calculation['bpjs_kes_employee'] + $calculation['bpjs_tk_employee'];
            $itemTotalDeductions = $bpjsDeductions;
            $netSalary = $grossSalary - $itemTotalDeductions - $taxAmount;

            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'employee_number' => $employee->employee_id,
                'department_name' => $employee->department?->name ?? 'N/A',
                'position_name' => $employee->position?->name ?? 'N/A',
                'working_days' => 22,
                'present_days' => 20,
                'absent_days' => 0,
                'late_days' => 2,
                'leave_days' => 0,
                'overtime_hours' => 0,
                'basic_salary' => $baseSalary,
                'total_earnings' => $allowances,
                'gross_salary' => $grossSalary,
                'tax_amount' => $taxAmount,
                'total_deductions' => $itemTotalDeductions,
                'net_salary' => $netSalary,
                'payment_method' => 'transfer',
                'bank_name' => $employee->bank_name,
                'bank_account_number' => $employee->bank_account_number,
                'bank_account_name' => $employee->bank_account_name,
                'status' => 'paid',
                'paid_at' => $lastMonth->copy()->endOfMonth(),
            ]);

            $totalEarnings += $grossSalary;
            $totalDeductions += $itemTotalDeductions + $taxAmount;
            $totalNet += $netSalary;
            $employeeCount++;
        }

        $payroll->update([
            'total_employees' => $employeeCount,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'total_net_salary' => $totalNet,
        ]);
    }
}
