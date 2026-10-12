<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'employee_salary_id',
        'employee_name',
        'employee_number',
        'department_name',
        'position_name',
        'working_days',
        'present_days',
        'absent_days',
        'late_days',
        'leave_days',
        'overtime_hours',
        'basic_salary',
        'total_earnings',
        'total_deductions',
        'gross_salary',
        'tax_amount',
        'net_salary',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'integer',
            'present_days' => 'integer',
            'absent_days' => 'integer',
            'late_days' => 'integer',
            'leave_days' => 'integer',
            'overtime_hours' => 'integer',
            'basic_salary' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeSalary()
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

    public function details()
    {
        return $this->hasMany(PayrollItemDetail::class);
    }

    public function earnings()
    {
        return $this->details()->where('type', 'earning');
    }

    public function deductions()
    {
        return $this->details()->where('type', 'deduction');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function recalculate(): void
    {
        $this->total_earnings = $this->earnings()->sum('amount');
        $this->total_deductions = $this->deductions()->sum('amount');
        $this->gross_salary = $this->basic_salary + $this->total_earnings;
        $this->net_salary = $this->gross_salary - $this->total_deductions;
        $this->status = 'calculated';
        $this->save();
    }

    public function addDetail(
        ?int $componentId,
        string $name,
        ?string $code,
        string $type,
        string $category,
        float $amount,
        bool $isTaxable = true,
        ?string $notes = null
    ): PayrollItemDetail {
        return $this->details()->create([
            'salary_component_id' => $componentId,
            'component_name' => $name,
            'component_code' => $code,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'is_taxable' => $isTaxable,
            'notes' => $notes,
        ]);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'calculated' => 'Terhitung',
            'approved' => 'Disetujui',
            'paid' => 'Dibayar',
            default => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'secondary',
            'calculated' => 'warning',
            'approved' => 'primary',
            'paid' => 'success',
            default => 'secondary',
        };
    }

    public function getFormattedNetSalaryAttribute(): string
    {
        return 'Rp ' . number_format($this->net_salary, 0, ',', '.');
    }

    public function getFormattedGrossSalaryAttribute(): string
    {
        return 'Rp ' . number_format($this->gross_salary, 0, ',', '.');
    }

    public function getFormattedBasicSalaryAttribute(): string
    {
        return 'Rp ' . number_format($this->basic_salary, 0, ',', '.');
    }

    public function getAttendanceSummaryAttribute(): string
    {
        return "Hadir: {$this->present_days}/{$this->working_days} hari";
    }

    public function getPaymentInfoAttribute(): ?string
    {
        if ($this->payment_method !== 'transfer') {
            return 'Tunai';
        }

        return "{$this->bank_name} - {$this->bank_account_number}";
    }
}
