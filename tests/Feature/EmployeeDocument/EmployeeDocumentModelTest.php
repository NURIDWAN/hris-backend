<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);
});

describe('EmployeeDocument Model', function () {
    it('can be created with valid data', function () {
        $document = EmployeeDocument::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'document_type' => 'ktp',
            'document_number' => '3201234567890001',
            'file_path' => 'documents/employee_1/ktp.pdf',
            'file_name' => 'ktp.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'is_verified' => false,
        ]);

        expect($document)->toBeInstanceOf(EmployeeDocument::class);
        expect($document->document_type)->toBe('ktp');
        expect($document->document_number)->toBe('3201234567890001');
    });

    it('belongs to an employee', function () {
        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->employee)->toBeInstanceOf(Employee::class);
        expect($document->employee->id)->toBe($this->employee->id);
    });

    it('belongs to a company', function () {
        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->company)->toBeInstanceOf(Company::class);
        expect($document->company->id)->toBe($this->company->id);
    });

    it('can have uploaded by user', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'uploaded_by' => $user->id,
        ]);

        expect($document->uploadedBy)->toBeInstanceOf(User::class);
        expect($document->uploadedBy->id)->toBe($user->id);
    });

    it('can have verified by user', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'is_verified' => true,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        expect($document->verifiedBy)->toBeInstanceOf(User::class);
        expect($document->is_verified)->toBeTrue();
    });
});

describe('EmployeeDocument - Document Types', function () {
    it('supports KTP document type', function () {
        $document = EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('ktp');
    });

    it('supports NPWP document type', function () {
        $document = EmployeeDocument::factory()->npwp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('npwp');
    });

    it('supports KK document type', function () {
        $document = EmployeeDocument::factory()->kk()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('kk');
    });

    it('supports BPJS Kesehatan document type', function () {
        $document = EmployeeDocument::factory()->bpjsKesehatan()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('bpjs_kesehatan');
    });

    it('supports BPJS Ketenagakerjaan document type', function () {
        $document = EmployeeDocument::factory()->bpjsKetenagakerjaan()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('bpjs_ketenagakerjaan');
    });

    it('supports Ijazah document type', function () {
        $document = EmployeeDocument::factory()->ijazah()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('ijazah');
    });

    it('supports Sertifikat document type', function () {
        $document = EmployeeDocument::factory()->sertifikat()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('sertifikat');
    });

    it('supports Kontrak Kerja document type', function () {
        $document = EmployeeDocument::factory()->kontrakKerja()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type)->toBe('kontrak_kerja');
    });

    it('supports other document type', function () {
        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'document_type' => 'other',
            'notes' => 'Custom document',
        ]);

        expect($document->document_type)->toBe('other');
    });
});

describe('EmployeeDocument - Scopes', function () {
    it('can filter by document type', function () {
        EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeDocument::factory()->npwp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $ktpDocs = EmployeeDocument::ofType('ktp')->get();

        expect($ktpDocs)->toHaveCount(1);
        expect($ktpDocs->first()->document_type)->toBe('ktp');
    });

    it('can filter verified documents', function () {
        EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'is_verified' => true,
        ]);
        EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'is_verified' => false,
        ]);

        $verifiedDocs = EmployeeDocument::verified()->get();

        expect($verifiedDocs)->toHaveCount(1);
    });

    it('can filter unverified documents', function () {
        EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'is_verified' => true,
        ]);
        EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'is_verified' => false,
        ]);

        $unverifiedDocs = EmployeeDocument::unverified()->get();

        expect($unverifiedDocs)->toHaveCount(1);
    });
});

describe('EmployeeDocument - Employee Relationship', function () {
    it('employee has many documents', function () {
        EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeDocument::factory()->npwp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->employee->refresh();

        expect($this->employee->documents)->toHaveCount(2);
    });

    it('can get specific document type from employee', function () {
        EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeDocument::factory()->npwp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $ktp = $this->employee->documents()->ofType('ktp')->first();

        expect($ktp)->not->toBeNull();
        expect($ktp->document_type)->toBe('ktp');
    });
});

describe('EmployeeDocument - Helpers', function () {
    it('returns human readable file size', function () {
        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'file_size' => 1048576, // 1 MB
        ]);

        expect($document->human_file_size)->toBe('1 MB');
    });

    it('returns document type label', function () {
        $document = EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($document->document_type_label)->toBe('KTP');
    });

    it('can check if document is expired', function () {
        $expiredDoc = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'expiry_date' => now()->subDay(),
        ]);

        $validDoc = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'expiry_date' => now()->addYear(),
        ]);

        expect($expiredDoc->is_expired)->toBeTrue();
        expect($validDoc->is_expired)->toBeFalse();
    });
});
