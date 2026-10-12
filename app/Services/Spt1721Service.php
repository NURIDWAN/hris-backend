<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Spt1721;
use App\Models\Spt1721Employee;
use App\Models\Spt1721Monthly;
use App\Models\TaxForm1721A1;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola SPT Tahunan 1721
 *
 * Fitur:
 * - Generate SPT dari data payroll
 * - Kalkulasi rekapitulasi bulanan
 * - Sync dengan bukti potong 1721-A1
 * - Export untuk e-SPT DJP
 */
class Spt1721Service
{
    /**
     * Buat SPT 1721 baru untuk tahun pajak tertentu
     */
    public function createSpt(Company $company, int $taxYear, ?int $userId = null): Spt1721
    {
        // Check if already exists
        $existing = Spt1721::where('company_id', $company->id)
            ->where('tax_year', $taxYear)
            ->where('spt_type', 'normal')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($company, $taxYear, $userId) {
            // Create SPT header
            $spt = Spt1721::create([
                'company_id' => $company->id,
                'tax_year' => $taxYear,
                'spt_type' => 'normal',
                'pembetulan_ke' => 0,
                'company_npwp' => $company->npwp ?? '',
                'company_name' => $company->name,
                'company_address' => $company->address,
                'company_phone' => $company->phone,
                'company_email' => $company->email,
                'signer_name' => $company->pic_name ?? 'Direktur',
                'signer_position' => 'Direktur',
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            // Create monthly entries (12 months)
            for ($month = 1; $month <= 12; $month++) {
                Spt1721Monthly::create([
                    'spt_1721_id' => $spt->id,
                    'month' => $month,
                ]);
            }

            return $spt;
        });
    }

    /**
     * Buat SPT Pembetulan
     */
    public function createPembetulan(Spt1721 $originalSpt, ?int $userId = null): Spt1721
    {
        // Get last pembetulan number
        $lastPembetulan = Spt1721::where('company_id', $originalSpt->company_id)
            ->where('tax_year', $originalSpt->tax_year)
            ->where('spt_type', 'pembetulan')
            ->max('pembetulan_ke') ?? 0;

        return DB::transaction(function () use ($originalSpt, $lastPembetulan, $userId) {
            // Clone SPT header
            $newSpt = $originalSpt->replicate();
            $newSpt->spt_type = 'pembetulan';
            $newSpt->pembetulan_ke = $lastPembetulan + 1;
            $newSpt->status = 'draft';
            $newSpt->ntpn = null;
            $newSpt->submission_date = null;
            $newSpt->submission_receipt = null;
            $newSpt->submitted_by = null;
            $newSpt->created_by = $userId;
            $newSpt->save();

            // Clone monthly data
            foreach ($originalSpt->monthlyData as $monthly) {
                $newMonthly = $monthly->replicate();
                $newMonthly->spt_1721_id = $newSpt->id;
                $newMonthly->save();
            }

            // Clone employee data
            foreach ($originalSpt->employees as $employee) {
                $newEmployee = $employee->replicate();
                $newEmployee->spt_1721_id = $newSpt->id;
                $newEmployee->save();
            }

            return $newSpt;
        });
    }

    /**
     * Hitung SPT dari data payroll
     */
    public function calculateFromPayroll(Spt1721 $spt): void
    {
        DB::transaction(function () use ($spt) {
            // Get all payrolls for the tax year
            $payrolls = Payroll::where('company_id', $spt->company_id)
                ->where('period_year', $spt->tax_year)
                ->where('status', 'paid')
                ->with(['items.details'])
                ->get();

            // Group by month
            $monthlyData = $payrolls->groupBy('period_month');

            // Process each month
            foreach ($monthlyData as $month => $payrollsInMonth) {
                $this->calculateMonthlyData($spt, $month, $payrollsInMonth);
            }

            // Sync employees from 1721-A1
            $this->syncEmployeesFromTaxForms($spt);

            // Recalculate totals
            $spt->recalculate();
        });
    }

    /**
     * Hitung data bulanan dari payroll
     */
    private function calculateMonthlyData(Spt1721 $spt, int $month, Collection $payrolls): void
    {
        $monthly = $spt->monthlyData()->where('month', $month)->first();

        if (! $monthly) {
            $monthly = Spt1721Monthly::create([
                'spt_1721_id' => $spt->id,
                'month' => $month,
            ]);
        }

        // Aggregate from payroll items
        $totals = [
            'employee_count' => 0,
            'employee_count_npwp' => 0,
            'bruto_pegawai_tetap' => 0,
            'pph21_pegawai_tetap' => 0,
            'pph21_disetor' => 0,
        ];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->items as $item) {
                $totals['employee_count']++;
                $totals['bruto_pegawai_tetap'] += (float) $item->gross_salary;
                $totals['pph21_pegawai_tetap'] += (float) $item->tax_amount;
                $totals['pph21_disetor'] += (float) $item->tax_amount;

                // Check if employee has NPWP
                $employee = Employee::find($item->employee_id);
                if ($employee && ! empty($employee->npwp)) {
                    $totals['employee_count_npwp']++;
                }
            }
        }

