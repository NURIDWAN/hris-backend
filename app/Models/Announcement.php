<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    use HasFactory, LogsActivityTrait;

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const TARGET_ALL = 'all';

    public const TARGET_DEPARTMENT = 'department';

    public const TARGET_POSITION = 'position';

    public const TARGET_SPECIFIC = 'specific';

    public const PRIORITY_LABELS = [
        self::PRIORITY_LOW => 'Rendah',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'Tinggi',
        self::PRIORITY_URGENT => 'Mendesak',
    ];

    public const TARGET_LABELS = [
        self::TARGET_ALL => 'Semua Karyawan',
        self::TARGET_DEPARTMENT => 'Departemen Tertentu',
        self::TARGET_POSITION => 'Jabatan Tertentu',
        self::TARGET_SPECIFIC => 'Karyawan Tertentu',
    ];

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'content',
        'priority',
        'target_audience',
        'target_ids',
        'is_pinned',
        'is_published',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'target_ids' => 'array',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    // Accessors
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function getTargetLabelAttribute(): string
    {
        return self::TARGET_LABELS[$this->target_audience] ?? $this->target_audience;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->is_published && ! $this->is_expired;
    }

    // Methods
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
        ]);
    }

    public function markAsRead(int $userId): void
    {
        if (! $this->readers()->where('user_id', $userId)->exists()) {
            $this->readers()->attach($userId, ['read_at' => now()]);
        }
    }

    public function isReadBy(int $userId): bool
    {
        return $this->readers()->where('user_id', $userId)->exists();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // All employees
            $q->where('target_audience', self::TARGET_ALL);

            // By department
            if ($user->employee) {
                $deptId = $user->employee->department_id;
                $posId = $user->employee->position_id;
                $empId = $user->employee->id;

                $q->orWhere(function ($q2) use ($deptId) {
                    $q2->where('target_audience', self::TARGET_DEPARTMENT)
                        ->whereRaw('(target_ids LIKE ? OR target_ids LIKE ? OR target_ids LIKE ? OR target_ids LIKE ?)', [
                            "[{$deptId}]",
                            "[{$deptId},%",
                            "%,{$deptId},%",
                            "%,{$deptId}]",
                        ]);
                });

                // By position
                $q->orWhere(function ($q2) use ($posId) {
                    $q2->where('target_audience', self::TARGET_POSITION)
                        ->whereRaw('(target_ids LIKE ? OR target_ids LIKE ? OR target_ids LIKE ? OR target_ids LIKE ?)', [
                            "[{$posId}]",
                            "[{$posId},%",
                            "%,{$posId},%",
                            "%,{$posId}]",
                        ]);
                });

                // Specific employees
                $q->orWhere(function ($q2) use ($empId) {
                    $q2->where('target_audience', self::TARGET_SPECIFIC)
                        ->whereRaw('(target_ids LIKE ? OR target_ids LIKE ? OR target_ids LIKE ? OR target_ids LIKE ?)', [
                            "[{$empId}]",
                            "[{$empId},%",
                            "%,{$empId},%",
                            "%,{$empId}]",
                        ]);
                });
            }
        });
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END");
    }
}
