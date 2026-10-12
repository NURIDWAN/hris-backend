<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

describe('LeaveType Model', function () {
    test('can create leave type', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
            'code' => 'CT',
            'default_days' => 12,
        ]);

        expect($leaveType)->toBeInstanceOf(LeaveType::class);
        expect($leaveType->name)->toBe('Cuti Tahunan');
        expect($leaveType->code)->toBe('CT');
        expect($leaveType->default_days)->toBe(12);
    });

    test('belongs to company', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        expect($leaveType->company)->toBeInstanceOf(Company::class);
        expect($leaveType->company->id)->toBe($this->company->id);
    });

    test('has many leave balances', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        expect($leaveType->leaveBalances)->toHaveCount(1);
    });

    test('has many leave requests', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        expect($leaveType->leaveRequests)->toHaveCount(1);
    });
});

describe('LeaveType Scopes', function () {
    test('active scope returns only active leave types', function () {
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_active' => false]);

        $activeTypes = LeaveType::where('company_id', $this->company->id)->active()->get();

        expect($activeTypes)->toHaveCount(1);
        expect($activeTypes->first()->is_active)->toBeTrue();
    });

    test('paid scope returns only paid leave types', function () {
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_paid' => true]);
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_paid' => false]);

        $paidTypes = LeaveType::where('company_id', $this->company->id)->paid()->get();

        expect($paidTypes)->toHaveCount(1);
        expect($paidTypes->first()->is_paid)->toBeTrue();
    });

    test('unpaid scope returns only unpaid leave types', function () {
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_paid' => true]);
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_paid' => false]);

        $unpaidTypes = LeaveType::where('company_id', $this->company->id)->unpaid()->get();

        expect($unpaidTypes)->toHaveCount(1);
        expect($unpaidTypes->first()->is_paid)->toBeFalse();
    });
});

describe('LeaveType Accessors', function () {
    test('paid label returns correct text for paid leave', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'is_paid' => true,
        ]);

        expect($leaveType->paid_label)->toBe('Berbayar');
    });

    test('paid label returns correct text for unpaid leave', function () {
        $leaveType = LeaveType::factory()->unpaid()->create([
            'company_id' => $this->company->id,
        ]);

        expect($leaveType->paid_label)->toBe('Tidak Berbayar');
    });

    test('color class returns color or default', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'color' => 'danger',
        ]);

        expect($leaveType->color_class)->toBe('danger');

        $leaveTypeNoColor = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'color' => null,
        ]);

        expect($leaveTypeNoColor->color_class)->toBe('primary');
    });
});

describe('LeaveType Factory States', function () {
    test('annual state creates annual leave type', function () {
        $leaveType = LeaveType::factory()->annual()->create(['company_id' => $this->company->id]);

        expect($leaveType->name)->toBe('Cuti Tahunan');
        expect($leaveType->code)->toBe('CT');
        expect($leaveType->default_days)->toBe(12);
        expect($leaveType->is_carry_forward)->toBeTrue();
        expect($leaveType->max_carry_forward_days)->toBe(6);
    });

    test('sick state creates sick leave type', function () {
        $leaveType = LeaveType::factory()->sick()->create(['company_id' => $this->company->id]);

        expect($leaveType->name)->toBe('Cuti Sakit');
        expect($leaveType->code)->toBe('CS');
        expect($leaveType->requires_attachment)->toBeTrue();
    });

    test('maternity state creates maternity leave type', function () {
        $leaveType = LeaveType::factory()->maternity()->create(['company_id' => $this->company->id]);

        expect($leaveType->name)->toBe('Cuti Melahirkan');
        expect($leaveType->default_days)->toBe(90);
    });

    test('unpaid state creates unpaid leave type', function () {
        $leaveType = LeaveType::factory()->unpaid()->create(['company_id' => $this->company->id]);

        expect($leaveType->is_paid)->toBeFalse();
    });

    test('inactive state creates inactive leave type', function () {
        $leaveType = LeaveType::factory()->inactive()->create(['company_id' => $this->company->id]);

        expect($leaveType->is_active)->toBeFalse();
    });
});

describe('LeaveType Casts', function () {
    test('boolean fields are cast correctly', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'is_paid' => 1,
            'requires_approval' => 1,
            'requires_attachment' => 0,
            'is_carry_forward' => 1,
            'is_active' => 1,
        ]);

        expect($leaveType->is_paid)->toBeTrue();
        expect($leaveType->requires_approval)->toBeTrue();
        expect($leaveType->requires_attachment)->toBeFalse();
        expect($leaveType->is_carry_forward)->toBeTrue();
        expect($leaveType->is_active)->toBeTrue();
    });

    test('integer fields are cast correctly', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'default_days' => '12',
            'max_consecutive_days' => '5',
            'min_notice_days' => '3',
        ]);

        expect($leaveType->default_days)->toBeInt();
        expect($leaveType->max_consecutive_days)->toBeInt();
        expect($leaveType->min_notice_days)->toBeInt();
    });
});

describe('LeaveType Soft Deletes', function () {
    test('leave type uses soft deletes', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $leaveType->delete();

        expect($leaveType->trashed())->toBeTrue();
        expect(LeaveType::withTrashed()->find($leaveType->id))->not->toBeNull();
    });
});
