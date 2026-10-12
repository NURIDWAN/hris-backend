<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = [
        'company_id',
        'department_id',
        'position_id',
        'work_schedule_id',
        'manager_id',
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'marital_status',
        'religion',
        'blood_type',
        'identity_number',
        'identity_address',
        'address',
        'city',
        'province',
        'postal_code',
        'photo',
        'hire_date',
        'resignation_date',
        'employment_status',
        'contract_start_date',
        'contract_end_date',
        'base_salary',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'npwp',
        'tax_status',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'resignation_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'base_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->employee_id) && $employee->company_id) {
                $employee->employee_id = static::generateEmployeeId($employee->company_id);
            }
        });

        static::deleting(function (Employee $employee) {
            if ($employee->isDemoEmployee()) {
                throw new \Exception('Data karyawan demo tidak dapat dihapus.');
            }
        });
    }

    /**
     * Check if this is a demo employee (linked to demo user account)
     */
    public function isDemoEmployee(): bool
    {
        if (! $this->user_id) {
            return false;
        }

        $user = $this->user ?? User::find($this->user_id);

        return $user && $user->isDemoAccount();
    }

    public static function generateEmployeeId(int $companyId): string
    {
        $prefix = 'EMP';
        $year = date('Y');
        $lastEmployee = static::where('company_id', $companyId)
            ->where('employee_id', 'like', "{$prefix}{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(EmployeeWeeklySchedule::class);
    }

    /**
     * Resolve the work schedule for a specific date.
     *
     * If employee has weekly schedule pattern → match by day_of_week (null = day off).
     * If no weekly pattern → fallback to workSchedule + isWorkingDay check.
     * If neither → return null.
     */
    public function resolveScheduleForDate(Carbon $date): ?WorkSchedule
    {
        $weeklySchedules = $this->relationLoaded('weeklySchedules')
            ? $this->weeklySchedules
            : $this->weeklySchedules()->with('workSchedule')->get();

        if ($weeklySchedules->isNotEmpty()) {
            $match = $weeklySchedules->firstWhere('day_of_week', $date->dayOfWeekIso);

            return $match?->workSchedule;
        }

        $schedule = $this->relationLoaded('workSchedule')
            ? $this->workSchedule
            : $this->workSchedule()->first();

        if ($schedule && $schedule->isWorkingDay($date->dayOfWeekIso)) {
            return $schedule;
        }

        return null;
    }

    public function hasWeeklySchedulePattern(): bool
    {
        if ($this->relationLoaded('weeklySchedules')) {
            return $this->weeklySchedules->isNotEmpty();
        }

        return $this->weeklySchedules()->exists();
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function currentSalary()
    {
        return $this->hasOne(EmployeeSalary::class)
            ->where('is_active', true)
            ->latest('effective_date');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    public function getYearsOfServiceAttribute(): int
    {
        return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
    }

    public function isContractExpiring(int $daysThreshold = 30): bool
    {
        if ($this->employment_status !== 'contract' || ! $this->contract_end_date) {
            return false;
        }

        $daysUntilEnd = now()->diffInDays($this->contract_end_date, false);

        return $daysUntilEnd > 0 && $daysUntilEnd <= $daysThreshold;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByEmploymentStatus($query, string $status)
    {
        return $query->where('employment_status', $status);
    }

    public function scopeContractExpiringSoon($query, int $days = 30)
    {
        return $query->where('employment_status', 'contract')
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now(), now()->addDays($days)]);
    }

    public function officeLocations()
    {
        return $this->belongsToMany(OfficeLocation::class, 'employee_office_locations')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryOfficeLocation()
    {
        return $this->belongsToMany(OfficeLocation::class, 'employee_office_locations')
            ->withPivot('is_primary')
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function getPrimaryOfficeLocationAttribute()
    {
        return $this->officeLocations()->wherePivot('is_primary', true)->first();
    }

    public function faceEmbedding()
    {
        return $this->hasOne(EmployeeFaceEmbedding::class);
    }

    public function faceVerificationLogs()
    {
        return $this->hasMany(FaceVerificationLog::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function exits()
    {
        return $this->hasMany(EmployeeExit::class);
    }

    public function latestExit()
    {
        return $this->hasOne(EmployeeExit::class)->latest();
    }

    public function hasFaceEnrolled(): bool
    {
        return $this->faceEmbedding()
            ->where('is_active', true)
            ->exists();
    }
}
