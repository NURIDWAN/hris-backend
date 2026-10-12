<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    public const TYPE_LEAVE_REQUEST = 'leave_request';

    public const TYPE_OVERTIME = 'overtime';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_REIMBURSEMENT = 'reimbursement';

    public const TYPES = [
        self::TYPE_LEAVE_REQUEST => 'Pengajuan Cuti',
        self::TYPE_OVERTIME => 'Pengajuan Lembur',
        self::TYPE_EXPENSE => 'Pengajuan Pengeluaran',
        self::TYPE_REIMBURSEMENT => 'Pengajuan Reimbursement',
    ];

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class)->orderBy('step_order');
    }

    public function activeSteps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class)
            ->where('is_active', true)
            ->orderBy('step_order');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
