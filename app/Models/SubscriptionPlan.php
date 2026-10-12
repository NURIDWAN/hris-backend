<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_employees',
        'max_users',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_employees' => 'integer',
            'max_users' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function getFormattedPriceMonthlyAttribute(): string
    {
        return 'Rp '.number_format($this->price_monthly, 0, ',', '.');
    }

    public function getFormattedPriceYearlyAttribute(): string
    {
        return 'Rp '.number_format($this->price_yearly, 0, ',', '.');
    }

    public function isFree(): bool
    {
        return $this->price_monthly == 0 && $this->price_yearly == 0;
    }

    public function hasUnlimitedEmployees(): bool
    {
        return $this->max_employees == 0;
    }

    public function hasUnlimitedUsers(): bool
    {
        return $this->max_users == 0;
    }
}
