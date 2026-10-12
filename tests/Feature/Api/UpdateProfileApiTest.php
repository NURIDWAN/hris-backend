<?php

/**
 * Tests for Update Profile API.
 *
 * Endpoint: PATCH /api/v1/auth/profile
 * - Update nama, phone, alamat
 * - Upload foto profil (opsional)
 */

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '081234567890',
        'address' => 'Jl. Sudirman No. 1',
    ]);
});

describe('Update Profile API', function () {
    describe('PATCH /api/v1/auth/profile', function () {
        it('updates employee profile successfully', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '089876543210',
                'address' => 'Jl. Thamrin No. 10',
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Profil berhasil diperbarui.')
                ->assertJsonPath('data.employee.first_name', 'Jane')
                ->assertJsonPath('data.employee.last_name', 'Smith')
                ->assertJsonPath('data.employee.phone', '089876543210');

            $this->assertDatabaseHas('employees', [
                'id' => $this->employee->id,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '089876543210',
                'address' => 'Jl. Thamrin No. 10',
            ]);
        });

        it('updates only provided fields', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'phone' => '089999999999',
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('employees', [
                'id' => $this->employee->id,
                'first_name' => 'John', // unchanged
                'last_name' => 'Doe', // unchanged
                'phone' => '089999999999', // updated
            ]);
        });

        it('uploads profile photo successfully', function () {
            Sanctum::actingAs($this->user, ['*']);

            $photo = UploadedFile::fake()->image('profile.jpg', 400, 400);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'photo' => $photo,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            // Check photo path is returned
            $photoPath = $response->json('data.employee.photo');
            expect($photoPath)->not->toBeNull();
            expect($photoPath)->toContain('employee-photos');

            // Check file exists in storage
            $employee = $this->employee->fresh();
            expect($employee->photo)->not->toBeNull();
            Storage::disk('public')->assertExists($employee->photo);
        });

        it('updates profile with photo and other fields', function () {
            Sanctum::actingAs($this->user, ['*']);

            $photo = UploadedFile::fake()->image('profile.jpg', 400, 400);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'first_name' => 'Updated',
                'phone' => '081111111111',
                'photo' => $photo,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.employee.first_name', 'Updated')
                ->assertJsonPath('data.employee.phone', '081111111111');

            $employee = $this->employee->fresh();
            expect($employee->photo)->not->toBeNull();
        });

        it('deletes old photo when uploading new one', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Upload first photo
            $oldPhoto = UploadedFile::fake()->image('old.jpg', 400, 400);
            $this->patchJson('/api/v1/auth/profile', ['photo' => $oldPhoto]);

            $oldPhotoPath = $this->employee->fresh()->photo;
            Storage::disk('public')->assertExists($oldPhotoPath);

            // Upload new photo
            $newPhoto = UploadedFile::fake()->image('new.jpg', 400, 400);
            $this->patchJson('/api/v1/auth/profile', ['photo' => $newPhoto]);

            // Old photo should be deleted
            Storage::disk('public')->assertMissing($oldPhotoPath);

            // New photo should exist
            $newPhotoPath = $this->employee->fresh()->photo;
            Storage::disk('public')->assertExists($newPhotoPath);
        });

        it('validates photo must be an image', function () {
            Sanctum::actingAs($this->user, ['*']);

            $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

            $response = $this->patchJson('/api/v1/auth/profile', [
                'photo' => $file,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['photo']);
        });

        it('validates photo max size', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Create a file larger than 2MB
            $photo = UploadedFile::fake()->image('large.jpg')->size(3000);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'photo' => $photo,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['photo']);
        });

        it('validates phone format', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'phone' => 'invalid-phone',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['phone']);
        });

        it('validates first_name max length', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'first_name' => str_repeat('a', 256),
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['first_name']);
        });

        it('returns 401 when not authenticated', function () {
            $response = $this->patchJson('/api/v1/auth/profile', [
                'first_name' => 'Jane',
            ]);

            $response->assertUnauthorized();
        });

        it('syncs user name with employee full_name', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ]);

            $response->assertOk();

            // User name should be updated
            $this->assertDatabaseHas('users', [
                'id' => $this->user->id,
                'name' => 'Jane Smith',
            ]);
        });

        it('returns updated profile data in response', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->patchJson('/api/v1/auth/profile', [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'employee' => [
                            'id',
                            'employee_id',
                            'full_name',
                            'first_name',
                            'last_name',
                            'phone',
                            'photo',
                            'address',
                        ],
                    ],
                ]);
        });
    });
});
