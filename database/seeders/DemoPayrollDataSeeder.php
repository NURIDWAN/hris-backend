<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollItemDetail;
use App\Models\PayrollSetting;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\SalaryComponent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk mengisi data menu Payroll PT Demo GajiPro
 *
 * Data yang dibuat:
 * =================
 *
 * 1. Komponen Gaji (Salary Components)
 *    - Earnings: Gaji Pokok, Tunjangan Jabatan, Tunjangan Transport, dll
 *    - Deductions: BPJS Kesehatan, BPJS TK, PPh 21, dll
 *
 * 2. Pengaturan Payroll (Payroll Settings)
 *    - Siklus gaji bulanan
 *    - Cut-off, hari pembayaran, dll
 *
 * 3. Pengaturan Gaji Karyawan (Employee Salaries)
 *    - Gaji pokok per karyawan
 *    - Komponen gaji tambahan
 *    - Info bank
 *
 * 4. Kategori Reimbursement
 *    - Transport, Medical, Meals, dll
 *
 * 5. Pengajuan Reimbursement
 *
 * 6. Riwayat Payroll (3 bulan terakhir)
 *    - Payroll dengan status paid
 *    - Payroll items dengan detail
 */
class DemoPayrollDataSeeder extends Seeder
{
    private Company $company;

    private array $components = [];

    private array $categories = [];

    private ?User $adminUser = null;

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Mengisi data payroll untuk: {$this->company->name}");

        $this->adminUser = User::where('company_id', $this->company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        // 1. Komponen Gaji
        $this->createSalaryComponents();

        // 2. Pengaturan Payroll
        $this->createPayrollSettings();

        // 3. Pengaturan Gaji Karyawan
        $this->createEmployeeSalaries();

        // 4. Kategori Reimbursement
        $this->createReimbursementCategories();

        // 5. Pengajuan Reimbursement
        $this->createReimbursements();

        // 6. Riwayat Payroll
        $this->createPayrollHistory();

        $this->command->info('Data payroll berhasil dibuat!');
        $this->printSummary();
    }

