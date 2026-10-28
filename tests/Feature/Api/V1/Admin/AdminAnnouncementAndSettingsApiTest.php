<?php

use App\Models\Announcement;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Admin Announcement and Settings API', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'PT Maju Bersama',
            'enable_face_recognition' => false,
            'face_match_threshold' => 0.8,
            'enable_gps_validation' => true,
            'office_radius' => 100,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        Sanctum::actingAs($this->adminUser);
    });

    it('can manage announcements lifecycle', function () {
        // Create announcement
        $res = $this->postJson('/api/v1/admin/announcements', [
            'title' => 'Libur Nasional Idul Fitri',
            'content' => 'Seluruh kantor libur operasional selama cuti bersama.',
            'priority' => 'high',
            'target_audience' => 'all',
            'is_pinned' => true,
            'is_published' => false,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Libur Nasional Idul Fitri')
            ->assertJsonPath('data.is_published', false);

        $announcementId = $res->json('data.id');

        // Publish
        $pubRes = $this->postJson("/api/v1/admin/announcements/{$announcementId}/publish");
        $pubRes->assertStatus(200)
            ->assertJsonPath('data.is_published', true);

        // Unpublish
        $unpubRes = $this->postJson("/api/v1/admin/announcements/{$announcementId}/unpublish");
        $unpubRes->assertStatus(200)
            ->assertJsonPath('data.is_published', false);

        // Update
        $upRes = $this->putJson("/api/v1/admin/announcements/{$announcementId}", [
            'title' => 'Libur Nasional Idul Fitri 1447H',
        ]);
        $upRes->assertStatus(200)
            ->assertJsonPath('data.title', 'Libur Nasional Idul Fitri 1447H');

        // Delete
        $delRes = $this->deleteJson("/api/v1/admin/announcements/{$announcementId}");
        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('announcements', ['id' => $announcementId]);
    });

    it('can manage company profile and logo', function () {
        Storage::fake('public');

        // Show company profile
        $res = $this->getJson('/api/v1/admin/settings/company-profile');
        $res->assertStatus(200)
            ->assertJsonPath('data.name', 'PT Maju Bersama');

        // Update company profile
        $upRes = $this->putJson('/api/v1/admin/settings/company-profile', [
            'name' => 'PT Maju Bersama Sejahtera',
            'email' => 'corporate@majubersama.com',
            'city' => 'Jakarta Selatan',
        ]);
        $upRes->assertStatus(200)
            ->assertJsonPath('data.name', 'PT Maju Bersama Sejahtera')
            ->assertJsonPath('data.city', 'Jakarta Selatan');

        // Upload logo
        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        $logoRes = $this->postJson('/api/v1/admin/settings/company-profile/logo', [
            'logo' => $file,
        ]);
        $logoRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $path = $this->company->fresh()->logo;
        expect($path)->not->toBeNull();
        Storage::disk('public')->assertExists($path);

        // Delete logo
        $delLogoRes = $this->deleteJson('/api/v1/admin/settings/company-profile/logo');
        $delLogoRes->assertStatus(200);
        expect($this->company->fresh()->logo)->toBeNull();
    });

    it('can manage attendance settings', function () {
        $res = $this->getJson('/api/v1/admin/settings/attendance');
        $res->assertStatus(200)
            ->assertJsonPath('data.enable_gps_validation', true)
            ->assertJsonPath('data.office_radius', 100);

        $upRes = $this->putJson('/api/v1/admin/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.85,
            'office_radius' => 150,
        ]);
        $upRes->assertStatus(200)
            ->assertJsonPath('data.enable_face_recognition', true)
            ->assertJsonPath('data.office_radius', 150);
        expect((float) $upRes->json('data.face_match_threshold'))->toEqual(0.85);
    });

    it('can manage admin users and roles with self-protection', function () {
        // List roles
        $rolesRes = $this->getJson('/api/v1/admin/settings/users/roles');
        $rolesRes->assertStatus(200);

        // Create new admin user
        $newUserRes = $this->postJson('/api/v1/admin/settings/users', [
            'name' => 'Staff HR Admin',
            'email' => 'hrstaff@majubersama.com',
            'password' => 'PasswordStaff123!',
            'phone' => '08123456789',
            'roles' => ['admin'],
        ]);
        $newUserRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Staff HR Admin');

        $userId = $newUserRes->json('data.id');

        // Toggle status
        $toggleRes = $this->patchJson("/api/v1/admin/settings/users/{$userId}/toggle-status");
        $toggleRes->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Self-toggle prevention
        $selfToggleRes = $this->patchJson("/api/v1/admin/settings/users/{$this->adminUser->id}/toggle-status");
        $selfToggleRes->assertStatus(422);

        // Self-delete prevention
        $selfDelRes = $this->deleteJson("/api/v1/admin/settings/users/{$this->adminUser->id}");
        $selfDelRes->assertStatus(422);

        // Delete user
        $delRes = $this->deleteJson("/api/v1/admin/settings/users/{$userId}");
        $delRes->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $userId]);
    });
});
