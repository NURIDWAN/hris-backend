<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpjsKesSetting extends Model
{
    use HasFactory;

    protected $table = 'bpjs_kes_settings';

    protected $fillable = [
        'company_id',
        'company_rate',
        'employee_rate',
        'min_salary_basis',
        'max_salary_basis',
        'effective_year',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'company_rate' => 'decimal:2',
        'employee_rate' => 'decimal:2',
        'min_salary_basis' => 'decimal:2',
        'max_salary_basis' => 'decimal:2',
        'effective_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getTotalRateAttribute(): float
    {
        return $this->company_rate + $this->employee_rate;
    }

    public function getFormattedMinSalaryBasisAttribute(): string
    {
        return 'Rp '.number_format($this->min_salary_basis, 0, ',', '.');
    }

    public function getFormattedMaxSalaryBasisAttribute(): string
    {
        return 'Rp '.number_format($this->max_salary_basis, 0, ',', '.');
    }

    // Calculate contribution based on salary
    public function calculateContribution(float $salary): array
    {
        // Apply min/max limits
        $calculationBasis = max($this->min_salary_basis, min($salary, $this->max_salary_basis));

        $companyContribution = $calculationBasis * ($this->company_rate / 100);
        $employeeContribution = $calculationBasis * ($this->employee_rate / 100);

        return [
            'salary_basis' => $calculationBasis,
            'company' => $companyContribution,
            'employee' => $employeeContribution,
            'total' => $companyContribution + $employeeContribution,
        ];
    }
}
