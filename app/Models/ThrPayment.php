<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThrPayment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'employee_id',
        'year',
        'religious_holiday',
        'base_salary',
        'allowances',
        'amount',
        'service_months',
        'calculation_method',
        'status',
        'payment_date',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'amount' => 'decimal:2',
            'service_months' => 'integer',
            'year' => 'integer',
            'payment_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_PAID => 'Dibayar',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedBaseSalaryAttribute(): string
    {
        return 'Rp '.number_format($this->base_salary, 0, ',', '.');
    }

    public function getReligiousHolidayLabelAttribute(): string
    {
        return ThrSetting::RELIGIOUS_HOLIDAYS[$this->religious_holiday] ?? $this->religious_holiday;
    }

    public function getCalculationMethodLabelAttribute(): string
    {
        return ThrSetting::CALCULATION_METHODS[$this->calculation_method] ?? $this->calculation_method;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
