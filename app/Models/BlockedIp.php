<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
        'blocked_until',
        'attempt_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public static function isBlocked(string $ip): bool
    {
        $blocked = self::where('ip_address', $ip)
            ->where('is_active', true)
            ->first();

        if (! $blocked) {
            return false;
        }

        // Check if temporary block has expired
        if ($blocked->blocked_until && $blocked->blocked_until->isPast()) {
            $blocked->update(['is_active' => false]);

            return false;
        }

        return true;
    }

    public static function block(
        string $ip,
        string $reason,
        ?int $blockedBy = null,
        ?int $minutes = null
    ): self {
        return self::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'blocked_by' => $blockedBy ?? auth()->id(),
                'blocked_until' => $minutes ? now()->addMinutes($minutes) : null,
                'is_active' => true,
                'attempt_count' => \DB::raw('attempt_count + 1'),
            ]
        );
    }

    public static function unblock(string $ip): bool
    {
        return self::where('ip_address', $ip)->update(['is_active' => false]) > 0;
    }

    public function isPermanent(): bool
    {
        return $this->blocked_until === null;
    }

    public function getRemainingTimeAttribute(): ?string
    {
        if ($this->isPermanent()) {
            return 'Permanent';
        }

        if ($this->blocked_until->isPast()) {
            return 'Expired';
        }

        return $this->blocked_until->diffForHumans();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            });
    }
}
