<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'cycle_type',
        'cutoff_day',
        'default_working_days',
        'auto_deduct_absent',
        'auto_deduct_late',
        'late_penalty_threshold',
        'late_penalty_amount',
        'late_penalty_type',
        'absent_deduction_type',
        'absent_deduction_amount',
        'prorate_new_employees',
        'prorate_resigned_employees',
        'include_overtime_in_payroll',
        'require_overtime_approval',
        'include_reimbursement_in_payroll',
        'payment_day',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cutoff_day' => 'integer',
            'default_working_days' => 'integer',
            'auto_deduct_absent' => 'boolean',
            'auto_deduct_late' => 'boolean',
            'late_penalty_threshold' => 'integer',
            'late_penalty_amount' => 'decimal:2',
            'absent_deduction_amount' => 'decimal:2',
            'prorate_new_employees' => 'boolean',
            'prorate_resigned_employees' => 'boolean',
            'include_overtime_in_payroll' => 'boolean',
            'require_overtime_approval' => 'boolean',
            'include_reimbursement_in_payroll' => 'boolean',
            'payment_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Calculate the payroll period dates for a given month/year
     *
     * Example: cutoff_day = 25
     * - For January 2026 payroll: Dec 25, 2025 to Jan 24, 2026
     * - For February 2026 payroll: Jan 25, 2026 to Feb 24, 2026
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function calculatePeriodDates(int $year, int $month): array
    {
        $cutoffDay = $this->cutoff_day;

        if ($cutoffDay === 1) {
            // Standard calendar month: 1st to last day of month
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $end = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        } else {
            // Custom cutoff: previous month's cutoff day to current month's (cutoff - 1)
            $previousMonth = Carbon::create($year, $month, 1)->subMonth();

            // Start date: cutoff day of previous month
            $start = Carbon::create(
                $previousMonth->year,
                $previousMonth->month,
                min($cutoffDay, $previousMonth->daysInMonth)
            )->startOfDay();

            // End date: (cutoff - 1) day of current month
            $endDay = $cutoffDay - 1;
            if ($endDay < 1) {
                $endDay = Carbon::create($year, $month, 1)->daysInMonth;
            }
            $end = Carbon::create($year, $month, min($endDay, Carbon::create($year, $month, 1)->daysInMonth))->endOfDay();
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Get the default payroll name for a period
     */
    public function getPayrollName(int $year, int $month): string
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return "Payroll {$monthNames[$month]} {$year}";
    }

    /**
     * Calculate daily rate from monthly salary
     */
    public function calculateDailyRate(float $monthlySalary): float
    {
        return $monthlySalary / $this->default_working_days;
    }

    /**
     * Get cycle type label
     */
    public function getCycleTypeLabelAttribute(): string
    {
        return match ($this->cycle_type) {
            'monthly' => 'Bulanan',
            'bi-weekly' => 'Dua Mingguan',
            'custom' => 'Kustom',
            default => '-',
        };
    }

    /**
     * Get cutoff description
     */
    public function getCutoffDescriptionAttribute(): string
    {
        if ($this->cutoff_day === 1) {
            return 'Tanggal 1 - Akhir Bulan';
        }

        $endDay = $this->cutoff_day - 1;

        return "Tanggal {$this->cutoff_day} - Tanggal {$endDay}";
    }

    /**
     * Get late penalty type label
     */
    public function getLatePenaltyTypeLabelAttribute(): string
    {
        return match ($this->late_penalty_type) {
            'fixed' => 'Nominal Tetap',
            'percentage' => 'Persentase Gaji',
            'daily_rate' => 'Rate Harian',
            default => '-',
        };
    }

    /**
     * Get absent deduction type label
     */
    public function getAbsentDeductionTypeLabelAttribute(): string
    {
        return match ($this->absent_deduction_type) {
            'daily_rate' => 'Rate Harian',
            'fixed' => 'Nominal Tetap',
            'percentage' => 'Persentase Gaji',
            default => '-',
        };
    }
}
