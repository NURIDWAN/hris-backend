<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\OvertimeRequest;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MobileTestingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(1);
        if (! $company) {
            $this->command->error('Company ID 1 tidak ditemukan!');

            return;
        }

        $employee = Employee::where('user_id', 16)->first() ?? Employee::find(27);
        if (! $employee) {
            $this->command->error('Employee untuk user mobile tidak ditemukan!');

            return;
        }

        $this->command->info("Menyiapkan data mobile testing untuk: {$employee->first_name} {$employee->last_name} (ID: {$employee->id})");

        // 1. Assign Work Schedule & Office Location
        $workSchedule = WorkSchedule::where('company_id', $company->id)->where('code', 'REG')->first();
        if ($workSchedule) {
            $employee->update(['work_schedule_id' => $workSchedule->id]);
            $this->command->info("✓ Work Schedule di-set ke {$workSchedule->name}");
        }

        $office = OfficeLocation::where('company_id', $company->id)->where('code', 'HQ-JKT')->first()
            ?? OfficeLocation::where('company_id', $company->id)->first();

        if ($office) {
            DB::table('employee_office_locations')->updateOrInsert(
                [
                    'employee_id' => $employee->id,
                    'office_location_id' => $office->id,
                ],
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $this->command->info("✓ Kantor di-set ke {$office->name}");
        }

        // 2. Seed 3 Sesi Presensi
        // Hapus absen 3 hari ini jika ada agar fresh
        $dates = [
            '2026-10-26', // Hari ini
            '2026-10-25', // Kemarin
            '2026-10-24', // 2 hari lalu
        ];
        Attendance::where('employee_id', $employee->id)->whereIn('date', $dates)->forceDelete();

        // Sesi 1: Hari Ini (2026-10-26) - Clock In Aktif (belum clock-out)
        Attendance::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'office_location_id' => $office?->id,
            'work_schedule_id' => $workSchedule?->id,
            'date' => '2026-10-26',
            'scheduled_start' => '08:00:00',
            'scheduled_end' => '17:00:00',
            'clock_in' => Carbon::parse('2026-10-26 08:05:00'),
            'clock_out' => null, // Belum clock out, tombol mobile bisa diuji
            'clock_in_ip' => '192.168.1.14',
            'clock_in_latitude' => $office?->latitude ?? -6.227146,
            'clock_in_longitude' => $office?->longitude ?? 106.808074,
            'face_verified' => true,
            'face_confidence' => 0.94,
            'status' => 'present',
            'clock_in_status' => 'on_time',
            'late_minutes' => 0,
            'working_minutes' => 0,
            'clock_in_notes' => 'Presensi masuk dari mobile app',
        ]);
        $this->command->info('✓ Sesi Presensi 1 (Hari ini, 2026-10-26): Clock-In 08:05, Status Hadir On-Time (Belum Clock-Out)');

        // Sesi 2: Kemarin (2026-10-25) - Lengkap On-Time
        Attendance::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'office_location_id' => $office?->id,
            'work_schedule_id' => $workSchedule?->id,
            'date' => '2026-10-25',
            'scheduled_start' => '08:00:00',
            'scheduled_end' => '17:00:00',
            'clock_in' => Carbon::parse('2026-10-25 08:00:00'),
            'clock_out' => Carbon::parse('2026-10-25 17:10:00'),
            'clock_in_ip' => '192.168.1.14',
            'clock_out_ip' => '192.168.1.14',
            'clock_in_latitude' => $office?->latitude ?? -6.227146,
            'clock_in_longitude' => $office?->longitude ?? 106.808074,
            'clock_out_latitude' => $office?->latitude ?? -6.227146,
            'clock_out_longitude' => $office?->longitude ?? 106.808074,
            'face_verified' => true,
            'face_confidence' => 0.92,
            'status' => 'present',
            'clock_in_status' => 'on_time',
            'clock_out_status' => 'on_time',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'working_minutes' => 550,
            'clock_in_notes' => 'Presensi normal',
            'clock_out_notes' => 'Selesai tugas harian',
        ]);
        $this->command->info('✓ Sesi Presensi 2 (Kemarin, 2026-10-25): Clock-In 08:00 - Clock-Out 17:10 (Lengkap)');

        // Sesi 3: 2 Hari Lalu (2026-10-24) - Terlambat + Lembur
        Attendance::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'office_location_id' => $office?->id,
            'work_schedule_id' => $workSchedule?->id,
            'date' => '2026-10-24',
            'scheduled_start' => '08:00:00',
            'scheduled_end' => '17:00:00',
            'clock_in' => Carbon::parse('2026-10-24 08:25:00'),
            'clock_out' => Carbon::parse('2026-10-24 18:30:00'),
            'clock_in_ip' => '192.168.1.14',
            'clock_out_ip' => '192.168.1.14',
            'clock_in_latitude' => $office?->latitude ?? -6.227146,
            'clock_in_longitude' => $office?->longitude ?? 106.808074,
            'clock_out_latitude' => $office?->latitude ?? -6.227146,
            'clock_out_longitude' => $office?->longitude ?? 106.808074,
            'face_verified' => true,
            'face_confidence' => 0.89,
            'status' => 'late',
            'clock_in_status' => 'late',
            'clock_out_status' => 'overtime',
            'late_minutes' => 25,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 90,
            'working_minutes' => 605,
            'clock_in_notes' => 'Macet di jalan',
            'clock_out_notes' => 'Lembur penyelesaian rilis',
        ]);
        $this->command->info('✓ Sesi Presensi 3 (2026-10-24): Clock-In 08:25 - Clock-Out 18:30 (Late 25m, Overtime 90m)');

        // 3. Seed Pengajuan Lembur (Overtime Requests)
        OvertimeRequest::where('employee_id', $employee->id)->delete();
        OvertimeRequest::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'date' => '2026-10-24',
            'start_time' => '17:00:00',
            'end_time' => '18:30:00',
            'overtime_hours' => 1.5,
            'overtime_type' => 'weekday',
            'overtime_amount' => 75000,
            'reason' => 'Penyelesaian rilis APK mobile HRIS dan deploy backend',
            'status' => 'approved',
            'notes' => 'Disetujui untuk rilis versi terbaru',
        ]);

        OvertimeRequest::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'date' => '2026-10-27',
            'start_time' => '17:00:00',
            'end_time' => '19:30:00',
            'overtime_hours' => 2.5,
            'overtime_type' => 'weekday',
            'overtime_amount' => 125000,
            'reason' => 'Monitoring performa server dan audit integrasi API mobile',
            'status' => 'pending',
            'notes' => 'Menunggu persetujuan manajer',
        ]);

        OvertimeRequest::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'date' => '2026-10-15',
            'start_time' => '17:00:00',
            'end_time' => '20:00:00',
            'overtime_hours' => 3.0,
            'overtime_type' => 'weekday',
            'overtime_amount' => 150000,
            'reason' => 'Setup infrastruktur database cadangan',
            'status' => 'rejected',
            'rejection_reason' => 'Kegiatan dipindahkan ke akhir pekan',
        ]);
        $this->command->info('✓ Pengajuan Lembur (3 records: 1 Approved, 1 Pending, 1 Rejected)');

        // 4. Seed Pengajuan Cuti (Leave Requests)
        $leaveTypeAnnual = LeaveType::where('company_id', $company->id)->where('code', 'CT')->first()
            ?? LeaveType::first();

        LeaveRequest::where('employee_id', $employee->id)->delete();
        if ($leaveTypeAnnual) {
            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveTypeAnnual->id,
                'request_number' => 'LV-'.date('Ymd').'-001',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-11',
                'total_days' => 2,
                'is_half_day' => false,
                'reason' => 'Keperluan keluarga di luar kota',
                'status' => 'pending',
                'emergency_contact' => '081298765432',
            ]);

            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveTypeAnnual->id,
                'request_number' => 'LV-20260901-002',
                'start_date' => '2026-09-15',
                'end_date' => '2026-09-16',
                'total_days' => 2,
                'is_half_day' => false,
                'reason' => 'Liburan tahunan bersama keluarga',
                'status' => 'approved',
                'approved_at' => Carbon::parse('2026-09-10 14:00:00'),
                'approval_notes' => 'Selamat berlibur',
            ]);
            $this->command->info('✓ Pengajuan Cuti (2 records: 1 Pending, 1 Approved)');
        }

        // 5. Seed Pengumuman (Announcements)
        Announcement::where('company_id', $company->id)->delete();
        Announcement::create([
            'company_id' => $company->id,
            'created_by' => 1,
            'title' => 'Pengumuman Rilis Aplikasi Mobile GajiPro v1.0',
            'content' => 'Aplikasi mobile GajiPro telah siap digunakan untuk seluruh karyawan. Fitur presensi wajah, pengajuan cuti, lembur, dan slip gaji kini dapat diakses langsung dari smartphone Anda.',
            'priority' => Announcement::PRIORITY_URGENT,
            'target_audience' => Announcement::TARGET_ALL,
            'is_pinned' => true,
            'is_published' => true,
            'published_at' => now()->subHours(2),
        ]);

        Announcement::create([
            'company_id' => $company->id,
            'created_by' => 1,
            'title' => 'Update Kebijakan Pengajuan Klaim & Reimbursement',
            'content' => 'Batas pengajuan reimbursement setiap bulannya adalah tanggal 25. Bukti struk/kuitansi digital wajib diunggah secara jelas.',
            'priority' => Announcement::PRIORITY_HIGH,
            'target_audience' => Announcement::TARGET_ALL,
            'is_pinned' => false,
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        Announcement::create([
            'company_id' => $company->id,
            'created_by' => 1,
            'title' => 'Jadwal Pemeliharaan Server Bulanan',
            'content' => 'Akan diadakan pemeliharaan rutin pada hari Minggu dini hari pukul 01:00 - 03:00 WIB. Layanan akan kembali normal setelahnya.',
            'priority' => Announcement::PRIORITY_NORMAL,
            'target_audience' => Announcement::TARGET_ALL,
            'is_pinned' => false,
            'is_published' => true,
            'published_at' => now()->subDays(5),
        ]);
        $this->command->info('✓ Pengumuman (3 records: 1 Urgent Pinned, 1 High, 1 Normal)');

        $this->command->info('Semua data seed mobile testing berhasil disiapkan!');
    }
}