    private function createSalaryComponents(): void
    {
        $this->command->info('Membuat komponen gaji...');

        // Hapus komponen lama
        SalaryComponent::where('company_id', $this->company->id)->forceDelete();

        $componentsData = [
            // === EARNINGS ===
            [
                'name' => 'Gaji Pokok',
                'code' => 'BASIC',
                'type' => 'earning',
                'category' => 'fixed',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_mandatory' => true,
                'sort_order' => 1,
                'description' => 'Gaji pokok karyawan',
            ],
            [
                'name' => 'Tunjangan Jabatan',
                'code' => 'TJ-JABATAN',
                'type' => 'earning',
                'category' => 'benefit',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_mandatory' => false,
                'sort_order' => 2,
                'description' => 'Tunjangan berdasarkan jabatan',
            ],
            [
                'name' => 'Tunjangan Transport',
                'code' => 'TJ-TRANSPORT',
                'type' => 'earning',
                'category' => 'benefit',
                'calculation_type' => 'fixed',
                'default_amount' => 500000,
                'is_taxable' => true,
                'is_mandatory' => false,
                'is_attendance_based' => true,
                'attendance_calculation' => 'daily',
                'daily_rate' => 25000,
                'sort_order' => 3,
                'description' => 'Tunjangan transport Rp 25.000/hari kehadiran',
            ],
            [
                'name' => 'Tunjangan Makan',
                'code' => 'TJ-MAKAN',
                'type' => 'earning',
                'category' => 'benefit',
                'calculation_type' => 'fixed',
                'default_amount' => 660000,
                'is_taxable' => true,
                'is_mandatory' => false,
                'is_attendance_based' => true,
                'attendance_calculation' => 'daily',
                'daily_rate' => 30000,
                'sort_order' => 4,
                'description' => 'Tunjangan makan Rp 30.000/hari kehadiran',
            ],
            [
                'name' => 'Tunjangan Komunikasi',
                'code' => 'TJ-KOMUNIKASI',
                'type' => 'earning',
                'category' => 'benefit',
                'calculation_type' => 'fixed',
                'default_amount' => 200000,
                'is_taxable' => true,
                'is_mandatory' => false,
                'sort_order' => 5,
                'description' => 'Tunjangan pulsa/internet',
            ],
            [
                'name' => 'Tunjangan Keluarga',
                'code' => 'TJ-KELUARGA',
                'type' => 'earning',
                'category' => 'benefit',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_mandatory' => false,
                'sort_order' => 6,
                'description' => 'Tunjangan untuk karyawan yang sudah menikah/punya anak',
            ],
            [
                'name' => 'Insentif Kinerja',
                'code' => 'INSENTIF',
                'type' => 'earning',
                'category' => 'variable',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_mandatory' => false,
                'sort_order' => 7,
                'description' => 'Insentif berdasarkan pencapaian KPI',
            ],
            [
                'name' => 'Lembur',
                'code' => 'OVERTIME',
                'type' => 'earning',
                'category' => 'variable',
                'calculation_type' => 'fixed',
                'is_taxable' => true,
                'is_mandatory' => false,
                'sort_order' => 8,
                'description' => 'Upah lembur berdasarkan jam lembur yang disetujui',
            ],

            // === DEDUCTIONS ===
            [
                'name' => 'BPJS Kesehatan',
                'code' => 'BPJS-KES',
                'type' => 'deduction',
                'category' => 'insurance',
                'calculation_type' => 'percentage',
                'percentage' => 1.00,
                'percentage_base' => 'basic_salary',
                'is_taxable' => false,
                'is_mandatory' => true,
                'sort_order' => 10,
                'description' => 'Iuran BPJS Kesehatan karyawan (1%)',
            ],
            [
                'name' => 'BPJS JHT',
                'code' => 'BPJS-JHT',
                'type' => 'deduction',
                'category' => 'insurance',
                'calculation_type' => 'percentage',
                'percentage' => 2.00,
                'percentage_base' => 'basic_salary',
                'is_taxable' => false,
                'is_mandatory' => true,
                'sort_order' => 11,
                'description' => 'Iuran BPJS Jaminan Hari Tua karyawan (2%)',
            ],
            [
                'name' => 'BPJS JP',
                'code' => 'BPJS-JP',
                'type' => 'deduction',
                'category' => 'insurance',
                'calculation_type' => 'percentage',
                'percentage' => 1.00,
                'percentage_base' => 'basic_salary',
                'is_taxable' => false,
                'is_mandatory' => true,
                'sort_order' => 12,
                'description' => 'Iuran BPJS Jaminan Pensiun karyawan (1%)',
            ],
            [
                'name' => 'PPh 21',
                'code' => 'PPH21',
                'type' => 'deduction',
                'category' => 'tax',
                'calculation_type' => 'formula',
                'is_taxable' => false,
                'is_mandatory' => true,
                'sort_order' => 13,
                'description' => 'Pajak Penghasilan Pasal 21',
            ],
            [
                'name' => 'Potongan Ketidakhadiran',
                'code' => 'POT-ABSEN',
                'type' => 'deduction',
                'category' => 'other',
                'calculation_type' => 'fixed',
                'is_taxable' => false,
                'is_mandatory' => false,
                'sort_order' => 14,
                'description' => 'Potongan untuk hari tidak hadir',
            ],
            [
                'name' => 'Potongan Pinjaman',
                'code' => 'POT-PINJAMAN',
                'type' => 'deduction',
                'category' => 'loan',
                'calculation_type' => 'fixed',
                'is_taxable' => false,
                'is_mandatory' => false,
                'sort_order' => 15,
                'description' => 'Cicilan pinjaman karyawan',
            ],
        ];

        foreach ($componentsData as $data) {
            $component = SalaryComponent::create(array_merge($data, [
                'company_id' => $this->company->id,
                'is_active' => true,
            ]));
            $this->components[$data['code']] = $component;
        }

        $this->command->info('Total komponen gaji: '.count($this->components));
    }

