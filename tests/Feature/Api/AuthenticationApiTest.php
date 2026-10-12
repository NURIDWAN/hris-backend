<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'employee@test.com',
        'password' => bcrypt('password123'),
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'email' => 'employee@test.com',
    ]);
});

describe('POST /api/v1/auth/login', function () {
    it('can login with valid credentials', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'employee' => [
                        'id',
                        'employee_id',
                        'full_name',
                        'department',
                        'position',
                    ],
                    'company' => [
                        'id',
                        'name',
                    ],
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect($response->json('data.user.email'))->toBe('employee@test.com');
    });

    it('returns error with invalid credentials', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password salah.',
            ]);
    });

    it('returns error when user has no employee record', function () {
        $userWithoutEmployee = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun ini tidak terdaftar sebagai karyawan.',
            ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('returns error for inactive employee', function () {
        $this->employee->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun karyawan tidak aktif.',
            ]);
    });
});

describe('POST /api/v1/auth/logout', function () {
    it('can logout and revoke token', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil logout.',
            ]);
    });

    it('returns error when not authenticated', function () {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    });
});

describe('GET /api/v1/auth/profile', function () {
    it('returns authenticated user profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'employee' => [
                        'id',
                        'employee_id',
                        'full_name',
                        'first_name',
                        'last_name',
                        'phone',
                        'photo',
                        'department',
                        'position',
                        'hire_date',
                        'employment_status',
                    ],
                    'company' => [
                        'id',
                        'name',
                        'logo',
                    ],
                ],
            ]);

        expect($response->json('data.user.email'))->toBe('employee@test.com');
    });

    it('returns error when not authenticated', function () {
        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertStatus(401);
    });
});

describe('POST /api/v1/auth/login - Face Recognition Data', function () {
    it('returns face recognition settings and embedding when company has face recognition enabled', function () {
        // Enable face recognition for company
        $this->company->update([
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.6,
        ]);

        // Create face embedding for employee
        \App\Models\EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'embedding_data' => [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ],
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'company' => [
                        'id',
                        'name',
                        'enable_face_recognition',
                        'face_match_threshold',
                    ],
                    'employee' => [
                        'id',
                        'face_enrolled',
                        'face_embedding',
                    ],
                ],
            ]);

        expect($response->json('data.company.enable_face_recognition'))->toBeTrue();
        expect($response->json('data.company.face_match_threshold'))->toBe(0.6);
        expect($response->json('data.employee.face_enrolled'))->toBeTrue();
        expect($response->json('data.employee.face_embedding'))->toBeArray();
        expect($response->json('data.employee.face_embedding.embedding'))->toHaveCount(128);
    });

    it('returns null face_embedding when employee has no face enrolled', function () {
        $this->company->update(['enable_face_recognition' => true]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        expect($response->json('data.employee.face_enrolled'))->toBeFalse();
        expect($response->json('data.employee.face_embedding'))->toBeNull();
    });

    it('does not return face data when company has face recognition disabled', function () {
        $this->company->update(['enable_face_recognition' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        expect($response->json('data.company.enable_face_recognition'))->toBeFalse();
        // face_embedding should be null when disabled
        expect($response->json('data.employee.face_embedding'))->toBeNull();
    });
});

describe('GET /api/v1/auth/profile - Face Recognition Data', function () {
    it('returns face recognition settings and embedding in profile', function () {
        $this->company->update([
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.6,
        ]);

        \App\Models\EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'embedding_data' => [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ],
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'company' => [
                        'id',
                        'name',
                        'enable_face_recognition',
                        'face_match_threshold',
                    ],
                    'employee' => [
                        'face_enrolled',
                        'face_embedding',
                    ],
                ],
            ]);

        expect($response->json('data.employee.face_enrolled'))->toBeTrue();
        expect($response->json('data.employee.face_embedding.embedding'))->toHaveCount(128);
    });

    it('returns null face_embedding when employee not enrolled', function () {
        $this->company->update(['enable_face_recognition' => true]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertOk();
        expect($response->json('data.employee.face_enrolled'))->toBeFalse();
        expect($response->json('data.employee.face_embedding'))->toBeNull();
    });
});

describe('POST /api/v1/auth/change-password', function () {
    it('can change password with valid current password', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password berhasil diubah.',
            ]);

        // Verify can login with new password
        $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@test.com',
            'password' => 'newpassword123',
        ])->assertOk();
    });

    it('returns error with wrong current password', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    });

    it('validates password confirmation', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('validates minimum password length', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});
