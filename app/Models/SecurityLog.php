<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class SecurityLog extends Model
{
    protected $fillable = [
        'ip_address',
        'event_type',
        'severity',
        'url',
        'method',
        'user_agent',
        'user_id',
        'payload',
        'description',
        'country',
        'city',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $eventType,
        string $severity = 'warning',
        ?string $description = null,
        ?array $payload = null,
        ?Request $request = null
    ): self {
        $request = $request ?? request();

        // Sanitize payload - remove sensitive data
        if ($payload) {
            $payload = self::sanitizePayload($payload);
        }

        return self::create([
            'ip_address' => $request->ip(),
            'event_type' => $eventType,
            'severity' => $severity,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'payload' => $payload,
            'description' => $description,
        ]);
    }

    protected static function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'key', 'authorization'];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $payload[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $payload[$key] = self::sanitizePayload($value);
            }
        }

        return $payload;
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary',
        };
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
