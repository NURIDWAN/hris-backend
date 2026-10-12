<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'batch_uuid',
        'company_id',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        // Auto-set company_id when creating activity
        static::creating(function (Activity $activity) {
            if (! $activity->company_id && app()->bound('tenant')) {
                $tenant = app('tenant');
                if ($tenant) {
                    $activity->company_id = $tenant->id;
                }
            }
        });
    }

    /**
     * Relationship to company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to filter by company (tenant isolation).
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Get the human-readable event label.
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            'login' => 'Login',
            'logout' => 'Logout',
            default => ucfirst($this->event ?? 'unknown'),
        };
    }

    /**
     * Get the subject type label (human-readable model name).
     */
    public function getSubjectTypeLabelAttribute(): string
    {
        if (! $this->subject_type) {
            return '-';
        }

        $modelLabels = [
            'App\\Models\\User' => 'User',
            'App\\Models\\Employee' => 'Karyawan',
            'App\\Models\\Department' => 'Departemen',
            'App\\Models\\Position' => 'Jabatan',
            'App\\Models\\Attendance' => 'Absensi',
            'App\\Models\\LeaveRequest' => 'Cuti',
            'App\\Models\\Payroll' => 'Payroll',
            'App\\Models\\PayrollItem' => 'Slip Gaji',
            'App\\Models\\EmployeeDocument' => 'Dokumen',
            'App\\Models\\Company' => 'Perusahaan',
            'App\\Models\\OfficeLocation' => 'Lokasi Kantor',
            'App\\Models\\WorkSchedule' => 'Jadwal Kerja',
            'App\\Models\\LeaveType' => 'Tipe Cuti',
            'App\\Models\\LeaveBalance' => 'Saldo Cuti',
            'App\\Models\\SalaryComponent' => 'Komponen Gaji',
            'App\\Models\\EmployeeSalary' => 'Gaji Karyawan',
            'App\\Models\\Announcement' => 'Pengumuman',
            'App\\Models\\OvertimeRequest' => 'Lembur',
            'App\\Models\\Loan' => 'Pinjaman',
            'App\\Models\\Reimbursement' => 'Reimbursement',
        ];

        return $modelLabels[$this->subject_type] ?? class_basename($this->subject_type);
    }

    /**
     * Get changes summary for display.
     */
    public function getChangesSummaryAttribute(): ?array
    {
        $properties = $this->properties;

        if (! $properties || (! isset($properties['old']) && ! isset($properties['attributes']))) {
            return null;
        }

        $changes = [];
        $old = $properties['old'] ?? [];
        $new = $properties['attributes'] ?? [];

        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;
            if ($oldValue !== $value) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                ];
            }
        }

        return $changes ?: null;
    }
}
