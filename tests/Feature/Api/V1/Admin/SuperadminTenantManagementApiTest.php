<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    // Tenant A
    $this->companyA = Company::factory()->create([
        'name' => 'PT Perusahaan A',
        'slug' => 'pt-perusahaan-a',
        'subscription_plan' => 'enterprise',
        'is_active' => true,
    ]);

    // Tenant B
    $this->companyB = Company::factory()->create([
        'name' => 'PT Perusahaan B',
        'slug' => 'pt-perusahaan-b',
        'subscription_plan' => 'pro',
        'is_active' => true,
    ]);

    // Superadmin user
    $this->superAdmin = User::factory()->create([
        'name' => 'Super Administrator',
        'email' => 'superadmin@gajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
        'is_superadmin' => true,
        'company_id' => null,
    ]);
    $this->superAdmin->assignRole('super-admin');

    // Admin of Company A
    $this->adminA = User::factory()->create([
        'name' => 'Admin Perusahaan A',
        'email' => 'admin.a@gajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
        'is_superadmin' => false,
        'company_id' => $this->companyA->id,
    ]);
    setPermissionsTeamId($this->companyA->id);
    $this->adminA->assignRole('admin');

    // Regular Employee of Company A
    $this->employeeA = User::factory()->create([
        'name' => 'Karyawan Perusahaan A',
        'email' => 'karyawan.a@gajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
        'is_superadmin' => false,
        'company_id' => $this->companyA->id,
    ]);
    $this->employeeA->assignRole('employee');

    // Department in Company A
    $this->deptA = Department::create([
        'company_id' => $this->companyA->id,
        'name' => 'Engineering Perusahaan A',
        'code' => 'ENG-A',
    ]);

    // Department in Company B
    $this->deptB = Department::create([
        'company_id' => $this->companyB->id,
        'name' => 'Finance Perusahaan B',
        'code' => 'FIN-B',
    ]);
});

it('allows superadmin to list all companies with aggregate metadata', function () {
    Sanctum::actingAs($this->superAdmin);

    $response = $this->getJson('/api/v1/admin/superadmin/companies');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Daftar perusahaan berhasil diambil',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'slug', 'subscription_plan', 'is_active', 'employees_count', 'users_count'],
            ],
            'meta' => [
                'current_page',
                'total',
                'total_companies',
                'active_companies',
                'plans_breakdown',
            ],
        ]);
});

it('allows superadmin to retrieve lightweight company options for dropdown switcher', function () {
    Sanctum::actingAs($this->superAdmin);

    $response = $this->getJson('/api/v1/admin/superadmin/companies/options');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Daftar opsi perusahaan berhasil diambil',
        ]);

    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(2);
});

it('allows superadmin to create a new tenant with initial admin account', function () {
    Sanctum::actingAs($this->superAdmin);

    $payload = [
        'name' => 'PT Mitra Baru Jaya',
        'slug' => 'pt-mitra-baru-jaya',
        'email' => 'contact@mitrabaru.com',
        'phone' => '081234567890',
        'address' => 'Jl. Sudirman No. 45, Jakarta',
        'subscription_plan' => 'pro',
        'max_employees' => 100,
        'create_admin_account' => true,
        'admin_name' => 'Admin Mitra Baru',
        'admin_email' => 'admin@mitrabaru.com',
        'admin_password' => 'secret12345',
    ];

    $response = $this->postJson('/api/v1/admin/superadmin/companies', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Perusahaan baru berhasil didaftarkan',
            'data' => [
                'name' => 'PT Mitra Baru Jaya',
                'slug' => 'pt-mitra-baru-jaya',
                'subscription_plan' => 'pro',
            ],
        ]);

    $newCompanyId = $response->json('data.id');
    expect(Company::where('id', $newCompanyId)->exists())->toBeTrue();
    expect(User::where('email', 'admin@mitrabaru.com')->where('company_id', $newCompanyId)->exists())->toBeTrue();
});

it('allows superadmin to view and update company details', function () {
    Sanctum::actingAs($this->superAdmin);

    // Show
    $showRes = $this->getJson("/api/v1/admin/superadmin/companies/{$this->companyA->id}");
    $showRes->assertStatus(200)
        ->assertJsonPath('data.id', $this->companyA->id);

    // Update
    $updateRes = $this->putJson("/api/v1/admin/superadmin/companies/{$this->companyA->id}", [
        'name' => 'PT Perusahaan A Updated',
        'subscription_plan' => 'enterprise',
        'max_employees' => 250,
    ]);

    $updateRes->assertStatus(200)
        ->assertJsonPath('data.name', 'PT Perusahaan A Updated')
        ->assertJsonPath('data.max_employees', 250);
});

it('allows superadmin to toggle tenant active status', function () {
    Sanctum::actingAs($this->superAdmin);

    expect($this->companyA->is_active)->toBeTrue();

    $response = $this->postJson("/api/v1/admin/superadmin/companies/{$this->companyA->id}/toggle-status");
    $response->assertStatus(200);

    $this->companyA->refresh();
    expect($this->companyA->is_active)->toBeFalse();
});

it('rejects company admin and employee from accessing superadmin endpoints with 403', function () {
    // Admin perusahaan
    Sanctum::actingAs($this->adminA);
    $this->getJson('/api/v1/admin/superadmin/companies')
        ->assertStatus(403);
    $this->getJson('/api/v1/admin/superadmin/companies/options')
        ->assertStatus(403);
    $this->postJson('/api/v1/admin/superadmin/companies', ['name' => 'Hacker Inc', 'subscription_plan' => 'trial'])
        ->assertStatus(403);

    // Regular employee
    Sanctum::actingAs($this->employeeA);
    $this->getJson('/api/v1/admin/superadmin/companies')
        ->assertStatus(403);
});

it('allows superadmin to switch context via X-Company-Id header while isolating company admins', function () {
    // 1. Superadmin switches context to Company B
    Sanctum::actingAs($this->superAdmin);
    $saResponse = $this->withHeader('X-Company-Id', (string) $this->companyB->id)
        ->getJson('/api/v1/admin/departments');

    $saResponse->assertStatus(200);
    $saDeptNames = collect($saResponse->json('data'))->pluck('name')->all();
    expect($saDeptNames)->toContain('Finance Perusahaan B');
    expect($saDeptNames)->not->toContain('Engineering Perusahaan A');

    // 2. Admin of Company A tries to pass X-Company-Id of Company B
    Sanctum::actingAs($this->adminA);
    $adminResponse = $this->withHeader('X-Company-Id', (string) $this->companyB->id)
        ->getJson('/api/v1/admin/departments');

    $adminResponse->assertStatus(200);
    $adminDeptNames = collect($adminResponse->json('data'))->pluck('name')->all();
    // Must remain strictly locked to Company A!
    expect($adminDeptNames)->toContain('Engineering Perusahaan A');
    expect($adminDeptNames)->not->toContain('Finance Perusahaan B');
});
