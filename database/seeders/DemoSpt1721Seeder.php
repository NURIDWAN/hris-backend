<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Spt1721;
use App\Models\Spt1721Employee;
use App\Models\Spt1721Monthly;
use App\Models\TaxForm1721A1;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk SPT Tahunan PPh 21 (Formulir 1721)
 *
 * Seeder ini menghasilkan SPT tahunan yang realistis berdasarkan:
 * - Data bukti potong 1721-A1 yang sudah ada
 * - Rekapitulasi bulanan PPh 21
 * - Status pelaporan yang bervariasi
 */
class DemoSpt1721Seeder extends Seeder
{
    private Company $company;

    private ?User $adminUser = null;

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat SPT Tahunan 1721 untuk: {$this->company->name}");

        $this->adminUser = User::where('company_id', $this->company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        // Hapus data lama (termasuk yang soft deleted)
        $existingSpts = Spt1721::withTrashed()->where('company_id', $this->company->id)->pluck('id');
        Spt1721Monthly::whereIn('spt_1721_id', $existingSpts)->forceDelete();
        Spt1721Employee::whereIn('spt_1721_id', $existingSpts)->forceDelete();
        Spt1721::withTrashed()->where('company_id', $this->company->id)->forceDelete();

        // Generate SPT untuk tahun yang memiliki bukti potong
        $taxYears = TaxForm1721A1::where('company_id', $this->company->id)
            ->distinct()
            ->pluck('tax_year');

        foreach ($taxYears as $year) {
            $this->createSptForYear($year);
        }

        $this->printSummary();
    }

    private function createSptForYear(int $taxYear): void
    {
        $this->command->info("  Membuat SPT tahun pajak {$taxYear}...");

        // Determine SPT status based on year
        $currentYear = Carbon::now()->year;
        $isPreviousYear = $taxYear < $currentYear;

        // Previous year should be reported, current year might be draft
        $status = $isPreviousYear ? 'reported' : 'calculated';

        // Create SPT header
        $spt = Spt1721::create([
            'company_id' => $this->company->id,
            'tax_year' => $taxYear,
            'spt_type' => 'normal',
            'pembetulan_ke' => 0,
            'company_npwp' => $this->company->npwp ?? '01.234.567.8-012.000',
            'company_name' => $this->company->name,
            'company_address' => $this->company->address ?? 'Jl. Sudirman No. 123, Jakarta Selatan 12190',
            'company_phone' => $this->company->phone,
            'company_email' => $this->company->email,
            'signer_name' => $this->company->pic_name ?? 'Direktur Utama',
            'signer_npwp' => $this->company->npwp,
            'signer_position' => 'Direktur',
            'sign_date' => $isPreviousYear ? Carbon::create($taxYear + 1, 3, rand(1, 31)) : null,
            'sign_place' => 'Jakarta',
            'status' => $status,
            'ntpn' => $isPreviousYear ? $this->generateNtpn() : null,
            'submission_date' => $isPreviousYear ? Carbon::create($taxYear + 1, 3, rand(1, 31)) : null,
            'submission_receipt' => $isPreviousYear ? $this->generateSubmissionReceipt($taxYear) : null,
            'created_by' => $this->adminUser?->id,
            'submitted_by' => $isPreviousYear ? $this->adminUser?->id : null,
        ]);

        // Create monthly data
        $this->createMonthlyData($spt, $taxYear);

        // Sync employees from tax forms
        $this->syncEmployeesFromTaxForms($spt, $taxYear);

        // Recalculate totals
        $spt->recalculate();

        $this->command->info("    - Status: {$spt->status_label}");
        $this->command->info("    - Total Karyawan: {$spt->total_employees}");
        $this->command->info('    - Total PPh 21: Rp '.number_format($spt->total_pph21_terutang, 0, ',', '.'));
    }