    private function createPayrollSettings(): void
    {
        $this->command->info('Membuat pengaturan payroll...');

        // Hapus setting lama
        PayrollSetting::where('company_id', $this->company->id)->forceDelete();

        PayrollSetting::create([
            'company_id' => $this->company->id,
            'cycle_type' => 'monthly',
            'cutoff_day' => 25, // Cut-off tanggal 25
            'default_working_days' => 22,
            'auto_deduct_absent' => true,
            'auto_deduct_late' => true,
            'late_penalty_threshold' => 30, // Lebih dari 30 menit dihitung terlambat
            'late_penalty_amount' => 50000, // Potongan Rp 50.000 per hari terlambat
            'late_penalty_type' => 'fixed',
            'absent_deduction_type' => 'daily_rate', // Potong 1/22 gaji pokok
            'absent_deduction_amount' => 0, // Tidak digunakan karena pakai daily_rate
            'prorate_new_employees' => true,
            'prorate_resigned_employees' => true,
            'include_overtime_in_payroll' => true,
            'require_overtime_approval' => true,
            'include_reimbursement_in_payroll' => false, // Reimbursement terpisah
            'payment_day' => 28, // Gaji dibayar tanggal 28
            'is_active' => true,
            'notes' => 'Pengaturan payroll standar PT Demo GajiPro',
        ]);

        $this->command->info('Pengaturan payroll berhasil dibuat');
    }

    private function createEmployeeSalaries(): void
    {
        $this->command->info('Membuat pengaturan gaji karyawan...');

        // Hapus employee salary lama
        EmployeeSalaryComponent::query()->delete();
        EmployeeSalary::where('company_id', $this->company->id)->forceDelete();

        $employees = Employee::where('company_id', $this->company->id)
            ->with(['position'])
            ->get();

        // Bank options
        $banks = ['BCA', 'Mandiri', 'BRI', 'BNI', 'CIMB Niaga', 'Permata'];

        $salaryCount = 0;
        foreach ($employees as $employee) {
            // Tentukan gaji berdasarkan position salary range
            $position = $employee->position;
            $baseSalary = $position?->min_salary ?? 5000000;

            // Variasi gaji dalam range
            if ($position && $position->max_salary > $position->min_salary) {
                $range = $position->max_salary - $position->min_salary;
                $baseSalary = $position->min_salary + rand(0, (int) ($range * 0.5));
            }

            // Round to nearest 100.000
            $baseSalary = round($baseSalary / 100000) * 100000;

            $bank = $banks[array_rand($banks)];

            $employeeSalary = EmployeeSalary::create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'basic_salary' => $baseSalary,
                'effective_date' => $employee->hire_date ?? Carbon::now()->subYear(),
                'payment_method' => 'transfer',
                'bank_name' => $bank,
                'bank_account_number' => $this->generateBankAccountNumber($bank),
                'bank_account_name' => $employee->full_name,
                'is_active' => true,
            ]);

            // Add salary components berdasarkan level/position
            $this->addEmployeeSalaryComponents($employeeSalary, $employee, $baseSalary);

