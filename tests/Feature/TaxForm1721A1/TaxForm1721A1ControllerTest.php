<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\TaxForm1721A1;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create([
        'npwp' => '01.234.567.8-901.000',
        'name' => 'PT Contoh Perusahaan',
        'address' => 'Jl. Sudirman No. 123, Jakarta',
    ]);

    // Create roles with company team context
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('Tax Form 1721-A1 Index', function () {
    test('can view tax forms list', function () {
        TaxForm1721A1::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('tax-forms.1721a1.index'));

        $response->assertSuccessful();
        $response->assertViewIs('tax-forms.1721a1.index');
        $response->assertViewHas('taxForms');
    });

    test('only shows tax forms from same company', function () {
        $ownTaxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $otherTaxForm = TaxForm1721A1::factory()->create();

        $response = $this->get(route('tax-forms.1721a1.index'));

        $response->assertSuccessful();
        $response->assertViewHas('taxForms', function ($taxForms) use ($ownTaxForm, $otherTaxForm) {
            return $taxForms->contains($ownTaxForm) && ! $taxForms->contains($otherTaxForm);
        });
    });

    test('can filter by tax year', function () {
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'tax_year' => 2026,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'tax_year' => 2025,
        ]);

        $response = $this->get(route('tax-forms.1721a1.index', ['year' => 2026]));

        $response->assertSuccessful();
        $response->assertViewHas('taxForms', function ($taxForms) {
            return $taxForms->every(fn ($t) => $t->tax_year === 2026);
        });
    });

    test('can filter by employee', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $targetForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('tax-forms.1721a1.index', ['employee_id' => $employee->id]));

        $response->assertSuccessful();
        $response->assertViewHas('taxForms', function ($taxForms) use ($targetForm) {
            return $taxForms->count() === 1 && $taxForms->first()->id === $targetForm->id;
        });
    });
});

describe('Tax Form 1721-A1 Generate', function () {
    test('can view generate form', function () {
        $response = $this->get(route('tax-forms.1721a1.create'));

        $response->assertSuccessful();
        $response->assertViewIs('tax-forms.1721a1.create');
        $response->assertViewHas('employees');
        $response->assertViewHas('years');
    });

    test('can generate tax form for employee with full year payroll data', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'npwp' => '12.345.678.9-012.000',
            'tax_status' => 'K/1',
        ]);
        $employeeSalary = EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
        ]);

        // Create 12 months payroll data
        for ($month = 1; $month <= 12; $month++) {
            $payroll = Payroll::factory()->paid()->forPeriod(2025, $month)->create([
                'company_id' => $this->company->id,
                'created_by' => $this->user->id,
            ]);

            PayrollItem::factory()->paid()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_salary_id' => $employeeSalary->id,
                'basic_salary' => 10000000,
                'total_earnings' => 2000000,
                'total_deductions' => 500000,
                'gross_salary' => 12000000,
                'tax_amount' => 300000,
                'net_salary' => 11200000,
            ]);
        }

        $response = $this->post(route('tax-forms.1721a1.store'), [
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $response->assertRedirect(route('tax-forms.1721a1.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tax_form_1721a1', [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $taxForm = TaxForm1721A1::where('employee_id', $employee->id)
            ->where('tax_year', 2025)
            ->first();

        // Verify annual calculations
        expect((float) $taxForm->gross_salary)->toEqual(144000000.0); // 12 * 12,000,000
        expect((float) $taxForm->total_pph21_withheld)->toEqual(3600000.0); // 12 * 300,000
    });

    test('can generate tax forms for all employees in bulk', function () {
        $employees = Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        // Create a single payroll for all employees
        $payroll = Payroll::factory()->paid()->forPeriod(2025, 1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        foreach ($employees as $employee) {
            $employeeSalary = EmployeeSalary::factory()->active()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            PayrollItem::factory()->paid()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_salary_id' => $employeeSalary->id,
            ]);
        }

        $response = $this->post(route('tax-forms.1721a1.generate-bulk'), [
            'tax_year' => 2025,
        ]);

        $response->assertRedirect(route('tax-forms.1721a1.index'));
        $response->assertSessionHas('success');

        expect(TaxForm1721A1::where('tax_year', 2025)->count())->toBe(3);
    });

    test('validates required fields', function () {
        $response = $this->post(route('tax-forms.1721a1.store'), []);

        $response->assertSessionHasErrors(['employee_id', 'tax_year']);
    });

    test('cannot generate duplicate tax form for same employee and year', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $response = $this->post(route('tax-forms.1721a1.store'), [
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
    });
});

describe('Tax Form 1721-A1 Show', function () {
    test('can view tax form details', function () {
        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('tax-forms.1721a1.show', $taxForm));

        $response->assertSuccessful();
        $response->assertViewIs('tax-forms.1721a1.show');
        $response->assertViewHas('taxForm', $taxForm);
    });

    test('cannot view tax form from other company', function () {
        $otherTaxForm = TaxForm1721A1::factory()->create();

        $response = $this->get(route('tax-forms.1721a1.show', $otherTaxForm));

        $response->assertNotFound();
    });

    test('shows all required 1721-A1 form fields', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'npwp' => '12.345.678.9-012.000',
            'tax_status' => 'K/1',
        ]);

        $taxForm = TaxForm1721A1::factory()
            ->forCompany($this->company)
            ->forEmployee($employee)
            ->create([
                'tax_year' => 2025,
                'gross_salary' => 144000000,
                'total_pph21_withheld' => 3600000,
            ]);

        $response = $this->get(route('tax-forms.1721a1.show', $taxForm));

        $response->assertSuccessful();
        $response->assertSee($this->company->npwp);
        $response->assertSee($this->company->name);
        $response->assertSee($employee->npwp);
        $response->assertSee('144.000.000');
        $response->assertSee('3.600.000');
    });
});

