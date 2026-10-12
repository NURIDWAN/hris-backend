<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\TaxForm1721A1;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxForm1721A1>
 */
class TaxForm1721A1Factory extends Factory
{
    use GeneratesRandomData;

    protected $model = TaxForm1721A1::class;

    public function definition(): array
    {
        $grossSalary = $this->randomFloat(60000000, 300000000, 2);
        $biayaJabatan = min($grossSalary * 0.05, 6000000);
        $iuranPensiun = $this->randomFloat(1000000, 5000000, 2);
        $totalPengurang = $biayaJabatan + $iuranPensiun;
        $netoSalary = $grossSalary - $totalPengurang;
        $ptkp = $this->randomElement([54000000, 58500000, 63000000, 67500000]);
        $pkp = max(0, $netoSalary - $ptkp);
        $pph21Terutang = $this->calculateProgressiveTax($pkp);

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'tax_year' => rand(2023, 2026),
            'form_number' => null, // Will be auto-generated

            // Company data
            'company_npwp' => $this->numerify('##.###.###.#-###.###'),
            'company_name' => $this->randomCompanyName(),
            'company_address' => $this->randomAddress(),

            // Employee data
            'employee_npwp' => $this->numerify('##.###.###.#-###.###'),
            'employee_nik' => $this->numerify('################'),
            'employee_name' => $this->randomFullName(),
            'employee_address' => $this->randomAddress(),
            'ptkp_status' => $this->randomElement(['TK/0', 'TK/1', 'K/0', 'K/1', 'K/2', 'K/3']),

            // Work period
            'work_start_month' => 1,
            'work_end_month' => 12,

            // Penghasilan Bruto
            'gaji_pokok' => $grossSalary * 0.7,
            'tunjangan_pph' => 0,
            'tunjangan_lainnya' => $grossSalary * 0.2,
            'honorarium' => 0,
            'premi_asuransi' => $grossSalary * 0.05,
            'natura' => 0,
            'tantiem_bonus_thr' => $grossSalary * 0.05,
            'gross_salary' => $grossSalary,

            // Pengurang
            'biaya_jabatan' => $biayaJabatan,
            'iuran_pensiun' => $iuranPensiun,
            'total_pengurang' => $totalPengurang,

            // Neto, PTKP, PKP
            'neto_salary' => $netoSalary,
            'ptkp' => $ptkp,
            'pkp' => $pkp,

            // PPh 21
            'pph21_terutang' => $pph21Terutang,
            'pph21_dipotong_sebelumnya' => 0,
            'total_pph21_withheld' => $pph21Terutang,

            // Timestamps
            'generated_at' => now(),
            'generated_by' => User::factory(),
        ];
    }

    private function calculateProgressiveTax(float $pkp): float
    {
        if ($pkp <= 0) {
            return 0;
        }

        $tax = 0;

        // Bracket 1: 0 - 60 juta (5%)
        if ($pkp > 0) {
            $bracket1 = min($pkp, 60000000);
            $tax += $bracket1 * 0.05;
        }

        // Bracket 2: 60 juta - 250 juta (15%)
        if ($pkp > 60000000) {
            $bracket2 = min($pkp - 60000000, 190000000);
            $tax += $bracket2 * 0.15;
        }

        // Bracket 3: 250 juta - 500 juta (25%)
        if ($pkp > 250000000) {
            $bracket3 = min($pkp - 250000000, 250000000);
            $tax += $bracket3 * 0.25;
        }

        // Bracket 4: 500 juta - 5 milyar (30%)
        if ($pkp > 500000000) {
            $bracket4 = min($pkp - 500000000, 4500000000);
            $tax += $bracket4 * 0.30;
        }

        // Bracket 5: > 5 milyar (35%)
        if ($pkp > 5000000000) {
            $bracket5 = $pkp - 5000000000;
            $tax += $bracket5 * 0.35;
        }

        return $tax;
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_year' => $year,
        ]);
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'employee_npwp' => $employee->npwp,
            'employee_nik' => $employee->identity_number,
            'employee_name' => $employee->full_name,
            'employee_address' => $employee->address,
            'ptkp_status' => $employee->tax_status,
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
            'company_npwp' => $company->npwp,
            'company_name' => $company->name,
            'company_address' => $company->address,
        ]);
    }

    public function withGrossSalary(float $grossSalary): static
    {
        $biayaJabatan = min($grossSalary * 0.05, 6000000);
        $iuranPensiun = $grossSalary * 0.03;
        $totalPengurang = $biayaJabatan + $iuranPensiun;
        $netoSalary = $grossSalary - $totalPengurang;

        return $this->state(fn (array $attributes) => [
            'gross_salary' => $grossSalary,
            'biaya_jabatan' => $biayaJabatan,
            'iuran_pensiun' => $iuranPensiun,
            'total_pengurang' => $totalPengurang,
            'neto_salary' => $netoSalary,
        ]);
    }

    public function withPtkp(string $status, float $amount): static
    {
        return $this->state(function (array $attributes) use ($status, $amount) {
            $pkp = max(0, $attributes['neto_salary'] - $amount);
            $pph21 = $this->calculateProgressiveTax($pkp);

            return [
                'ptkp_status' => $status,
                'ptkp' => $amount,
                'pkp' => $pkp,
                'pph21_terutang' => $pph21,
                'total_pph21_withheld' => $pph21,
            ];
        });
    }
}
