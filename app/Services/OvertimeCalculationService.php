<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeSetting;

class OvertimeCalculationService
{
    /**
     * Calculate overtime amount for an employee
     */
    public function calculate(
        Employee $employee,
        float $overtimeHours,
        string $overtimeType,
        OvertimeSetting $setting
    ): array {
        $salary = $employee->currentSalary;

        if (! $salary) {
            return [
                'employee_id' => $employee->id,
                'overtime_hours' => $overtimeHours,
                'overtime_amount' => 0.0,
                'hourly_rate' => 0.0,
                'calculation_method' => $overtimeType,
                'error' => 'Employee does not have active salary data',
            ];
        }

        $monthlySalary = (float) $salary->basic_salary;
        $hourlyRate = $setting->calculateHourlyRate($monthlySalary);

        $overtimeAmount = match ($overtimeType) {
            'weekday' => $setting->calculateWeekdayOvertime($hourlyRate, $overtimeHours),
            'weekend' => $setting->calculateWeekendOvertime($hourlyRate, $overtimeHours),
            'holiday' => $setting->calculateHolidayOvertime($hourlyRate, $overtimeHours),
            default => 0,
        };

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'monthly_salary' => $monthlySalary,
            'hourly_rate' => $hourlyRate,
            'overtime_hours' => $overtimeHours,
            'overtime_type' => $overtimeType,
            'overtime_amount' => $overtimeAmount,
            'calculation_method' => $overtimeType,
            'rate_applied' => $this->getRateApplied($overtimeType, $overtimeHours, $setting),
        ];
    }

    /**
     * Get rate description for the calculation
     */
    protected function getRateApplied(string $overtimeType, float $hours, OvertimeSetting $setting): string
    {
        return match ($overtimeType) {
            'weekday' => $hours > 1
                ? "1st hour: {$setting->weekday_rate_first_hour}x, next hours: {$setting->weekday_rate_next_hours}x"
                : "{$setting->weekday_rate_first_hour}x",
            'weekend' => "{$setting->weekend_rate}x",
            'holiday' => "{$setting->holiday_rate}x",
            default => '',
        };
    }
}