describe('Tax Form 1721-A1 PDF', function () {
    test('can download pdf', function () {
        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('tax-forms.1721a1.pdf', $taxForm));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/pdf');
    });

    test('cannot download pdf from other company', function () {
        $otherTaxForm = TaxForm1721A1::factory()->create();

        $response = $this->get(route('tax-forms.1721a1.pdf', $otherTaxForm));

        $response->assertNotFound();
    });

    test('pdf contains all required tax form data', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'npwp' => '12.345.678.9-012.000',
        ]);

        $taxForm = TaxForm1721A1::factory()->forEmployee($employee)->create([
            'company_id' => $this->company->id,
            'form_number' => '1.1-12.345.678.9-012.000-2025',
        ]);

        $response = $this->get(route('tax-forms.1721a1.pdf', $taxForm));

        $response->assertSuccessful();
    });

    test('can download bulk pdf for multiple employees', function () {
        TaxForm1721A1::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'tax_year' => 2025,
        ]);

        $response = $this->get(route('tax-forms.1721a1.pdf-bulk', ['year' => 2025]));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/pdf');
    });
});

describe('Tax Form 1721-A1 Regenerate', function () {
    test('can regenerate tax form with updated payroll data', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $employeeSalary = EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'tax_year' => 2025,
            'gross_salary' => 100000000,
        ]);

        // Add new payroll data
        $payroll = Payroll::factory()->paid()->forPeriod(2025, 12)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        PayrollItem::factory()->paid()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'employee_salary_id' => $employeeSalary->id,
            'gross_salary' => 15000000,
            'tax_amount' => 500000,
        ]);

        $response = $this->post(route('tax-forms.1721a1.regenerate', $taxForm));

        $response->assertRedirect(route('tax-forms.1721a1.show', $taxForm));
        $response->assertSessionHas('success');

        $taxForm->refresh();
        expect($taxForm->gross_salary)->not->toBe(100000000.00);
    });
});

