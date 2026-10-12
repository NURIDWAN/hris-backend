<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    use HasFactory;

    public const GATEWAY_MIDTRANS = 'midtrans';

    public const GATEWAY_XENDIT = 'xendit';

    public const GATEWAY_MANUAL = 'manual';

    public const GATEWAYS = [
        self::GATEWAY_MIDTRANS => 'Midtrans',
        self::GATEWAY_XENDIT => 'Xendit',
        self::GATEWAY_MANUAL => 'Manual Transfer',
    ];

    public const ENV_SANDBOX = 'sandbox';

    public const ENV_PRODUCTION = 'production';

    public const ENVIRONMENTS = [
        self::ENV_SANDBOX => 'Sandbox',
        self::ENV_PRODUCTION => 'Production',
    ];

    protected $fillable = [
        'gateway',
        'name',
        'environment',
        'credentials',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helpers
    public function isProduction(): bool
    {
        return $this->environment === self::ENV_PRODUCTION;
    }

    public function isSandbox(): bool
    {
        return $this->environment === self::ENV_SANDBOX;
    }

    public function getCredential(string $key, $default = null)
    {
        return $this->credentials[$key] ?? $default;
    }

    // Accessors
    public function getGatewayLabelAttribute(): string
    {
        return self::GATEWAYS[$this->gateway] ?? $this->gateway;
    }

    public function getEnvironmentLabelAttribute(): string
    {
        return self::ENVIRONMENTS[$this->environment] ?? $this->environment;
    }
}
