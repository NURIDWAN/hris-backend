<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'request_number',
        'start_date',
        'end_date',
        'total_days',
        'is_half_day',
        'half_day_type',
        'reason',
        'attachment',
        'emergency_contact',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'approval_workflow_id',
        'current_approval_step',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:1',
            'is_half_day' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'current_approval_step' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            if (empty($request->request_number)) {
                $request->request_number = static::generateRequestNumber($request->company_id);
            }
        });
    }

    public static function generateRequestNumber(int $companyId): string
    {
        $prefix = 'LV';
        $year = date('Y');
        $month = date('m');

        $lastRequest = static::where('company_id', $companyId)
            ->where('request_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function approvalWorkflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }

    public function approvalRecords()
    {
        return $this->morphMany(ApprovalRecord::class, 'approvable')->orderBy('step_order');
    }

    public function hasWorkflow(): bool
    {
        return $this->approval_workflow_id !== null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now()->toDateString())
            ->where('status', 'approved');
    }

    public function scopeOngoing($query)
    {
        $today = now()->toDateString();

        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', 'approved');
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending() || ($this->isApproved() && $this->start_date > now());
    }

    // Actions
    public function approve(int $userId, ?string $notes = null): void
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->save();

        // Update leave balance
        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();

        if ($balance) {
            $balance->convertPendingToUsed($this->total_days);
        }
    }

    public function reject(int $userId, ?string $reason = null): void
    {
        $this->status = 'rejected';
        $this->rejected_by = $userId;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        $this->save();

        // Remove pending days from balance
        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();

        if ($balance) {
            $balance->removePendingDays($this->total_days);
        }
    }

    public function cancel(int $userId, ?string $reason = null): void
    {
        $wasApproved = $this->isApproved();

        $this->status = 'cancelled';
        $this->cancelled_by = $userId;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        // Restore days to balance
        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();

        if ($balance) {
            if ($wasApproved) {
                $balance->restoreDays($this->total_days);
            } else {
                $balance->removePendingDays($this->total_days);
            }
        }
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getDateRangeAttribute(): string
    {
        if ($this->start_date->eq($this->end_date)) {
            return $this->start_date->format('d M Y');
        }

        return $this->start_date->format('d M Y').' - '.$this->end_date->format('d M Y');
    }

    public function getDurationTextAttribute(): string
    {
        if ($this->is_half_day) {
            $period = $this->half_day_type === 'morning' ? 'Pagi' : 'Siang';

            return "0.5 hari ({$period})";
        }

        $days = (int) $this->total_days;

        return "{$days} hari";
    }
}