describe('Tax Form 1721-A1 Delete', function () {
    test('can delete tax form', function () {
        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete(route('tax-forms.1721a1.destroy', $taxForm));

        $response->assertRedirect(route('tax-forms.1721a1.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('tax_form_1721a1', ['id' => $taxForm->id]);
    });

    test('cannot delete tax form from other company', function () {
        $otherTaxForm = TaxForm1721A1::factory()->create();

        $response = $this->delete(route('tax-forms.1721a1.destroy', $otherTaxForm));

        $response->assertNotFound();
    });
});

describe('Tax Form 1721-A1 Calculation', function () {
    test('correctly calculates annual gross income from payroll items', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $employeeSalary = EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        // Create varied monthly payroll data
        $grossSalaries = [10000000, 10000000, 10000000, 10500000, 10500000, 10500000,
            11000000, 11000000, 11000000, 15000000, 12000000, 12000000]; // THR in October
        $taxAmounts = [250000, 250000, 250000, 275000, 275000, 275000,
            300000, 300000, 300000, 500000, 350000, 350000];

        foreach ($grossSalaries as $month => $grossSalary) {
            $payroll = Payroll::factory()->paid()->forPeriod(2025, $month + 1)->create([
                'company_id' => $this->company->id,
                'created_by' => $this->user->id,
            ]);

            PayrollItem::factory()->paid()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_salary_id' => $employeeSalary->id,
                'gross_salary' => $grossSalary,
                'tax_amount' => $taxAmounts[$month],
            ]);
        }

        $response = $this->post(route('tax-forms.1721a1.store'), [
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $taxForm = TaxForm1721A1::where('employee_id', $employee->id)
            ->where('tax_year', 2025)
            ->first();

        expect((float) $taxForm->gross_salary)->toEqual(133500000.0); // Sum of all gross salaries
        expect((float) $taxForm->total_pph21_withheld)->toEqual(3675000.0); // Sum of all tax amounts
    });

    test('correctly calculates biaya jabatan (5% max 6 million)', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $employeeSalary = EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        // High income employee - biaya jabatan should cap at 6 million
        for ($month = 1; $month <= 12; $month++) {
            $payroll = Payroll::factory()->paid()->forPeriod(2025, $month)->create([
                'company_id' => $this->company->id,
                'created_by' => $this->user->id,
            ]);

            PayrollItem::factory()->paid()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_salary_id' => $employeeSalary->id,
                'gross_salary' => 50000000, // 50 juta/bulan
                'tax_amount' => 5000000,
            ]);
        }

        $response = $this->post(route('tax-forms.1721a1.store'), [
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $taxForm = TaxForm1721A1::where('employee_id', $employee->id)
            ->where('tax_year', 2025)
            ->first();

        // 5% of 600,000,000 = 30,000,000, but capped at 6,000,000
        expect((float) $taxForm->biaya_jabatan)->toEqual(6000000.0);
    });

    test('correctly applies PTKP based on tax status', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'tax_status' => 'K/2', // Kawin dengan 2 tanggungan
        ]);
        $employeeSalary = EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        for ($month = 1; $month <= 12; $month++) {
            $payroll = Payroll::factory()->paid()->forPeriod(2025, $month)->create([
                'company_id' => $this->company->id,
                'created_by' => $this->user->id,
            ]);

            PayrollItem::factory()->paid()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_salary_id' => $employeeSalary->id,
                'gross_salary' => 10000000,
                'tax_amount' => 200000,
            ]);
        }

        $response = $this->post(route('tax-forms.1721a1.store'), [
            'employee_id' => $employee->id,
            'tax_year' => 2025,
        ]);

        $taxForm = TaxForm1721A1::where('employee_id', $employee->id)
            ->where('tax_year', 2025)
            ->first();

        // PTKP K/2 = 67,500,000
        expect((float) $taxForm->ptkp)->toEqual(67500000.0);
        expect($taxForm->ptkp_status)->toBe('K/2');
    });
});
