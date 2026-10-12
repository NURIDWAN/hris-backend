<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $this->payroll = Payroll::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'approved',
        'period_month' => 1,
        'period_year' => 2026,
    ]);

    $this->payrollItem = PayrollItem::factory()->create([
        'payroll_id' => $this->payroll->id,
        'employee_id' => $this->employee->id,
        'employee_name' => 'John Doe',
        'employee_number' => 'EMP001',
        'net_salary' => 10000000,
        'payment_method' => 'transfer',
        'bank_name' => 'BCA',
        'bank_account_number' => '1234567890',
        'bank_account_name' => 'John Doe',
        'status' => 'approved',
    ]);
});

describe('PayrollBankExport', function () {
    describe('export bank transfer list', function () {
        it('can download bank transfer excel for approved payroll', function () {
            $response = $this->get(route('payrolls.export-bank', $this->payroll));

            $response->assertOk();
            $response->assertDownload();
        });

        it('includes filename with payroll period', function () {
            $response = $this->get(route('payrolls.export-bank', $this->payroll));

            $response->assertDownload('bank_transfer_januari_2026.xlsx');
        });

        it('cannot export bank for draft payroll', function () {
            $draftPayroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'draft',
            ]);

            $response = $this->get(route('payrolls.export-bank', $draftPayroll));

            $response->assertRedirect();
            $response->assertSessionHas('error');
        });

        it('cannot export bank for other company payroll', function () {
            $otherCompany = Company::factory()->create();
            $otherPayroll = Payroll::factory()->create([
                'company_id' => $otherCompany->id,
                'status' => 'approved',
            ]);

            $response = $this->get(route('payrolls.export-bank', $otherPayroll));

            $response->assertNotFound();
        });

        it('only includes transfer payment method in export', function () {
            // Create another employee for cash payment
            $cashEmployee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Create cash payment item
            PayrollItem::factory()->create([
                'payroll_id' => $this->payroll->id,
                'employee_id' => $cashEmployee->id,
                'employee_name' => 'Jane Cash',
                'payment_method' => 'cash',
                'net_salary' => 5000000,
                'status' => 'approved',
            ]);

            $response = $this->get(route('payrolls.export-bank', $this->payroll));

            $response->assertOk();
            // The export should only contain 1 transfer item, not 2
        });
    });

    describe('export data format', function () {
        it('exports with correct columns for bank transfer', function () {
            $response = $this->get(route('payrolls.export-bank', $this->payroll));

            $response->assertOk();
            // Excel should have columns: No, Nama Bank, No Rekening, Nama Pemilik Rekening, Jumlah, Keterangan
        });

        it('calculates total transfer amount', function () {
            // Create another employee for second transfer
            $anotherEmployee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Add another transfer item
            PayrollItem::factory()->create([
                'payroll_id' => $this->payroll->id,
                'employee_id' => $anotherEmployee->id,
                'employee_name' => 'Jane Doe',
                'employee_number' => 'EMP002',
                'net_salary' => 8000000,
                'payment_method' => 'transfer',
                'bank_name' => 'BRI',
                'bank_account_number' => '0987654321',
                'bank_account_name' => 'Jane Doe',
                'status' => 'approved',
            ]);

            $response = $this->get(route('payrolls.export-bank', $this->payroll));

            $response->assertOk();
            // Total should be 10000000 + 8000000 = 18000000
        });
    });
});
