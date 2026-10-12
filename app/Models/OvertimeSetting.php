<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'weekday_rate_first_hour',
        'weekday_rate_next_hours',
        'weekend_rate',
        'holiday_rate',
        'max_overtime_hours_per_day',
        'max_overtime_hours_per_week',
        'min_overtime_minutes',
        'working_hours_per_month',
        'requires_approval',
        'auto_calculate_from_attendance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekday_rate_first_hour' => 'decimal:2',
            'weekday_rate_next_hours' => 'decimal:2',
            'weekend_rate' => 'decimal:2',
            'holiday_rate' => 'decimal:2',
            'max_overtime_hours_per_day' => 'integer',
            'max_overtime_hours_per_week' => 'integer',
            'min_overtime_minutes' => 'integer',
            'working_hours_per_month' => 'integer',
            'requires_approval' => 'boolean',
            'auto_calculate_from_attendance' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Calculate hourly rate from monthly salary
     */
    public function calculateHourlyRate(float $monthlySalary): float
    {
        return round($monthlySalary / $this->working_hours_per_month, 2);
    }

    /**
     * Calculate overtime amount for weekday
     */
    public function calculateWeekdayOvertime(float $hourlyRate, float $hours): float
    {
        if ($hours <= 0) {
            return 0;
        }

        // First hour
        $amount = $hourlyRate * $this->weekday_rate_first_hour;

        // Next hours
        if ($hours > 1) {
            $amount += $hourlyRate * $this->weekday_rate_next_hours * ($hours - 1);
        }

        return round($amount, 2);
    }

    /**
     * Calculate overtime amount for weekend
     */
    public function calculateWeekendOvertime(float $hourlyRate, float $hours): float
    {
        return round($hourlyRate * $this->weekend_rate * $hours, 2);
    }

    /**
     * Calculate overtime amount for holiday
     */
    public function calculateHolidayOvertime(float $hourlyRate, float $hours): float
    {
        return round($hourlyRate * $this->holiday_rate * $hours, 2);
    }
}
