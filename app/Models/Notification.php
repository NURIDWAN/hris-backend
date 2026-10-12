<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'data',
        'read_at',
        'push_sent_at',
        'push_error',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'push_sent_at' => 'datetime',
        ];
    }

    // Icon mapping based on notification type
    protected const TYPE_ICONS = [
        'leave_request' => 'calendar',
        'leave_requested' => 'calendar',
        'payroll' => 'banknotes',
        'attendance' => 'clock',
        'employee' => 'user',
        'approval' => 'check-circle',
        'info' => 'information-circle',
        'warning' => 'exclamation-triangle',
        'success' => 'check-circle',
        'error' => 'x-circle',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function getIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'information-circle';
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $limit = 5)
    {
        return $query->latest()->limit($limit);
    }
}
