<?php

namespace App\Models;

use App\Observers\CompanyObserver;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'enable_face_recognition' => true,
        'face_match_threshold' => 0.60,  // Cosine similarity threshold (0.6 = moderate, 0.7 = strict)
        'require_liveness_detection' => false,
    ];

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo',
        'website',
        'npwp',
        'is_active',
        'is_demo_mode',
        'demo_started_at',
        'subscription_plan',
        'subscription_ends_at',
        'max_employees',
        'settings',
        'office_latitude',
        'office_longitude',
        'office_radius',
        'enable_gps_validation',
        'enable_face_recognition',
        'face_match_threshold',
        'require_liveness_detection',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo_mode' => 'boolean',
            'demo_started_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'max_employees' => 'integer',
            'settings' => 'array',
            'office_latitude' => 'decimal:8',
            'office_longitude' => 'decimal:8',
            'office_radius' => 'integer',
            'enable_gps_validation' => 'boolean',
            'enable_face_recognition' => 'boolean',
            'face_match_threshold' => 'float',
            'require_liveness_detection' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });

        // Protect all companies from deletion - data belongs to tenants
        static::deleting(function (Company $company) {
            throw new \Exception('Perusahaan tidak dapat dihapus. Data perusahaan adalah milik tenant.');
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function larkIntegration(): HasOne
    {
        return $this->hasOne(LarkIntegration::class);
    }

    public function isSubscriptionActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->subscription_ends_at) {
            return true;
        }

        return $this->subscription_ends_at->isFuture();
    }

    public function canAddEmployee(): bool
    {
        return $this->users()->count() < $this->max_employees;
    }

    public function isInDemoMode(): bool
    {
        return $this->is_demo_mode;
    }

    public function switchToProduction(): void
    {
        $this->update([
            'is_demo_mode' => false,
        ]);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function officeLocations()
    {
        return $this->hasMany(OfficeLocation::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get current time in company timezone
     */
    public function now(): Carbon
    {
        return Carbon::now($this->timezone);
    }

    /**
     * Get today's date in company timezone
     */
    public function today(): Carbon
    {
        return Carbon::today($this->timezone);
    }

    /**
     * Convert a datetime to company timezone
     */
    public function toCompanyTimezone(Carbon $datetime): Carbon
    {
        return $datetime->copy()->setTimezone($this->timezone);
    }

    /**
     * Convert a datetime from company timezone to UTC
     */
    public function toUtc(Carbon $datetime): Carbon
    {
        return $datetime->copy()->setTimezone('UTC');
    }

    /**
     * Parse a time string in company timezone (for schedule comparisons)
     */
    public function parseTime(string $time): Carbon
    {
        return Carbon::parse($time, $this->timezone);
    }

    /**
     * Get timezone offset string (e.g., "+07:00")
     */
    public function getTimezoneOffsetAttribute(): string
    {
        $tz = new DateTimeZone($this->timezone);
        $offset = $tz->getOffset(new \DateTime('now', $tz));
        $hours = intdiv($offset, 3600);
        $minutes = abs($offset % 3600 / 60);

        return sprintf('%+03d:%02d', $hours, $minutes);
    }

    /**
     * Get list of supported timezones for Indonesia
     *
     * @return array<string, string>
     */
    public static function getIndonesianTimezones(): array
    {
        return [
            'Asia/Jakarta' => 'WIB - Waktu Indonesia Barat (UTC+7)',
            'Asia/Makassar' => 'WITA - Waktu Indonesia Tengah (UTC+8)',
            'Asia/Jayapura' => 'WIT - Waktu Indonesia Timur (UTC+9)',
        ];
    }

    /**
     * Get list of all common timezones
     *
     * @return array<string, string>
     */
    public static function getAllTimezones(): array
    {
        return [
            // Indonesia
            'Asia/Jakarta' => 'WIB - Waktu Indonesia Barat (UTC+7)',
            'Asia/Makassar' => 'WITA - Waktu Indonesia Tengah (UTC+8)',
            'Asia/Jayapura' => 'WIT - Waktu Indonesia Timur (UTC+9)',
            // Other Asian timezones
            'Asia/Singapore' => 'Singapore (UTC+8)',
            'Asia/Kuala_Lumpur' => 'Malaysia (UTC+8)',
            'Asia/Bangkok' => 'Thailand (UTC+7)',
            'Asia/Ho_Chi_Minh' => 'Vietnam (UTC+7)',
            'Asia/Manila' => 'Philippines (UTC+8)',
            'Asia/Tokyo' => 'Japan (UTC+9)',
            'Asia/Seoul' => 'South Korea (UTC+9)',
            'Asia/Shanghai' => 'China (UTC+8)',
            'Asia/Hong_Kong' => 'Hong Kong (UTC+8)',
            'Asia/Dubai' => 'Dubai (UTC+4)',
            // Australia
            'Australia/Sydney' => 'Sydney (UTC+10/11)',
            'Australia/Perth' => 'Perth (UTC+8)',
            // Europe
            'Europe/London' => 'London (UTC+0/1)',
            'Europe/Paris' => 'Paris (UTC+1/2)',
            'Europe/Berlin' => 'Berlin (UTC+1/2)',
            // Americas
            'America/New_York' => 'New York (UTC-5/-4)',
            'America/Los_Angeles' => 'Los Angeles (UTC-8/-7)',
        ];
    }
}
