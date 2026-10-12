<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'base_salary' => 5000000,
    ]);

    // Create paid payroll
    $this->payroll = Payroll::factory()->create([
        'company_id' => $this->company->id,
        'period_month' => now()->month,
        'period_year' => now()->year,
        'status' => 'paid',
    ]);

    $this->payrollItem = PayrollItem::factory()->create([
        'payroll_id' => $this->payroll->id,
        'employee_id' => $this->employee->id,
        'basic_salary' => 5000000,
        'total_earnings' => 5500000,
        'total_deductions' => 500000,
        'net_salary' => 5000000,
        'status' => 'paid',
    ]);
});

describe('GET /api/v1/payslips', function () {
    it('returns list of payslips', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/payslips');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'payroll_id',
                        'period',
                        'period_month',
                        'period_year',
                        'net_salary',
                        'formatted_net_salary',
                        'payment_date',
                        'status',
                    ],
                ],
            ]);

        expect(count($response->json('data')))->toBe(1);
    });

    it('only shows paid payslips', function () {
        Sanctum::actingAs($this->user);

        // Create unpaid payroll
        $draftPayroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'period_month' => now()->subMonth()->month,
            'period_year' => now()->year,
            'status' => 'draft',
        ]);

        PayrollItem::factory()->create([
            'payroll_id' => $draftPayroll->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/payslips');

        $response->assertOk();
        // Should only return paid payslip
        expect(count($response->json('data')))->toBe(1);
    });

    it('can filter by year', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/payslips?year='.now()->year);

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('orders by period descending', function () {
        Sanctum::actingAs($this->user);

        // Create older payroll
        $olderPayroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'period_month' => now()->subMonth()->month,
            'period_year' => now()->year,
            'status' => 'paid',
        ]);

        PayrollItem::factory()->create([
            'payroll_id' => $olderPayroll->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/payslips');

        $response->assertOk();
        $data = $response->json('data');
        expect($data[0]['period_month'])->toBe(now()->month);
    });
});

describe('GET /api/v1/payslips/{id}', function () {
    it('returns payslip detail', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/payslips/{$this->payrollItem->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'payroll_id',
                    'period',
                    'period_month',
                    'period_year',
                    'employee' => [
                        'id',
                        'employee_id',
                        'full_name',
                        'department',
                        'position',
                    ],
                    'base_salary',
                    'earnings' => [
                        '*' => [
                            'name',
                            'amount',
                            'formatted_amount',
                        ],
                    ],
                    'deductions' => [
                        '*' => [
                            'name',
                            'amount',
                            'formatted_amount',
                        ],
                    ],
                    'total_earnings',
                    'total_deductions',
                    'net_salary',
                    'formatted_base_salary',
                    'formatted_total_earnings',
                    'formatted_total_deductions',
                    'formatted_net_salary',
                    'payment_date',
                    'payment_method',
                    'status',
                ],
            ]);
    });

    it('returns 404 for other employee payslip', function () {
        Sanctum::actingAs($this->user);

        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $otherPayrollItem = PayrollItem::factory()->create([
            'payroll_id' => $this->payroll->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->getJson("/api/v1/payslips/{$otherPayrollItem->id}");

        $response->assertStatus(404);
    });

    it('returns 404 for unpaid payslip', function () {
        Sanctum::actingAs($this->user);

        $draftPayroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $draftPayrollItem = PayrollItem::factory()->create([
            'payroll_id' => $draftPayroll->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson("/api/v1/payslips/{$draftPayrollItem->id}");

        $response->assertStatus(404);
    });
});

describe('GET /api/v1/payslips/{id}/download', function () {
    it('returns pdf download url', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/payslips/{$this->payrollItem->id}/download");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'download_url',
                    'filename',
                ],
            ]);
    });
});

describe('GET /api/v1/payslips/summary', function () {
    it('returns yearly salary summary', function () {
        Sanctum::actingAs($this->user);

        // Create multiple payroll items for the year (use different months than beforeEach)
        for ($i = 3; $i <= 5; $i++) {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'period_month' => $i,
                'period_year' => now()->year,
                'status' => 'paid',
            ]);

            PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $this->employee->id,
                'net_salary' => 5000000,
                'total_earnings' => 5500000,
                'total_deductions' => 500000,
            ]);
        }

        $response = $this->getJson('/api/v1/payslips/summary?year='.now()->year);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'year',
                    'total_months',
                    'total_earnings',
                    'total_deductions',
                    'total_net_salary',
                    'average_net_salary',
                    'formatted_total_earnings',
                    'formatted_total_deductions',
                    'formatted_total_net_salary',
                    'formatted_average_net_salary',
                    'monthly_breakdown' => [
                        '*' => [
                            'month',
                            'month_name',
                            'net_salary',
                            'formatted_net_salary',
                        ],
                    ],
                ],
            ]);
    });
});
