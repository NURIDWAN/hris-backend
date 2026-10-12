<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'basic_salary',
        'effective_date',
        'end_date',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'effective_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function components()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function salaryComponents()
    {
        return $this->belongsToMany(SalaryComponent::class, 'employee_salary_components')
            ->withPivot('amount', 'notes')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Helpers
    public function isEffective(): bool
    {
        $today = now()->toDateString();
        return $this->effective_date <= $today
            && ($this->end_date === null || $this->end_date >= $today);
    }

    public function getTotalEarnings(): float
    {
        return $this->components()
            ->whereHas('salaryComponent', fn($q) => $q->where('type', 'earning'))
            ->sum('amount');
    }

    public function getTotalDeductions(): float
    {
        return $this->components()
            ->whereHas('salaryComponent', fn($q) => $q->where('type', 'deduction'))
            ->sum('amount');
    }

    public function getGrossSalary(): float
    {
        return (float) $this->basic_salary + $this->getTotalEarnings();
    }

    public function getNetSalary(): float
    {
        return $this->getGrossSalary() - $this->getTotalDeductions();
    }

    // Accessors
    public function getFormattedBasicSalaryAttribute(): string
    {
        return 'Rp ' . number_format($this->basic_salary, 0, ',', '.');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'transfer' => 'Transfer Bank',
            'cash' => 'Tunai',
            default => '-',
        };
    }

    public function getBankInfoAttribute(): ?string
    {
        if ($this->payment_method !== 'transfer') {
            return null;
        }

        return "{$this->bank_name} - {$this->bank_account_number} ({$this->bank_account_name})";
    }
}