            $salaryCount++;
        }

        $this->command->info("Total pengaturan gaji karyawan: {$salaryCount}");
    }

    private function addEmployeeSalaryComponents(EmployeeSalary $employeeSalary, Employee $employee, float $baseSalary): void
    {
        $positionLevel = $employee->position?->level ?? 'staff';

        // Tunjangan Jabatan berdasarkan level
        $jabatanAmounts = [
            'director' => 5000000,
            'manager' => 2500000,
            'supervisor' => 1500000,
            'senior' => 750000,
            'staff' => 0,
        ];

        if (isset($this->components['TJ-JABATAN']) && ($jabatanAmounts[$positionLevel] ?? 0) > 0) {
            EmployeeSalaryComponent::create([
                'employee_salary_id' => $employeeSalary->id,
                'salary_component_id' => $this->components['TJ-JABATAN']->id,
                'amount' => $jabatanAmounts[$positionLevel],
            ]);
        }

        // Tunjangan Transport (semua karyawan)
        if (isset($this->components['TJ-TRANSPORT'])) {
            EmployeeSalaryComponent::create([
                'employee_salary_id' => $employeeSalary->id,
                'salary_component_id' => $this->components['TJ-TRANSPORT']->id,
                'amount' => 500000,
            ]);
        }

        // Tunjangan Makan (semua karyawan)
        if (isset($this->components['TJ-MAKAN'])) {
            EmployeeSalaryComponent::create([
                'employee_salary_id' => $employeeSalary->id,
                'salary_component_id' => $this->components['TJ-MAKAN']->id,
                'amount' => 660000,
            ]);
        }

        // Tunjangan Komunikasi (supervisor ke atas)
        if (isset($this->components['TJ-KOMUNIKASI']) && in_array($positionLevel, ['director', 'manager', 'supervisor'])) {
            $komunikasiAmounts = [
                'director' => 500000,
                'manager' => 300000,
                'supervisor' => 200000,
            ];
            EmployeeSalaryComponent::create([
                'employee_salary_id' => $employeeSalary->id,
                'salary_component_id' => $this->components['TJ-KOMUNIKASI']->id,
                'amount' => $komunikasiAmounts[$positionLevel] ?? 0,
            ]);
        }

        // Tunjangan Keluarga (jika status menikah - random 40%)
        if (isset($this->components['TJ-KELUARGA']) && rand(1, 100) <= 40) {
            EmployeeSalaryComponent::create([
                'employee_salary_id' => $employeeSalary->id,
                'salary_component_id' => $this->components['TJ-KELUARGA']->id,
                'amount' => 500000,
            ]);
        }
    }

    private function generateBankAccountNumber(string $bank): string
    {
        $prefixes = [
            'BCA' => '123',
            'Mandiri' => '128',
            'BRI' => '002',
            'BNI' => '008',
            'CIMB Niaga' => '022',
            'Permata' => '013',
        ];

        $prefix = $prefixes[$bank] ?? '000';

        return $prefix.rand(1000000, 9999999);
    }

    private function createReimbursementCategories(): void
    {
        $this->command->info('Membuat kategori reimbursement...');

        // Hapus kategori lama
        ReimbursementCategory::where('company_id', $this->company->id)->delete();

        $categoriesData = [
            [
                'name' => 'Transport Dinas',
                'description' => 'Penggantian biaya transportasi untuk keperluan dinas',
                'max_amount' => 1000000,
                'requires_receipt' => true,
            ],
            [
                'name' => 'Pengobatan/Medical',
                'description' => 'Penggantian biaya pengobatan yang tidak ditanggung BPJS',
                'max_amount' => 2000000,
                'requires_receipt' => true,
            ],
            [
                'name' => 'Makan Dinas',
                'description' => 'Penggantian biaya makan untuk keperluan dinas/lembur',
                'max_amount' => 500000,
                'requires_receipt' => true,
            ],
            [
                'name' => 'Akomodasi Dinas',
                'description' => 'Penggantian biaya hotel/penginapan untuk perjalanan dinas',
                'max_amount' => 1500000,
                'requires_receipt' => true,
            ],
            [
                'name' => 'Perlengkapan Kerja',
                'description' => 'Penggantian biaya perlengkapan kerja (alat tulis, dll)',
                'max_amount' => 300000,
                'requires_receipt' => true,
            ],
            [
                'name' => 'Parkir',
                'description' => 'Penggantian biaya parkir untuk keperluan dinas',
                'max_amount' => 200000,
                'requires_receipt' => false,
            ],
            [
                'name' => 'Internet/Pulsa',
                'description' => 'Penggantian biaya internet/pulsa untuk keperluan kerja',
                'max_amount' => 300000,
                'requires_receipt' => true,
            ],
            [
                'name' => 'Lainnya',
                'description' => 'Penggantian biaya lainnya yang tidak termasuk kategori di atas',
                'max_amount' => null,
                'requires_receipt' => true,
            ],
        ];

        foreach ($categoriesData as $data) {
            $category = ReimbursementCategory::create(array_merge($data, [
                'company_id' => $this->company->id,
                'is_active' => true,
            ]));
            $this->categories[$data['name']] = $category;
        }

        $this->command->info('Total kategori reimbursement: '.count($this->categories));
    }

    private function createReimbursements(): void
    {
        $this->command->info('Membuat pengajuan reimbursement...');

        // Hapus reimbursement lama
        Reimbursement::where('company_id', $this->company->id)->delete();

        $employees = Employee::where('company_id', $this->company->id)
            ->inRandomOrder()
            ->take(25)
            ->get();

        $statuses = ['pending', 'approved', 'approved', 'approved', 'rejected', 'paid', 'paid'];
        $descriptions = [
            'Transport Dinas' => [
                'Grab/Gojek untuk meeting di client',
                'Tiket kereta ke Bandung untuk kunjungan cabang',
                'Bensin perjalanan dinas ke Cikarang',
                'Tol perjalanan dinas Jakarta-Tangerang',
            ],
            'Pengobatan/Medical' => [
                'Biaya konsultasi dokter spesialis',
                'Pembelian obat resep dokter',
                'Medical check up tahunan',
                'Biaya fisioterapi',
            ],
            'Makan Dinas' => [
                'Makan siang dengan client',
                'Makan malam lembur project',
                'Coffee meeting dengan vendor',
            ],
            'Akomodasi Dinas' => [
                'Hotel 2 malam di Surabaya untuk training',
                'Hotel 1 malam di Bandung untuk meeting',
            ],
            'Perlengkapan Kerja' => [
                'Pembelian alat tulis kantor',
                'Mouse dan keyboard untuk WFH',
                'Headset untuk meeting online',
            ],
            'Parkir' => [
                'Parkir meeting di gedung client',
                'Parkir bulanan kantor',
            ],
            'Internet/Pulsa' => [
                'Paket internet untuk WFH',
                'Pulsa untuk telepon client',
            ],
        ];

        $reimbursementCount = 0;

        foreach ($employees as $employee) {
            // 1-3 reimbursement per karyawan
            $numReimbursements = rand(1, 3);

            for ($i = 0; $i < $numReimbursements; $i++) {
                $categoryName = array_rand($this->categories);
                $category = $this->categories[$categoryName];

                $maxAmount = $category->max_amount ?? 500000;
                $amount = rand((int) ($maxAmount * 0.2), (int) $maxAmount);
                $amount = round($amount / 10000) * 10000; // Round to nearest 10k

                $descOptions = $descriptions[$categoryName] ?? ['Biaya operasional'];
                $description = $descOptions[array_rand($descOptions)];

                $expenseDate = Carbon::now()->subDays(rand(1, 60));
                $status = $statuses[array_rand($statuses)];

                $reimbursement = Reimbursement::create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'category_id' => $category->id,
                    'amount' => $amount,
                    'description' => $description,
                    'expense_date' => $expenseDate,
                    'status' => $status,
                ]);

                if (in_array($status, ['approved', 'paid']) && $this->adminUser) {
                    $reimbursement->update([
                        'approved_by' => $this->adminUser->id,
                        'approved_at' => $expenseDate->copy()->addDays(rand(1, 5)),
                    ]);
                }

                if ($status === 'paid') {
                    $reimbursement->update([
                        'paid_at' => $expenseDate->copy()->addDays(rand(5, 10)),
                        'payment_method' => 'transfer',
                    ]);
                }

                if ($status === 'rejected') {
                    $reimbursement->update([
                        'rejection_reason' => 'Bukti transaksi tidak lengkap atau tidak sesuai ketentuan',
                    ]);
                }

                $reimbursementCount++;
            }
        }

        $this->command->info("Total pengajuan reimbursement: {$reimbursementCount}");
    }

    private function createPayrollHistory(): void
    {
        $this->command->info('Membuat riwayat payroll (3 bulan terakhir)...');

        // Hapus payroll lama
        PayrollItemDetail::query()
            ->whereHas('payrollItem.payroll', fn ($q) => $q->where('company_id', $this->company->id))
            ->delete();
        PayrollItem::query()
            ->whereHas('payroll', fn ($q) => $q->where('company_id', $this->company->id))
            ->delete();
        Payroll::where('company_id', $this->company->id)->forceDelete();

        $employees = Employee::where('company_id', $this->company->id)
            ->with(['department', 'position', 'currentSalary.components.salaryComponent'])
            ->get();

        // Generate payroll untuk 3 bulan terakhir (sudah dibayar) + bulan ini (draft)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $months = [
            ['year' => $currentYear, 'month' => $currentMonth - 3, 'status' => 'paid'],
            ['year' => $currentYear, 'month' => $currentMonth - 2, 'status' => 'paid'],
            ['year' => $currentYear, 'month' => $currentMonth - 1, 'status' => 'paid'],
        ];

        // Adjust for year boundary
        foreach ($months as &$m) {
            if ($m['month'] <= 0) {
                $m['month'] += 12;
                $m['year'] -= 1;
            }
        }

        foreach ($months as $period) {
            $this->createPayrollForPeriod($period['year'], $period['month'], $period['status'], $employees);
        }
    }

    private function createPayrollForPeriod(int $year, int $month, string $status, $employees): void
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $periodStart = Carbon::create($year, $month, 26)->subMonth();
        $periodEnd = Carbon::create($year, $month, 25);
        $paymentDate = Carbon::create($year, $month, 28);

        $this->command->info("  Membuat payroll {$monthNames[$month]} {$year}...");

        // Create payroll header
        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'name' => "Payroll {$monthNames[$month]} {$year}",
            'period_month' => $month,
            'period_year' => $year,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_date' => $paymentDate,
            'status' => $status,
            'total_employees' => 0,
            'total_earnings' => 0,
            'total_deductions' => 0,
            'total_net_salary' => 0,
            'created_by' => $this->adminUser?->id,
            'notes' => "Payroll periode {$monthNames[$month]} {$year}",
        ]);

        if ($status === 'paid') {
            $payroll->update([
                'approved_by' => $this->adminUser?->id,
                'approved_at' => $paymentDate->copy()->subDays(2),
                'paid_by' => $this->adminUser?->id,
                'paid_at' => $paymentDate,
            ]);
        }

        $totalEarnings = 0;
        $totalDeductions = 0;
        $totalNetSalary = 0;

        foreach ($employees as $employee) {
            $salary = $employee->currentSalary;

            if (! $salary) {
                continue;
            }

            // Get attendance data for period
            $attendanceData = $this->getAttendanceData($employee->id, $periodStart, $periodEnd);

            $basicSalary = (float) $salary->basic_salary;

            // Calculate earnings
            $earnings = $this->calculateEarnings($salary, $attendanceData, $basicSalary);
            $grossSalary = $basicSalary + $earnings['total'];

            // Calculate deductions
            $deductions = $this->calculateDeductions($basicSalary, $grossSalary, $attendanceData);

            $netSalary = $grossSalary - $deductions['total'];

            // Create payroll item
            $payrollItem = PayrollItem::create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_salary_id' => $salary->id,
                'employee_name' => $employee->full_name,
                'employee_number' => $employee->employee_id,
                'department_name' => $employee->department?->name ?? '-',
                'position_name' => $employee->position?->name ?? '-',
                'working_days' => $attendanceData['working_days'],
                'present_days' => $attendanceData['present_days'],
                'absent_days' => $attendanceData['absent_days'],
                'late_days' => $attendanceData['late_days'],
                'leave_days' => $attendanceData['leave_days'],
                'overtime_hours' => $attendanceData['overtime_hours'],
                'basic_salary' => $basicSalary,
                'total_earnings' => $earnings['total'],
                'total_deductions' => $deductions['total'],
                'gross_salary' => $grossSalary,
                'tax_amount' => $deductions['pph21'],
                'net_salary' => $netSalary,
                'payment_method' => $salary->payment_method,
                'bank_name' => $salary->bank_name,
                'bank_account_number' => $salary->bank_account_number,
                'bank_account_name' => $salary->bank_account_name,
                'status' => $status === 'paid' ? 'paid' : 'calculated',
                'paid_at' => $status === 'paid' ? $paymentDate : null,
            ]);

            // Create payroll item details
            $this->createPayrollItemDetails($payrollItem, $earnings, $deductions);

            $totalEarnings += $earnings['total'];
            $totalDeductions += $deductions['total'];
            $totalNetSalary += $netSalary;
        }

        // Update payroll totals
        $payroll->update([
            'total_employees' => $employees->count(),
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'total_net_salary' => $totalNetSalary,
        ]);
    }

    private function getAttendanceData(int $employeeId, Carbon $startDate, Carbon $endDate): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $workingDays = 22;
        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $leaveDays = $attendances->where('status', 'leave')->count();
        $absentDays = max(0, $workingDays - $presentDays - $leaveDays);
        $overtimeHours = (int) ($attendances->sum('overtime_minutes') / 60);

        return [
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'leave_days' => $leaveDays,
            'overtime_hours' => $overtimeHours,
            'half_days' => 0,
        ];
    }

    private function calculateEarnings(EmployeeSalary $salary, array $attendanceData, float $basicSalary): array
    {
        $earnings = [
            'items' => [],
            'total' => 0,
        ];

        foreach ($salary->components as $component) {
            $salaryComponent = $component->salaryComponent;

            if (! $salaryComponent || $salaryComponent->type !== 'earning') {
                continue;
            }

            $amount = (float) $component->amount;

            // Calculate attendance-based components
            if ($salaryComponent->is_attendance_based) {
                $amount = $salaryComponent->calculateAttendanceBasedAmount($attendanceData, $amount);
            }

            if ($amount > 0) {
                $earnings['items'][] = [
                    'component_id' => $salaryComponent->id,
                    'name' => $salaryComponent->name,
                    'code' => $salaryComponent->code,
                    'category' => $salaryComponent->category,
                    'amount' => $amount,
                    'is_taxable' => $salaryComponent->is_taxable,
                ];
                $earnings['total'] += $amount;
            }
        }

        // Add overtime if any
        if ($attendanceData['overtime_hours'] > 0) {
            $hourlyRate = $basicSalary / 173;
            $overtimeAmount = $hourlyRate * 1.5 * $attendanceData['overtime_hours'];

            $earnings['items'][] = [
                'component_id' => $this->components['OVERTIME']->id ?? null,
                'name' => 'Lembur',
                'code' => 'OVERTIME',
                'category' => 'variable',
                'amount' => round($overtimeAmount),
                'is_taxable' => true,
            ];
            $earnings['total'] += round($overtimeAmount);
        }

        return $earnings;
    }

    private function calculateDeductions(float $basicSalary, float $grossSalary, array $attendanceData): array
    {
        $deductions = [
            'items' => [],
            'total' => 0,
            'pph21' => 0,
        ];

        // BPJS Kesehatan (1% dari gaji pokok, max 12jt)
        $bpjsKesBasis = min($basicSalary, 12000000);
        $bpjsKes = $bpjsKesBasis * 0.01;
        $deductions['items'][] = [
            'component_id' => $this->components['BPJS-KES']->id ?? null,
            'name' => 'BPJS Kesehatan',
            'code' => 'BPJS-KES',
            'category' => 'insurance',
            'amount' => round($bpjsKes),
            'is_taxable' => false,
        ];
        $deductions['total'] += round($bpjsKes);

        // BPJS JHT (2% dari gaji pokok)
        $bpjsJht = $basicSalary * 0.02;
        $deductions['items'][] = [
            'component_id' => $this->components['BPJS-JHT']->id ?? null,
            'name' => 'BPJS JHT',
            'code' => 'BPJS-JHT',
            'category' => 'insurance',
            'amount' => round($bpjsJht),
            'is_taxable' => false,
        ];
        $deductions['total'] += round($bpjsJht);

        // BPJS JP (1% dari gaji pokok, max ~10jt)
        $bpjsJpBasis = min($basicSalary, 10042300);
        $bpjsJp = $bpjsJpBasis * 0.01;
        $deductions['items'][] = [
            'component_id' => $this->components['BPJS-JP']->id ?? null,
            'name' => 'BPJS JP',
            'code' => 'BPJS-JP',
            'category' => 'insurance',
            'amount' => round($bpjsJp),
            'is_taxable' => false,
        ];
        $deductions['total'] += round($bpjsJp);

        // PPh 21 (simplified calculation)
        // PKP = (Gross - BPJS - PTKP_monthly)
        $ptkpMonthly = 54000000 / 12; // TK/0
        $pkpMonthly = $grossSalary - $bpjsJht - $bpjsJp - $ptkpMonthly;

        $pph21 = 0;
        if ($pkpMonthly > 0) {
            // Simplified: 5% of PKP annualized then monthly
            $pkpAnnual = $pkpMonthly * 12;
            $pph21Annual = $this->calculateProgressiveTax($pkpAnnual);
            $pph21 = round($pph21Annual / 12);
        }

        $deductions['items'][] = [
            'component_id' => $this->components['PPH21']->id ?? null,
            'name' => 'PPh 21',
            'code' => 'PPH21',
            'category' => 'tax',
            'amount' => $pph21,
            'is_taxable' => false,
        ];
        $deductions['total'] += $pph21;
        $deductions['pph21'] = $pph21;

        // Potongan ketidakhadiran
        if ($attendanceData['absent_days'] > 0) {
            $dailyRate = $basicSalary / 22;
            $absentDeduction = round($dailyRate * $attendanceData['absent_days']);

            $deductions['items'][] = [
                'component_id' => $this->components['POT-ABSEN']->id ?? null,
                'name' => 'Potongan Ketidakhadiran',
                'code' => 'POT-ABSEN',
                'category' => 'other',
                'amount' => $absentDeduction,
                'is_taxable' => false,
            ];
            $deductions['total'] += $absentDeduction;
        }

        return $deductions;
    }

    private function calculateProgressiveTax(float $pkpAnnual): float
    {
        // Progressive tax brackets Indonesia
        $brackets = [
            ['limit' => 60000000, 'rate' => 0.05],
            ['limit' => 250000000, 'rate' => 0.15],
            ['limit' => 500000000, 'rate' => 0.25],
            ['limit' => 5000000000, 'rate' => 0.30],
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.35],
        ];

        $tax = 0;
        $remaining = $pkpAnnual;
        $previousLimit = 0;

        foreach ($brackets as $bracket) {
            if ($remaining <= 0) {
                break;
            }

            $taxableInBracket = min($remaining, $bracket['limit'] - $previousLimit);
            $tax += $taxableInBracket * $bracket['rate'];
            $remaining -= $taxableInBracket;
            $previousLimit = $bracket['limit'];
        }

        return $tax;
    }

    private function createPayrollItemDetails(PayrollItem $payrollItem, array $earnings, array $deductions): void
    {
        // Add earnings details
        foreach ($earnings['items'] as $item) {
            PayrollItemDetail::create([
                'payroll_item_id' => $payrollItem->id,
                'salary_component_id' => $item['component_id'],
                'component_name' => $item['name'],
                'component_code' => $item['code'],
                'type' => 'earning',
                'category' => $item['category'],
                'amount' => $item['amount'],
                'is_taxable' => $item['is_taxable'],
            ]);
        }

        // Add deduction details
        foreach ($deductions['items'] as $item) {
            PayrollItemDetail::create([
                'payroll_item_id' => $payrollItem->id,
                'salary_component_id' => $item['component_id'],
                'component_name' => $item['name'],
                'component_code' => $item['code'],
                'type' => 'deduction',
                'category' => $item['category'],
                'amount' => $item['amount'],
                'is_taxable' => $item['is_taxable'],
            ]);
        }
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== RINGKASAN DATA PAYROLL PT DEMO GAJIPRO ===');
        $this->command->newLine();

        // Salary Components
        $componentCount = SalaryComponent::where('company_id', $this->company->id)->count();
        $earningCount = SalaryComponent::where('company_id', $this->company->id)->where('type', 'earning')->count();
        $deductionCount = SalaryComponent::where('company_id', $this->company->id)->where('type', 'deduction')->count();
        $this->command->line("Komponen Gaji: {$componentCount} (Pendapatan: {$earningCount}, Potongan: {$deductionCount})");

        // Employee Salaries
        $salaryCount = EmployeeSalary::where('company_id', $this->company->id)->count();
        $this->command->line("Pengaturan Gaji Karyawan: {$salaryCount}");

        // Reimbursement Categories
        $categoryCount = ReimbursementCategory::where('company_id', $this->company->id)->count();
        $this->command->line("Kategori Reimbursement: {$categoryCount}");

        // Reimbursements
        $reimbursementStats = Reimbursement::where('company_id', $this->company->id)
            ->selectRaw('status, count(*) as count, sum(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalReimbursements = $reimbursementStats->sum('count');
        $this->command->line("Pengajuan Reimbursement: {$totalReimbursements}");
        foreach ($reimbursementStats as $status => $stat) {
            $total = number_format($stat->total, 0, ',', '.');
            $this->command->line("  - {$status}: {$stat->count} (Rp {$total})");
        }

        // Payrolls
        $payrollStats = Payroll::where('company_id', $this->company->id)
            ->selectRaw('status, count(*) as count, sum(total_net_salary) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalPayrolls = $payrollStats->sum('count');
        $this->command->line("Riwayat Payroll: {$totalPayrolls}");
        foreach ($payrollStats as $status => $stat) {
            $total = number_format($stat->total, 0, ',', '.');
            $this->command->line("  - {$status}: {$stat->count} (Total: Rp {$total})");
        }

        $this->command->newLine();
    }
}
