<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    use GeneratesRandomData;

    public function definition(): array
    {
        $date = $this->randomDateBetween('-1 month', 'now');
        $clockIn = Carbon::parse($date)->setTime(8, rand(0, 30), 0);
        $clockOut = $clockIn->copy()->addHours(8)->addMinutes(rand(0, 60));

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'work_schedule_id' => null,
            'date' => $date,
            'scheduled_start' => '08:00',
            'scheduled_end' => '17:00',
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_ip' => rand(1, 254).'.'.rand(1, 254).'.'.rand(1, 254).'.'.rand(1, 254),
            'clock_out_ip' => rand(1, 254).'.'.rand(1, 254).'.'.rand(1, 254).'.'.rand(1, 254),
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'working_minutes' => $clockIn->diffInMinutes($clockOut),
            'status' => 'present',
            'clock_in_status' => 'on_time',
            'clock_out_status' => 'on_time',
            'is_manual_entry' => false,
        ];
    }

    public function forDate(Carbon|string $date): static
    {
        $date = Carbon::parse($date);

        return $this->state(fn (array $attributes) => [
            'date' => $date,
            'clock_in' => $date->copy()->setTime(8, rand(0, 15), 0),
            'clock_out' => $date->copy()->setTime(17, rand(0, 30), 0),
        ]);
    }

    public function late(int $minutes = 30): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $date = Carbon::parse($attributes['date']);
            $clockIn = $date->copy()->setTime(8, 0, 0)->addMinutes($minutes);

            return [
                'clock_in' => $clockIn,
                'late_minutes' => $minutes,
                'status' => 'late',
                'clock_in_status' => $minutes > 30 ? 'very_late' : 'late',
            ];
        });
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_in' => null,
            'clock_out' => null,
            'status' => 'absent',
            'clock_in_status' => null,
            'clock_out_status' => null,
            'working_minutes' => 0,
        ]);
    }

    public function halfDay(): static
    {
        return $this->state(function (array $attributes) {
            $date = Carbon::parse($attributes['date']);

            return [
                'clock_in' => $date->copy()->setTime(8, 0, 0),
                'clock_out' => $date->copy()->setTime(12, 0, 0),
                'status' => 'half_day',
                'working_minutes' => 240, // 4 hours
                'early_leave_minutes' => 300, // 5 hours early
                'clock_out_status' => 'early',
            ];
        });
    }

    public function withOvertime(int $minutes = 60): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $date = Carbon::parse($attributes['date']);
            $clockOut = $date->copy()->setTime(17, 0, 0)->addMinutes($minutes);

            return [
                'clock_out' => $clockOut,
                'overtime_minutes' => $minutes,
                'clock_out_status' => 'overtime',
                'working_minutes' => 480 + $minutes, // 8 hours + overtime
            ];
        });
    }

    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_in' => null,
            'clock_out' => null,
            'status' => 'leave',
            'clock_in_status' => null,
            'clock_out_status' => null,
            'working_minutes' => 0,
        ]);
    }

    public function manualEntry(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_manual_entry' => true,
        ]);
    }

    public function atOffice(OfficeLocation $officeLocation): static
    {
        return $this->state(fn (array $attributes) => [
            'office_location_id' => $officeLocation->id,
        ]);
    }

    public function clockedInOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_out' => null,
            'clock_out_status' => null,
            'working_minutes' => 0,
        ]);
    }
}
