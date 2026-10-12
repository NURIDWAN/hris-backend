<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk mengisi data menu Kehadiran PT Demo GajiPro
 *
 * Data yang dibuat:
 * =================
 *
 * 1. Jadwal Kerja (Work Schedules)
 *    - Reguler Office (08:00-17:00)
 *    - Shift Pagi (06:00-14:00)
 *    - Shift Siang (14:00-22:00)
 *    - Shift Malam (22:00-06:00)
 *    - Flexi Time (07:00-16:00)
 *
 * 2. Hari Libur 2026
 *    - Libur Nasional Indonesia
 *    - Cuti Bersama
 *
 * 3. Pengaturan Lembur
 *    - Setting tarif lembur sesuai UU Ketenagakerjaan
 *
 * 4. Jenis Cuti
 *    - Cuti Tahunan, Sakit, Melahirkan, dll
 *
 * 5. Saldo Cuti per Karyawan
 *
 * 6. Data Kehadiran (3 bulan terakhir)
 *
 * 7. Pengajuan Cuti (berbagai status)
 *
 * 8. Pengajuan Lembur (berbagai status)
 */
class DemoAttendanceDataSeeder extends Seeder
{
    private Company $company;

    private array $schedules = [];

    private array $leaveTypes = [];

    private array $locations = [];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Mengisi data kehadiran untuk: {$this->company->name}");

        // Load office locations
        $this->locations = OfficeLocation::where('company_id', $this->company->id)
            ->get()
            ->keyBy('code')
            ->toArray();

        // 1. Jadwal Kerja
        $this->createWorkSchedules();

        // 2. Hari Libur
        $this->createHolidays();

        // 3. Pengaturan Lembur
        $this->createOvertimeSettings();

        // 4. Jenis Cuti
        $this->createLeaveTypes();

        // 5. Saldo Cuti
        $this->createLeaveBalances();

        // 6. Data Kehadiran
        $this->createAttendanceData();

        // 7. Pengajuan Cuti
        $this->createLeaveRequests();

        // 8. Pengajuan Lembur
        $this->createOvertimeRequests();

