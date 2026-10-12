<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $this->leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
});

describe('LeaveRequest Model', function () {
    test('can create leave request', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest)->toBeInstanceOf(LeaveRequest::class);
        expect($leaveRequest->company_id)->toBe($this->company->id);
    });

    test('generates request number automatically', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'request_number' => null,
        ]);

        expect($leaveRequest->request_number)->toStartWith('LV');
        expect($leaveRequest->request_number)->toContain(date('Ym'));
    });

    test('belongs to company', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->company)->toBeInstanceOf(Company::class);
    });

    test('belongs to employee', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->employee)->toBeInstanceOf(Employee::class);
    });

    test('belongs to leave type', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->leaveType)->toBeInstanceOf(LeaveType::class);
    });
});

describe('LeaveRequest Status Checks', function () {
    test('isPending returns true for pending status', function () {
        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->isPending())->toBeTrue();
        expect($leaveRequest->isApproved())->toBeFalse();
    });

    test('isApproved returns true for approved status', function () {
        $leaveRequest = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->isApproved())->toBeTrue();
        expect($leaveRequest->isPending())->toBeFalse();
    });

    test('isRejected returns true for rejected status', function () {
        $leaveRequest = LeaveRequest::factory()->rejected()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->isRejected())->toBeTrue();
    });

    test('isCancelled returns true for cancelled status', function () {
        $leaveRequest = LeaveRequest::factory()->cancelled()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($leaveRequest->isCancelled())->toBeTrue();
    });
});

describe('LeaveRequest Can Actions', function () {
    test('canBeApproved is true only for pending requests', function () {
        $pending = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $approved = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($pending->canBeApproved())->toBeTrue();
        expect($approved->canBeApproved())->toBeFalse();
    });

    test('canBeCancelled is true for pending or approved before start', function () {
        $pending = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(5),
        ]);

        $approvedFuture = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(5),
        ]);

        $approvedPast = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->subDay(),
        ]);

        expect($pending->canBeCancelled())->toBeTrue();
        expect($approvedFuture->canBeCancelled())->toBeTrue();
        expect($approvedPast->canBeCancelled())->toBeFalse();
    });
});

describe('LeaveRequest Actions', function () {
    test('approve updates status and balance', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 3,
            'start_date' => now()->addDays(5),
        ]);

        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'entitled_days' => 12,
            'used_days' => 0,
            'pending_days' => 3,
        ]);

        $leaveRequest->approve($user->id, 'Approved');

        expect($leaveRequest->fresh()->status)->toBe('approved');
        expect($leaveRequest->fresh()->approved_by)->toBe($user->id);
        expect($balance->fresh()->used_days)->toBe('3.0');
        expect($balance->fresh()->pending_days)->toBe('0.0');
    });

    test('reject updates status and restores balance', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $leaveRequest = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 3,
            'start_date' => now()->addDays(5),
        ]);

        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'pending_days' => 3,
        ]);

        $leaveRequest->reject($user->id, 'Not enough staff');

        expect($leaveRequest->fresh()->status)->toBe('rejected');
        expect($leaveRequest->fresh()->rejected_by)->toBe($user->id);
        expect($leaveRequest->fresh()->rejection_reason)->toBe('Not enough staff');
        expect($balance->fresh()->pending_days)->toBe('0.0');
    });

    test('cancel restores used days if was approved', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $leaveRequest = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 3,
            'start_date' => now()->addDays(5),
        ]);

        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => $leaveRequest->start_date->year,
            'used_days' => 3,
        ]);

        $leaveRequest->cancel($user->id, 'Plans changed');

        expect($leaveRequest->fresh()->status)->toBe('cancelled');
        expect($balance->fresh()->used_days)->toBe('0.0');
    });
});

describe('LeaveRequest Scopes', function () {
    test('pending scope filters pending requests', function () {
        LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);
        LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $pending = LeaveRequest::where('company_id', $this->company->id)->pending()->get();

        expect($pending)->toHaveCount(1);
        expect($pending->first()->status)->toBe('pending');
    });

    test('approved scope filters approved requests', function () {
        LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);
        LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $approved = LeaveRequest::where('company_id', $this->company->id)->approved()->get();

        expect($approved)->toHaveCount(1);
        expect($approved->first()->status)->toBe('approved');
    });

    test('forPeriod scope filters by date range', function () {
        // Request inside period
        LeaveRequest::factory()->forDates('2026-03-10', '2026-03-15')->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        // Request outside period
        LeaveRequest::factory()->forDates('2026-04-01', '2026-04-05')->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $requests = LeaveRequest::where('company_id', $this->company->id)
            ->forPeriod('2026-03-01', '2026-03-31')
            ->get();

        expect($requests)->toHaveCount(1);
    });
});

describe('LeaveRequest Accessors', function () {
    test('status_label returns correct Indonesian label', function () {
        $pending = LeaveRequest::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $approved = LeaveRequest::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($pending->status_label)->toBe('Menunggu');
        expect($approved->status_label)->toBe('Disetujui');
    });

    test('date_range returns formatted date range', function () {
        $singleDay = LeaveRequest::factory()->singleDay('2026-03-15')->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $multiDay = LeaveRequest::factory()->forDates('2026-03-15', '2026-03-18')->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($singleDay->date_range)->toBe('15 Mar 2026');
        expect($multiDay->date_range)->toBe('15 Mar 2026 - 18 Mar 2026');
    });

    test('duration_text returns correct format for half day', function () {
        $halfDay = LeaveRequest::factory()->halfDay('2026-03-15', 'morning')->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($halfDay->duration_text)->toBe('0.5 hari (Pagi)');
    });

    test('duration_text returns correct format for full days', function () {
        $fullDays = LeaveRequest::factory()->forDates('2026-03-15', '2026-03-17')->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'total_days' => 3,
        ]);

        expect($fullDays->duration_text)->toBe('3 hari');
    });
});
