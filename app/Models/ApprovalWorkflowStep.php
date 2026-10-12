<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStep extends Model
{
    use HasFactory;

    public const APPROVER_TYPE_ROLE = 'role';

    public const APPROVER_TYPE_SPECIFIC_USER = 'specific_user';

    public const APPROVER_TYPE_DEPARTMENT_HEAD = 'department_head';

    public const APPROVER_TYPE_DIRECT_MANAGER = 'direct_manager';

    public const APPROVER_TYPES = [
        self::APPROVER_TYPE_ROLE => 'Berdasarkan Role',
        self::APPROVER_TYPE_SPECIFIC_USER => 'Pengguna Tertentu',
        self::APPROVER_TYPE_DEPARTMENT_HEAD => 'Kepala Departemen',
        self::APPROVER_TYPE_DIRECT_MANAGER => 'Atasan Langsung',
    ];

    protected $fillable = [
        'approval_workflow_id',
        'step_order',
        'approver_type',
        'approver_role',
        'approver_user_id',
        'can_skip',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'can_skip' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function getApproverTypeLabelAttribute(): string
    {
        return self::APPROVER_TYPES[$this->approver_type] ?? $this->approver_type;
    }

    public function getApproverDescriptionAttribute(): string
    {
        return match ($this->approver_type) {
            self::APPROVER_TYPE_ROLE => ucfirst(str_replace('-', ' ', $this->approver_role ?? '')),
            self::APPROVER_TYPE_SPECIFIC_USER => $this->approverUser?->name ?? 'User tidak ditemukan',
            self::APPROVER_TYPE_DEPARTMENT_HEAD => 'Kepala Departemen Pemohon',
            self::APPROVER_TYPE_DIRECT_MANAGER => 'Atasan Langsung Pemohon',
            default => '-',
        };
    }
}
