<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    setPermissionsTeamId($this->company->id);
    $this->user->assignRole('employee');
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);
    $this->leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
    $this->leaveBalance = LeaveBalance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => now()->year,
        'entitled_days' => 12,
        'used_days' => 0,
        'pending_days' => 0,
    ]);
    $this->actingAs($this->user);
});

describe('Portal Leave Index', function () {
    test('can view leave requests list', function () {
        LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->get(route('portal.leave.index'));

        $response->assertSuccessful();
        $response->assertViewIs('portal.leave.index');
        $response->assertViewHas('leaveRequests');
        $response->assertViewHas('leaveBalances');
    });
});

describe('Portal Leave Create', function () {
    test('can view leave create form', function () {
        $response = $this->get(route('portal.leave.create'));

        $response->assertSuccessful();
        $response->assertViewIs('portal.leave.create');
        $response->assertViewHas('leaveTypes');
        $response->assertViewHas('leaveBalances');
    });
});

describe('Portal Leave Store', function () {
    test('can submit leave request', function () {
        $startDate = now()->addDays(7)->toDateString();
        $endDate = now()->addDays(9)->toDateString();

        $response = $this->post(route('portal.leave.store'), [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Keperluan keluarga',
        ]);

        $response->assertRedirect(route('portal.leave.index'));
        $response->assertSessionHas('success');

        $leaveRequest = LeaveRequest::where('employee_id', $this->employee->id)->first();
        expect($leaveRequest)->not->toBeNull();
        expect($leaveRequest->leave_type_id)->toBe($this->leaveType->id);
        expect($leaveRequest->start_date->toDateString())->toBe($startDate);
        expect($leaveRequest->end_date->toDateString())->toBe($endDate);
        expect((float) $leaveRequest->total_days)->toBe(3.0);
        expect($leaveRequest->status)->toBe('pending');
    });

    test('updates pending days in leave balance after submission', function () {
        $startDate = now()->addDays(7)->toDateString();
        $endDate = now()->addDays(8)->toDateString();

        $this->post(route('portal.leave.store'), [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Keperluan keluarga',
        ]);

        $this->leaveBalance->refresh();
        expect((float) $this->leaveBalance->pending_days)->toBe(2.0);
    });

    test('rejects submission when balance is insufficient', function () {
        $this->leaveBalance->update(['used_days' => 10, 'pending_days' => 1]);

        $startDate = now()->addDays(7)->toDateString();
        $endDate = now()->addDays(9)->toDateString();

        $response = $this->post(route('portal.leave.store'), [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Keperluan keluarga',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('leave_type_id');

        $this->assertDatabaseCount('leave_requests', 0);
    });

    test('validates required fields', function () {
        $response = $this->post(route('portal.leave.store'), []);

        $response->assertSessionHasErrors(['leave_type_id', 'start_date', 'end_date', 'reason']);
    });
});

describe('Portal Leave Cancel', function () {
    test('can cancel pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 2,
        ]);

        $this->leaveBalance->update(['pending_days' => 2]);

        $response = $this->post(route('portal.leave.cancel', $leaveRequest));

        $response->assertRedirect(route('portal.leave.index'));
        $response->assertSessionHas('success');

        $leaveRequest->refresh();
        expect($leaveRequest->status)->toBe('cancelled');

        $this->leaveBalance->refresh();
        expect((float) $this->leaveBalance->pending_days)->toBe(0.0);
    });

    test('cannot cancel other employee leave request', function () {
        $otherEmployee = Employee::factory()->create(['company_id' => $this->company->id]);
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->post(route('portal.leave.cancel', $leaveRequest));

        $response->assertForbidden();
    });

    test('cannot cancel non-pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->rejected()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->post(route('portal.leave.cancel', $leaveRequest));

        $response->assertRedirect(route('portal.leave.index'));
        $response->assertSessionHas('error');
    });
});
