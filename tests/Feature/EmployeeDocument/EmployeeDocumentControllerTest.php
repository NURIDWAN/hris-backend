<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);
});

describe('EmployeeDocument - Index', function () {
    it('displays document list for an employee', function () {
        EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeDocument::factory()->npwp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.documents.index', $this->employee));

        $response->assertOk();
        $response->assertViewIs('employee-documents.index');
        $response->assertViewHas('documents');
    });

    it('only shows documents for the specific employee', function () {
        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeDocument::factory()->npwp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.documents.index', $this->employee));

        $response->assertOk();
        expect($response->viewData('documents'))->toHaveCount(1);
    });
});

describe('EmployeeDocument - Create', function () {
    it('displays create document form', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.documents.create', $this->employee));

        $response->assertOk();
        $response->assertViewIs('employee-documents.create');
        $response->assertViewHas('documentTypes');
    });
});

describe('EmployeeDocument - Store', function () {
    it('uploads and stores a new document', function () {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.store', $this->employee), [
                'document_type' => 'ktp',
                'document_number' => '3201234567890001',
                'file' => UploadedFile::fake()->create('ktp.pdf', 1024, 'application/pdf'),
            ]);

        $response->assertRedirect(route('employees.documents.index', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $this->employee->id,
            'document_type' => 'ktp',
            'document_number' => '3201234567890001',
        ]);
    });

    it('requires document type', function () {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.store', $this->employee), [
                'file' => UploadedFile::fake()->create('doc.pdf', 1024),
            ]);

        $response->assertSessionHasErrors('document_type');
    });

    it('requires file upload', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.store', $this->employee), [
                'document_type' => 'ktp',
            ]);

        $response->assertSessionHasErrors('file');
    });

    it('validates file size max 10MB', function () {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.store', $this->employee), [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->create('large.pdf', 15000), // 15MB
            ]);

        $response->assertSessionHasErrors('file');
    });

    it('validates allowed file types', function () {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.store', $this->employee), [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->create('script.exe', 100),
            ]);

        $response->assertSessionHasErrors('file');
    });

    it('stores uploaded by user', function () {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('employees.documents.store', $this->employee), [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->create('ktp.pdf', 1024, 'application/pdf'),
            ]);

        $document = EmployeeDocument::first();
        expect($document->uploaded_by)->toBe($this->admin->id);
    });
});

describe('EmployeeDocument - Show', function () {
    it('displays document details', function () {
        $document = EmployeeDocument::factory()->ktp()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.documents.show', [$this->employee, $document]));

        $response->assertOk();
        $response->assertViewIs('employee-documents.show');
        $response->assertViewHas('document');
    });
});

describe('EmployeeDocument - Download', function () {
    it('downloads the document file', function () {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('ktp.pdf', 1024, 'application/pdf');
        $path = $file->store('documents', 'public');

        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'file_path' => $path,
            'file_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.documents.download', [$this->employee, $document]));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=ktp.pdf');
    });
});

describe('EmployeeDocument - Destroy', function () {
    it('deletes a document', function () {
        Storage::fake('public');

        $document = EmployeeDocument::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('employees.documents.destroy', [$this->employee, $document]));

        $response->assertRedirect(route('employees.documents.index', $this->employee));
        $response->assertSessionHas('success');

        // Soft deleted
        $this->assertSoftDeleted('employee_documents', ['id' => $document->id]);
    });
});

describe('EmployeeDocument - Verify', function () {
    it('verifies a document', function () {
        $document = EmployeeDocument::factory()->unverified()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.verify', [$this->employee, $document]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        expect($document->is_verified)->toBeTrue();
        expect($document->verified_by)->toBe($this->admin->id);
    });

    it('unverifies a document', function () {
        $document = EmployeeDocument::factory()->verified()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('employees.documents.unverify', [$this->employee, $document]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        expect($document->is_verified)->toBeFalse();
    });
});

describe('EmployeeDocument - Access Control', function () {
    it('requires authentication', function () {
        $response = $this->get(route('employees.documents.index', $this->employee));

        $response->assertRedirect(route('login'));
    });

    it('restricts access to employees from different company', function () {
        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'work_schedule_id' => WorkSchedule::factory()->create([
                'company_id' => $otherCompany->id,
            ])->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.documents.index', $otherEmployee));

        $response->assertNotFound();
    });
});
