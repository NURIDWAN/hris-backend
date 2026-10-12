<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkSchedule;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);

    $this->morningShift = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Shift Pagi',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'working_days' => [1, 2, 3, 4, 5],
    ]);

    $this->nightShift = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Shift Malam',
        'start_time' => '22:00:00',
        'end_time' => '06:00:00',
        'is_overnight' => true,
        'working_days' => [1, 2, 3, 4, 5],
    ]);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->morningShift->id,
    ]);
});

describe('GET /api/v1/schedule', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/schedule');

        $response->assertUnauthorized();
    });

    it('returns monthly schedule with default schedule', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/schedule?month=2026-04');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'month',
                    'month_label',
                    'prev_month',
                    'next_month',
                    'days',
                ],
            ])
            ->assertJsonPath('data.month', '2026-04')
            ->assertJsonPath('data.prev_month', '2026-03')
            ->assertJsonPath('data.next_month', '2026-05');

        $days = $response->json('data.days');
        expect($days)->toHaveCount(30); // April has 30 days

        // Check a Wednesday (working day, ISO day 3)
        $wednesday = collect($days)->firstWhere('date', '2026-04-01'); // April 1 2026 = Wednesday
        expect($wednesday['is_working_day'])->toBeTrue();
        expect($wednesday['day_name'])->toBe('Rabu');
        expect($wednesday['schedule']['name'])->toBe('Shift Pagi');
        expect($wednesday['schedule']['start_time'])->toBe('08:00');
        expect($wednesday['schedule']['end_time'])->toBe('17:00');

        // Check a Sunday (non-working day, ISO day 7)
        $sunday = collect($days)->firstWhere('date', '2026-04-05'); // April 5 2026 = Sunday
        expect($sunday['is_working_day'])->toBeFalse();
        expect($sunday['schedule'])->toBeNull();
    });

    it('returns weekly schedule pattern when assigned', function () {
        // Assign different shifts per day
        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'day_of_week' => 1, // Monday
            'work_schedule_id' => $this->morningShift->id,
        ]);
        EmployeeWeeklySchedule::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'day_of_week' => 2, // Tuesday
            'work_schedule_id' => $this->nightShift->id,
        ]);
        // Wednesday (3) not assigned = day off

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/schedule?month=2026-04');

        $response->assertOk();

        $days = $response->json('data.days');

        // Monday April 6 = morning shift
        $monday = collect($days)->firstWhere('date', '2026-04-06');
        expect($monday['is_working_day'])->toBeTrue();
        expect($monday['schedule']['name'])->toBe('Shift Pagi');

        // Tuesday April 7 = night shift
        $tuesday = collect($days)->firstWhere('date', '2026-04-07');
        expect($tuesday['is_working_day'])->toBeTrue();
        expect($tuesday['schedule']['name'])->toBe('Shift Malam');
        expect($tuesday['schedule']['is_overnight'])->toBeTrue();

        // Wednesday April 8 = day off (not in weekly pattern)
        $wednesday = collect($days)->firstWhere('date', '2026-04-08');
        expect($wednesday['is_working_day'])->toBeFalse();
        expect($wednesday['schedule'])->toBeNull();
    });

    it('marks holidays correctly', function () {
        Holiday::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hari Kemerdekaan',
            'date' => '2026-04-10',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/schedule?month=2026-04');

        $response->assertOk();

        $holiday = collect($response->json('data.days'))->firstWhere('date', '2026-04-10');
        expect($holiday['is_holiday'])->toBeTrue();
        expect($holiday['holiday_name'])->toBe('Hari Kemerdekaan');
        expect($holiday['is_working_day'])->toBeFalse();
    });

    it('uses current month when no month parameter provided', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/schedule');

        $response->assertOk();
        $expectedMonth = now('Asia/Jakarta')->format('Y-m');
        expect($response->json('data.month'))->toBe($expectedMonth);
    });

    it('returns 404 when user has no employee record', function () {
        $userWithoutEmployee = User::factory()->create(['company_id' => $this->company->id]);

        Sanctum::actingAs($userWithoutEmployee);

        $response = $this->getJson('/api/v1/schedule');

        $response->assertNotFound();
    });

    it('does not expose other tenant schedules', function () {
        $otherCompany = Company::factory()->create();
        WorkSchedule::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Shift',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/schedule?month=2026-04');

        $response->assertOk();

        $allScheduleNames = collect($response->json('data.days'))
            ->pluck('schedule.name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        expect($allScheduleNames)->not->toContain('Other Shift');
    });

    it('handles overnight shift data correctly', function () {
        $this->employee->update(['work_schedule_id' => $this->nightShift->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/schedule?month=2026-04');

        $response->assertOk();

        // Check a working day
        $workingDay = collect($response->json('data.days'))->firstWhere('date', '2026-04-01');
        expect($workingDay['schedule']['is_overnight'])->toBeTrue();
        expect($workingDay['schedule']['start_time'])->toBe('22:00');
        expect($workingDay['schedule']['end_time'])->toBe('06:00');
    });
});
