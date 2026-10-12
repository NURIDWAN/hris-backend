<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_face_recognition' => true,
    ]);

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

describe('Face Enrollment - Index', function () {
    it('displays face enrollment list page', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('face-recognition.index'));

        $response->assertOk();
        $response->assertViewIs('face-recognition.index');
    });

    it('shows employees with their enrollment status', function () {
        // Create enrolled and non-enrolled employees
        $enrolledEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $enrolledEmployee->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('face-recognition.index'));

        $response->assertOk();
        $response->assertSee($this->employee->full_name);
        $response->assertSee($enrolledEmployee->full_name);
    });
});

describe('Face Enrollment - Show', function () {
    it('displays face enrollment form for specific employee', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('face-recognition.show', $this->employee));

        $response->assertOk();
        $response->assertViewIs('face-recognition.show');
        $response->assertViewHas('employee', $this->employee);
    });

    it('shows existing enrollment if employee is already enrolled', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('face-recognition.show', $this->employee));

        $response->assertOk();
        $response->assertViewHas('embedding');
    });
});

describe('Face Enrollment - Store', function () {
    it('enrolls employee face from uploaded photo', function () {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('face-recognition.store', $this->employee), [
                'photo' => UploadedFile::fake()->image('face.jpg', 640, 480),
            ]);

        $response->assertRedirect(route('face-recognition.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_face_embeddings', [
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);
    });

    it('requires photo for enrollment', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('face-recognition.store', $this->employee), []);

        $response->assertSessionHasErrors('photo');
    });

    it('validates photo is an image', function () {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('face-recognition.store', $this->employee), [
                'photo' => UploadedFile::fake()->create('document.pdf', 100),
            ]);

        $response->assertSessionHasErrors('photo');
    });

    it('replaces existing enrollment', function () {
        Storage::fake('public');

        $oldEmbedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        $oldEmbeddingId = $oldEmbedding->id;

        $response = $this->actingAs($this->admin)
            ->post(route('face-recognition.store', $this->employee), [
                'photo' => UploadedFile::fake()->image('new_face.jpg', 640, 480),
            ]);

        $response->assertRedirect();

        // Old embedding should be removed (forceDeleted by service)
        $this->assertDatabaseMissing('employee_face_embeddings', [
            'id' => $oldEmbeddingId,
        ]);

        // New embedding should be active
        $this->assertDatabaseHas('employee_face_embeddings', [
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);
    });
});

describe('Face Enrollment - Destroy', function () {
    it('removes employee face enrollment', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('face-recognition.destroy', $this->employee));

        $response->assertRedirect(route('face-recognition.index'));
        $response->assertSessionHas('success');

        expect($embedding->fresh()->is_active)->toBeFalse();
    });

    it('returns error if employee has no enrollment', function () {
        $response = $this->actingAs($this->admin)
            ->delete(route('face-recognition.destroy', $this->employee));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Face Enrollment - Access Control', function () {
    it('requires authentication', function () {
        $response = $this->get(route('face-recognition.index'));

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
            ->get(route('face-recognition.show', $otherEmployee));

        $response->assertNotFound();
    });
});