    private function createMonthlyData(Spt1721 $spt, int $taxYear): void
    {
        // Get tax forms for this year to calculate monthly distribution
        $taxForms = TaxForm1721A1::where('company_id', $this->company->id)
            ->where('tax_year', $taxYear)
            ->get();

        // Calculate total annual figures
        $totalEmployees = $taxForms->count();
        $totalBruto = $taxForms->sum('gross_salary');
        $totalPph = $taxForms->sum('total_pph21_withheld');

        // Distribute across 12 months
        for ($month = 1; $month <= 12; $month++) {
            // Get employees working in this month
            $employeesInMonth = $taxForms->filter(function ($form) use ($month) {
                return $form->work_start_month <= $month && $form->work_end_month >= $month;
            });

            $employeeCount = $employeesInMonth->count();
            $employeeCountNpwp = $employeesInMonth->filter(fn ($f) => ! empty($f->employee_npwp))->count();

            // Calculate monthly figures (simplified: divide annual by 12 with some variation)
            $monthlyBruto = round($totalBruto / 12 * $this->getMonthlyVariation($month));
            $monthlyPph = round($totalPph / 12 * $this->getMonthlyVariation($month));

            Spt1721Monthly::create([
                'spt_1721_id' => $spt->id,
                'month' => $month,
                'employee_count' => $employeeCount,
                'employee_count_npwp' => $employeeCountNpwp,
                'employee_count_non_npwp' => $employeeCount - $employeeCountNpwp,
                'bruto_pegawai_tetap' => $monthlyBruto,
                'pph21_pegawai_tetap' => $monthlyPph,
                'bruto_pegawai_tidak_tetap' => 0,
                'pph21_pegawai_tidak_tetap' => 0,
                'bruto_bukan_pegawai' => 0,
                'pph21_bukan_pegawai' => 0,
                'total_bruto' => $monthlyBruto,
                'total_pph21' => $monthlyPph,
                'pph21_disetor' => $monthlyPph,
                'pph21_kurang_lebih' => 0,
                'ssp_ntpn' => $this->generateNtpn(),
                'ssp_date' => Carbon::create($taxYear, $month, 10)->addMonth(), // Setor tanggal 10 bulan berikutnya
            ]);
        }
    }

    private function syncEmployeesFromTaxForms(Spt1721 $spt, int $taxYear): void
    {
        $taxForms = TaxForm1721A1::where('company_id', $this->company->id)
            ->where('tax_year', $taxYear)
            ->get();

        foreach ($taxForms as $taxForm) {
            Spt1721Employee::create([
                'spt_1721_id' => $spt->id,
                'employee_id' => $taxForm->employee_id,
                'tax_form_1721a1_id' => $taxForm->id,
                'employee_npwp' => $taxForm->employee_npwp,
                'employee_nik' => $taxForm->employee_nik,
                'employee_name' => $taxForm->employee_name,
                'ptkp_status' => $taxForm->ptkp_status,
                'work_start_month' => $taxForm->work_start_month,
                'work_end_month' => $taxForm->work_end_month,
                'gross_salary' => $taxForm->gross_salary,
                'neto_salary' => $taxForm->neto_salary,
                'ptkp' => $taxForm->ptkp,
                'pkp' => $taxForm->pkp,
                'pph21_terutang' => $taxForm->pph21_terutang,
                'pph21_telah_dipotong' => $taxForm->total_pph21_withheld,
                'pph21_kurang_lebih' => $taxForm->pph21_terutang - $taxForm->total_pph21_withheld,
                'employee_type' => 'tetap',
            ]);
        }
    }

    /**
     * Get monthly variation factor for realistic distribution
     * THR bulan (Mei-Juni) dan bonus akhir tahun lebih tinggi
     */
    private function getMonthlyVariation(int $month): float
    {
        return match ($month) {
            5, 6 => 1.2,      // THR period
            12 => 1.3,        // Bonus akhir tahun
            1 => 0.9,         // Januari lebih rendah
            default => 1.0,
        };
    }

    /**
     * Generate NTPN (16 karakter alfanumerik)
     */
    private function generateNtpn(): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ntpn = '';
        for ($i = 0; $i < 16; $i++) {
            $ntpn .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $ntpn;
    }

    /**
     * Generate nomor tanda terima pelaporan
     */
    private function generateSubmissionReceipt(int $taxYear): string
    {
        $randomDigits = str_pad((string) rand(1, 999999999), 9, '0', STR_PAD_LEFT);

        return "BPE-{$randomDigits}-{$taxYear}";
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== RINGKASAN SPT TAHUNAN 1721 ===');
        $this->command->newLine();

        $spts = Spt1721::where('company_id', $this->company->id)
            ->orderBy('tax_year', 'desc')
            ->get();

        foreach ($spts as $spt) {
            $totalPph = number_format($spt->total_pph21_terutang, 0, ',', '.');
            $this->command->line("SPT Tahun Pajak {$spt->tax_year}:");
            $this->command->line("  - No. SPT: {$spt->spt_number}");
            $this->command->line("  - Status: {$spt->status_label}");
            $this->command->line("  - Jumlah Karyawan: {$spt->total_employees}");
            $this->command->line("  - Total PPh 21: Rp {$totalPph}");
            if ($spt->ntpn) {
                $this->command->line("  - NTPN: {$spt->ntpn}");
            }
            if ($spt->submission_receipt) {
                $this->command->line("  - No. Bukti Lapor: {$spt->submission_receipt}");
            }
            $this->command->newLine();
        }

        $this->command->info('Seeder SPT Tahunan 1721 selesai!');
    }
}
