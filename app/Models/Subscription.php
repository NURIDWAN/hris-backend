<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_CANCELLED => 'Dibatalkan',
        self::STATUS_EXPIRED => 'Kedaluwarsa',
        self::STATUS_PENDING => 'Menunggu',
    ];

    public const CYCLE_MONTHLY = 'monthly';

    public const CYCLE_YEARLY = 'yearly';

    public const CYCLES = [
        self::CYCLE_MONTHLY => 'Bulanan',
        self::CYCLE_YEARLY => 'Tahunan',
    ];

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'started_at',
        'ends_at',
        'cancelled_at',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getCycleLabelAttribute(): string
    {
        return self::CYCLES[$this->billing_cycle] ?? $this->billing_cycle;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->ends_at) {
            return null;
        }

        return now()->diffInDays($this->ends_at, false);
    }
}
