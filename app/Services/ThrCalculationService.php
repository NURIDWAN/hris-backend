<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ThrSetting;

class ThrCalculationService
{
    /**
     * Calculate THR for a single employee
     */
    public function calculateForEmployee(Employee $employee, ThrSetting $setting): array
    {
        $salary = $employee->currentSalary;

        if (! $salary) {
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'eligible' => false,
                'reason' => 'Tidak memiliki data gaji aktif',
            ];
        }

        $serviceMonths = $this->calculateServiceMonths($employee);

        if ($serviceMonths < $setting->min_service_months) {
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'eligible' => false,
                'service_months' => $serviceMonths,
                'reason' => "Masa kerja kurang dari {$setting->min_service_months} bulan",
            ];
        }

        $baseSalary = (float) $salary->basic_salary;
        $allowances = 0;

        if ($setting->include_allowances) {
            // Get total earnings from salary components
            $allowances = $salary->getTotalEarnings();
        }

        $totalSalary = $baseSalary + $allowances;
        $thrAmount = $this->calculateThrAmount($totalSalary, $serviceMonths, $setting);

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'employee_number' => $employee->employee_number,
            'department' => $employee->department?->name,
            'position' => $employee->position?->name,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'eligible' => true,
            'service_months' => $serviceMonths,
            'base_salary' => $baseSalary,
            'allowances' => $allowances,
            'total_salary' => $totalSalary,
            'thr_amount' => $thrAmount,
            'calculation_method' => $setting->calculation_method,
        ];
    }

    /**
     * Calculate THR for all eligible employees in a company
     */
    public function calculateForCompany(int $companyId, ThrSetting $setting): array
    {
        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['currentSalary', 'department', 'position'])
            ->get();

        $results = [];

        foreach ($employees as $employee) {
            $calculation = $this->calculateForEmployee($employee, $setting);

            if ($calculation['eligible'] ?? false) {
                $results[] = $calculation;
            }
        }

        return $results;
    }

    /**
     * Calculate service months from hire date
     */
    protected function calculateServiceMonths(Employee $employee): int
    {
        if (! $employee->hire_date) {
            return 0;
        }

        return (int) $employee->hire_date->diffInMonths(now());
    }

    /**
     * Calculate THR amount based on method
     */
    protected function calculateThrAmount(float $totalSalary, int $serviceMonths, ThrSetting $setting): float
    {
        if ($setting->calculation_method === 'one_month_salary') {
            return $totalSalary;
        }

        // Prorata calculation
        if ($setting->calculation_method === 'prorata') {
            // Cap at 12 months
            $monthsForCalculation = min($serviceMonths, 12);

            if ($setting->prorata_formula === 'months_worked_per_12') {
                return ($monthsForCalculation / 12) * $totalSalary;
            }
        }

        return $totalSalary;
    }
}
