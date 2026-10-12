<?php

namespace App\Services;

use App\Models\BpjsKesSetting;
use App\Models\BpjsTkSetting;
use App\Models\Employee;
use App\Models\Pph21Setting;
use App\Models\Pph21TerRate;

class PayrollCalculationService
{
    /**
     * Calculate all payroll components for an employee
     *
     * @param  array{
     *     gross_salary: float,
     *     basic_salary: float,
     *     total_earnings: float,
     *     taxable_earnings: float
     * }  $salaryData
     * @return array{
     *     pph21: float,
     *     bpjs_kes_employee: float,
     *     bpjs_kes_company: float,
     *     bpjs_tk_employee: float,
     *     bpjs_tk_company: float,
     *     bpjs_tk_details: array,
     *     total_deductions: float,
     *     net_salary: float
     * }
     */
    public function calculate(Employee $employee, array $salaryData, int $companyId): array
    {
        $grossSalary = $salaryData['gross_salary'];

        // Calculate BPJS first (as it may affect taxable income)
        $bpjsKes = $this->calculateBpjsKesehatan($companyId, $grossSalary);
        $bpjsTk = $this->calculateBpjsKetenagakerjaan($companyId, $grossSalary);

        // Calculate PPh21 based on gross salary (TER method)
        $pph21 = $this->calculatePph21($companyId, $employee, $grossSalary);

        // Total employee deductions
        $totalDeductions = $salaryData['total_deductions'] ?? 0;
        $totalDeductions += $bpjsKes['employee'];
        $totalDeductions += $bpjsTk['employee_total'];

        // Net salary = Gross - Deductions - PPh21
        $netSalary = $grossSalary - $totalDeductions - $pph21;

        return [
            'pph21' => round($pph21, 0),
            'bpjs_kes_employee' => round($bpjsKes['employee'], 0),
            'bpjs_kes_company' => round($bpjsKes['company'], 0),
            'bpjs_tk_employee' => round($bpjsTk['employee_total'], 0),
            'bpjs_tk_company' => round($bpjsTk['company_total'], 0),
            'bpjs_tk_details' => $bpjsTk,
            'total_deductions' => round($totalDeductions, 0),
            'net_salary' => round($netSalary, 0),
        ];
    }

    /**
     * Calculate PPh21 using TER (Tarif Efektif Rata-rata) method
     */
    public function calculatePph21(int $companyId, Employee $employee, float $grossSalary): float
    {
        // Get PPh21 settings
        $settings = Pph21Setting::where('company_id', $companyId)->first();

        if (! $settings) {
            // Default: 5% simplified calculation if no settings
            return $grossSalary * 0.05;
        }

        // Get employee tax status (PTKP status)
        $taxStatus = $employee->tax_status ?? 'TK/0';
        $hasNpwp = ! empty($employee->npwp);

        if ($settings->use_ter) {
            // Use TER (Tarif Efektif Rata-rata) method
            $terRate = $this->getTerRate($companyId, $taxStatus, $grossSalary);

            if ($terRate) {
                $pph21 = $grossSalary * ($terRate / 100);
            } else {
                // Fallback to simplified calculation
                $pph21 = $this->calculatePph21Progressive($grossSalary, $taxStatus);
            }
        } else {
            // Use progressive rate
            $pph21 = $this->calculatePph21Progressive($grossSalary, $taxStatus);
        }

        // Apply NPWP discount (20% higher if no NPWP)
        if (! $hasNpwp) {
            $npwpPenalty = $settings->npwp_discount_rate ?? 20;
            $pph21 = $pph21 * (1 + ($npwpPenalty / 100));
        }

        return max(0, $pph21);
    }

    /**
     * Get TER rate based on category and gross income
     */
    protected function getTerRate(int $companyId, string $taxStatus, float $grossSalary): ?float
    {
        // Determine TER category based on PTKP status
        $category = $this->getTerCategoryByPtkpStatus($taxStatus);

        // Find matching TER rate
        $terRate = Pph21TerRate::where('company_id', $companyId)
            ->where('category', $category)
            ->where('is_active', true)
            ->where('min_income', '<=', $grossSalary)
            ->where(function ($query) use ($grossSalary) {
                $query->whereNull('max_income')
                    ->orWhere('max_income', '>=', $grossSalary);
            })
            ->orderBy('min_income', 'desc')
            ->first();

        return $terRate?->rate;
    }

    /**
     * Get TER category based on PTKP status
     */
    protected function getTerCategoryByPtkpStatus(string $status): string
    {
        $categoryA = ['TK/0', 'TK/1', 'K/0'];
        $categoryB = ['TK/2', 'TK/3', 'K/1', 'K/2'];
        $categoryC = ['K/3', 'K/I/0', 'K/I/1', 'K/I/2', 'K/I/3'];

        if (in_array($status, $categoryA)) {
            return 'A';
        }

        if (in_array($status, $categoryB)) {
            return 'B';
        }

        if (in_array($status, $categoryC)) {
            return 'C';
        }

        // Default to category A
        return 'A';
    }

