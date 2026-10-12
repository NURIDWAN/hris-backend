<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\ThrPayment;
use App\Models\ThrSetting;
use App\Models\User;
use App\Services\ThrCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'hr-manager']);
    Role::create(['name' => 'employee']);

    $this->company = Company::factory()->create();

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');

    $this->thrSetting = ThrSetting::factory()->create([
        'company_id' => $this->company->id,
        'calculation_method' => 'one_month_salary',
        'min_service_months' => 1,
        'prorata_formula' => 'months_worked_per_12',
        'include_allowances' => true,
    ]);
});

describe('THR Payment Index', function () {
    it('displays thr payment page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/thr');

        $response->assertStatus(200);
        $response->assertViewIs('thr.index');
        $response->assertSee('THR');
    });

    it('shows thr payment history', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payment = ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'year' => 2026,
            'amount' => 5000000,
            'status' => 'paid',
        ]);

        $response = $this->get('/thr');

        $response->assertStatus(200);
        $response->assertSee($employee->full_name);
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/thr');

        $response->assertRedirect('/login');
    });
});

describe('THR Calculation', function () {
    it('displays calculation page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/thr/calculate');

        $response->assertStatus(200);
        $response->assertViewIs('thr.calculate');
    });

    it('calculates thr for eligible employees', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'is_active' => true,
        ]);

        $response = $this->post('/thr/calculate', [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'employee_id',
                    'employee_name',
                    'service_months',
                    'base_salary',
                    'thr_amount',
                    'calculation_method',
                ],
            ],
        ]);
    });

    it('excludes employees with insufficient service months', function () {
        $this->actingAs($this->admin);

        // Set minimum service to 3 months
        $this->thrSetting->update(['min_service_months' => 3]);

        // Employee with only 1 month service
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'is_active' => true,
        ]);

        $response = $this->post('/thr/calculate', [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toBeEmpty();
    });
});

describe('THR Payment Process', function () {
    it('can process thr payments', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subYear(),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'is_active' => true,
        ]);

        $response = $this->post('/thr/process', [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
            'employee_ids' => [$employee->id],
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('thr_payments', [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'year' => 2026,
            'status' => 'pending',
        ]);
    });

    it('prevents duplicate thr payment for same year', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subYear(),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'is_active' => true,
        ]);

        // Create existing payment
        ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'year' => 2026,
        ]);

        $response = $this->post('/thr/process', [
            'year' => 2026,
            'religious_holiday' => 'idul_fitri',
            'employee_ids' => [$employee->id],
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
    });
});

describe('THR Payment Actions', function () {
    it('can mark thr as paid', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payment = ThrPayment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->post("/thr/{$payment->id}/pay");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        expect($payment->status)->toBe('paid');
        expect($payment->paid_at)->not->toBeNull();
    });

    it('can cancel thr payment', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payment = ThrPayment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->post("/thr/{$payment->id}/cancel");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        expect($payment->status)->toBe('cancelled');
    });

    it('cannot cancel already paid thr', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payment = ThrPayment::factory()->paid()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->post("/thr/{$payment->id}/cancel");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $payment->refresh();
        expect($payment->status)->toBe('paid');
    });
});

describe('THR Calculation Service', function () {
    it('calculates one month salary correctly', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subYear(),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'is_active' => true,
        ]);

        $this->thrSetting->update([
            'calculation_method' => 'one_month_salary',
            'include_allowances' => false,
        ]);

        $service = new ThrCalculationService;
        $result = $service->calculateForEmployee($employee, $this->thrSetting);

        expect($result['thr_amount'])->toBe(5000000.0);
    });

    it('calculates prorata correctly for new employees', function () {
        // Employee joined 6 months ago
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subMonths(6),
            'is_active' => true,
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 6000000,
            'is_active' => true,
        ]);

        $this->thrSetting->update([
            'calculation_method' => 'prorata',
            'prorata_formula' => 'months_worked_per_12',
            'include_allowances' => false,
        ]);

        $service = new ThrCalculationService;
        $result = $service->calculateForEmployee($employee, $this->thrSetting);

        // 6/12 * 6,000,000 = 3,000,000
        expect($result['thr_amount'])->toBe(3000000.0);
        expect($result['service_months'])->toBe(6);
    });

    it('includes allowances when configured', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'hire_date' => now()->subYear(),
            'is_active' => true,
        ]);

        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'is_active' => true,
        ]);

        // Create salary components for allowances
        $transportAllowance = SalaryComponent::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Transport Allowance',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 500000,
            'is_active' => true,
        ]);

        $mealAllowance = SalaryComponent::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Meal Allowance',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 300000,
            'is_active' => true,
        ]);

        // Attach allowances to salary
        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $transportAllowance->id,
            'amount' => 500000,
        ]);

        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $mealAllowance->id,
            'amount' => 300000,
        ]);

        $this->thrSetting->update([
            'calculation_method' => 'one_month_salary',
            'include_allowances' => true,
        ]);

        $service = new ThrCalculationService;
        $result = $service->calculateForEmployee($employee, $this->thrSetting);

        // 5,000,000 + 500,000 + 300,000 = 5,800,000
        expect($result['thr_amount'])->toBe(5800000.0);
    });
});

describe('THR Payment Model', function () {
    it('has correct status labels', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payment = ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        expect($payment->status_label)->toBe('Menunggu');

        $payment->update(['status' => 'paid']);
        expect($payment->status_label)->toBe('Dibayar');

        $payment->update(['status' => 'cancelled']);
        expect($payment->status_label)->toBe('Dibatalkan');
    });

    it('formats amount correctly', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payment = ThrPayment::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'amount' => 5000000,
        ]);

        expect($payment->formatted_amount)->toBe('Rp 5.000.000');
    });
});
