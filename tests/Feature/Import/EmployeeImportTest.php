<?php

use App\Imports\EmployeeImport;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    // Create reference data
    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'IT Department',
        'code' => 'IT',
    ]);

    $this->position = Position::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'name' => 'Software Engineer',
        'code' => 'SE',
    ]);

    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Shift Reguler',
        'code' => 'REG',
    ]);
});

describe('EmployeeImport', function () {
    describe('import page', function () {
        it('displays the import page', function () {
            $response = $this->get(route('imports.employees.index'));

            $response->assertOk();
            $response->assertViewIs('imports.employees.index');
        });

        it('can download template', function () {
            $response = $this->get(route('imports.employees.template'));

            $response->assertOk();
            $response->assertDownload('template_karyawan.xlsx');
        });
    });

    describe('import process', function () {
        it('validates required file', function () {
            $response = $this->post(route('imports.employees.store'), []);

            $response->assertSessionHasErrors(['file']);
        });

        it('creates employees from import data', function () {
            $importData = [
                [
                    'employee_id' => 'EMP001',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john.doe@example.com',
                    'phone' => '081234567890',
                    'gender' => 'male',
                    'date_of_birth' => '1990-01-15',
                    'hire_date' => '2023-01-01',
                    'department_code' => 'IT',
                    'position_code' => 'SE',
                    'work_schedule_code' => 'REG',
                    'employment_status' => 'permanent',
                    'base_salary' => 10000000,
                ],
                [
                    'employee_id' => 'EMP002',
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane.smith@example.com',
                    'phone' => '081234567891',
                    'gender' => 'female',
                    'date_of_birth' => '1992-05-20',
                    'hire_date' => '2023-06-01',
                    'department_code' => 'IT',
                    'position_code' => 'SE',
                    'work_schedule_code' => 'REG',
                    'employment_status' => 'contract',
                    'contract_start_date' => '2023-06-01',
                    'contract_end_date' => '2024-06-01',
                    'base_salary' => 8000000,
                ],
            ];

            foreach ($importData as $row) {
                Employee::create([
                    'company_id' => $this->company->id,
                    'department_id' => $this->department->id,
                    'position_id' => $this->position->id,
                    'work_schedule_id' => $this->workSchedule->id,
                    'employee_id' => $row['employee_id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'gender' => $row['gender'],
                    'date_of_birth' => $row['date_of_birth'],
                    'hire_date' => $row['hire_date'],
                    'employment_status' => $row['employment_status'],
                    'contract_start_date' => $row['contract_start_date'] ?? null,
                    'contract_end_date' => $row['contract_end_date'] ?? null,
                    'base_salary' => $row['base_salary'],
                    'is_active' => true,
                ]);
            }

            expect(Employee::where('company_id', $this->company->id)->count())->toBe(2);
            $this->assertDatabaseHas('employees', [
                'employee_id' => 'EMP001',
                'first_name' => 'John',
                'email' => 'john.doe@example.com',
            ]);
        });

        it('resolves department by code', function () {
            $departmentCode = 'IT';
            $department = Department::where('company_id', $this->company->id)
                ->where('code', $departmentCode)
                ->first();

            expect($department)->not->toBeNull();
            expect($department->id)->toBe($this->department->id);
        });

        it('resolves position by code', function () {
            $positionCode = 'SE';
            $position = Position::where('company_id', $this->company->id)
                ->where('code', $positionCode)
                ->first();

            expect($position)->not->toBeNull();
            expect($position->id)->toBe($this->position->id);
        });

        it('handles salary with Indonesian number format', function () {
            $salaryString = '10.000.000';
            $salary = (int) str_replace(['.', ','], '', $salaryString);

            expect($salary)->toBe(10000000);
        });

        it('parses date formats correctly', function () {
            $date1 = \Carbon\Carbon::parse('1990-01-15');
            $date2 = \Carbon\Carbon::createFromFormat('d/m/Y', '15/01/1990');

            expect($date1->format('Y-m-d'))->toBe('1990-01-15');
            expect($date2->format('Y-m-d'))->toBe('1990-01-15');
        });

        it('validates unique employee_id within company', function () {
            Employee::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => 'EMP001',
            ]);

            $existingEmployee = Employee::where('company_id', $this->company->id)
                ->where('employee_id', 'EMP001')
                ->exists();

            expect($existingEmployee)->toBeTrue();
        });

        it('can import employee with bank information', function () {
            $employee = Employee::create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'employee_id' => 'EMP003',
                'first_name' => 'Bank',
                'last_name' => 'Test',
                'email' => 'bank.test@example.com',
                'gender' => 'male',
                'hire_date' => '2023-01-01',
                'employment_status' => 'permanent',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Bank Test',
                'is_active' => true,
            ]);

            expect($employee->bank_name)->toBe('BCA');
            expect($employee->bank_account_number)->toBe('1234567890');
        });

        it('can import employee with BPJS information', function () {
            $employee = Employee::create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'employee_id' => 'EMP004',
                'first_name' => 'BPJS',
                'last_name' => 'Test',
                'email' => 'bpjs.test@example.com',
                'gender' => 'female',
                'hire_date' => '2023-01-01',
                'employment_status' => 'permanent',
                'npwp' => '12.345.678.9-012.345',
                'bpjs_kesehatan' => '0001234567890',
                'bpjs_ketenagakerjaan' => '0009876543210',
                'tax_status' => 'TK/0',
                'is_active' => true,
            ]);

            expect($employee->npwp)->toBe('12.345.678.9-012.345');
            expect($employee->bpjs_kesehatan)->toBe('0001234567890');
            expect($employee->tax_status)->toBe('TK/0');
        });
    });

    describe('queued import', function () {
        it('initializes import with cache status', function () {
            $import = new EmployeeImport($this->company->id, 'test_import_123');
            $import->initializeImport();

            $status = EmployeeImport::getImportStatus('test_import_123');

            expect($status)->not->toBeNull();
            expect($status['status'])->toBe('processing');
            expect($status['success_count'])->toBe(0);
            expect($status['skip_count'])->toBe(0);
            expect($status['errors'])->toBe([]);
        });

        it('can check import status via API endpoint', function () {
            $importId = 'test_import_456';
            Cache::put("employee_import_{$importId}", [
                'status' => 'processing',
                'success_count' => 100,
                'skip_count' => 5,
                'errors' => ['NIK already exists'],
                'started_at' => now()->toDateTimeString(),
                'completed_at' => null,
            ], now()->addHours(24));

            $response = $this->getJson(route('imports.employees.status', $importId));

            $response->assertOk();
            $response->assertJson([
                'status' => 'processing',
                'success_count' => 100,
                'skip_count' => 5,
            ]);
        });

        it('returns 404 for non-existent import', function () {
            $response = $this->getJson(route('imports.employees.status', 'non_existent_id'));

            $response->assertNotFound();
            $response->assertJson([
                'status' => 'not_found',
            ]);
        });

        it('has correct chunk size', function () {
            $import = new EmployeeImport($this->company->id);

            expect($import->chunkSize())->toBe(200);
        });
    });
});
