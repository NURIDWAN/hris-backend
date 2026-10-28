<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles and permissions
    $this->seed(RolePermissionSeeder::class);

    $this->company = Company::factory()->create([
        'enable_face_recognition' => false,
        'enable_gps_validation' => false,
    ]);

    // Admin user
    $this->adminUser = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'admin@testgajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    setPermissionsTeamId($this->company->id);
    $this->adminUser->assignRole('admin');

    // Regular employee user without admin role
    $this->regularUser = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'regular@testgajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    $this->regularUser->assignRole('employee');

    // Create department and position
    $this->department = Department::create([
        'company_id' => $this->company->id,
        'name' => 'IT Department',
        'code' => 'IT',
    ]);

    $this->position = Position::create([
        'company_id' => $this->company->id,
        'name' => 'Software Engineer',
        'code' => 'SWE',
        'level' => 1,
        'base_salary' => 10000000,
    ]);

    // Create an employee linked to the regular user
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->regularUser->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $this->regularUser->email,
        'is_active' => true,
    ]);
});

describe('POST /api/v1/admin/auth/login', function () {
    it('successfully logs in admin user and returns token', function () {
        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@testgajipro.com',
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
                        'is_superadmin',
                        'roles',
                    ],
                    'company' => [
                        'id',
                        'name',
                    ],
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect($response->json('data.token'))->not->toBeEmpty();
        expect($response->json('data.user.email'))->toBe('admin@testgajipro.com');
    });

    it('rejects invalid password with 401', function () {
        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@testgajipro.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password salah.',
            ]);
    });

    it('rejects inactive user with 403', function () {
        $this->adminUser->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@testgajipro.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun dinonaktifkan. Silakan hubungi superadmin.',
            ]);
    });

    it('rejects user without admin role with 403', function () {
        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'regular@testgajipro.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki hak akses dashboard admin.',
            ]);
    });
});

describe('GET /api/v1/admin/auth/me', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/admin/auth/me');

        $response->assertUnauthorized();
    });

    it('returns current admin profile and company', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'is_superadmin',
                        'roles',
                    ],
                    'company' => [
                        'id',
                        'name',
                    ],
                ],
            ]);

        expect($response->json('data.user.id'))->toBe($this->adminUser->id);
    });
});

describe('POST /api/v1/admin/auth/logout', function () {
    it('revokes the current token', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/admin/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil.',
            ]);
    });
});

describe('GET /api/v1/admin/dashboard/stats', function () {
    it('returns dashboard summary statistics and attendance breakdown', function () {
        Sanctum::actingAs($this->adminUser);

        // Add an office location
        OfficeLocation::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Pusat',
            'code' => 'HQ',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 100,
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/admin/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'stats' => [
                        'total_employees',
                        'present_today',
                        'pending_leaves',
                        'total_payroll_this_month',
                        'attendance_percentage',
                    ],
                    'attendance_today' => [
                        'present',
                        'late',
                        'on_leave',
                        'absent',
                    ],
                    'recent_clock_ins',
                    'office_locations',
                    'attendance_locations',
                ],
            ]);

        expect($response->json('data.stats.total_employees'))->toBeGreaterThanOrEqual(1);
    });
});

describe('GET /api/v1/admin/employees/options', function () {
    it('returns department and position options', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/employees/options');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'departments' => [
                        '*' => ['id', 'name', 'code'],
                    ],
                    'positions' => [
                        '*' => ['id', 'name', 'code', 'level'],
                    ],
                ],
            ]);

        expect($response->json('data.departments'))->not->toBeEmpty();
        expect($response->json('data.positions'))->not->toBeEmpty();
    });
});

describe('GET /api/v1/admin/employees', function () {
    it('returns paginated list of employees', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/employees');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'department',
                        'position',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
    });

    it('filters employees by search name', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/employees?search=John');

        $response->assertOk();
        expect($response->json('meta.total'))->toBe(1);
        expect($response->json('data.0.first_name'))->toBe('John');
    });

    it('filters employees by department id', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson("/api/v1/admin/employees?department_id={$this->department->id}");

        $response->assertOk();
        expect($response->json('meta.total'))->toBe(1);
    });
});

describe('GET /api/v1/admin/employees/{id}', function () {
    it('returns employee detail', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson("/api/v1/admin/employees/{$this->employee->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'department',
                    'position',
                ],
            ]);

        expect($response->json('data.id'))->toBe($this->employee->id);
        expect($response->json('data.first_name'))->toBe('John');
    });

    it('returns 404 for non-existent employee', function () {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/employees/999999');

        $response->assertNotFound();
    });
});
