<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('Payroll Index', function () {
    test('can view payrolls list', function () {
        Payroll::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('payrolls.index'));

        $response->assertStatus(200);
        $response->assertViewIs('payrolls.index');
        $response->assertViewHas('payrolls');
    });

    test('only shows payrolls from same company', function () {
        $ownPayroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $otherPayroll = Payroll::factory()->create();

        $response = $this->get(route('payrolls.index'));

        $response->assertStatus(200);
        $response->assertViewHas('payrolls', function ($payrolls) use ($ownPayroll, $otherPayroll) {
            return $payrolls->contains($ownPayroll) && ! $payrolls->contains($otherPayroll);
        });
    });

    test('can filter by year', function () {
        Payroll::factory()->forPeriod(2026, 1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        Payroll::factory()->forPeriod(2025, 12)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('payrolls.index', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertViewHas('payrolls', function ($payrolls) {
            return $payrolls->every(fn ($p) => $p->period_year === 2026);
        });
    });

    test('can filter by status', function () {
        Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        Payroll::factory()->paid()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('payrolls.index', ['status' => 'draft']));

        $response->assertStatus(200);
        $response->assertViewHas('payrolls', function ($payrolls) {
            return $payrolls->every(fn ($p) => $p->status === 'draft');
        });
    });
});

describe('Payroll Create', function () {
    test('can view create form', function () {
        $response = $this->get(route('payrolls.create'));

        $response->assertStatus(200);
        $response->assertViewIs('payrolls.create');
    });

    test('can create payroll', function () {
        $data = [
            'name' => 'Gaji Januari 2026',
            'period_month' => 1,
            'period_year' => 2026,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'notes' => 'Payroll bulanan',
        ];

        $response = $this->post(route('payrolls.store'), $data);

        $response->assertRedirect(route('payrolls.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payrolls', [
            'company_id' => $this->company->id,
            'name' => 'Gaji Januari 2026',
            'period_month' => 1,
            'period_year' => 2026,
            'status' => 'draft',
        ]);
    });

    test('period_month is required', function () {
        $data = [
            'name' => 'Gaji Test',
            'period_month' => null,
            'period_year' => 2026,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ];

        $response = $this->post(route('payrolls.store'), $data);

        $response->assertSessionHasErrors('period_month');
    });

    test('period_year is required', function () {
        $data = [
            'name' => 'Gaji Test',
            'period_month' => 1,
            'period_year' => null,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ];

        $response = $this->post(route('payrolls.store'), $data);

        $response->assertSessionHasErrors('period_year');
    });
});

describe('Payroll Show', function () {
    test('can view payroll details', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('payrolls.show', $payroll));

        $response->assertStatus(200);
        $response->assertViewIs('payrolls.show');
        $response->assertViewHas('payroll', $payroll);
    });

    test('cannot view payroll from other company', function () {
        $otherPayroll = Payroll::factory()->create();

        $response = $this->get(route('payrolls.show', $otherPayroll));

        $response->assertStatus(404);
    });

    test('shows payroll items', function () {
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

        $response = $this->get(route('payrolls.show', $payroll));

        $response->assertStatus(200);
        $response->assertViewHas('payroll');
    });
});

describe('Payroll Process', function () {
    test('can process payroll to calculate salaries', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
        EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
        ]);

        $response = $this->post(route('payrolls.process', $payroll));

        $response->assertRedirect(route('payrolls.show', $payroll));
        $response->assertSessionHas('success');

        $payroll->refresh();
        expect($payroll->status)->toBe('calculated');
        expect($payroll->items()->count())->toBeGreaterThan(0);
    });

    test('cannot process already processed payroll', function () {
        $payroll = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.process', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    test('uses basic salary from BASIC component instead of employee_salaries field', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);

        // employee_salaries.basic_salary = 5,000,000 (old/wrong value)
        $salary = EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
        ]);

        // BASIC component amount = 8,000,000 (HR-inputted correct value)
        $basicComponent = SalaryComponent::factory()->earning()->create([
            'company_id' => $this->company->id,
            'name' => 'Gaji Pokok',
            'code' => 'BASIC',
            'category' => 'fixed',
            'is_taxable' => true,
        ]);
        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $basicComponent->id,
            'amount' => 8000000,
        ]);

        $this->post(route('payrolls.process', $payroll));

        $payroll->refresh();
        $item = $payroll->items()->first();

        // basic_salary should be 8,000,000 from BASIC component, not 5,000,000
        expect((float) $item->basic_salary)->toBe(8000000.0);
    });
});

describe('Payroll Approve', function () {
    test('can approve calculated payroll', function () {
        $payroll = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.approve', $payroll));

        $response->assertRedirect(route('payrolls.show', $payroll));
        $response->assertSessionHas('success');

        $payroll->refresh();
        expect($payroll->status)->toBe('approved');
        expect($payroll->approved_by)->toBe($this->user->id);
    });

    test('cannot approve draft payroll', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.approve', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Payroll Pay', function () {
    test('can mark approved payroll as paid', function () {
        $payroll = Payroll::factory()->approved()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.pay', $payroll));

        $response->assertRedirect(route('payrolls.show', $payroll));
        $response->assertSessionHas('success');

        $payroll->refresh();
        expect($payroll->status)->toBe('paid');
        expect($payroll->paid_by)->toBe($this->user->id);
    });

    test('cannot pay unapproved payroll', function () {
        $payroll = Payroll::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.pay', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Payroll Cancel', function () {
    test('can cancel draft payroll', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.cancel', $payroll));

        $response->assertRedirect(route('payrolls.index'));
        $response->assertSessionHas('success');

        $payroll->refresh();
        expect($payroll->status)->toBe('cancelled');
    });

    test('cannot cancel paid payroll', function () {
        $payroll = Payroll::factory()->paid()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('payrolls.cancel', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Payroll Delete', function () {
    test('can delete draft payroll', function () {
        $payroll = Payroll::factory()->draft()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('payrolls.destroy', $payroll));

        $response->assertRedirect(route('payrolls.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('payrolls', ['id' => $payroll->id]);
    });

    test('cannot delete payroll from other company', function () {
        $otherPayroll = Payroll::factory()->create();

        $response = $this->delete(route('payrolls.destroy', $otherPayroll));

        $response->assertStatus(404);
    });

    test('cannot delete paid payroll', function () {
        $payroll = Payroll::factory()->paid()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('payrolls.destroy', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});
