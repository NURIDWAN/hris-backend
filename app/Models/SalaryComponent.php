<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'category',
        'calculation_type',
        'default_amount',
        'percentage',
        'percentage_base',
        'formula',
        'is_taxable',
        'is_active',
        'is_mandatory',
        'is_attendance_based',
        'attendance_calculation',
        'daily_rate',
        'deduct_for_late',
        'deduct_for_early_leave',
        'include_half_days',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'is_mandatory' => 'boolean',
            'is_attendance_based' => 'boolean',
            'deduct_for_late' => 'boolean',
            'deduct_for_early_leave' => 'boolean',
            'include_half_days' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeSalaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEarnings($query)
    {
        return $query->where('type', 'earning');
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helpers
    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }

    public function isFixed(): bool
    {
        return $this->calculation_type === 'fixed';
    }

    public function isPercentage(): bool
    {
        return $this->calculation_type === 'percentage';
    }

    public function calculateAmount(float $baseAmount = 0): float
    {
        if ($this->calculation_type === 'fixed') {
            return (float) $this->default_amount;
        }

        if ($this->calculation_type === 'percentage' && $this->percentage) {
            return $baseAmount * ($this->percentage / 100);
        }

        return (float) $this->default_amount;
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earning' => 'Pendapatan',
            'deduction' => 'Potongan',
            default => '-',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'earning' => 'success',
            'deduction' => 'danger',
            default => 'secondary',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'fixed' => 'Tetap',
            'variable' => 'Variabel',
            'benefit' => 'Tunjangan',
            'tax' => 'Pajak',
            'insurance' => 'Asuransi',
            'loan' => 'Pinjaman',
            'other' => 'Lainnya',
            default => '-',
        };
    }

    public function getCalculationTypeLabelAttribute(): string
    {
        return match ($this->calculation_type) {
            'fixed' => 'Nominal Tetap',
            'percentage' => 'Persentase',
            'formula' => 'Formula',
            default => '-',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        if ($this->calculation_type === 'percentage') {
            return $this->percentage.'%';
        }

        return 'Rp '.number_format($this->default_amount, 0, ',', '.');
    }

    public function getFormattedDefaultAmountAttribute(): ?string
    {
        if ($this->default_amount === null) {
            return null;
        }

        return 'Rp '.number_format($this->default_amount, 0, ',', '.');
    }

    public function getFormattedDailyRateAttribute(): ?string
    {
        if ($this->daily_rate === null) {
            return null;
        }

        return 'Rp '.number_format($this->daily_rate, 0, ',', '.');
    }

    public function getAttendanceCalculationLabelAttribute(): string
    {
        return match ($this->attendance_calculation) {
            'daily' => 'Per Hari Kehadiran',
            'monthly' => 'Bulanan (Potong Absen)',
            default => '-',
        };
    }

    /**
     * Check if this component is attendance-based
     */
    public function isAttendanceBased(): bool
    {
        return $this->is_attendance_based === true;
    }

    /**
     * Calculate amount based on attendance data
     *
     * @param  array{
     *     present_days: int,
     *     absent_days: int,
     *     late_days: int,
     *     half_days: int,
     *     working_days: int
     * }  $attendanceData
     */
    public function calculateAttendanceBasedAmount(array $attendanceData, float $employeeAmount = 0): float
    {
        if (! $this->is_attendance_based) {
            return $employeeAmount ?: (float) $this->default_amount;
        }

        $presentDays = $attendanceData['present_days'] ?? 0;
        $halfDays = $attendanceData['half_days'] ?? 0;
        $lateDays = $attendanceData['late_days'] ?? 0;
        $workingDays = $attendanceData['working_days'] ?? 22;

        // Calculate effective present days
        $effectiveDays = $presentDays;

        // Include half days as 0.5
        if ($this->include_half_days && $halfDays > 0) {
            $effectiveDays += ($halfDays * 0.5);
        }

        // Deduct late days if configured
        if ($this->deduct_for_late && $lateDays > 0) {
            $effectiveDays -= $lateDays;
        }

        $effectiveDays = max(0, $effectiveDays);

        if ($this->attendance_calculation === 'daily') {
            // Daily calculation: daily_rate × present days
            $dailyRate = $this->daily_rate ?: ($employeeAmount / $workingDays);

            return $dailyRate * $effectiveDays;
        }

        // Monthly calculation: proportional deduction for absences
        $monthlyAmount = $employeeAmount ?: (float) $this->default_amount;
        $dailyRate = $monthlyAmount / $workingDays;
        $absentDays = $workingDays - $effectiveDays;

        return $monthlyAmount - ($dailyRate * max(0, $absentDays));
    }
}
