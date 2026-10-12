<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_face_recognition' => true,
        'face_match_threshold' => 0.75,
        'enable_gps_validation' => true,
        'office_radius' => 100,
    ]);
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');
});

describe('Attendance Setting Index', function () {
    it('displays attendance settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('settings.attendance.index');
        $response->assertSee('Pengaturan Absensi');
    });

    it('shows current attendance settings', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/attendance');

        $response->assertStatus(200);
        $response->assertSee('Face Recognition');
        $response->assertSee('Validasi GPS');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/settings/attendance');

        $response->assertRedirect('/login');
    });

    it('denies access to employee role', function () {
        $employee = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee->assignRole('employee');

        $this->actingAs($employee);

        $response = $this->get('/settings/attendance');

        $response->assertRedirect('/portal');
    });
});

describe('Update Attendance Settings', function () {
    it('can update face recognition settings', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.8,
            'enable_gps_validation' => true,
            'office_radius' => 150,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->company->refresh();
        expect($this->company->face_match_threshold)->toBe(0.8);
        expect($this->company->office_radius)->toBe(150);
    });

    it('can disable face recognition', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => false,
            'face_match_threshold' => 0.75,
            'enable_gps_validation' => true,
            'office_radius' => 100,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->company->refresh();
        expect($this->company->enable_face_recognition)->toBeFalse();
    });

    it('can disable gps validation', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.75,
            'enable_gps_validation' => false,
            'office_radius' => 100,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->company->refresh();
        expect($this->company->enable_gps_validation)->toBeFalse();
    });

    it('validates face match threshold minimum', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.3, // below minimum 0.5
            'enable_gps_validation' => true,
            'office_radius' => 100,
        ]);

        $response->assertSessionHasErrors(['face_match_threshold']);
    });

    it('validates face match threshold maximum', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 1.5, // above maximum 1
            'enable_gps_validation' => true,
            'office_radius' => 100,
        ]);

        $response->assertSessionHasErrors(['face_match_threshold']);
    });

    it('validates office radius minimum', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.75,
            'enable_gps_validation' => true,
            'office_radius' => 5, // below minimum 10
        ]);

        $response->assertSessionHasErrors(['office_radius']);
    });

    it('validates office radius maximum', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', [
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.75,
            'enable_gps_validation' => true,
            'office_radius' => 10000, // above maximum 5000
        ]);

        $response->assertSessionHasErrors(['office_radius']);
    });

    it('requires all fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/attendance', []);

        $response->assertSessionHasErrors([
            'enable_face_recognition',
            'face_match_threshold',
            'enable_gps_validation',
            'office_radius',
        ]);
    });
});
