<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivityTrait, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'is_superadmin',
        'deletion_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_superadmin' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_superadmin === true;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function activeDeviceTokens()
    {
        return $this->deviceTokens()->where('is_active', true);
    }

    /**
     * Check if this is a demo account
     */
    public function isDemoAccount(): bool
    {
        return str_ends_with($this->email, '@demo.gajipro.com')
            || $this->email === 'superadmin@gajipro.com';
    }

    /**
     * Prevent deletion of demo accounts
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->isDemoAccount()) {
                throw new \Exception('Akun demo tidak dapat dihapus.');
            }
        });
    }
}
