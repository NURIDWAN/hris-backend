<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'start_time' => '08:00',
        'end_time' => '17:00',
        'late_tolerance' => 15,
    ]);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);
});

describe('AttendanceController', function () {
    describe('index', function () {
        it('displays list of attendances', function () {
            Attendance::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            $response = $this->get(route('attendances.index'));

            $response->assertOk();
            $response->assertViewIs('attendances.index');
            $response->assertViewHas('attendances');
        });

        it('only shows attendances belonging to current company', function () {
            $myAttendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);
            $otherAttendance = Attendance::factory()->create([
                'company_id' => $otherCompany->id,
                'employee_id' => $otherEmployee->id,
            ]);

            $response = $this->get(route('attendances.index'));

            $response->assertOk();
            $response->assertSee($this->employee->full_name);
        });

        it('can filter by date', function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $today,
            ]);

            $response = $this->get(route('attendances.index', ['date' => $today->format('Y-m-d')]));

            $response->assertOk();
        });

        it('can filter by employee', function () {
            $employee2 = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee2->id,
            ]);

            $response = $this->get(route('attendances.index', ['employee_id' => $this->employee->id]));

            $response->assertOk();
        });

        it('can filter by status', function () {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'present',
            ]);

            $response = $this->get(route('attendances.index', ['status' => 'present']));

            $response->assertOk();
        });
    });

    describe('create', function () {
        it('displays create attendance form (manual entry)', function () {
            $response = $this->get(route('attendances.create'));

            $response->assertOk();
            $response->assertViewIs('attendances.create');
            $response->assertViewHas('employees');
        });
    });

    describe('store', function () {
        it('creates a manual attendance entry', function () {
            $data = [
                'employee_id' => $this->employee->id,
                'date' => Carbon::today()->format('Y-m-d'),
                'clock_in' => '08:00',
                'clock_out' => '17:00',
                'status' => 'present',
                'admin_notes' => 'Manual entry',
            ];

            $response = $this->post(route('attendances.store'), $data);

            $response->assertRedirect(route('attendances.index'));
            $this->assertDatabaseHas('attendances', [
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'present',
                'is_manual_entry' => true,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->post(route('attendances.store'), []);

            $response->assertSessionHasErrors(['employee_id', 'date']);
        });

        it('prevents duplicate attendance for same employee and date', function () {
            $date = Carbon::today();

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $date,
            ]);

            $response = $this->post(route('attendances.store'), [
                'employee_id' => $this->employee->id,
                'date' => $date->format('Y-m-d'),
                'status' => 'present',
            ]);

            $response->assertSessionHasErrors(['date']);
        });
    });

    describe('edit', function () {
        it('displays edit attendance form', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            $response = $this->get(route('attendances.edit', $attendance));

            $response->assertOk();
            $response->assertViewIs('attendances.edit');
            $response->assertViewHas('attendance');
        });

        it('returns 404 for attendance from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);
            $attendance = Attendance::factory()->create([
                'company_id' => $otherCompany->id,
                'employee_id' => $otherEmployee->id,
            ]);

            $response = $this->get(route('attendances.edit', $attendance));

            $response->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates attendance', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'present',
            ]);

            $response = $this->put(route('attendances.update', $attendance), [
                'employee_id' => $this->employee->id,
                'date' => $attendance->date->format('Y-m-d'),
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'status' => 'late',
                'admin_notes' => 'Updated by admin',
            ]);

            $response->assertRedirect(route('attendances.index'));
            $this->assertDatabaseHas('attendances', [
                'id' => $attendance->id,
                'status' => 'late',
            ]);
        });

        it('cannot update attendance from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);
            $attendance = Attendance::factory()->create([
                'company_id' => $otherCompany->id,
                'employee_id' => $otherEmployee->id,
            ]);

            $response = $this->put(route('attendances.update', $attendance), [
                'employee_id' => $otherEmployee->id,
                'date' => $attendance->date->format('Y-m-d'),
                'status' => 'present',
            ]);

            $response->assertNotFound();
        });
    });

    describe('destroy', function () {
        it('deletes attendance', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            $response = $this->delete(route('attendances.destroy', $attendance));

            $response->assertRedirect(route('attendances.index'));
            $this->assertSoftDeleted('attendances', ['id' => $attendance->id]);
        });

        it('cannot delete attendance from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);
            $attendance = Attendance::factory()->create([
                'company_id' => $otherCompany->id,
                'employee_id' => $otherEmployee->id,
            ]);

            $response = $this->delete(route('attendances.destroy', $attendance));

            $response->assertNotFound();
        });
    });

    describe('show', function () {
        it('displays attendance details', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            $response = $this->get(route('attendances.show', $attendance));

            $response->assertOk();
            $response->assertViewIs('attendances.show');
            $response->assertViewHas('attendance');
        });
    });
});

