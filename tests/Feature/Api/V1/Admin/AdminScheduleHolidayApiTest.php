<?php

use App\Models\Company;
use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->company = Company::factory()->create([
        'enable_face_recognition' => false,
        'enable_gps_validation' => false,
    ]);

    $this->adminUser = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'admin@testgajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    setPermissionsTeamId($this->company->id);
    $this->adminUser->assignRole('admin');

    Sanctum::actingAs($this->adminUser);
});

describe('Admin Work Schedule API', function () {
    it('can create, show, update, and delete work schedules', function () {
        // Create
        $response = $this->postJson('/api/v1/admin/work-schedules', [
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
            'working_days' => [1, 2, 3, 4, 5, 6],
            'late_tolerance' => 10,
            'is_default' => true,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Shift Pagi')
            ->assertJsonPath('data.is_default', true);

        $id = $response->json('data.id');

        // Show
        $showRes = $this->getJson("/api/v1/admin/work-schedules/{$id}");
        $showRes->assertStatus(200)->assertJsonPath('data.code', 'PAGI');

        // Update
        $updateRes = $this->putJson("/api/v1/admin/work-schedules/{$id}", [
            'name' => 'Shift Pagi Fleksibel',
            'late_tolerance' => 15,
        ]);
        $updateRes->assertStatus(200)->assertJsonPath('data.name', 'Shift Pagi Fleksibel');

        // Delete
        $delRes = $this->deleteJson("/api/v1/admin/work-schedules/{$id}");
        $delRes->assertStatus(200)->assertJsonPath('success', true);
    });
});

describe('Admin Holiday API', function () {
    it('can create, update, delete, and auto-generate holidays', function () {
        // Create manual holiday
        $response = $this->postJson('/api/v1/admin/holidays', [
            'name' => 'HUT Perusahaan',
            'date' => '2026-11-10',
            'type' => 'company',
            'description' => 'Ulang tahun perusahaan',
            'is_recurring' => true,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'HUT Perusahaan');

        $holidayId = $response->json('data.id');

        // Update
        $updRes = $this->putJson("/api/v1/admin/holidays/{$holidayId}", [
            'name' => 'HUT Perusahaan Ke-10',
        ]);
        $updRes->assertStatus(200)->assertJsonPath('data.name', 'HUT Perusahaan Ke-10');

        // Auto-generate national holidays
        $genRes = $this->postJson('/api/v1/admin/holidays/generate', [
            'year' => 2026,
        ]);
        $genRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.year', 2026);

        expect(Holiday::where('company_id', $this->company->id)->whereYear('date', 2026)->count())->toBeGreaterThan(3);

        // Delete manual holiday
        $delRes = $this->deleteJson("/api/v1/admin/holidays/{$holidayId}");
        $delRes->assertStatus(200)->assertJsonPath('success', true);
    });
});
