<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
});

describe('Payroll Model', function () {
    test('can create payroll', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'period_month' => 1,
            'period_year' => 2026,
        ]);

        expect($payroll)->toBeInstanceOf(Payroll::class);
        expect($payroll->period_month)->toBe(1);
        expect($payroll->period_year)->toBe(2026);
    });

    test('auto generates payroll number on create', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'period_month' => 1,
            'period_year' => 2026,
        ]);

        expect($payroll->payroll_number)->toStartWith('PAY202601');
    });

    test('belongs to company', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->company)->toBeInstanceOf(Company::class);
        expect($payroll->company->id)->toBe($this->company->id);
    });

    test('belongs to created_by user', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->createdBy)->toBeInstanceOf(User::class);
        expect($payroll->createdBy->id)->toBe($this->user->id);
    });

    test('has many items', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $employeeSalary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'employee_salary_id' => $employeeSalary->id,
        ]);

        expect($payroll->items)->toHaveCount(1);
    });
});

describe('Payroll Scopes', function () {
    test('draft scope filters draft payrolls', function () {
        Payroll::factory()->draft()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $drafts = Payroll::where('company_id', $this->company->id)->draft()->get();

        expect($drafts)->toHaveCount(2);
        expect($drafts->every(fn ($p) => $p->status === 'draft'))->toBeTrue();
    });

    test('calculated scope filters calculated payrolls', function () {
        Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        Payroll::factory()->calculated()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $calculated = Payroll::where('company_id', $this->company->id)->calculated()->get();

        expect($calculated)->toHaveCount(2);
    });

    test('forPeriod scope filters by year and month', function () {
        Payroll::factory()->forPeriod(2026, 1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        Payroll::factory()->forPeriod(2026, 2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $january = Payroll::where('company_id', $this->company->id)->forPeriod(2026, 1)->get();

        expect($january)->toHaveCount(1);
    });

    test('forYear scope filters by year', function () {
        Payroll::factory()->forPeriod(2026, 1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        Payroll::factory()->forPeriod(2025, 12)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $year2026 = Payroll::where('company_id', $this->company->id)->forYear(2026)->get();

        expect($year2026)->toHaveCount(1);
    });
});

describe('Payroll Status Checks', function () {
    test('isDraft returns true for draft status', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->isDraft())->toBeTrue();
        expect($payroll->isCalculated())->toBeFalse();
    });

    test('isCalculated returns true for calculated status', function () {
        $payroll = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->isCalculated())->toBeTrue();
    });

    test('isApproved returns true for approved status', function () {
        $payroll = Payroll::factory()->approved()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->isApproved())->toBeTrue();
    });

    test('isPaid returns true for paid status', function () {
        $payroll = Payroll::factory()->paid()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->isPaid())->toBeTrue();
    });
});

describe('Payroll Can Actions', function () {
    test('canBeEdited is true only for draft', function () {
        $draft = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $calculated = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($draft->canBeEdited())->toBeTrue();
        expect($calculated->canBeEdited())->toBeFalse();
    });

    test('canBeCalculated is true only for draft', function () {
        $draft = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($draft->canBeCalculated())->toBeTrue();
    });

    test('canBeApproved is true only for calculated', function () {
        $calculated = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $draft = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($calculated->canBeApproved())->toBeTrue();
        expect($draft->canBeApproved())->toBeFalse();
    });

    test('canBePaid is true only for approved', function () {
        $approved = Payroll::factory()->approved()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $calculated = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($approved->canBePaid())->toBeTrue();
        expect($calculated->canBePaid())->toBeFalse();
    });

    test('canBeCancelled is true for draft, calculated, approved', function () {
        $draft = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $paid = Payroll::factory()->paid()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($draft->canBeCancelled())->toBeTrue();
        expect($paid->canBeCancelled())->toBeFalse();
    });
});

describe('Payroll Actions', function () {
    test('approve sets approved status and user', function () {
        $payroll = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $payroll->approve($this->user->id);

        expect($payroll->status)->toBe('approved');
        expect($payroll->approved_by)->toBe($this->user->id);
        expect($payroll->approved_at)->not->toBeNull();
    });

    test('markAsPaid sets paid status and user', function () {
        $payroll = Payroll::factory()->approved()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $payroll->markAsPaid($this->user->id);

        expect($payroll->status)->toBe('paid');
        expect($payroll->paid_by)->toBe($this->user->id);
        expect($payroll->paid_at)->not->toBeNull();
    });

    test('cancel sets cancelled status', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $payroll->cancel();

        expect($payroll->status)->toBe('cancelled');
    });
});

describe('Payroll Accessors', function () {
    test('period_label returns formatted month year', function () {
        $payroll = Payroll::factory()->forPeriod(2026, 1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($payroll->period_label)->toBe('Januari 2026');
    });

    test('status_label returns Indonesian label', function () {
        $draft = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $paid = Payroll::factory()->paid()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($draft->status_label)->toBe('Draft');
        expect($paid->status_label)->toBe('Dibayar');
    });

    test('formatted_total_net_salary returns formatted currency', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'total_net_salary' => 50000000,
        ]);

        expect($payroll->formatted_total_net_salary)->toBe('Rp 50.000.000');
    });
});

describe('Payroll Soft Deletes', function () {
    test('can soft delete payroll', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $payroll->delete();

        $this->assertSoftDeleted('payrolls', ['id' => $payroll->id]);
        expect(Payroll::withTrashed()->find($payroll->id))->not->toBeNull();
    });
});
