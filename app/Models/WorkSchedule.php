<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'start_time',
        'end_time',
        'is_overnight',
        'break_start',
        'break_end',
        'break_duration',
        'working_hours',
        'working_days',
        'late_tolerance',
        'early_leave_tolerance',
        'is_flexible',
        'is_default',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'break_start' => 'datetime:H:i',
            'break_end' => 'datetime:H:i',
            'working_days' => 'array',
            'is_overnight' => 'boolean',
            'is_flexible' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Auto-detect overnight shift when saving
        static::saving(function (WorkSchedule $schedule) {
            if ($schedule->start_time && $schedule->end_time) {
                $startTime = $schedule->start_time instanceof Carbon
                    ? $schedule->start_time
                    : Carbon::parse($schedule->start_time);
                $endTime = $schedule->end_time instanceof Carbon
                    ? $schedule->end_time
                    : Carbon::parse($schedule->end_time);

                // If end_time is before start_time, it's an overnight shift
                $schedule->is_overnight = $endTime->format('H:i') < $startTime->format('H:i');
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getFormattedWorkingHoursAttribute(): string
    {
        $formatted = $this->start_time->format('H:i').' - '.$this->end_time->format('H:i');
        if ($this->is_overnight) {
            $formatted .= ' (+1)';
        }

        return $formatted;
    }

    public function getWorkingDaysTextAttribute(): string
    {
        $days = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $workingDays = $this->working_days ?? [1, 2, 3, 4, 5];

        return collect($workingDays)->map(fn ($day) => $days[$day] ?? '')->filter()->implode(', ');
    }

    public function isWorkingDay(int $dayOfWeek): bool
    {
        $workingDays = $this->working_days ?? [1, 2, 3, 4, 5];

        return in_array($dayOfWeek, $workingDays);
    }

    /**
     * Get the scheduled start datetime for a given date
     */
    public function getScheduledStart(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->start_time->format('H:i:s'));
    }

    /**
     * Get the scheduled end datetime for a given date, accounting for overnight shifts
     */
    public function getScheduledEnd(Carbon $date): Carbon
    {
        $end = $date->copy()->setTimeFromTimeString($this->end_time->format('H:i:s'));

        // If overnight shift, end time is on the next day
        if ($this->is_overnight) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Get the scheduled break start datetime for a given date
     */
    public function getBreakStart(Carbon $date): ?Carbon
    {
        if (! $this->break_start) {
            return null;
        }

        $breakStart = $date->copy()->setTimeFromTimeString($this->break_start->format('H:i:s'));

        // If overnight shift and break is after midnight
        if ($this->is_overnight && $this->break_start->format('H:i') < $this->start_time->format('H:i')) {
            $breakStart->addDay();
        }

        return $breakStart;
    }

    /**
     * Get the scheduled break end datetime for a given date
     */
    public function getBreakEnd(Carbon $date): ?Carbon
    {
        if (! $this->break_end) {
            return null;
        }

        $breakEnd = $date->copy()->setTimeFromTimeString($this->break_end->format('H:i:s'));

        // If overnight shift and break is after midnight
        if ($this->is_overnight && $this->break_end->format('H:i') < $this->start_time->format('H:i')) {
            $breakEnd->addDay();
        }

        return $breakEnd;
    }

    /**
     * Check if a given datetime is late for clock-in
     */
    public function isLate(\DateTime $clockIn): bool
    {
        $scheduledStart = Carbon::parse($clockIn)->setTimeFromTimeString($this->start_time->format('H:i:s'));
        $tolerance = $scheduledStart->copy()->addMinutes($this->late_tolerance ?? 0);

        return Carbon::parse($clockIn) > $tolerance;
    }

    /**
     * Get late minutes for a given clock-in time
     */
    public function getLateMinutes(\DateTime $clockIn): int
    {
        $clockInCarbon = Carbon::parse($clockIn);
        $scheduledStart = $clockInCarbon->copy()->setTimeFromTimeString($this->start_time->format('H:i:s'));

        if ($clockInCarbon <= $scheduledStart) {
            return 0;
        }

        return $scheduledStart->diffInMinutes($clockInCarbon);
    }

    /**
     * Check if clock-out is early leave
     */
    public function isEarlyLeave(\DateTime $clockOut, Carbon $shiftDate): bool
    {
        $scheduledEnd = $this->getScheduledEnd($shiftDate);
        $tolerance = $scheduledEnd->copy()->subMinutes($this->early_leave_tolerance ?? 0);

        return Carbon::parse($clockOut) < $tolerance;
    }

    /**
     * Get early leave minutes
     */
    public function getEarlyLeaveMinutes(\DateTime $clockOut, Carbon $shiftDate): int
    {
        $clockOutCarbon = Carbon::parse($clockOut);
        $scheduledEnd = $this->getScheduledEnd($shiftDate);

        if ($clockOutCarbon >= $scheduledEnd) {
            return 0;
        }

        return $clockOutCarbon->diffInMinutes($scheduledEnd);
    }

    /**
     * Get overtime minutes (clock-out after scheduled end)
     */
    public function getOvertimeMinutes(\DateTime $clockOut, Carbon $shiftDate): int
    {
        $clockOutCarbon = Carbon::parse($clockOut);
        $scheduledEnd = $this->getScheduledEnd($shiftDate);

        if ($clockOutCarbon <= $scheduledEnd) {
            return 0;
        }

        return $scheduledEnd->diffInMinutes($clockOutCarbon);
    }

    /**
     * Calculate total working minutes between clock-in and clock-out
     */
    public function calculateWorkingMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $totalMinutes = $clockIn->diffInMinutes($clockOut);

        // Subtract break duration if applicable
        if ($this->break_duration) {
            $totalMinutes -= $this->break_duration;
        }

        return max(0, $totalMinutes);
    }
}
