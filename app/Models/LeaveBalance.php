<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'used_days',
        'pending_days',
        'carried_forward_days',
        'adjustment_days',
        'adjustment_notes',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled_days' => 'decimal:1',
            'used_days' => 'decimal:1',
            'pending_days' => 'decimal:1',
            'carried_forward_days' => 'decimal:1',
            'adjustment_days' => 'decimal:1',
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

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    public function getTotalEntitledAttribute(): float
    {
        return $this->entitled_days + $this->carried_forward_days + $this->adjustment_days;
    }

    public function getRemainingDaysAttribute(): float
    {
        return $this->total_entitled - $this->used_days - $this->pending_days;
    }

    public function getAvailableDaysAttribute(): float
    {
        return $this->total_entitled - $this->used_days;
    }

    public function hasEnoughBalance(float $days): bool
    {
        return $this->remaining_days >= $days;
    }

    public function deductDays(float $days): void
    {
        $this->used_days += $days;
        $this->save();
    }

    public function addPendingDays(float $days): void
    {
        $this->pending_days += $days;
        $this->save();
    }

    public function removePendingDays(float $days): void
    {
        $this->pending_days = max(0, $this->pending_days - $days);
        $this->save();
    }

    public function convertPendingToUsed(float $days): void
    {
        $this->pending_days = max(0, $this->pending_days - $days);
        $this->used_days += $days;
        $this->save();
    }

    public function restoreDays(float $days): void
    {
        $this->used_days = max(0, $this->used_days - $days);
        $this->save();
    }
}
