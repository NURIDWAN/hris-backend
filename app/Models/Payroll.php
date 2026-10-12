<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = [
        'company_id',
        'payroll_number',
        'name',
        'period_month',
        'period_year',
        'period_start',
        'period_end',
        'payment_date',
        'status',
        'total_employees',
        'total_earnings',
        'total_deductions',
        'total_net_salary',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
            'total_employees' => 'integer',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net_salary' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payroll $payroll) {
            if (empty($payroll->payroll_number)) {
                $payroll->payroll_number = static::generatePayrollNumber($payroll->company_id, $payroll->period_year, $payroll->period_month);
            }
        });
    }

    public static function generatePayrollNumber(int $companyId, int $year, int $month): string
    {
        $prefix = 'PAY';
        $companyPrefix = str_pad($companyId, 4, '0', STR_PAD_LEFT);
        $period = $year.str_pad($month, 2, '0', STR_PAD_LEFT);

        $lastPayroll = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('payroll_number', 'like', "{$prefix}{$companyPrefix}{$period}%")
            ->orderBy('payroll_number', 'desc')
            ->first();

        if ($lastPayroll) {
            $lastNumber = (int) substr($lastPayroll->payroll_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: PAY{company_id_4digit}{YYYYMM}{sequence_3digit}
        // Example: PAY0001202601001 (company 1, January 2026, sequence 1)
        return $prefix.$companyPrefix.$period.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('period_year', $year);
    }

    // Status checks
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
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

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Can actions
    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    public function canBeCalculated(): bool
    {
        return $this->isDraft();
    }

    public function canBeApproved(): bool
    {
        return $this->isCalculated();
    }

    public function canBePaid(): bool
    {
        return $this->isApproved();
    }

    public function canBeAdjusted(): bool
    {
        return $this->isCalculated();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'calculated', 'approved']);
    }

    // Actions
    public function markAsProcessing(): void
    {
        $this->status = 'processing';
        $this->save();
    }

    public function markAsCalculated(): void
    {
        $this->status = 'calculated';
        $this->recalculateTotals();
        $this->save();
    }

    public function approve(int $userId): void
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->save();

        $this->items()->update(['status' => 'approved']);
    }

    public function markAsPaid(int $userId, ?string $paymentDate = null): void
    {
        $this->status = 'paid';
        $this->paid_by = $userId;
        $this->paid_at = now();
        $this->payment_date = $paymentDate ?? now()->toDateString();
        $this->save();

        $this->items()->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function recalculateTotals(): void
    {
        $this->total_employees = $this->items()->count();
        $this->total_earnings = $this->items()->sum('total_earnings');
        $this->total_deductions = $this->items()->sum('total_deductions');
        $this->total_net_salary = $this->items()->sum('net_salary');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'processing' => 'Proses',
            'calculated' => 'Terhitung',
            'approved' => 'Disetujui',
            'paid' => 'Dibayar',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'processing' => 'info',
            'calculated' => 'warning',
            'approved' => 'primary',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[$this->period_month].' '.$this->period_year;
    }

    public function getFormattedTotalNetSalaryAttribute(): string
    {
        return 'Rp '.number_format($this->total_net_salary, 0, ',', '.');
    }

    public function getFormattedTotalEarningsAttribute(): string
    {
        return 'Rp '.number_format($this->total_earnings, 0, ',', '.');
    }

    public function getFormattedTotalDeductionsAttribute(): string
    {
        return 'Rp '.number_format($this->total_deductions, 0, ',', '.');
    }
}
