<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\TaxForm1721A1;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk Bukti Potong PPh 21 (Formulir 1721-A1)
 *
 * Seeder ini menghasilkan bukti potong pajak yang realistis berdasarkan:
 * - Data karyawan yang sudah ada
 * - Kalkulasi PPh 21 sesuai tarif progresif Indonesia
 * - PTKP sesuai status karyawan (TK/0, K/0, K/1, K/2, K/3)
 *
 * Data yang dibuat untuk setiap karyawan:
 * - Bukti potong tahun pajak berjalan
 * - Bukti potong tahun pajak sebelumnya (opsional)
 */
class DemoTaxForm1721A1Seeder extends Seeder
{
    private Company $company;

    private ?User $adminUser = null;

    /**
     * PTKP (Penghasilan Tidak Kena Pajak) tahun 2024
     */
    private array $ptkpValues = [
        'TK/0' => 54000000,  // Tidak Kawin tanpa tanggungan
        'TK/1' => 58500000,  // Tidak Kawin dengan 1 tanggungan
        'TK/2' => 63000000,  // Tidak Kawin dengan 2 tanggungan
        'TK/3' => 67500000,  // Tidak Kawin dengan 3 tanggungan
        'K/0' => 58500000,   // Kawin tanpa tanggungan
        'K/1' => 63000000,   // Kawin dengan 1 tanggungan
        'K/2' => 67500000,   // Kawin dengan 2 tanggungan
        'K/3' => 72000000,   // Kawin dengan 3 tanggungan
        'K/I/0' => 112500000, // Kawin + penghasilan istri digabung
        'K/I/1' => 117000000,
        'K/I/2' => 121500000,
        'K/I/3' => 126000000,
    ];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat bukti potong 1721-A1 untuk: {$this->company->name}");

        $this->adminUser = User::where('company_id', $this->company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        // Hapus data lama
        TaxForm1721A1::where('company_id', $this->company->id)->forceDelete();

        // Generate bukti potong
        $this->createTaxFormsForCurrentYear();
        $this->createTaxFormsForPreviousYear();

        $this->printSummary();
    }

    private function createTaxFormsForCurrentYear(): void
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        // Hanya buat jika sudah lewat bulan Maret (biasanya 1721-A1 diterbitkan Januari-Maret)
        // Untuk demo, kita buat untuk semua karyawan
        $this->command->info("Membuat bukti potong tahun pajak {$currentYear}...");

