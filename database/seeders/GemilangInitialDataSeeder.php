<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

/**
 * Seed client-ready default data for PT Gemilang Sari Husada.
 *
 * Depends on GemilangCompanySeeder. Idempotent — safe to run multiple times.
 */
class GemilangInitialDataSeeder extends Seeder
{
    protected Company $company;

    public function run(): void
    {
        $this->company = Company::where('name', 'PT Gemilang Sari Husada')->firstOrFail();

        $this->seedDepartments();
        $this->seedPositions();
        $this->seedLeaveTypes();
        $this->seedWorkSchedules();
        $this->seedHolidays2026();
        $this->seedSalaryComponents();
    }

    protected function seedDepartments(): void
    {
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Sales & Marketing', 'code' => 'SLS'],
            ['name' => 'Information Technology', 'code' => 'IT'],
        ];

        foreach ($departments as $data) {
            Department::updateOrCreate(
                ['company_id' => $this->company->id, 'code' => $data['code']],
                ['name' => $data['name'], 'is_active' => true]
            );
        }
    }

    protected function seedPositions(): void
    {
        $hr = Department::where('company_id', $this->company->id)->where('code', 'HR')->first();
        $fin = Department::where('company_id', $this->company->id)->where('code', 'FIN')->first();
        $ops = Department::where('company_id', $this->company->id)->where('code', 'OPS')->first();

        $positions = [
            ['name' => 'HR Manager', 'code' => 'HR-MGR', 'level' => 4, 'department_id' => $hr?->id, 'base_salary' => 12000000],
            ['name' => 'HR Staff', 'code' => 'HR-STF', 'level' => 2, 'department_id' => $hr?->id, 'base_salary' => 6000000],
            ['name' => 'Finance Manager', 'code' => 'FIN-MGR', 'level' => 4, 'department_id' => $fin?->id, 'base_salary' => 13000000],
            ['name' => 'Accounting Staff', 'code' => 'ACC-STF', 'level' => 2, 'department_id' => $fin?->id, 'base_salary' => 6500000],
            ['name' => 'Operations Manager', 'code' => 'OPS-MGR', 'level' => 4, 'department_id' => $ops?->id, 'base_salary' => 12500000],
            ['name' => 'Operations Staff', 'code' => 'OPS-STF', 'level' => 2, 'department_id' => $ops?->id, 'base_salary' => 5500000],
        ];

        foreach ($positions as $data) {
            Position::updateOrCreate(
                ['company_id' => $this->company->id, 'code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }

    protected function seedLeaveTypes(): void
    {
        $leaveTypes = [
            [
                'code' => 'ANNUAL', 'name' => 'Cuti Tahunan', 'default_days' => 12,
                'is_paid' => true, 'is_annual' => true, 'requires_approval' => true,
                'color' => '#3b82f6',
            ],
            [
                'code' => 'SICK', 'name' => 'Cuti Sakit', 'default_days' => 14,
                'is_paid' => true, 'requires_approval' => true, 'requires_attachment' => true,
                'color' => '#f43f5e',
            ],
            [
                'code' => 'MATERNITY', 'name' => 'Cuti Melahirkan', 'default_days' => 90,
                'is_paid' => true, 'requires_approval' => true, 'requires_attachment' => true,
                'color' => '#ec4899',
            ],
            [
                'code' => 'MARRIAGE', 'name' => 'Cuti Menikah', 'default_days' => 3,
                'is_paid' => true, 'requires_approval' => true, 'max_consecutive_days' => 3,
                'color' => '#a855f7',
            ],
            [
                'code' => 'BEREAVEMENT', 'name' => 'Cuti Duka', 'default_days' => 2,
                'is_paid' => true, 'requires_approval' => true, 'max_consecutive_days' => 2,
                'color' => '#64748b',
            ],
            [
                'code' => 'PERMIT', 'name' => 'Izin', 'default_days' => 0,
                'is_paid' => false, 'requires_approval' => true,
                'color' => '#f59e0b',
            ],
        ];

        foreach ($leaveTypes as $data) {
            LeaveType::updateOrCreate(
                ['company_id' => $this->company->id, 'code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }

    protected function seedWorkSchedules(): void
    {
        $schedules = [
            [
                'code' => 'PAGI',
                'name' => 'Shift Pagi',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_overnight' => false,
                'break_start' => '12:00',
                'break_end' => '13:00',
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5],
                'late_tolerance' => 15,
                'early_leave_tolerance' => 15,
                'is_default' => true,
            ],
            [
                'code' => 'MALAM',
                'name' => 'Shift Malam',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'is_overnight' => true,
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5],
                'late_tolerance' => 15,
                'early_leave_tolerance' => 15,
                'is_default' => false,
            ],
        ];

        foreach ($schedules as $data) {
            WorkSchedule::updateOrCreate(
                ['company_id' => $this->company->id, 'code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }

    protected function seedHolidays2026(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Tahun Baru'],
            ['date' => '2026-01-29', 'name' => 'Tahun Baru Imlek'],
            ['date' => '2026-03-19', 'name' => 'Hari Raya Nyepi'],
            ['date' => '2026-03-21', 'name' => 'Wafat Isa Al Masih'],
            ['date' => '2026-03-23', 'name' => 'Hari Raya Idul Fitri Hari Pertama'],
            ['date' => '2026-03-24', 'name' => 'Hari Raya Idul Fitri Hari Kedua'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional'],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Isa Al Masih'],
            ['date' => '2026-05-31', 'name' => 'Hari Raya Waisak'],
            ['date' => '2026-05-30', 'name' => 'Hari Raya Idul Adha'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila'],
            ['date' => '2026-06-19', 'name' => 'Tahun Baru Islam'],
            ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI'],
            ['date' => '2026-08-28', 'name' => 'Maulid Nabi Muhammad SAW'],
            ['date' => '2026-12-25', 'name' => 'Hari Raya Natal'],
        ];

        foreach ($holidays as $data) {
            $existing = Holiday::query()
                ->where('company_id', $this->company->id)
                ->whereDate('date', $data['date'])
                ->first();

            $payload = [
                'company_id' => $this->company->id,
                'date' => $data['date'],
                'name' => $data['name'],
                'type' => 'national',
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Holiday::create($payload);
            }
        }
    }

    protected function seedSalaryComponents(): void
    {
        $components = [
            // Earnings
            [
                'code' => 'BASIC', 'name' => 'Gaji Pokok', 'type' => 'earning',
                'category' => 'fixed', 'calculation_type' => 'fixed',
                'is_taxable' => true, 'is_mandatory' => true, 'sort_order' => 1,
            ],
            [
                'code' => 'POSITION_ALLOW', 'name' => 'Tunjangan Jabatan', 'type' => 'earning',
                'category' => 'benefit', 'calculation_type' => 'fixed',
                'is_taxable' => true, 'sort_order' => 2,
            ],
            [
                'code' => 'TRANSPORT', 'name' => 'Tunjangan Transport', 'type' => 'earning',
                'category' => 'benefit', 'calculation_type' => 'fixed',
                'is_taxable' => true, 'sort_order' => 3,
            ],
            [
                'code' => 'MEAL', 'name' => 'Tunjangan Makan', 'type' => 'earning',
                'category' => 'benefit', 'calculation_type' => 'fixed',
                'is_taxable' => true, 'sort_order' => 4,
            ],
            // Deductions
            [
                'code' => 'BPJS_TK', 'name' => 'BPJS Ketenagakerjaan', 'type' => 'deduction',
                'category' => 'insurance', 'calculation_type' => 'percentage',
                'percentage' => 2.00, 'percentage_base' => 'basic_salary',
                'is_taxable' => false, 'sort_order' => 10,
            ],
            [
                'code' => 'BPJS_KES', 'name' => 'BPJS Kesehatan', 'type' => 'deduction',
                'category' => 'insurance', 'calculation_type' => 'percentage',
                'percentage' => 1.00, 'percentage_base' => 'basic_salary',
                'is_taxable' => false, 'sort_order' => 11,
            ],
            [
                'code' => 'PPH21', 'name' => 'PPh 21', 'type' => 'deduction',
                'category' => 'tax', 'calculation_type' => 'formula',
                'formula' => 'pph21_ter', 'is_taxable' => false, 'sort_order' => 12,
            ],
        ];

        foreach ($components as $data) {
            SalaryComponent::updateOrCreate(
                ['company_id' => $this->company->id, 'code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
