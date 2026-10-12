<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);
});

describe('Office Location API', function () {
    describe('GET /api/v1/office-locations', function () {
        it('returns all active office locations for the company', function () {
            Sanctum::actingAs($this->user, ['*']);

            $activeOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Active Office',
                'is_active' => true,
            ]);

            $inactiveOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Inactive Office',
                'is_active' => false,
            ]);

            $response = $this->getJson('/api/v1/office-locations');

            $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Active Office');
        });

        it('does not return office locations from other companies', function () {
            Sanctum::actingAs($this->user, ['*']);

            $otherCompany = Company::factory()->create();

            OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'My Office',
                'is_active' => true,
            ]);

            OfficeLocation::factory()->create([
                'company_id' => $otherCompany->id,
                'name' => 'Other Company Office',
                'is_active' => true,
            ]);

            $response = $this->getJson('/api/v1/office-locations');

            $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'My Office');
        });

        it('returns 401 when not authenticated', function () {
            $response = $this->getJson('/api/v1/office-locations');

            $response->assertUnauthorized();
        });
    });

    describe('GET /api/v1/office-locations/assigned', function () {
        it('returns office locations assigned to the employee', function () {
            Sanctum::actingAs($this->user, ['*']);

            $assignedOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Assigned Office',
                'is_active' => true,
            ]);

            $unassignedOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Unassigned Office',
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($assignedOffice->id, ['is_primary' => true]);

            $response = $this->getJson('/api/v1/office-locations/assigned');

            $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Assigned Office')
                ->assertJsonPath('data.0.is_primary', true);
        });

        it('includes is_primary flag in response', function () {
            Sanctum::actingAs($this->user, ['*']);

            $primaryOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Primary Office',
                'is_active' => true,
            ]);

            $secondaryOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Secondary Office',
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach([
                $primaryOffice->id => ['is_primary' => true],
                $secondaryOffice->id => ['is_primary' => false],
            ]);

            $response = $this->getJson('/api/v1/office-locations/assigned');

            $response->assertOk()
                ->assertJsonCount(2, 'data');

            $data = $response->json('data');
            $primary = collect($data)->firstWhere('name', 'Primary Office');
            $secondary = collect($data)->firstWhere('name', 'Secondary Office');

            expect($primary['is_primary'])->toBeTrue();
            expect($secondary['is_primary'])->toBeFalse();
        });

        it('returns empty array when no offices assigned', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->getJson('/api/v1/office-locations/assigned');

            $response->assertOk()
                ->assertJsonCount(0, 'data');
        });

        it('returns 404 when user has no associated employee', function () {
            $userWithoutEmployee = User::factory()->create([
                'company_id' => $this->company->id,
            ]);

            Sanctum::actingAs($userWithoutEmployee, ['*']);

            $response = $this->getJson('/api/v1/office-locations/assigned');

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/office-locations/{id}', function () {
        it('returns a single office location', function () {
            Sanctum::actingAs($this->user, ['*']);

            $office = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Jakarta HQ',
                'code' => 'JKT-HQ',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => true,
            ]);

            $response = $this->getJson("/api/v1/office-locations/{$office->id}");

            $response->assertOk()
                ->assertJsonPath('data.name', 'Jakarta HQ')
                ->assertJsonPath('data.code', 'JKT-HQ')
                ->assertJsonPath('data.latitude', -6.200000)
                ->assertJsonPath('data.longitude', 106.816666)
                ->assertJsonPath('data.radius', 100);
        });

        it('returns 404 for office location from other company', function () {
            Sanctum::actingAs($this->user, ['*']);

            $otherCompany = Company::factory()->create();
            $office = OfficeLocation::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->getJson("/api/v1/office-locations/{$office->id}");

            $response->assertNotFound();
        });
    });

    describe('POST /api/v1/office-locations/validate-gps', function () {
        it('validates employee GPS location against assigned offices', function () {
            Sanctum::actingAs($this->user, ['*']);

            $office = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($office->id, ['is_primary' => true]);

            $response = $this->postJson('/api/v1/office-locations/validate-gps', [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
            ]);

            $response->assertOk()
                ->assertJsonPath('data.valid', true)
                ->assertJsonPath('data.office_location_id', $office->id);
        });

        it('returns invalid when outside all office radii', function () {
            Sanctum::actingAs($this->user, ['*']);

            $office = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($office->id, ['is_primary' => true]);

            // Bandung coordinates - far from Jakarta
            $response = $this->postJson('/api/v1/office-locations/validate-gps', [
                'latitude' => -6.917464,
                'longitude' => 107.619123,
            ]);

            $response->assertOk()
                ->assertJsonPath('data.valid', false)
                ->assertJsonPath('data.reason', 'outside_radius');
        });

        it('validates request data', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/office-locations/validate-gps', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['latitude', 'longitude']);
        });

        it('validates latitude range', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/office-locations/validate-gps', [
                'latitude' => 100, // Invalid
                'longitude' => 106.816666,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['latitude']);
        });

        it('validates longitude range', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/office-locations/validate-gps', [
                'latitude' => -6.200000,
                'longitude' => 200, // Invalid
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['longitude']);
        });
    });
});