        $monthly->update([
            'employee_count' => $totals['employee_count'],
            'employee_count_npwp' => $totals['employee_count_npwp'],
            'employee_count_non_npwp' => $totals['employee_count'] - $totals['employee_count_npwp'],
            'bruto_pegawai_tetap' => $totals['bruto_pegawai_tetap'],
            'pph21_pegawai_tetap' => $totals['pph21_pegawai_tetap'],
            'total_bruto' => $totals['bruto_pegawai_tetap'],
            'total_pph21' => $totals['pph21_pegawai_tetap'],
            'pph21_disetor' => $totals['pph21_disetor'],
            'pph21_kurang_lebih' => 0, // Assuming fully paid
        ]);

        // Generate NTPN if paid
        if ($totals['pph21_disetor'] > 0 && empty($monthly->ssp_ntpn)) {
            $monthly->update([
                'ssp_ntpn' => $this->generateNtpn(),
                'ssp_date' => Carbon::create($spt->tax_year, $month, 10)->addMonth(),
            ]);
        }
    }

    /**
     * Sync data karyawan dari bukti potong 1721-A1
     */
    public function syncEmployeesFromTaxForms(Spt1721 $spt): void
    {
        // Get all 1721-A1 for this company and year
        $taxForms = TaxForm1721A1::where('company_id', $spt->company_id)
            ->where('tax_year', $spt->tax_year)
            ->get();

        foreach ($taxForms as $taxForm) {
            // Find or create employee entry
            $sptEmployee = Spt1721Employee::firstOrNew([
                'spt_1721_id' => $spt->id,
                'employee_id' => $taxForm->employee_id,
            ]);

            // Sync data from tax form
            $sptEmployee->fill([
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

            $sptEmployee->save();
        }
    }

    /**
     * Submit SPT (tandai sudah disetor)
     */
    public function submitSpt(Spt1721 $spt, string $ntpn, ?int $userId = null): void
    {
        $spt->update([
            'status' => 'submitted',
            'ntpn' => $ntpn,
            'submission_date' => now(),
            'submitted_by' => $userId,
        ]);
    }

    /**
     * Report SPT (tandai sudah dilaporkan ke DJP)
     */
    public function reportSpt(Spt1721 $spt, string $receipt): void
    {
        $spt->update([
            'status' => 'reported',
            'submission_receipt' => $receipt,
        ]);
    }

    /**
     * Generate NTPN dummy untuk seeder
     */
    public function generateNtpn(): string
    {
        // Format: 16 digit alphanumeric
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ntpn = '';
        for ($i = 0; $i < 16; $i++) {
            $ntpn .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $ntpn;
    }

    /**
     * Export data untuk e-SPT DJP (format CSV)
     */
    public function exportForEspt(Spt1721 $spt): array
    {
        $exports = [];

        // 1. Data Pemotong (Induk)
        $exports['induk'] = $this->exportInduk($spt);

        // 2. Lampiran 1721-I (Bulanan)
        $exports['lampiran_i'] = $this->exportLampiranI($spt);

        // 3. Lampiran 1721-II (Bukti Potong)
        $exports['lampiran_ii'] = $this->exportLampiranII($spt);

        return $exports;
    }

    private function exportInduk(Spt1721 $spt): array
    {
        return [
            'headers' => [
                'NPWP', 'NAMA', 'ALAMAT', 'TELEPON', 'EMAIL',
                'TAHUN_PAJAK', 'JENIS_SPT', 'PEMBETULAN_KE',
                'TOTAL_KARYAWAN', 'TOTAL_BRUTO', 'TOTAL_PPH21',
                'TOTAL_DISETOR', 'KURANG_LEBIH',
                'PENANDATANGAN', 'JABATAN', 'TANGGAL', 'TEMPAT',
            ],
            'data' => [[
                $spt->company_npwp,
                $spt->company_name,
                $spt->company_address,
                $spt->company_phone,
                $spt->company_email,
                $spt->tax_year,
                $spt->spt_type === 'normal' ? 'N' : 'P',
                $spt->pembetulan_ke,
                $spt->total_employees,
                $spt->total_bruto,
                $spt->total_pph21_terutang,
                $spt->total_pph21_disetor,
                $spt->total_pph21_kurang_lebih,
                $spt->signer_name,
                $spt->signer_position,
                $spt->sign_date?->format('d-m-Y'),
                $spt->sign_place,
            ]],
        ];
    }

    private function exportLampiranI(Spt1721 $spt): array
    {
        $data = [];
        foreach ($spt->monthlyData as $monthly) {
            $data[] = [
                $spt->company_npwp,
                $spt->tax_year,
                $monthly->month,
                $monthly->employee_count,
                $monthly->employee_count_npwp,
                $monthly->employee_count_non_npwp,
                $monthly->bruto_pegawai_tetap,
                $monthly->pph21_pegawai_tetap,
                $monthly->pph21_disetor,
                $monthly->ssp_ntpn,
                $monthly->ssp_date?->format('d-m-Y'),
            ];
        }

        return [
            'headers' => [
                'NPWP_PEMOTONG', 'TAHUN_PAJAK', 'MASA_PAJAK',
                'JML_PENERIMA', 'JML_BER_NPWP', 'JML_NON_NPWP',
                'BRUTO_PEGAWAI', 'PPH21_PEGAWAI', 'PPH21_DISETOR',
                'NTPN', 'TGL_SETOR',
            ],
            'data' => $data,
        ];
    }

    private function exportLampiranII(Spt1721 $spt): array
    {
        $data = [];
        foreach ($spt->employees as $employee) {
            $data[] = [
                $spt->company_npwp,
                $spt->tax_year,
                $employee->employee_npwp,
                $employee->employee_nik,
                $employee->employee_name,
                $employee->ptkp_status,
                $employee->work_start_month,
                $employee->work_end_month,
                $employee->gross_salary,
                $employee->neto_salary,
                $employee->ptkp,
                $employee->pkp,
                $employee->pph21_terutang,
                $employee->pph21_telah_dipotong,
                $employee->pph21_kurang_lebih,
                $employee->employee_type === 'tetap' ? 'A1' : 'A2',
            ];
        }

        return [
            'headers' => [
                'NPWP_PEMOTONG', 'TAHUN_PAJAK',
                'NPWP_PENERIMA', 'NIK', 'NAMA',
                'STATUS_PTKP', 'MASA_AWAL', 'MASA_AKHIR',
                'BRUTO', 'NETO', 'PTKP', 'PKP',
                'PPH21_TERUTANG', 'PPH21_DIPOTONG', 'KURANG_LEBIH',
                'JENIS_BUKTI_POTONG',
            ],
            'data' => $data,
        ];
    }

    /**
     * Generate CSV string from export data
     */
    public function generateCsv(array $exportData): string
    {
        $output = '';

        // Add headers
        $output .= implode(';', $exportData['headers'])."\n";

        // Add data rows
        foreach ($exportData['data'] as $row) {
            $output .= implode(';', array_map(function ($value) {
                // Escape and quote strings
                if (is_string($value) && (str_contains($value, ';') || str_contains($value, '"'))) {
                    return '"'.str_replace('"', '""', $value).'"';
                }

                return $value ?? '';
            }, $row))."\n";
        }

        return $output;
    }
}