        $employees = Employee::where('company_id', $this->company->id)
            ->with(['position', 'department', 'currentSalary'])
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            $this->createTaxFormForEmployee($employee, $currentYear);
            $count++;
        }

        $this->command->info("  - {$count} bukti potong tahun {$currentYear} dibuat");
    }

    private function createTaxFormsForPreviousYear(): void
    {
        $previousYear = Carbon::now()->year - 1;

        $this->command->info("Membuat bukti potong tahun pajak {$previousYear}...");

        // Hanya untuk karyawan yang sudah bergabung sebelum tahun ini
        $employees = Employee::where('company_id', $this->company->id)
            ->where('hire_date', '<', Carbon::create(Carbon::now()->year, 1, 1))
            ->with(['position', 'department', 'currentSalary'])
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            $this->createTaxFormForEmployee($employee, $previousYear);
            $count++;
        }

        $this->command->info("  - {$count} bukti potong tahun {$previousYear} dibuat");
    }

    private function createTaxFormForEmployee(Employee $employee, int $taxYear): void
    {
        $salary = $employee->currentSalary;

        if (! $salary) {
            return;
        }

        // Tentukan masa kerja
        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : Carbon::create($taxYear, 1, 1);
        $workStartMonth = $hireDate->year < $taxYear ? 1 : $hireDate->month;
        $workEndMonth = 12;

        // Jika karyawan baru bergabung di tahun pajak, hitung prorata
        $workingMonths = $workEndMonth - $workStartMonth + 1;

        // Determine PTKP status secara random yang realistis
        $ptkpStatus = $this->generateRealisticPtkpStatus();
        $ptkpValue = $this->ptkpValues[$ptkpStatus] ?? 54000000;

        // Calculate salary components
        $basicSalary = (float) $salary->basic_salary;

        // Get tunjangan dari employee salary components atau estimasi
        $calculation = $this->calculateAnnualIncome($employee, $basicSalary, $workingMonths, $taxYear);

        // Calculate PKP and PPh 21
        $netoSalary = $calculation['gross_salary'] - $calculation['total_pengurang'];
        $pkp = max(0, $netoSalary - $ptkpValue);

        // Round PKP to nearest 1000 (sesuai aturan pajak)
        $pkp = floor($pkp / 1000) * 1000;

        $pph21Terutang = $this->calculateProgressiveTax($pkp);

        // PPh yang sudah dipotong sebelumnya (asumsi sudah dipotong per bulan)
        $pph21DipotongSebelumnya = round($pph21Terutang * (($workingMonths - 1) / $workingMonths));
        $totalPph21Withheld = $pph21Terutang;

        // Generate NPWP realistis
        $employeeNpwp = $this->generateNpwp();

        TaxForm1721A1::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'tax_year' => $taxYear,
            // Company data
            'company_npwp' => $this->company->npwp ?? '01.234.567.8-012.000',
            'company_name' => $this->company->name,
            'company_address' => $this->company->address ?? 'Jl. Sudirman No. 123, Jakarta Selatan 12190',
            // Employee data
            'employee_npwp' => $employeeNpwp,
            'employee_nik' => $employee->nik ?? $this->generateNik(),
            'employee_name' => $employee->full_name,
            'employee_address' => $employee->address ?? 'Jakarta',
            'ptkp_status' => $ptkpStatus,
            // Work period
            'work_start_month' => $workStartMonth,
            'work_end_month' => $workEndMonth,
            // Penghasilan Bruto
            'gaji_pokok' => $calculation['gaji_pokok'],
            'tunjangan_pph' => $calculation['tunjangan_pph'],
            'tunjangan_lainnya' => $calculation['tunjangan_lainnya'],
            'honorarium' => $calculation['honorarium'],
            'premi_asuransi' => $calculation['premi_asuransi'],
            'natura' => $calculation['natura'],
            'tantiem_bonus_thr' => $calculation['tantiem_bonus_thr'],
            'gross_salary' => $calculation['gross_salary'],
            // Pengurang
            'biaya_jabatan' => $calculation['biaya_jabatan'],
            'iuran_pensiun' => $calculation['iuran_pensiun'],
            'total_pengurang' => $calculation['total_pengurang'],
            // Perhitungan
            'neto_salary' => $netoSalary,
            'ptkp' => $ptkpValue,
            'pkp' => $pkp,
            'pph21_terutang' => $pph21Terutang,
            'pph21_dipotong_sebelumnya' => $pph21DipotongSebelumnya,
            'total_pph21_withheld' => $totalPph21Withheld,
            // Meta
            'generated_at' => Carbon::create($taxYear + 1, rand(1, 3), rand(1, 28)),
            'generated_by' => $this->adminUser?->id,
        ]);
    }

    private function calculateAnnualIncome(Employee $employee, float $basicSalary, int $workingMonths, int $taxYear): array
    {
        // Gaji pokok tahunan (prorata jika tidak setahun penuh)
        $gajiPokok = $basicSalary * $workingMonths;

        // Tunjangan berdasarkan level jabatan
        $positionLevel = $employee->position?->level ?? 'staff';

        // Tunjangan jabatan
        $tunjanganJabatanMonthly = match ($positionLevel) {
            'director' => 5000000,
            'manager' => 2500000,
            'supervisor' => 1500000,
            'senior' => 750000,
            default => 0,
        };

        // Tunjangan lainnya (transport + makan + komunikasi)
        $tunjanganTransportMonthly = 500000;
        $tunjanganMakanMonthly = 660000;
        $tunjanganKomunikasiMonthly = match ($positionLevel) {
            'director' => 500000,
            'manager' => 300000,
            'supervisor' => 200000,
            default => 0,
        };

        $tunjanganLainnya = ($tunjanganJabatanMonthly + $tunjanganTransportMonthly +
            $tunjanganMakanMonthly + $tunjanganKomunikasiMonthly) * $workingMonths;

        // Tunjangan PPh (jika perusahaan menanggung PPh karyawan - set 0 untuk demo)
        $tunjanganPph = 0;

        // Honorarium (occasional)
        $honorarium = rand(0, 100) < 20 ? rand(1, 5) * 1000000 : 0;

        // Premi asuransi yang dibayar pemberi kerja (BPJS Kesehatan 4% + JKK 0.24% + JKM 0.3%)
        $premiRate = 0.0454; // ~4.54%
        $premiAsuransi = round($gajiPokok * $premiRate);

        // Natura (fasilitas kantor - random)
        $natura = rand(0, 100) < 15 ? rand(1, 3) * 1000000 : 0;

        // THR + Bonus (biasanya 1-2x gaji)
        $tantiemBonusThr = $basicSalary * rand(1, 2);
        // Tambah bonus kinerja untuk level tertentu
        if (in_array($positionLevel, ['director', 'manager'])) {
            $tantiemBonusThr += $basicSalary * rand(1, 3);
        }

        $grossSalary = $gajiPokok + $tunjanganPph + $tunjanganLainnya +
            $honorarium + $premiAsuransi + $natura + $tantiemBonusThr;

        // === PENGURANG ===

        // Biaya jabatan: 5% dari penghasilan bruto, max 6 juta/tahun
        $biayaJabatan = min($grossSalary * 0.05, 6000000);

        // Iuran pensiun/JHT yang dibayar sendiri (BPJS JHT 2% + JP 1%)
        $iuranPensiunRate = 0.03;
        $iuranPensiun = round($gajiPokok * $iuranPensiunRate);

        $totalPengurang = $biayaJabatan + $iuranPensiun;

        return [
            'gaji_pokok' => $gajiPokok,
            'tunjangan_pph' => $tunjanganPph,
            'tunjangan_lainnya' => $tunjanganLainnya,
            'honorarium' => $honorarium,
            'premi_asuransi' => $premiAsuransi,
            'natura' => $natura,
            'tantiem_bonus_thr' => $tantiemBonusThr,
            'gross_salary' => $grossSalary,
            'biaya_jabatan' => $biayaJabatan,
            'iuran_pensiun' => $iuranPensiun,
            'total_pengurang' => $totalPengurang,
        ];
    }

    /**
     * Hitung PPh 21 dengan tarif progresif
     */
    private function calculateProgressiveTax(float $pkp): float
    {
        if ($pkp <= 0) {
            return 0;
        }

        // Tarif PPh 21 progresif (berlaku sejak 2022)
        $brackets = [
            ['limit' => 60000000, 'rate' => 0.05],     // 5% untuk PKP s.d. 60 juta
            ['limit' => 250000000, 'rate' => 0.15],   // 15% untuk PKP 60-250 juta
            ['limit' => 500000000, 'rate' => 0.25],   // 25% untuk PKP 250-500 juta
            ['limit' => 5000000000, 'rate' => 0.30],  // 30% untuk PKP 500 juta - 5 miliar
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.35], // 35% untuk PKP di atas 5 miliar
        ];

        $tax = 0;
        $remaining = $pkp;
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

        return round($tax);
    }

    /**
     * Generate status PTKP yang realistis
     */
    private function generateRealisticPtkpStatus(): string
    {
        // Distribusi realistis status PTKP
        $distribution = [
            'TK/0' => 35, // 35% single tanpa tanggungan
            'TK/1' => 5,  // 5% single dengan 1 tanggungan
            'K/0' => 20,  // 20% menikah tanpa anak
            'K/1' => 20,  // 20% menikah 1 anak
            'K/2' => 15,  // 15% menikah 2 anak
            'K/3' => 5,   // 5% menikah 3 anak
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $status => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $status;
            }
        }

        return 'TK/0';
    }

    /**
     * Generate NPWP format Indonesia
     * Format: XX.XXX.XXX.X-XXX.XXX
     */
    private function generateNpwp(): string
    {
        $part1 = str_pad((string) rand(0, 99), 2, '0', STR_PAD_LEFT);
        $part2 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
        $part3 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
        $part4 = (string) rand(0, 9);
        $part5 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
        $part6 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);

        return "{$part1}.{$part2}.{$part3}.{$part4}-{$part5}.{$part6}";
    }

    /**
     * Generate NIK format Indonesia (16 digit)
     */
    private function generateNik(): string
    {
        // Kode provinsi Jakarta
        $provinsi = '31';
        // Kode kota/kabupaten random
        $kota = str_pad((string) rand(1, 75), 2, '0', STR_PAD_LEFT);
        // Kode kecamatan random
        $kecamatan = str_pad((string) rand(1, 10), 2, '0', STR_PAD_LEFT);
        // Tanggal lahir (DDMMYY) - untuk wanita DD+40
        $day = str_pad((string) rand(1, 28), 2, '0', STR_PAD_LEFT);
        $month = str_pad((string) rand(1, 12), 2, '0', STR_PAD_LEFT);
        $year = str_pad((string) rand(80, 99), 2, '0', STR_PAD_LEFT);
        // Nomor urut
        $urut = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$provinsi}{$kota}{$kecamatan}{$day}{$month}{$year}{$urut}";
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== RINGKASAN BUKTI POTONG 1721-A1 ===');
        $this->command->newLine();

        $taxForms = TaxForm1721A1::where('company_id', $this->company->id)
            ->selectRaw('tax_year, count(*) as count, sum(gross_salary) as total_gross, sum(total_pph21_withheld) as total_pph')
            ->groupBy('tax_year')
            ->orderBy('tax_year', 'desc')
            ->get();

        foreach ($taxForms as $form) {
            $totalGross = number_format($form->total_gross, 0, ',', '.');
            $totalPph = number_format($form->total_pph, 0, ',', '.');
            $this->command->line("Tahun Pajak {$form->tax_year}:");
            $this->command->line("  - Jumlah Bukti Potong: {$form->count}");
            $this->command->line("  - Total Penghasilan Bruto: Rp {$totalGross}");
            $this->command->line("  - Total PPh 21 Dipotong: Rp {$totalPph}");
            $this->command->newLine();
        }

        // Sample data untuk verifikasi
        $sample = TaxForm1721A1::where('company_id', $this->company->id)
            ->with('employee')
            ->orderBy('gross_salary', 'desc')
            ->first();

        if ($sample) {
            $this->command->info('=== CONTOH BUKTI POTONG (PENGHASILAN TERTINGGI) ===');
            $this->command->line("Nama: {$sample->employee_name}");
            $this->command->line("NPWP: {$sample->employee_npwp}");
            $this->command->line("Status PTKP: {$sample->ptkp_status}");
            $this->command->line("Masa Kerja: {$sample->work_period}");
            $this->command->line("Penghasilan Bruto: {$sample->formatted_gross_salary}");
            $this->command->line("Penghasilan Neto: {$sample->formatted_neto_salary}");
            $this->command->line("PTKP: {$sample->formatted_ptkp}");
            $this->command->line("PKP: {$sample->formatted_pkp}");
            $this->command->line("PPh 21 Terutang: {$sample->formatted_total_pph21_withheld}");
        }

        $this->command->newLine();
        $this->command->info('Seeder bukti potong 1721-A1 selesai!');
    }
}
