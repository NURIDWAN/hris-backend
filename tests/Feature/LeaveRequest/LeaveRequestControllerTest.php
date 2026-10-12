<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $this->leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('LeaveRequest Index', function () {
    test('can view leave requests list', function () {
        $leaveRequests = LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->get(route('leave-requests.index'));

        $response->assertStatus(200);
        $response->assertViewIs('leave-requests.index');
        $response->assertViewHas('leaveRequests');
    });

    test('only shows leave requests from same company', function () {
        $ownRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);
        $otherRequest = LeaveRequest::factory()->create();

        $response = $this->get(route('leave-requests.index'));

        $response->assertStatus(200);
        $response->assertViewHas('leaveRequests', function ($requests) use ($ownRequest, $otherRequest) {
            return $requests->contains($ownRequest) && !$requests->contains($otherRequest);
        });
    });

    test('can filter by status', function () {
        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
        ]);
        LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->get(route('leave-requests.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertViewHas('leaveRequests', function ($requests) {
            return $requests->count() === 1 && $requests->first()->status === 'pending';
        });
    });

    test('can filter by employee', function () {
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);

        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);
        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee2->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->get(route('leave-requests.index', ['employee_id' => $this->employee->id]));

        $response->assertStatus(200);
        $response->assertViewHas('leaveRequests', function ($requests) {
            return $requests->count() === 1 && $requests->first()->employee_id === $this->employee->id;
        });
    });
});

describe('LeaveRequest Create', function () {
    test('can view create form', function () {
        $response = $this->get(route('leave-requests.create'));

        $response->assertStatus(200);
        $response->assertViewIs('leave-requests.create');
        $response->assertViewHas('employees');
        $response->assertViewHas('leaveTypes');
    });

    test('can create leave request', function () {
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'entitled_days' => 12,
            'used_days' => 0,
            'pending_days' => 0,
        ]);

        $startDate = now()->addDays(7)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $data = [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Keperluan keluarga',
        ];

        $response = $this->post(route('leave-requests.store'), $data);

        $response->assertRedirect(route('leave-requests.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_requests', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ]);
    });

    test('employee_id is required', function () {
        $data = [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(8)->format('Y-m-d'),
            'reason' => 'Keperluan keluarga',
        ];

        $response = $this->post(route('leave-requests.store'), $data);

        $response->assertSessionHasErrors('employee_id');
    });

    test('end_date must be after or equal to start_date', function () {
        $data = [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'reason' => 'Keperluan keluarga',
        ];

        $response = $this->post(route('leave-requests.store'), $data);

        $response->assertSessionHasErrors('end_date');
    });

    test('cannot request leave without sufficient balance', function () {
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'entitled_days' => 5,
            'used_days' => 4,
            'pending_days' => 0,
        ]);

        $data = [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(14)->format('Y-m-d'), // 8 days
            'reason' => 'Keperluan keluarga',
        ];

        $response = $this->post(route('leave-requests.store'), $data);

        $response->assertSessionHasErrors('leave_type_id');
    });

    test('can create half day leave request', function () {
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'entitled_days' => 12,
        ]);

        $date = now()->addDays(7)->format('Y-m-d');

        $data = [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $date,
            'end_date' => $date,
            'is_half_day' => true,
            'half_day_type' => 'morning',
            'reason' => 'Keperluan dokter pagi',
        ];

        $response = $this->post(route('leave-requests.store'), $data);

        $response->assertRedirect(route('leave-requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee->id,
            'is_half_day' => true,
            'half_day_type' => 'morning',
            'total_days' => 0.5,
        ]);
    });
});

describe('LeaveRequest Show', function () {
    test('can view leave request details', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->get(route('leave-requests.show', $leaveRequest));

        $response->assertStatus(200);
        $response->assertViewIs('leave-requests.show');
        $response->assertViewHas('leaveRequest');
    });

    test('cannot view leave request from other company', function () {
        $otherRequest = LeaveRequest::factory()->create();

        $response = $this->get(route('leave-requests.show', $otherRequest));

        $response->assertStatus(404);
    });
});

describe('LeaveRequest Approval', function () {
    test('can approve pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 2,
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'entitled_days' => 12,
            'pending_days' => 2,
        ]);

        $response = $this->post(route('leave-requests.approve', $leaveRequest), [
            'notes' => 'Disetujui untuk cuti',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    });

    test('cannot approve already approved request', function () {
        $leaveRequest = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->post(route('leave-requests.approve', $leaveRequest));

        $response->assertSessionHas('error');
    });

    test('cannot approve leave request from other company', function () {
        $otherRequest = LeaveRequest::factory()->pending()->create();

        $response = $this->post(route('leave-requests.approve', $otherRequest));

        $response->assertStatus(404);
    });
});

describe('LeaveRequest Rejection', function () {
    test('can reject pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 2,
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'entitled_days' => 12,
            'pending_days' => 2,
        ]);

        $response = $this->post(route('leave-requests.reject', $leaveRequest), [
            'reason' => 'Jadwal proyek padat',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'rejected',
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Jadwal proyek padat',
        ]);
    });

    test('rejection reason is required', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->post(route('leave-requests.reject', $leaveRequest), [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
    });
});

describe('LeaveRequest Cancellation', function () {
    test('can cancel pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 2,
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'pending_days' => 2,
        ]);

        $response = $this->post(route('leave-requests.cancel', $leaveRequest), [
            'reason' => 'Rencana berubah',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->user->id,
        ]);
    });

    test('can cancel approved leave request before start date', function () {
        $leaveRequest = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'total_days' => 3,
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'used_days' => 3,
        ]);

        $response = $this->post(route('leave-requests.cancel', $leaveRequest), [
            'reason' => 'Ada kebutuhan mendadak',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });

    test('cannot cancel leave request that already started', function () {
        $leaveRequest = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(2),
        ]);

        $response = $this->post(route('leave-requests.cancel', $leaveRequest), [
            'reason' => 'Ingin membatalkan',
        ]);

        $response->assertSessionHas('error');
    });
});

describe('LeaveRequest Delete', function () {
    test('can delete pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 2,
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'pending_days' => 2,
        ]);

        $response = $this->delete(route('leave-requests.destroy', $leaveRequest));

        $response->assertRedirect(route('leave-requests.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('leave_requests', ['id' => $leaveRequest->id]);
    });

    test('cannot delete leave request from other company', function () {
        $otherRequest = LeaveRequest::factory()->create();

        $response = $this->delete(route('leave-requests.destroy', $otherRequest));

        $response->assertStatus(404);
    });
});