    /**
     * Calculate PPh21 using progressive rate (fallback)
     * Simplified version based on monthly gross salary
     */
    protected function calculatePph21Progressive(float $grossSalary, string $taxStatus): float
    {
        // PTKP per month (2024 rates)
        $ptkpMonthly = $this->getPtkpMonthly($taxStatus);

        // Calculate taxable income (PKP)
        $pkp = max(0, $grossSalary - $ptkpMonthly);

        if ($pkp <= 0) {
            return 0;
        }

        // Annual PKP estimation
        $annualPkp = $pkp * 12;

        // Progressive tax calculation (2024 rates)
        $tax = 0;
        if ($annualPkp > 0) {
            // 5% for first 60 million
            $bracket1 = min($annualPkp, 60000000);
            $tax += $bracket1 * 0.05;

            // 15% for 60-250 million
            if ($annualPkp > 60000000) {
                $bracket2 = min($annualPkp - 60000000, 190000000);
                $tax += $bracket2 * 0.15;
            }

            // 25% for 250-500 million
            if ($annualPkp > 250000000) {
                $bracket3 = min($annualPkp - 250000000, 250000000);
                $tax += $bracket3 * 0.25;
            }

            // 30% for 500 million - 5 billion
            if ($annualPkp > 500000000) {
                $bracket4 = min($annualPkp - 500000000, 4500000000);
                $tax += $bracket4 * 0.30;
            }

            // 35% for above 5 billion
            if ($annualPkp > 5000000000) {
                $bracket5 = $annualPkp - 5000000000;
                $tax += $bracket5 * 0.35;
            }
        }

        // Return monthly tax
        return $tax / 12;
    }

    /**
     * Get PTKP monthly amount based on status
     */
    protected function getPtkpMonthly(string $status): float
    {
        // PTKP 2024 annual amounts
        $ptkpAnnual = match ($status) {
            'TK/0' => 54000000,
            'TK/1' => 58500000,
            'TK/2' => 63000000,
            'TK/3' => 67500000,
            'K/0' => 58500000,
            'K/1' => 63000000,
            'K/2' => 67500000,
            'K/3' => 72000000,
            'K/I/0' => 112500000,
            'K/I/1' => 117000000,
            'K/I/2' => 121500000,
            'K/I/3' => 126000000,
            default => 54000000,
        };

        return $ptkpAnnual / 12;
    }

    /**
     * Calculate BPJS Kesehatan contribution
     */
    public function calculateBpjsKesehatan(int $companyId, float $grossSalary): array
    {
        $settings = BpjsKesSetting::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $settings) {
            // Default rates if no settings
            return [
                'salary_basis' => $grossSalary,
                'company' => $grossSalary * 0.04,
                'employee' => $grossSalary * 0.01,
                'total' => $grossSalary * 0.05,
            ];
        }

        return $settings->calculateContribution($grossSalary);
    }

    /**
     * Calculate BPJS Ketenagakerjaan contribution
     */
    public function calculateBpjsKetenagakerjaan(int $companyId, float $grossSalary): array
    {
        $settings = BpjsTkSetting::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $settings) {
            // Default rates if no settings
            $jhtEmployee = $grossSalary * 0.02;
            $jhtCompany = $grossSalary * 0.037;
            $jpEmployee = $grossSalary * 0.01;
            $jpCompany = $grossSalary * 0.02;
            $jkkCompany = $grossSalary * 0.0024;
            $jkmCompany = $grossSalary * 0.003;

            return [
                'jht_employee' => $jhtEmployee,
                'jht_company' => $jhtCompany,
                'jp_employee' => $jpEmployee,
                'jp_company' => $jpCompany,
                'jkk_company' => $jkkCompany,
                'jkm_company' => $jkmCompany,
                'employee_total' => $jhtEmployee + $jpEmployee,
                'company_total' => $jhtCompany + $jpCompany + $jkkCompany + $jkmCompany,
            ];
        }

        $employeeContribution = $settings->calculateEmployeeContribution($grossSalary);
        $companyContribution = $settings->calculateCompanyContribution($grossSalary);

        return [
            'jht_employee' => $employeeContribution['jht'],
            'jht_company' => $companyContribution['jht'],
            'jp_employee' => $employeeContribution['jp'],
            'jp_company' => $companyContribution['jp'],
            'jkk_company' => $companyContribution['jkk'],
            'jkm_company' => $companyContribution['jkm'],
            'employee_total' => $employeeContribution['total'],
            'company_total' => $companyContribution['total'],
        ];
    }

    /**
     * Get breakdown of all deductions for display
     */
    public function getDeductionBreakdown(Employee $employee, float $grossSalary, int $companyId): array
    {
        $bpjsKes = $this->calculateBpjsKesehatan($companyId, $grossSalary);
        $bpjsTk = $this->calculateBpjsKetenagakerjaan($companyId, $grossSalary);
        $pph21 = $this->calculatePph21($companyId, $employee, $grossSalary);

        return [
            'pph21' => [
                'name' => 'PPh 21',
                'code' => 'PPH21',
                'amount' => round($pph21, 0),
                'type' => 'deduction',
                'category' => 'tax',
            ],
            'bpjs_kes' => [
                'name' => 'BPJS Kesehatan',
                'code' => 'BPJS-KES',
                'amount' => round($bpjsKes['employee'], 0),
                'type' => 'deduction',
                'category' => 'bpjs',
            ],
            'bpjs_jht' => [
                'name' => 'BPJS JHT',
                'code' => 'BPJS-JHT',
                'amount' => round($bpjsTk['jht_employee'], 0),
                'type' => 'deduction',
                'category' => 'bpjs',
            ],
            'bpjs_jp' => [
                'name' => 'BPJS JP',
                'code' => 'BPJS-JP',
                'amount' => round($bpjsTk['jp_employee'], 0),
                'type' => 'deduction',
                'category' => 'bpjs',
            ],
        ];
    }
}
