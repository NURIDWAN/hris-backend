<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Manager user (approver)
    $this->managerUser = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->manager = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->managerUser->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);

    // Regular employee
    $this->employeeUser = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->employeeUser->id,
        'work_schedule_id' => $this->workSchedule->id,
        'manager_id' => $this->manager->id,
    ]);
});

describe('GET /api/v1/approvals/pending', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/approvals/pending');

        $response->assertUnauthorized();
    });

    it('returns pending leave requests for manager', function () {
        Sanctum::actingAs($this->managerUser);

        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        LeaveRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/approvals/pending');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'leave_requests',
                    'overtime_requests',
                    'reimbursements',
                    'total_pending',
                ],
            ]);

        expect($response->json('data.total_pending'))->toBeGreaterThanOrEqual(2);
    });

    it('returns pending overtime requests for manager', function () {
        Sanctum::actingAs($this->managerUser);

        for ($i = 1; $i <= 3; $i++) {
            OvertimeRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => now()->addDays($i)->toDateString(),
                'status' => 'pending',
            ]);
        }

        $response = $this->getJson('/api/v1/approvals/pending');

        $response->assertOk();
        expect(count($response->json('data.overtime_requests')))->toBe(3);
    });

    it('returns pending reimbursements for manager', function () {
        Sanctum::actingAs($this->managerUser);

        Reimbursement::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/approvals/pending');

        $response->assertOk();
        expect(count($response->json('data.reimbursements')))->toBe(2);
    });
});

describe('POST /api/v1/approvals/leave/{id}/approve', function () {
    it('requires authentication', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $leave = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/leave/{$leave->id}/approve");

        $response->assertUnauthorized();
    });

    it('allows manager to approve leave request', function () {
        Sanctum::actingAs($this->managerUser);

        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $leave = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/leave/{$leave->id}/approve", [
            'notes' => 'Approved by manager',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Leave request approved successfully',
            ]);

        expect($leave->fresh()->status)->toBe('approved');
    });
});

describe('POST /api/v1/approvals/leave/{id}/reject', function () {
    it('allows manager to reject leave request', function () {
        Sanctum::actingAs($this->managerUser);

        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $leave = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/leave/{$leave->id}/reject", [
            'notes' => 'Insufficient leave balance',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Leave request rejected successfully',
            ]);

        expect($leave->fresh()->status)->toBe('rejected');
    });

    it('requires rejection notes', function () {
        Sanctum::actingAs($this->managerUser);

        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $leave = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/leave/{$leave->id}/reject");

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/approvals/overtime/{id}/approve', function () {
    it('allows manager to approve overtime request', function () {
        Sanctum::actingAs($this->managerUser);

        $overtime = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => now()->addDay()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/overtime/{$overtime->id}/approve");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Overtime request approved successfully',
            ]);

        expect($overtime->fresh()->status)->toBe('approved');
    });
});

describe('POST /api/v1/approvals/overtime/{id}/reject', function () {
    it('allows manager to reject overtime request', function () {
        Sanctum::actingAs($this->managerUser);

        $overtime = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(2)->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/overtime/{$overtime->id}/reject", [
            'notes' => 'Not required',
        ]);

        $response->assertOk();
        expect($overtime->fresh()->status)->toBe('rejected');
    });
});

describe('POST /api/v1/approvals/reimbursement/{id}/approve', function () {
    it('allows manager to approve reimbursement', function () {
        Sanctum::actingAs($this->managerUser);

        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/reimbursement/{$reimbursement->id}/approve");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reimbursement approved successfully',
            ]);

        expect($reimbursement->fresh()->status)->toBe('approved');
    });
});

describe('POST /api/v1/approvals/reimbursement/{id}/reject', function () {
    it('allows manager to reject reimbursement', function () {
        Sanctum::actingAs($this->managerUser);

        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/approvals/reimbursement/{$reimbursement->id}/reject", [
            'notes' => 'Invalid receipt',
        ]);

        $response->assertOk();
        expect($reimbursement->fresh()->status)->toBe('rejected');
    });
});

describe('GET /api/v1/approvals/history', function () {
    it('returns approval history for manager', function () {
        Sanctum::actingAs($this->managerUser);

        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        // Approved request
        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
        ]);

        // Rejected request
        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'rejected',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/approvals/history');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'employee_name',
                        'status',
                        'approved_at',
                    ],
                ],
            ]);
    });
});
