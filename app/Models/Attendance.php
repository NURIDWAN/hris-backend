<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'office_location_id',
        'work_schedule_id',
        'date',
        'scheduled_start',
        'scheduled_end',
        'clock_in',
        'clock_out',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_office_location_id',
        'clock_in_photo',
        'clock_out_photo',
        'face_verified',
        'face_confidence',
        'clock_in_notes',
        'clock_out_notes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'working_minutes',
        'status',
        'clock_in_status',
        'clock_out_status',
        'is_manual_entry',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'approved_at' => 'datetime',
            'is_manual_entry' => 'boolean',
            'face_verified' => 'boolean',
            'face_confidence' => 'decimal:4',
            'clock_in_latitude' => 'decimal:8',
            'clock_in_longitude' => 'decimal:8',
            'clock_out_latitude' => 'decimal:8',
            'clock_out_longitude' => 'decimal:8',
        ];
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function officeLocation()
    {
        return $this->belongsTo(OfficeLocation::class);
    }

    public function clockOutOfficeLocation()
    {
        return $this->belongsTo(OfficeLocation::class, 'clock_out_office_location_id');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    // Accessors
    public function getFormattedClockInAttribute(): ?string
    {
        $timezone = $this->company?->timezone ?? config('app.timezone');

        return $this->clock_in?->setTimezone($timezone)->format('H:i');
    }

    public function getFormattedClockOutAttribute(): ?string
    {
        $timezone = $this->company?->timezone ?? config('app.timezone');

        return $this->clock_out?->setTimezone($timezone)->format('H:i');
    }

    public function getWorkingHoursAttribute(): string
    {
        if ($this->working_minutes === 0) {
            return '-';
        }

        $hours = floor($this->working_minutes / 60);
        $minutes = $this->working_minutes % 60;

        return sprintf('%d jam %d menit', $hours, $minutes);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present' => 'Hadir',
            'absent' => 'Tidak Hadir',
            'late' => 'Terlambat',
            'half_day' => 'Setengah Hari',
            'leave' => 'Cuti',
            'holiday' => 'Libur',
            'weekend' => 'Akhir Pekan',
            default => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'half_day' => 'info',
            'leave' => 'info',
            'holiday' => 'secondary',
            'weekend' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the correct scheduled end datetime accounting for overnight shifts
     */
    public function getScheduledEndDatetime(): ?Carbon
    {
        if (! $this->scheduled_end || ! $this->date) {
            return null;
        }

        $shiftDate = $this->date instanceof Carbon ? $this->date : Carbon::parse($this->date);
        $timeStr = $this->scheduled_end instanceof \DateTimeInterface
            ? $this->scheduled_end->format('H:i:s')
            : $this->scheduled_end;
        $scheduledEnd = $shiftDate->copy()->setTimeFromTimeString($timeStr);

        // If this is an overnight shift, the end time is on the next day
        if ($this->workSchedule && $this->workSchedule->is_overnight) {
            $scheduledEnd->addDay();
        }

        return $scheduledEnd;
    }

    /**
     * Get the correct scheduled start datetime
     */
    public function getScheduledStartDatetime(): ?Carbon
    {
        if (! $this->scheduled_start || ! $this->date) {
            return null;
        }

        $shiftDate = $this->date instanceof Carbon ? $this->date : Carbon::parse($this->date);
        $timeStr = $this->scheduled_start instanceof \DateTimeInterface
            ? $this->scheduled_start->format('H:i:s')
            : $this->scheduled_start;

        return $shiftDate->copy()->setTimeFromTimeString($timeStr);
    }

    // Methods
    public function clockIn(array $data = []): self
    {
        $now = $this->company ? $this->company->now() : Carbon::now();

        $this->clock_in = $now;
        $this->clock_in_ip = $data['ip'] ?? null;
        $this->clock_in_latitude = $data['latitude'] ?? null;
        $this->clock_in_longitude = $data['longitude'] ?? null;
        $this->clock_in_photo = $data['photo'] ?? null;
        $this->clock_in_notes = $data['notes'] ?? null;

        // Calculate late minutes
        $scheduledStart = $this->getScheduledStartDatetime();
        if ($scheduledStart) {
            $tolerance = $this->workSchedule?->late_tolerance ?? 15;

            if ($now->gt($scheduledStart->copy()->addMinutes($tolerance))) {
                $this->late_minutes = $scheduledStart->diffInMinutes($now);
                $this->clock_in_status = $this->late_minutes > 30 ? 'very_late' : 'late';
                $this->status = 'late';
            } else {
                $this->clock_in_status = 'on_time';
                $this->status = 'present';
            }
        }

        $this->save();

        return $this;
    }

    public function clockOut(array $data = []): self
    {
        $now = $this->company ? $this->company->now() : Carbon::now();

        $this->clock_out = $now;
        $this->clock_out_ip = $data['ip'] ?? null;
        $this->clock_out_latitude = $data['latitude'] ?? null;
        $this->clock_out_longitude = $data['longitude'] ?? null;
        $this->clock_out_photo = $data['photo'] ?? null;
        $this->clock_out_notes = $data['notes'] ?? null;

        // Calculate working minutes
        if ($this->clock_in) {
            $workingMinutes = $this->clock_in->diffInMinutes($now);

            // Subtract break duration if the work schedule has one
            if ($this->workSchedule && $this->workSchedule->break_duration) {
                $workingMinutes -= $this->workSchedule->break_duration;
            }

            $this->working_minutes = max(0, $workingMinutes);
        }

        // Calculate early leave or overtime using correct scheduled end (accounts for overnight)
        $scheduledEnd = $this->getScheduledEndDatetime();
        if ($scheduledEnd) {
            $tolerance = $this->workSchedule?->early_leave_tolerance ?? 15;

            if ($now->lt($scheduledEnd->copy()->subMinutes($tolerance))) {
                $this->early_leave_minutes = $now->diffInMinutes($scheduledEnd);
                $this->clock_out_status = 'early';
            } elseif ($now->gt($scheduledEnd)) {
                $this->overtime_minutes = $scheduledEnd->diffInMinutes($now);
                $this->clock_out_status = 'overtime';
            } else {
                $this->clock_out_status = 'on_time';
            }
        }

        $this->save();

        return $this;
    }

    /**
     * Recalculate attendance metrics (useful for manual entries or corrections)
     */
    public function recalculate(): self
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return $this;
        }

        // Recalculate working minutes
        $workingMinutes = $this->clock_in->diffInMinutes($this->clock_out);
        if ($this->workSchedule && $this->workSchedule->break_duration) {
            $workingMinutes -= $this->workSchedule->break_duration;
        }
        $this->working_minutes = max(0, $workingMinutes);

        // Recalculate late minutes
        $scheduledStart = $this->getScheduledStartDatetime();
        if ($scheduledStart) {
            $tolerance = $this->workSchedule?->late_tolerance ?? 15;
            if ($this->clock_in->gt($scheduledStart->copy()->addMinutes($tolerance))) {
                $this->late_minutes = $scheduledStart->diffInMinutes($this->clock_in);
                $this->clock_in_status = $this->late_minutes > 30 ? 'very_late' : 'late';
                $this->status = 'late';
            } else {
                $this->late_minutes = 0;
                $this->clock_in_status = 'on_time';
                $this->status = 'present';
            }
        }

        // Recalculate early leave / overtime
        $scheduledEnd = $this->getScheduledEndDatetime();
        if ($scheduledEnd) {
            $tolerance = $this->workSchedule?->early_leave_tolerance ?? 15;

            if ($this->clock_out->lt($scheduledEnd->copy()->subMinutes($tolerance))) {
                $this->early_leave_minutes = $this->clock_out->diffInMinutes($scheduledEnd);
                $this->overtime_minutes = 0;
                $this->clock_out_status = 'early';
            } elseif ($this->clock_out->gt($scheduledEnd)) {
                $this->overtime_minutes = $scheduledEnd->diffInMinutes($this->clock_out);
                $this->early_leave_minutes = 0;
                $this->clock_out_status = 'overtime';
            } else {
                $this->early_leave_minutes = 0;
                $this->overtime_minutes = 0;
                $this->clock_out_status = 'on_time';
            }
        }

        $this->save();

        return $this;
    }

    public function hasClockedIn(): bool
    {
        return $this->clock_in !== null;
    }

    public function hasClockedOut(): bool
    {
        return $this->clock_out !== null;
    }

    public function isComplete(): bool
    {
        return $this->hasClockedIn() && $this->hasClockedOut();
    }

    /**
     * Check if this attendance is for an overnight shift
     */
    public function isOvernightShift(): bool
    {
        return $this->workSchedule && $this->workSchedule->is_overnight;
    }
}
