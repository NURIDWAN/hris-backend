<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeExit extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'exit_type',
        'request_date',
        'last_working_day',
        'effective_date',
        'reason',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'assets_returned',
        'knowledge_transfer_done',
        'final_settlement_done',
        'exit_interview_done',
        'exit_interview_notes',
        'is_rehire_eligible',
        'rehire_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'last_working_day' => 'date',
            'effective_date' => 'date',
            'approved_at' => 'datetime',
            'assets_returned' => 'boolean',
            'knowledge_transfer_done' => 'boolean',
            'final_settlement_done' => 'boolean',
            'exit_interview_done' => 'boolean',
            'is_rehire_eligible' => 'boolean',
        ];
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('exit_type', $type);
    }

    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->whereIn('status', ['pending', 'approved'])
            ->whereBetween('effective_date', [now(), now()->addDays($days)]);
    }

    // Accessors
    public function getExitTypeLabelAttribute(): string
    {
        return match ($this->exit_type) {
            'resignation' => 'Resign',
            'termination' => 'PHK',
            'retirement' => 'Pensiun',
            'contract_end' => 'Akhir Kontrak',
            'mutual_agreement' => 'Kesepakatan Bersama',
            'death' => 'Meninggal',
            default => $this->exit_type,
        };
    }

    public function getExitTypeColorAttribute(): string
    {
        return match ($this->exit_type) {
            'resignation' => 'warning',
            'termination' => 'danger',
            'retirement' => 'info',
            'contract_end' => 'secondary',
            'mutual_agreement' => 'primary',
            'death' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getChecklistProgressAttribute(): int
    {
        $total = 4;
        $completed = 0;

        if ($this->assets_returned) {
            $completed++;
        }
        if ($this->knowledge_transfer_done) {
            $completed++;
        }
        if ($this->final_settlement_done) {
            $completed++;
        }
        if ($this->exit_interview_done) {
            $completed++;
        }

        return (int) (($completed / $total) * 100);
    }

    public function isChecklistComplete(): bool
    {
        return $this->assets_returned
            && $this->knowledge_transfer_done
            && $this->final_settlement_done
            && $this->exit_interview_done;
    }

    // Methods
    public function approve(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function reject(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);

        // Deactivate the employee
        $this->employee->update([
            'is_active' => false,
            'resignation_date' => $this->effective_date,
        ]);

        // Deactivate user account if exists
        if ($this->employee->user) {
            $this->employee->user->update(['is_active' => false]);
        }
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
