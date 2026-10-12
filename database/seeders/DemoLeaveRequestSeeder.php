<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoLeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'PT Demo GajiPro')->first();

        if (! $company) {
            $this->command->warn('PT Demo GajiPro not found. Skipping DemoLeaveRequestSeeder.');

            return;
        }

        // Clean up existing leave requests for demo company
        LeaveRequest::where('company_id', $company->id)->forceDelete();
        LeaveBalance::where('company_id', $company->id)->forceDelete();

        // Get leave types
        $cutiTahunan = LeaveType::where('company_id', $company->id)->where('code', 'CT')->first();
        $cutiSakit = LeaveType::where('company_id', $company->id)->where('code', 'CS')->first();
        $izinTidakMasuk = LeaveType::where('company_id', $company->id)->where('code', 'ITM')->first();

        if (! $cutiTahunan || ! $cutiSakit || ! $izinTidakMasuk) {
            $this->command->warn('Required leave types not found. Skipping DemoLeaveRequestSeeder.');

            return;
        }

        // Get employees with users
        $employees = Employee::where('company_id', $company->id)
            ->whereNotNull('user_id')
            ->with('user')
            ->take(10)
            ->get();

        // Get admin user for approvals
        $adminUser = User::where('company_id', $company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        $currentYear = now()->year;
        $requestNumber = 1;

        foreach ($employees as $employee) {
            // Create leave balances for current year
            LeaveBalance::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $cutiTahunan->id,
                'year' => $currentYear,
                'entitled_days' => 12,
                'used_days' => 3,
                'pending_days' => 2,
                'carried_forward_days' => 0,
                'adjustment_days' => 0,
            ]);

            LeaveBalance::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $cutiSakit->id,
                'year' => $currentYear,
                'entitled_days' => 14,
                'used_days' => 1,
                'pending_days' => 0,
                'carried_forward_days' => 0,
                'adjustment_days' => 0,
            ]);

            LeaveBalance::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $izinTidakMasuk->id,
                'year' => $currentYear,
                'entitled_days' => 6,
                'used_days' => 0,
                'pending_days' => 0,
                'carried_forward_days' => 0,
                'adjustment_days' => 0,
            ]);
        }

        // Create various leave requests for first 5 employees
        $employee1 = $employees->get(0);
        $employee2 = $employees->get(1);
        $employee3 = $employees->count() > 2 ? $employees->get(2) : null;
        $employee4 = $employees->count() > 3 ? $employees->get(3) : null;
        $employee5 = $employees->count() > 4 ? $employees->get(4) : null;

        // 1. Approved leave (past) - already taken
        if ($employee1) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee1->id,
                'leave_type_id' => $cutiTahunan->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subDays(14),
                'end_date' => now()->subDays(12),
                'total_days' => 3,
                'is_half_day' => false,
                'reason' => 'Liburan keluarga ke Bali',
                'status' => 'approved',
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subDays(20),
                'approval_notes' => 'Disetujui. Selamat berlibur!',
            ]);
        }

        // 2. Pending leave (future) - awaiting approval
        if ($employee1) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee1->id,
                'leave_type_id' => $cutiTahunan->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->addDays(14),
                'end_date' => now()->addDays(15),
                'total_days' => 2,
                'is_half_day' => false,
                'reason' => 'Menghadiri pernikahan saudara di Surabaya',
                'status' => 'pending',
            ]);
        }

        // 3. Approved sick leave (recent)
        if ($employee2) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee2->id,
                'leave_type_id' => $cutiSakit->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subDays(5),
                'end_date' => now()->subDays(5),
                'total_days' => 1,
                'is_half_day' => false,
                'reason' => 'Demam dan flu, perlu istirahat dengan surat dokter',
                'attachment' => 'attachments/leave/surat-dokter-demo.pdf',
                'status' => 'approved',
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subDays(5),
            ]);
        }

        // 4. Pending half-day leave
        if ($employee2) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee2->id,
                'leave_type_id' => $izinTidakMasuk->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(3),
                'total_days' => 0.5,
                'is_half_day' => true,
                'half_day_type' => 'morning',
                'reason' => 'Kontrol ke dokter gigi pagi hari',
                'status' => 'pending',
            ]);
        }

        // 5. Rejected leave
        if ($employee3) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee3->id,
                'leave_type_id' => $cutiTahunan->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subDays(7),
                'end_date' => now()->subDays(3),
                'total_days' => 5,
                'is_half_day' => false,
                'reason' => 'Liburan ke luar negeri',
                'status' => 'rejected',
                'rejected_by' => $adminUser?->id,
                'rejected_at' => now()->subDays(10),
                'rejection_reason' => 'Tidak bisa disetujui karena ada deadline project penting di minggu tersebut.',
            ]);
        }

        // 6. Cancelled leave
        if ($employee3) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee3->id,
                'leave_type_id' => $cutiTahunan->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(32),
                'total_days' => 3,
                'is_half_day' => false,
                'reason' => 'Liburan akhir bulan',
                'status' => 'cancelled',
                'cancelled_by' => $employee3->user_id,
                'cancelled_at' => now()->subDays(2),
                'cancellation_reason' => 'Rencana berubah, tidak jadi cuti.',
            ]);
        }

        // 7. Approved upcoming leave
        if ($employee4) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee4->id,
                'leave_type_id' => $cutiTahunan->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(9),
                'total_days' => 3,
                'is_half_day' => false,
                'reason' => 'Acara keluarga di kampung halaman',
                'status' => 'approved',
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subDays(3),
                'approval_notes' => 'Disetujui.',
            ]);
        }

        // 8. Ongoing leave (currently on leave)
        if ($employee5) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee5->id,
                'leave_type_id' => $cutiTahunan->id,
                'request_number' => 'LV'.date('Ym').str_pad($requestNumber++, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subDays(1),
                'end_date' => now()->addDays(2),
                'total_days' => 4,
                'is_half_day' => false,
                'reason' => 'Mudik lebaran ke Jogja',
                'emergency_contact' => '081234567890',
                'status' => 'approved',
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subDays(7),
            ]);
        }

        $this->command->info('Demo leave requests created successfully!');
        $this->command->info('- Leave balances created for '.$employees->count().' employees');
        $this->command->info('- '.($requestNumber - 1).' leave requests created with various statuses');
    }
}