describe('Clock In/Out', function () {
    beforeEach(function () {
        // Create employee user for self clock in/out
        // User needs admin role to access admin routes (attendances.clock-in)
        // Regular employees use portal routes (portal.attendance.clock-in)
        $this->employeeUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        setPermissionsTeamId($this->company->id);
        // Give both admin and employee roles so they can access admin routes
        $this->employeeUser->assignRole(['admin', 'employee']);

        $this->selfEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);
    });

    it('employee can clock in', function () {
        $this->actingAs($this->employeeUser);

        Carbon::setTestNow(Carbon::today()->setTime(8, 5, 0));

        $response = $this->post(route('attendances.clock-in'));

        $response->assertRedirect();

        $attendance = Attendance::where('employee_id', $this->selfEmployee->id)
            ->whereDate('date', Carbon::today())
            ->first();

        expect($attendance)->not->toBeNull();
        expect($attendance->clock_in_status)->toBe('on_time');

        Carbon::setTestNow();
    });

    it('employee cannot clock in twice on same day', function () {
        $this->actingAs($this->employeeUser);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->selfEmployee->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(8, 0, 0),
        ]);

        $response = $this->post(route('attendances.clock-in'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('employee can clock out', function () {
        $this->actingAs($this->employeeUser);

        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->selfEmployee->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(8, 0, 0),
        ]);

        Carbon::setTestNow(Carbon::today()->setTime(17, 5, 0));

        $response = $this->post(route('attendances.clock-out'));

        $response->assertRedirect();
        $attendance->refresh();
        expect($attendance->clock_out)->not->toBeNull();

        Carbon::setTestNow();
    });

    it('employee cannot clock out without clock in', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('attendances.clock-out'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('marks attendance as late when clocking in after tolerance', function () {
        $this->actingAs($this->employeeUser);

        // Set test time in company timezone (Asia/Jakarta)
        Carbon::setTestNow(Carbon::today($this->company->timezone)->setTime(8, 30, 0));

        $response = $this->post(route('attendances.clock-in'));

        $response->assertRedirect();

        $attendance = Attendance::where('employee_id', $this->selfEmployee->id)
            ->whereDate('date', Carbon::today())
            ->first();

        expect($attendance)->not->toBeNull();
        expect($attendance->status)->toBe('late');
        expect($attendance->clock_in_status)->toBe('late');

        Carbon::setTestNow();
    });

    it('marks attendance as very late when clocking in more than 30 minutes late', function () {
        $this->actingAs($this->employeeUser);

        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0)); // 1 hour late

        $response = $this->post(route('attendances.clock-in'));

        $response->assertRedirect();

        $attendance = Attendance::where('employee_id', $this->selfEmployee->id)
            ->whereDate('date', Carbon::today())
            ->first();

        expect($attendance)->not->toBeNull();
        expect($attendance->clock_in_status)->toBe('very_late');

        Carbon::setTestNow();
    });

    it('calculates working minutes correctly', function () {
        $this->actingAs($this->employeeUser);

        // Use company timezone for clock_in time
        $clockIn = Carbon::today($this->company->timezone)->setTime(8, 0, 0);

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->selfEmployee->id,
            'date' => Carbon::today(),
            'clock_in' => $clockIn,
            'clock_out' => null,
            'working_minutes' => 0,
        ]);

        // Set test time in company timezone
        Carbon::setTestNow(Carbon::today($this->company->timezone)->setTime(17, 0, 0)); // 9 hours later

        $response = $this->post(route('attendances.clock-out'));

        $attendance->refresh();
        expect($attendance->working_minutes)->toBe(540); // 9 hours = 540 minutes

        Carbon::setTestNow();
    });
});
