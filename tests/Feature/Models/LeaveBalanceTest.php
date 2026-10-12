<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $this->leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
});

describe('LeaveBalance Model', function () {
    test('can create leave balance', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
            'entitled_days' => 12,
        ]);

        expect($balance)->toBeInstanceOf(LeaveBalance::class);
        expect($balance->entitled_days)->toBe('12.0');
    });

    test('belongs to company', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->company)->toBeInstanceOf(Company::class);
    });

    test('belongs to employee', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->employee)->toBeInstanceOf(Employee::class);
    });

    test('belongs to leave type', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->leaveType)->toBeInstanceOf(LeaveType::class);
    });
});

describe('LeaveBalance Calculations', function () {
    test('total_entitled includes entitled, carried forward and adjustment', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'entitled_days' => 12,
            'carried_forward_days' => 3,
            'adjustment_days' => 2,
        ]);

        expect($balance->total_entitled)->toBe(17.0);
    });

    test('remaining_days subtracts used and pending from total', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'entitled_days' => 12,
            'carried_forward_days' => 0,
            'adjustment_days' => 0,
            'used_days' => 5,
            'pending_days' => 2,
        ]);

        expect($balance->remaining_days)->toBe(5.0);
    });

    test('available_days subtracts only used from total', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'entitled_days' => 12,
            'used_days' => 5,
            'pending_days' => 2,
        ]);

        expect($balance->available_days)->toBe(7.0);
    });

    test('hasEnoughBalance checks remaining days', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'entitled_days' => 12,
            'used_days' => 5,
            'pending_days' => 2,
        ]);

        expect($balance->hasEnoughBalance(5))->toBeTrue();
        expect($balance->hasEnoughBalance(6))->toBeFalse();
    });
});

describe('LeaveBalance Operations', function () {
    test('deductDays increases used_days', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'used_days' => 2,
        ]);

        $balance->deductDays(3);

        expect($balance->fresh()->used_days)->toBe('5.0');
    });

    test('addPendingDays increases pending_days', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'pending_days' => 1,
        ]);

        $balance->addPendingDays(2);

        expect($balance->fresh()->pending_days)->toBe('3.0');
    });

    test('removePendingDays decreases pending_days', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'pending_days' => 5,
        ]);

        $balance->removePendingDays(3);

        expect($balance->fresh()->pending_days)->toBe('2.0');
    });

    test('removePendingDays does not go below zero', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'pending_days' => 2,
        ]);

        $balance->removePendingDays(5);

        expect($balance->fresh()->pending_days)->toBe('0.0');
    });

    test('convertPendingToUsed moves days from pending to used', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'pending_days' => 5,
            'used_days' => 2,
        ]);

        $balance->convertPendingToUsed(3);

        expect($balance->fresh()->pending_days)->toBe('2.0');
        expect($balance->fresh()->used_days)->toBe('5.0');
    });

    test('restoreDays decreases used_days', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'used_days' => 5,
        ]);

        $balance->restoreDays(3);

        expect($balance->fresh()->used_days)->toBe('2.0');
    });

    test('restoreDays does not go below zero', function () {
        $balance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'used_days' => 2,
        ]);

        $balance->restoreDays(5);

        expect($balance->fresh()->used_days)->toBe('0.0');
    });
});

describe('LeaveBalance Scopes', function () {
    test('forYear scope filters by year', function () {
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2025,
        ]);
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
        ]);

        $balances = LeaveBalance::where('company_id', $this->company->id)->forYear(2026)->get();

        expect($balances)->toHaveCount(1);
        expect($balances->first()->year)->toBe(2026);
    });

    test('currentYear scope filters by current year', function () {
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
        ]);
        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year - 1,
        ]);

        $balances = LeaveBalance::where('company_id', $this->company->id)->currentYear()->get();

        expect($balances)->toHaveCount(1);
        expect($balances->first()->year)->toBe(now()->year);
    });
});

describe('LeaveBalance Factory States', function () {
    test('withUsedDays state sets used days', function () {
        $balance = LeaveBalance::factory()->withUsedDays(5)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->used_days)->toBe('5.0');
    });

    test('withPendingDays state sets pending days', function () {
        $balance = LeaveBalance::factory()->withPendingDays(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->pending_days)->toBe('3.0');
    });

    test('fullyUsed state uses all entitled days', function () {
        $balance = LeaveBalance::factory()->withEntitlement(10)->fullyUsed()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->used_days)->toBe('10.0');
        expect($balance->remaining_days)->toBe(0.0);
    });

    test('partiallyUsed state sets partial usage', function () {
        $balance = LeaveBalance::factory()->partiallyUsed()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        expect($balance->entitled_days)->toBe('12.0');
        expect($balance->used_days)->toBe('5.0');
        expect($balance->pending_days)->toBe('2.0');
    });
});
