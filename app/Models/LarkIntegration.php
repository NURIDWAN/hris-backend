<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LarkIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'app_id',
        'app_secret',
        'is_active',
        'push_clock_in',
        'push_clock_out',
        'enable_bot_notifications',
        'bot_webhook_url',
        'verification_token',
        'encrypt_key',
        'last_synced_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'app_secret' => 'encrypted',
            'encrypt_key' => 'encrypted',
            'is_active' => 'boolean',
            'push_clock_in' => 'boolean',
            'push_clock_out' => 'boolean',
            'enable_bot_notifications' => 'boolean',
            'last_synced_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
