<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollItemDetail;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class DashboardDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (! $company) {
            $this->command->error('No company found. Please run DatabaseSeeder first.');

            return;
        }

        // Update company name to GajiPro
        $company->update([
            'name' => 'PT Demo GajiPro',
            'slug' => 'pt-demo-gajipro',
            'email' => 'hr@demo-gajipro.com',
        ]);

        $this->command->info('Company name updated to GajiPro');

        // Create Office Location
        $officeLocation = OfficeLocation::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'HQ'],
            [
                'name' => 'Kantor Pusat',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_active' => true,
                'is_headquarters' => true,
            ]
        );

        $this->command->info('Office location created');

        // Create Work Schedule
        $workSchedule = WorkSchedule::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Regular Office Hours'],
            [
                'code' => 'REG',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $this->command->info('Work schedule created');

        // Create Leave Types
        $leaveTypes = $this->createLeaveTypes($company);
        $this->command->info('Leave types created');

        // Create Salary Components
        $salaryComponents = $this->createSalaryComponents($company);
        $this->command->info('Salary components created');

        // Get all employees
        $employees = Employee::where('company_id', $company->id)->get();

        if ($employees->isEmpty()) {
            $this->command->error('No employees found. Please run DatabaseSeeder first.');

            return;
        }

        // Create Leave Balances for all employees
        $this->createLeaveBalances($company, $employees, $leaveTypes);
        $this->command->info('Leave balances created');

        // Create Attendance Data (last 14 days)
        $this->createAttendanceData($company, $employees, $officeLocation, $workSchedule);
        $this->command->info('Attendance data created');

        // Create Leave Requests
        $this->createLeaveRequests($company, $employees, $leaveTypes);
        $this->command->info('Leave requests created');

        // Create Payroll Data (last 6 months)
        $this->createPayrollData($company, $employees, $salaryComponents);
        $this->command->info('Payroll data created');

        $this->command->info('Dashboard dummy data seeded successfully!');
    }

    private function createLeaveTypes(Company $company): array
    {
        $types = [
            [
                'name' => 'Cuti Tahunan',
                'code' => 'CT',
                'description' => 'Cuti tahunan sesuai UU Ketenagakerjaan',
                'default_days' => 12,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => false,
                'max_consecutive_days' => 12,
                'min_notice_days' => 3,
                'is_carry_forward' => true,
                'max_carry_forward_days' => 6,
                'is_active' => true,
                'color' => '#3b82f6',
                'is_annual' => true,
            ],
            [
                'name' => 'Cuti Sakit',
                'code' => 'CS',
                'description' => 'Cuti karena sakit dengan surat dokter',
                'default_days' => 14,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'max_consecutive_days' => 14,
                'min_notice_days' => 0,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
                'color' => '#ef4444',
                'is_annual' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'code' => 'CM',
                'description' => 'Cuti melahirkan 3 bulan',
                'default_days' => 90,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'max_consecutive_days' => 90,
                'min_notice_days' => 30,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
                'color' => '#ec4899',
                'is_annual' => false,
            ],
            [
                'name' => 'Cuti Menikah',
                'code' => 'CMK',
                'description' => 'Cuti pernikahan karyawan',
                'default_days' => 3,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'max_consecutive_days' => 3,
                'min_notice_days' => 7,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
                'color' => '#f59e0b',
                'is_annual' => false,
            ],
            [
                'name' => 'Izin Tidak Masuk',
                'code' => 'ITM',
                'description' => 'Izin tidak masuk kerja tanpa potong gaji',
                'default_days' => 6,
                'is_paid' => false,
                'requires_approval' => true,
                'requires_attachment' => false,
                'max_consecutive_days' => 3,
                'min_notice_days' => 1,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
                'color' => '#6b7280',
                'is_annual' => true,
            ],
        ];

        $createdTypes = [];
        foreach ($types as $type) {
            $createdTypes[] = LeaveType::firstOrCreate(
                ['company_id' => $company->id, 'code' => $type['code']],
                array_merge($type, ['company_id' => $company->id])
            );
        }

        return $createdTypes;
    }

    private function createSalaryComponents(Company $company): array
    {
        // category enum: 'fixed','variable','benefit','tax','insurance','loan','other'
        $components = [
            // Earnings
            [
                'name' => 'Gaji Pokok',
                'code' => 'GP',
                'type' => 'earning',
                'category' => 'fixed',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tunjangan Transportasi',
                'code' => 'TT',
                'type' => 'earning',
                'category' => 'fixed',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tunjangan Makan',
                'code' => 'TM',
                'type' => 'earning',
                'category' => 'variable',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tunjangan Jabatan',
                'code' => 'TJ',
                'type' => 'earning',
                'category' => 'benefit',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Lembur',
                'code' => 'OT',
                'type' => 'earning',
                'category' => 'variable',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_active' => true,
            ],
            // Deductions
            [
                'name' => 'BPJS Kesehatan',
                'code' => 'BPJSK',
                'type' => 'deduction',
                'category' => 'insurance',
                'calculation_type' => 'percentage',
                'percentage' => 1.00,
                'percentage_base' => 'basic_salary',
                'is_taxable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPJS Ketenagakerjaan',
                'code' => 'BPJSTK',
                'type' => 'deduction',
                'category' => 'insurance',
                'calculation_type' => 'percentage',
                'percentage' => 2.00,
                'percentage_base' => 'basic_salary',
                'is_taxable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'PPh 21',
                'code' => 'PPH21',
                'type' => 'deduction',
                'category' => 'tax',
                'calculation_type' => 'formula',
                'is_taxable' => false,
                'is_active' => true,
            ],
        ];

        $createdComponents = [];
        foreach ($components as $component) {
            $createdComponents[] = SalaryComponent::firstOrCreate(
                ['company_id' => $company->id, 'code' => $component['code']],
                array_merge($component, ['company_id' => $company->id])
            );
        }

        return $createdComponents;
    }

    private function createLeaveBalances(Company $company, $employees, array $leaveTypes): void
    {
        $currentYear = now()->year;

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                if ($leaveType->is_annual) {
                    $usedDays = round(rand(0, min(50, $leaveType->default_days * 10)) / 10, 1);
                    LeaveBalance::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'year' => $currentYear,
                        ],
                        [
                            'entitled_days' => $leaveType->default_days,
                            'used_days' => $usedDays,
                            'pending_days' => 0,
                            'carried_forward_days' => round(rand(0, 30) / 10, 1),
                            'adjustment_days' => 0,
                        ]
                    );
                }
            }
        }
    }

    private function createAttendanceData(Company $company, $employees, $officeLocation, $workSchedule): void
    {
        // Delete existing attendance data to avoid duplicates
        Attendance::where('company_id', $company->id)
            ->where('date', '>=', now()->subDays(14)->toDateString())
            ->delete();

        $startDate = now()->subDays(13);
        $endDate = now();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($employees as $employee) {
                // 85% chance of being present
                $isPresent = rand(1, 100) <= 85;

                if (! $isPresent) {
                    // Random chance: absent, leave, or late arrival that didn't clock in
                    continue;
                }

                // Determine if late (20% chance)
                $isLate = rand(1, 100) <= 20;

                // Clock in time
                $clockInHour = 8;
                $clockInMinute = 0;

                if ($isLate) {
                    $clockInHour = rand(8, 9);
                    $clockInMinute = rand(5, 45);
                }

                $clockIn = $date->copy()->setTime($clockInHour, $clockInMinute, rand(0, 59));

                // Calculate late minutes
                $scheduledStart = $date->copy()->setTime(8, 0, 0);
                $lateMinutes = max(0, $clockIn->diffInMinutes($scheduledStart, false));

                // Clock out time (90% chance if present)
                $clockOut = null;
                $earlyLeaveMinutes = 0;
                $workingMinutes = 0;

                if (rand(1, 100) <= 90) {
                    // Normal clock out between 17:00 - 18:30
                    $clockOutHour = rand(17, 18);
                    $clockOutMinute = rand(0, 30);
                    $clockOut = $date->copy()->setTime($clockOutHour, $clockOutMinute, rand(0, 59));

                    // Calculate early leave
                    $scheduledEnd = $date->copy()->setTime(17, 0, 0);
                    if ($clockOut->lt($scheduledEnd)) {
                        $earlyLeaveMinutes = $scheduledEnd->diffInMinutes($clockOut);
                    }

                    // Calculate working minutes
                    $workingMinutes = $clockIn->diffInMinutes($clockOut) - 60; // minus 1 hour lunch
                }

                // Determine status
                $status = 'present';
                $clockInStatus = 'on_time';
                $clockOutStatus = 'on_time';

                if ($lateMinutes > 0) {
                    $status = 'late';
                    $clockInStatus = $lateMinutes > 30 ? 'very_late' : 'late';
                }

                if ($clockOut && $earlyLeaveMinutes > 0) {
                    $clockOutStatus = 'early';
                }

                // Generate random offset for location
                $latOffset = (rand(-5, 5) / 10000);
                $lngOffset = (rand(-5, 5) / 10000);

                Attendance::create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'office_location_id' => $officeLocation->id,
                    'work_schedule_id' => $workSchedule->id,
                    'date' => $date->toDateString(),
                    'scheduled_start' => '08:00:00',
                    'scheduled_end' => '17:00:00',
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'clock_in_latitude' => $officeLocation->latitude + $latOffset,
                    'clock_in_longitude' => $officeLocation->longitude + $lngOffset,
                    'clock_out_latitude' => $clockOut ? $officeLocation->latitude + (rand(-5, 5) / 10000) : null,
                    'clock_out_longitude' => $clockOut ? $officeLocation->longitude + (rand(-5, 5) / 10000) : null,
                    'late_minutes' => $lateMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'working_minutes' => $workingMinutes,
                    'status' => $status,
                    'clock_in_status' => $clockInStatus,
                    'clock_out_status' => $clockOutStatus,
                    'is_manual_entry' => false,
                ]);
            }
        }
    }

    private function createLeaveRequests(Company $company, $employees, array $leaveTypes): void
    {
        $annualLeaveType = collect($leaveTypes)->firstWhere('code', 'CT');
        $sickLeaveType = collect($leaveTypes)->firstWhere('code', 'CS');

        if (! $annualLeaveType) {
            return;
        }

        $approver = User::where('company_id', $company->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'hr-manager']))
            ->first();

        // Create some pending leave requests
        $randomEmployees = $employees->random(min(5, $employees->count()));

        $reasons = [
            'Keperluan keluarga',
            'Acara pernikahan kerabat',
            'Liburan keluarga',
            'Urusan pribadi',
            'Pemeriksaan kesehatan rutin',
        ];

        foreach ($randomEmployees as $index => $employee) {
            $startDate = now()->addDays(rand(3, 14));
            $totalDays = rand(1, 3);
            $endDate = $startDate->copy()->addDays($totalDays - 1);

            // Alternate between pending and approved
            $status = $index % 3 === 0 ? 'pending' : 'approved';

            $useAnnualLeave = rand(1, 100) <= 70;
            $leaveTypeId = $useAnnualLeave ? $annualLeaveType->id : ($sickLeaveType?->id ?? $annualLeaveType->id);

            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveTypeId,
                'request_number' => 'LV'.now()->format('Ymd').str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'is_half_day' => false,
                'reason' => $reasons[array_rand($reasons)],
                'status' => $status,
                'approved_by' => $status === 'approved' ? $approver?->id : null,
                'approved_at' => $status === 'approved' ? now()->subDays(rand(1, 5)) : null,
            ]);
        }
    }

    private function createPayrollData(Company $company, $employees, array $salaryComponents): void
    {
        // Delete existing payroll data
        $existingPayrolls = Payroll::where('company_id', $company->id)->pluck('id');
        if ($existingPayrolls->isNotEmpty()) {
            PayrollItemDetail::whereIn('payroll_item_id', function ($query) use ($existingPayrolls) {
                $query->select('id')->from('payroll_items')->whereIn('payroll_id', $existingPayrolls);
            })->delete();
            PayrollItem::whereIn('payroll_id', $existingPayrolls)->delete();
            Payroll::whereIn('id', $existingPayrolls)->delete();
        }

        $gpComponent = collect($salaryComponents)->firstWhere('code', 'GP');
        $ttComponent = collect($salaryComponents)->firstWhere('code', 'TT');
        $tmComponent = collect($salaryComponents)->firstWhere('code', 'TM');
        $bpjskComponent = collect($salaryComponents)->firstWhere('code', 'BPJSK');
        $pph21Component = collect($salaryComponents)->firstWhere('code', 'PPH21');

        $salaryOptions = [5000000, 7500000, 10000000, 15000000, 20000000, 25000000];
        $bankOptions = ['BCA', 'Mandiri', 'BNI', 'BRI'];

        // Create payroll for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $periodDate = now()->subMonths($i);
            $periodMonth = $periodDate->month;
            $periodYear = $periodDate->year;

            $periodStart = $periodDate->copy()->startOfMonth();
            $periodEnd = $periodDate->copy()->endOfMonth();

            $totalEarnings = 0;
            $totalDeductions = 0;
            $totalNetSalary = 0;

            $payroll = Payroll::create([
                'company_id' => $company->id,
                'payroll_number' => 'PAY'.$periodYear.str_pad($periodMonth, 2, '0', STR_PAD_LEFT).'001',
                'name' => 'Payroll '.$periodDate->translatedFormat('F Y'),
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'payment_date' => $periodEnd->copy()->addDays(5),
                'status' => $i === 0 ? 'draft' : 'paid',
                'total_employees' => $employees->count(),
                'total_earnings' => 0,
                'total_deductions' => 0,
                'total_net_salary' => 0,
                'paid_at' => $i === 0 ? null : $periodEnd->copy()->addDays(5),
            ]);

            foreach ($employees as $employee) {
                // Base salary varies by position
                $baseSalary = $salaryOptions[array_rand($salaryOptions)];

                $transportAllowance = 500000;
                $mealAllowance = rand(20, 26) * 35000; // per working day

                $earnings = $baseSalary + $transportAllowance + $mealAllowance;

                // Deductions
                $bpjsDeduction = round($baseSalary * 0.01); // 1% BPJS Kesehatan employee
                $pph21Deduction = round(($baseSalary - 4500000) * 0.05); // Simplified PPh 21
                if ($pph21Deduction < 0) {
                    $pph21Deduction = 0;
                }

                $deductions = $bpjsDeduction + $pph21Deduction;
                $netSalary = $earnings - $deductions;

                $totalEarnings += $earnings;
                $totalDeductions += $deductions;
                $totalNetSalary += $netSalary;

                $payrollItem = PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'employee_number' => $employee->employee_id,
                    'department_name' => $employee->department?->name ?? 'N/A',
                    'position_name' => $employee->position?->name ?? 'N/A',
                    'working_days' => rand(20, 23),
                    'present_days' => rand(18, 23),
                    'absent_days' => rand(0, 2),
                    'late_days' => rand(0, 3),
                    'leave_days' => rand(0, 2),
                    'overtime_hours' => rand(0, 20),
                    'basic_salary' => $baseSalary,
                    'total_earnings' => $earnings,
                    'total_deductions' => $deductions,
                    'gross_salary' => $earnings,
                    'tax_amount' => $pph21Deduction,
                    'net_salary' => $netSalary,
                    'payment_method' => 'transfer',
                    'bank_name' => $bankOptions[array_rand($bankOptions)],
                    'bank_account_number' => str_pad((string) rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                    'bank_account_name' => $employee->full_name,
                    'status' => $i === 0 ? 'pending' : 'paid',
                    'paid_at' => $i === 0 ? null : $periodEnd->copy()->addDays(5),
                ]);

                // Create payroll item details
                if ($gpComponent) {
                    PayrollItemDetail::create([
                        'payroll_item_id' => $payrollItem->id,
                        'salary_component_id' => $gpComponent->id,
                        'component_name' => 'Gaji Pokok',
                        'component_code' => 'GP',
                        'type' => 'earning',
                        'category' => 'basic',
                        'amount' => $baseSalary,
                        'is_taxable' => true,
                    ]);
                }

                if ($ttComponent) {
                    PayrollItemDetail::create([
                        'payroll_item_id' => $payrollItem->id,
                        'salary_component_id' => $ttComponent->id,
                        'component_name' => 'Tunjangan Transportasi',
                        'component_code' => 'TT',
                        'type' => 'earning',
                        'category' => 'allowance',
                        'amount' => $transportAllowance,
                        'is_taxable' => true,
                    ]);
                }

                if ($tmComponent) {
                    PayrollItemDetail::create([
                        'payroll_item_id' => $payrollItem->id,
                        'salary_component_id' => $tmComponent->id,
                        'component_name' => 'Tunjangan Makan',
                        'component_code' => 'TM',
                        'type' => 'earning',
                        'category' => 'allowance',
                        'amount' => $mealAllowance,
                        'is_taxable' => true,
                    ]);
                }

                if ($bpjskComponent) {
                    PayrollItemDetail::create([
                        'payroll_item_id' => $payrollItem->id,
                        'salary_component_id' => $bpjskComponent->id,
                        'component_name' => 'BPJS Kesehatan',
                        'component_code' => 'BPJSK',
                        'type' => 'deduction',
                        'category' => 'insurance',
                        'amount' => $bpjsDeduction,
                        'is_taxable' => false,
                    ]);
                }

                if ($pph21Component && $pph21Deduction > 0) {
                    PayrollItemDetail::create([
                        'payroll_item_id' => $payrollItem->id,
                        'salary_component_id' => $pph21Component->id,
                        'component_name' => 'PPh 21',
                        'component_code' => 'PPH21',
                        'type' => 'deduction',
                        'category' => 'tax',
                        'amount' => $pph21Deduction,
                        'is_taxable' => false,
                    ]);
                }
            }

            // Update payroll totals
            $payroll->update([
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'total_net_salary' => $totalNetSalary,
            ]);
        }
    }
}