        $this->command->info('Data kehadiran berhasil dibuat!');
        $this->printSummary();
    }

    private function createWorkSchedules(): void
    {
        $this->command->info('Membuat jadwal kerja...');

        // Hapus jadwal lama
        WorkSchedule::where('company_id', $this->company->id)->forceDelete();

        $schedulesData = [
            [
                'name' => 'Reguler Office',
                'code' => 'REG',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5], // Senin-Jumat
                'late_tolerance' => 15,
                'early_leave_tolerance' => 15,
                'is_flexible' => false,
                'is_default' => true,
                'description' => 'Jadwal kerja standar kantor (08:00-17:00)',
            ],
            [
                'name' => 'Shift Pagi',
                'code' => 'SHIFT-1',
                'start_time' => '06:00',
                'end_time' => '14:00',
                'break_start' => '10:00',
                'break_end' => '10:30',
                'break_duration' => 30,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5, 6], // Senin-Sabtu
                'late_tolerance' => 10,
                'early_leave_tolerance' => 10,
                'is_flexible' => false,
                'is_default' => false,
                'description' => 'Shift pagi untuk operasional pabrik',
            ],
            [
                'name' => 'Shift Siang',
                'code' => 'SHIFT-2',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'break_start' => '18:00',
                'break_end' => '18:30',
                'break_duration' => 30,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5, 6],
                'late_tolerance' => 10,
                'early_leave_tolerance' => 10,
                'is_flexible' => false,
                'is_default' => false,
                'description' => 'Shift siang untuk operasional pabrik',
            ],
            [
                'name' => 'Shift Malam',
                'code' => 'SHIFT-3',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'break_start' => '02:00',
                'break_end' => '02:30',
                'break_duration' => 30,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5, 6],
                'late_tolerance' => 10,
                'early_leave_tolerance' => 10,
                'is_flexible' => false,
                'is_default' => false,
                'description' => 'Shift malam untuk operasional pabrik',
            ],
            [
                'name' => 'Flexi Time',
                'code' => 'FLEXI',
                'start_time' => '07:00',
                'end_time' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => [1, 2, 3, 4, 5],
                'late_tolerance' => 60, // Toleransi lebih lebar
                'early_leave_tolerance' => 30,
                'is_flexible' => true,
                'is_default' => false,
                'description' => 'Jadwal fleksibel untuk divisi IT',
            ],
        ];

        foreach ($schedulesData as $data) {
            $schedule = WorkSchedule::create(array_merge($data, [
                'company_id' => $this->company->id,
                'is_active' => true,
            ]));
            $this->schedules[$data['code']] = $schedule;
        }

        $this->command->info('Total jadwal kerja: '.count($this->schedules));
    }

    private function createHolidays(): void
    {
        $this->command->info('Membuat hari libur 2026...');

        // Hapus holiday lama
        Holiday::where('company_id', $this->company->id)->forceDelete();

        $adminUser = User::where('company_id', $this->company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        $holidays2026 = [
            // Libur Nasional 2026
            ['date' => '2026-01-01', 'name' => 'Tahun Baru 2026', 'type' => 'national'],
            ['date' => '2026-01-29', 'name' => 'Tahun Baru Imlek 2577', 'type' => 'religious'],
            ['date' => '2026-03-20', 'name' => 'Hari Raya Nyepi', 'type' => 'religious'],
            ['date' => '2026-03-31', 'name' => 'Isra Miraj Nabi Muhammad SAW', 'type' => 'religious'],
            ['date' => '2026-04-03', 'name' => 'Wafat Isa Al-Masih', 'type' => 'religious'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional', 'type' => 'national'],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Isa Al-Masih', 'type' => 'religious'],
            ['date' => '2026-05-17', 'name' => 'Idul Fitri 1447 H (Hari 1)', 'type' => 'religious'],
            ['date' => '2026-05-18', 'name' => 'Idul Fitri 1447 H (Hari 2)', 'type' => 'religious'],
            ['date' => '2026-05-26', 'name' => 'Hari Raya Waisak', 'type' => 'religious'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'type' => 'national'],
            ['date' => '2026-07-24', 'name' => 'Idul Adha 1447 H', 'type' => 'religious'],
            ['date' => '2026-08-14', 'name' => 'Tahun Baru Islam 1448 H', 'type' => 'religious'],
            ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI', 'type' => 'national'],
            ['date' => '2026-10-23', 'name' => 'Maulid Nabi Muhammad SAW', 'type' => 'religious'],
            ['date' => '2026-12-25', 'name' => 'Hari Natal', 'type' => 'religious'],

            // Cuti Bersama 2026
            ['date' => '2026-01-02', 'name' => 'Cuti Bersama Tahun Baru', 'type' => 'company'],
            ['date' => '2026-05-15', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'company'],
            ['date' => '2026-05-16', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'company'],
            ['date' => '2026-05-19', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'company'],
            ['date' => '2026-05-20', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'company'],
            ['date' => '2026-12-24', 'name' => 'Cuti Bersama Natal', 'type' => 'company'],
            ['date' => '2026-12-31', 'name' => 'Cuti Bersama Akhir Tahun', 'type' => 'company'],
        ];

        foreach ($holidays2026 as $holiday) {
            Holiday::create([
                'company_id' => $this->company->id,
                'name' => $holiday['name'],
                'date' => $holiday['date'],
                'type' => $holiday['type'],
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ]);
        }

        $this->command->info('Total hari libur: '.count($holidays2026));
    }

    private function createOvertimeSettings(): void
    {
        $this->command->info('Membuat pengaturan lembur...');

        // Hapus setting lama
        OvertimeSetting::where('company_id', $this->company->id)->delete();

        // Sesuai UU Ketenagakerjaan Indonesia
        OvertimeSetting::create([
            'company_id' => $this->company->id,
            'weekday_rate_first_hour' => 1.5, // 1.5x upah per jam untuk jam pertama
            'weekday_rate_next_hours' => 2.0, // 2x upah per jam untuk jam berikutnya
            'weekend_rate' => 2.0, // 2x untuk 8 jam pertama di hari libur
            'holiday_rate' => 3.0, // 3x untuk jam berikutnya di hari libur
            'max_overtime_hours_per_day' => 4,
            'max_overtime_hours_per_week' => 18,
            'min_overtime_minutes' => 30, // Minimal 30 menit untuk dihitung lembur
            'working_hours_per_month' => 173, // Standar 173 jam per bulan
            'requires_approval' => true,
            'auto_calculate_from_attendance' => false,
            'is_active' => true,
        ]);

        $this->command->info('Pengaturan lembur berhasil dibuat');
    }

    private function createLeaveTypes(): void
    {
        $this->command->info('Membuat jenis cuti...');

        // Hapus jenis cuti lama
        LeaveType::where('company_id', $this->company->id)->forceDelete();

        $leaveTypesData = [
            [
                'name' => 'Cuti Tahunan',
                'code' => 'CT',
                'default_days' => 12,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => false,
                'max_consecutive_days' => 12,
                'min_notice_days' => 3,
                'is_carry_forward' => true,
                'max_carry_forward_days' => 6,
                'is_annual' => true,
                'color' => 'primary',
                'description' => 'Cuti tahunan sesuai UU Ketenagakerjaan (12 hari/tahun setelah 1 tahun kerja)',
            ],
            [
                'name' => 'Cuti Sakit',
                'code' => 'CS',
                'default_days' => 14,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true, // Surat dokter
                'max_consecutive_days' => 14,
                'min_notice_days' => 0,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => true,
                'color' => 'danger',
                'description' => 'Cuti sakit dengan surat keterangan dokter',
            ],
            [
                'name' => 'Cuti Melahirkan',
                'code' => 'CM',
                'default_days' => 90,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'max_consecutive_days' => 90,
                'min_notice_days' => 30,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => false,
                'color' => 'pink',
                'description' => 'Cuti melahirkan 3 bulan (1.5 bulan sebelum + 1.5 bulan sesudah)',
            ],
            [
                'name' => 'Cuti Menikah',
                'code' => 'CMN',
                'default_days' => 3,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'max_consecutive_days' => 3,
                'min_notice_days' => 7,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => false,
                'color' => 'success',
                'description' => 'Cuti pernikahan karyawan',
            ],
            [
                'name' => 'Cuti Menikahkan Anak',
                'code' => 'CMA',
                'default_days' => 2,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'max_consecutive_days' => 2,
                'min_notice_days' => 7,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => false,
                'color' => 'success',
                'description' => 'Cuti untuk menikahkan anak',
            ],
            [
                'name' => 'Cuti Khitanan/Baptis Anak',
                'code' => 'CKB',
                'default_days' => 2,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => false,
                'max_consecutive_days' => 2,
                'min_notice_days' => 3,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => false,
                'color' => 'info',
                'description' => 'Cuti khitanan atau pembaptisan anak',
            ],
            [
                'name' => 'Cuti Kematian',
                'code' => 'CK',
                'default_days' => 2,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_attachment' => false,
                'max_consecutive_days' => 2,
                'min_notice_days' => 0,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => false,
                'color' => 'secondary',
                'description' => 'Cuti kematian anggota keluarga (2 hari untuk keluarga inti)',
            ],
            [
                'name' => 'Izin Tidak Masuk',
                'code' => 'ITM',
                'default_days' => 6,
                'is_paid' => false,
                'requires_approval' => true,
                'requires_attachment' => false,
                'max_consecutive_days' => 3,
                'min_notice_days' => 1,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_annual' => true,
                'color' => 'warning',
                'description' => 'Izin tidak masuk kerja (tanpa gaji)',
            ],
        ];

        foreach ($leaveTypesData as $data) {
            $leaveType = LeaveType::create(array_merge($data, [
                'company_id' => $this->company->id,
                'is_active' => true,
            ]));
            $this->leaveTypes[$data['code']] = $leaveType;
        }

        $this->command->info('Total jenis cuti: '.count($this->leaveTypes));
    }

    private function createLeaveBalances(): void
    {
        $this->command->info('Membuat saldo cuti karyawan...');

        // Hapus saldo cuti lama
        LeaveBalance::where('company_id', $this->company->id)->forceDelete();

        $employees = Employee::where('company_id', $this->company->id)->get();
        $currentYear = Carbon::now()->year;

        $balanceCount = 0;
        foreach ($employees as $employee) {
            // Hitung masa kerja
            $yearsOfService = $employee->hire_date
                ? Carbon::parse($employee->hire_date)->diffInYears(Carbon::now())
                : 0;

            foreach ($this->leaveTypes as $code => $leaveType) {
                // Skip cuti khusus yang tidak perlu saldo tahunan
                if (in_array($code, ['CM', 'CMN', 'CMA', 'CKB', 'CK'])) {
                    continue;
                }

                $entitledDays = $leaveType->default_days;

                // Untuk cuti tahunan, baru dapat jatah setelah 1 tahun kerja
                if ($code === 'CT' && $yearsOfService < 1) {
                    // Prorata untuk tahun pertama (setelah 6 bulan)
                    $monthsWorked = $employee->hire_date
                        ? Carbon::parse($employee->hire_date)->diffInMonths(Carbon::now())
                        : 0;
                    if ($monthsWorked >= 6) {
                        $entitledDays = round(($monthsWorked / 12) * 12);
                    } else {
                        $entitledDays = 0;
                    }
                }

                // Tambahkan carry forward untuk karyawan lama
                $carriedForward = 0;
                if ($code === 'CT' && $yearsOfService >= 1) {
                    $carriedForward = rand(0, min(3, $leaveType->max_carry_forward_days ?? 0));
                }

                // Simulasi penggunaan cuti
                $usedDays = 0;
                if ($entitledDays > 0) {
                    // Beberapa karyawan sudah menggunakan cuti
                    $usedDays = rand(0, min(5, (int) $entitledDays));
                }

                LeaveBalance::create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $currentYear,
                    'entitled_days' => $entitledDays,
                    'used_days' => $usedDays,
                    'pending_days' => 0,
                    'carried_forward_days' => $carriedForward,
                    'adjustment_days' => 0,
                ]);
                $balanceCount++;
            }
        }

        $this->command->info("Total saldo cuti dibuat: {$balanceCount}");
    }

    private function createAttendanceData(): void
    {
        $this->command->info('Membuat data kehadiran (3 bulan terakhir)...');

        // Hapus attendance lama
        Attendance::where('company_id', $this->company->id)->forceDelete();

        $employees = Employee::where('company_id', $this->company->id)
            ->with(['department'])
            ->get();

        // Assign jadwal kerja ke karyawan berdasarkan departemen
        $departmentScheduleMap = [
            'DEPT-PRD' => ['SHIFT-1', 'SHIFT-2', 'SHIFT-3'], // Produksi - rotasi shift
            'DEPT-QC' => ['SHIFT-1', 'SHIFT-2'], // QC - shift pagi/siang
            'DEPT-WH' => ['SHIFT-1', 'SHIFT-2'], // Warehouse - shift pagi/siang
            'DEPT-DEV' => ['FLEXI'], // Developer - flexi
            'DEPT-INF' => ['FLEXI'], // Infrastructure - flexi
        ];

        // Get holidays untuk skip
        $holidays = Holiday::where('company_id', $this->company->id)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $startDate = Carbon::now()->subMonths(3)->startOfMonth();
        $endDate = Carbon::now()->subDay(); // Sampai kemarin

        $attendanceCount = 0;

        foreach ($employees as $employee) {
            // Tentukan jadwal berdasarkan departemen
            $deptCode = $employee->department?->code;
            $scheduleCode = 'REG'; // Default

            if (isset($departmentScheduleMap[$deptCode])) {
                $scheduleOptions = $departmentScheduleMap[$deptCode];
                $scheduleCode = $scheduleOptions[array_rand($scheduleOptions)];
            }

            $schedule = $this->schedules[$scheduleCode] ?? $this->schedules['REG'];
            $workingDays = $schedule->working_days ?? [1, 2, 3, 4, 5];

            // Get employee location
            $employeeLocation = DB::table('employee_office_locations')
                ->where('employee_id', $employee->id)
                ->where('is_primary', true)
                ->first();

            $locationId = $employeeLocation?->office_location_id;

            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $dayOfWeek = $currentDate->dayOfWeekIso; // 1=Monday, 7=Sunday
                $dateStr = $currentDate->format('Y-m-d');

                // Skip weekend (jika bukan hari kerja)
                if (! in_array($dayOfWeek, $workingDays)) {
                    $currentDate->addDay();

                    continue;
                }

                // Skip holiday
                if (in_array($dateStr, $holidays)) {
                    $currentDate->addDay();

                    continue;
                }

                // Generate attendance data dengan variasi realistis
                $attendanceData = $this->generateAttendanceData(
                    $employee,
                    $schedule,
                    $currentDate,
                    $locationId
                );

                if ($attendanceData) {
                    Attendance::create($attendanceData);
                    $attendanceCount++;
                }

                $currentDate->addDay();
            }
        }

        $this->command->info("Total data kehadiran: {$attendanceCount}");
    }

    private function generateAttendanceData(
        Employee $employee,
        WorkSchedule $schedule,
        Carbon $date,
        ?int $locationId
    ): ?array {
        // 5% chance absent
        if (rand(1, 100) <= 5) {
            return null;
        }

        $scheduledStart = Carbon::parse($date->format('Y-m-d').' '.$schedule->start_time->format('H:i:s'));
        $scheduledEnd = Carbon::parse($date->format('Y-m-d').' '.$schedule->end_time->format('H:i:s'));

        if ($schedule->is_overnight) {
            $scheduledEnd->addDay();
        }

        // Generate clock in time dengan variasi
        $clockInVariation = rand(-15, 30); // -15 menit (awal) sampai +30 menit (terlambat)
        $clockIn = $scheduledStart->copy()->addMinutes($clockInVariation);

        // Generate clock out time
        $clockOutVariation = rand(-10, 60); // -10 menit (pulang awal) sampai +60 menit (lembur)
        $clockOut = $scheduledEnd->copy()->addMinutes($clockOutVariation);

        // Calculate late minutes
        $lateMinutes = 0;
        $clockInStatus = 'on_time';
        $status = 'present';

        if ($clockIn > $scheduledStart->copy()->addMinutes($schedule->late_tolerance ?? 15)) {
            $lateMinutes = $scheduledStart->diffInMinutes($clockIn);
            $clockInStatus = $lateMinutes > 30 ? 'very_late' : 'late';
            $status = 'late';
        }

        // Calculate early leave / overtime
        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;
        $clockOutStatus = 'on_time';

        if ($clockOut < $scheduledEnd->copy()->subMinutes($schedule->early_leave_tolerance ?? 15)) {
            $earlyLeaveMinutes = $clockOut->diffInMinutes($scheduledEnd);
            $clockOutStatus = 'early';
        } elseif ($clockOut > $scheduledEnd) {
            $overtimeMinutes = $scheduledEnd->diffInMinutes($clockOut);
            $clockOutStatus = 'overtime';
        }

        // Calculate working minutes
        $workingMinutes = $clockIn->diffInMinutes($clockOut);
        if ($schedule->break_duration) {
            $workingMinutes -= $schedule->break_duration;
        }
        $workingMinutes = max(0, $workingMinutes);

        // Generate coordinates based on location
        $location = $locationId ? OfficeLocation::find($locationId) : null;
        $latitude = $location?->latitude ?? -6.2271460;
        $longitude = $location?->longitude ?? 106.8080740;

        // Add small variation to coordinates
        $latVariation = (rand(-100, 100) / 1000000);
        $lngVariation = (rand(-100, 100) / 1000000);

        return [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'office_location_id' => $locationId,
            'work_schedule_id' => $schedule->id,
            'date' => $date->format('Y-m-d'),
            'scheduled_start' => $scheduledStart->format('H:i:s'),
            'scheduled_end' => $schedule->is_overnight
                ? $scheduledEnd->copy()->subDay()->format('H:i:s')
                : $scheduledEnd->format('H:i:s'),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_ip' => '192.168.1.'.rand(10, 250),
            'clock_out_ip' => '192.168.1.'.rand(10, 250),
            'clock_in_latitude' => $latitude + $latVariation,
            'clock_in_longitude' => $longitude + $lngVariation,
            'clock_out_latitude' => $latitude + $latVariation,
            'clock_out_longitude' => $longitude + $lngVariation,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'working_minutes' => $workingMinutes,
            'status' => $status,
            'clock_in_status' => $clockInStatus,
            'clock_out_status' => $clockOutStatus,
            'is_manual_entry' => false,
            'face_verified' => true,
            'face_confidence' => rand(85, 99) / 100,
        ];
    }

    private function createLeaveRequests(): void
    {
        $this->command->info('Membuat pengajuan cuti...');

        // Hapus leave request lama
        LeaveRequest::where('company_id', $this->company->id)->forceDelete();

        $employees = Employee::where('company_id', $this->company->id)
            ->inRandomOrder()
            ->take(15) // 15 karyawan dengan pengajuan cuti
            ->get();

        $adminUser = User::where('company_id', $this->company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        $ctLeaveType = $this->leaveTypes['CT'] ?? null;
        $csLeaveType = $this->leaveTypes['CS'] ?? null;

        if (! $ctLeaveType) {
            return;
        }

        $statuses = ['pending', 'approved', 'approved', 'approved', 'rejected']; // Mostly approved
        $leaveCount = 0;

        foreach ($employees as $index => $employee) {
            // Cuti Tahunan
            $startDate = Carbon::now()->subMonths(rand(0, 2))->addDays(rand(1, 20));
            $endDate = $startDate->copy()->addDays(rand(1, 5));
            $totalDays = $startDate->diffInDays($endDate) + 1;

            $status = $statuses[array_rand($statuses)];

            $leaveRequest = LeaveRequest::create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $ctLeaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'is_half_day' => false,
                'reason' => $this->getRandomLeaveReason(),
                'status' => $status,
                'emergency_contact' => '08'.rand(1000000000, 9999999999),
            ]);

            if ($status === 'approved' && $adminUser) {
                $leaveRequest->update([
                    'approved_by' => $adminUser->id,
                    'approved_at' => $startDate->copy()->subDays(rand(1, 3)),
                    'approval_notes' => 'Disetujui',
                ]);
            } elseif ($status === 'rejected' && $adminUser) {
                $leaveRequest->update([
                    'rejected_by' => $adminUser->id,
                    'rejected_at' => $startDate->copy()->subDays(rand(1, 3)),
                    'rejection_reason' => 'Jadwal tidak memungkinkan, silakan ajukan tanggal lain',
                ]);
            }

            $leaveCount++;

            // Beberapa karyawan juga punya cuti sakit
            if ($index < 5 && $csLeaveType) {
                $sickDate = Carbon::now()->subMonths(rand(1, 2))->subDays(rand(1, 15));

                LeaveRequest::create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $csLeaveType->id,
                    'start_date' => $sickDate,
                    'end_date' => $sickDate->copy()->addDays(rand(1, 3)),
                    'total_days' => rand(1, 3),
                    'is_half_day' => false,
                    'reason' => 'Sakit (dengan surat dokter)',
                    'status' => 'approved',
                    'approved_by' => $adminUser?->id,
                    'approved_at' => $sickDate->copy()->addDay(),
                ]);
                $leaveCount++;
            }
        }

        $this->command->info("Total pengajuan cuti: {$leaveCount}");
    }

    private function getRandomLeaveReason(): string
    {
        $reasons = [
            'Acara keluarga',
            'Liburan keluarga',
            'Keperluan pribadi',
            'Menghadiri acara pernikahan',
            'Perjalanan ke luar kota',
            'Menemani keluarga berobat',
            'Urusan administrasi (KTP/SIM)',
            'Renovasi rumah',
            'Wisuda anak',
            'Reuni keluarga',
        ];

        return $reasons[array_rand($reasons)];
    }

    private function createOvertimeRequests(): void
    {
        $this->command->info('Membuat pengajuan lembur...');

        // Hapus overtime request lama
        OvertimeRequest::where('company_id', $this->company->id)->delete();

        // Ambil karyawan dari departemen yang sering lembur
        $employees = Employee::where('company_id', $this->company->id)
            ->whereHas('department', function ($q) {
                $q->whereIn('code', ['DEPT-PRD', 'DEPT-DEV', 'DEPT-FIN', 'DEPT-ACC']);
            })
            ->inRandomOrder()
            ->take(20)
            ->get();

        $adminUser = User::where('company_id', $this->company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        $overtimeCount = 0;
        $statuses = ['pending', 'approved', 'approved', 'approved', 'rejected'];

        // Track used dates per employee to avoid duplicate
        $usedDates = [];

        foreach ($employees as $employee) {
            $usedDates[$employee->id] = [];

            // 1-3 pengajuan lembur per karyawan
            $numRequests = rand(1, 3);

            for ($i = 0; $i < $numRequests; $i++) {
                // Find a unique date for this employee
                $maxAttempts = 10;
                $attempts = 0;
                $date = null;

                while ($attempts < $maxAttempts) {
                    $date = Carbon::now()->subMonths(rand(0, 2))->subDays(rand(1, 60));

                    // Skip weekend untuk overtime weekday
                    while ($date->isWeekend()) {
                        $date->subDay();
                    }

                    $dateStr = $date->format('Y-m-d');

                    // Check if date already used for this employee
                    if (! in_array($dateStr, $usedDates[$employee->id])) {
                        $usedDates[$employee->id][] = $dateStr;

                        break;
                    }

                    $attempts++;
                }

                if ($attempts >= $maxAttempts) {
                    continue; // Skip if can't find unique date
                }

                // Tentukan tipe overtime
                $dayOfWeek = $date->dayOfWeekIso;
                $overtimeType = 'weekday';
                if ($dayOfWeek >= 6) {
                    $overtimeType = 'weekend';
                }

                // Check if holiday
                $isHoliday = Holiday::where('company_id', $this->company->id)
                    ->whereDate('date', $date)
                    ->exists();
                if ($isHoliday) {
                    $overtimeType = 'holiday';
                }

                $startTime = '17:00';
                $hours = rand(1, 4);
                $endHour = 17 + $hours;
                $endTime = str_pad($endHour, 2, '0', STR_PAD_LEFT).':00';

                $status = $statuses[array_rand($statuses)];

                // Calculate overtime amount (simplified)
                $baseSalary = 5000000; // Assume base salary
                $hourlyRate = $baseSalary / 173;
                $multiplier = $overtimeType === 'weekday' ? 1.5 : ($overtimeType === 'weekend' ? 2.0 : 3.0);
                $overtimeAmount = $hourlyRate * $hours * $multiplier;

                $overtime = OvertimeRequest::create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'overtime_hours' => $hours,
                    'overtime_type' => $overtimeType,
                    'overtime_amount' => round($overtimeAmount),
                    'reason' => $this->getRandomOvertimeReason(),
                    'status' => $status,
                ]);

                if ($status === 'approved' && $adminUser) {
                    $overtime->update([
                        'approved_by' => $adminUser->id,
                        'approved_at' => $date->copy()->subDay(),
                    ]);
                } elseif ($status === 'rejected') {
                    $overtime->update([
                        'rejection_reason' => 'Melebihi kuota lembur bulan ini',
                    ]);
                }

                $overtimeCount++;
            }
        }

        $this->command->info("Total pengajuan lembur: {$overtimeCount}");
    }

    private function getRandomOvertimeReason(): string
    {
        $reasons = [
            'Deadline project',
            'Closing bulanan',
            'Persiapan audit',
            'Maintenance sistem',
            'Target produksi',
            'Perbaikan bug kritis',
            'Implementasi fitur baru',
            'Backup data',
            'Training karyawan baru',
            'Persiapan laporan manajemen',
        ];

        return $reasons[array_rand($reasons)];
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== RINGKASAN DATA KEHADIRAN PT DEMO GAJIPRO ===');
        $this->command->newLine();

        // Work Schedules
        $scheduleCount = WorkSchedule::where('company_id', $this->company->id)->count();
        $this->command->line("Jadwal Kerja: {$scheduleCount}");

        // Holidays
        $holidayCount = Holiday::where('company_id', $this->company->id)->count();
        $this->command->line("Hari Libur 2026: {$holidayCount}");

        // Leave Types
        $leaveTypeCount = LeaveType::where('company_id', $this->company->id)->count();
        $this->command->line("Jenis Cuti: {$leaveTypeCount}");

        // Leave Balances
        $balanceCount = LeaveBalance::where('company_id', $this->company->id)->count();
        $this->command->line("Saldo Cuti Karyawan: {$balanceCount}");

        // Attendance
        $attendanceCount = Attendance::where('company_id', $this->company->id)->count();
        $this->command->line("Data Kehadiran: {$attendanceCount}");

        // Leave Requests
        $leaveStats = LeaveRequest::where('company_id', $this->company->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $this->command->line('Pengajuan Cuti: '.array_sum($leaveStats));
        foreach ($leaveStats as $status => $count) {
            $this->command->line("  - {$status}: {$count}");
        }

        // Overtime Requests
        $overtimeStats = OvertimeRequest::where('company_id', $this->company->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $this->command->line('Pengajuan Lembur: '.array_sum($overtimeStats));
        foreach ($overtimeStats as $status => $count) {
            $this->command->line("  - {$status}: {$count}");
        }

        $this->command->newLine();
    }
}
