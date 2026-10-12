<?php

/**
 * Tests for HRD Employee-Office Assignment API.
 *
 * Flow:
 * - HRD/Admin can assign employees to multiple office locations
 * - Employee can only clock in/out at assigned offices
 * - One office can be marked as primary
 */

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();

    // Create roles
    Role::create(['name' => 'hr-manager', 'guard_name' => 'web']);
    Role::create(['name' => 'employee', 'guard_name' => 'web']);

    // HR Manager user
    $this->hrUser = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->hrUser->assignRole('hr-manager');
    $this->hrEmployee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->hrUser->id,
    ]);

    // Regular employee user
    $this->employeeUser = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employeeUser->assignRole('employee');
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->employeeUser->id,
    ]);

    // Office locations
    $this->officeA = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Office A',
        'is_active' => true,
    ]);
    $this->officeB = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Office B',
        'is_active' => true,
    ]);
    $this->officeC = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Office C',
        'is_active' => true,
    ]);
});

describe('Employee Office Assignment API - HRD Access', function () {
    describe('GET /api/v1/employees/{employee}/offices', function () {
        it('allows HR to view employee assigned offices', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            // Assign offices to employee
            $this->employee->officeLocations()->attach([
                $this->officeA->id => ['is_primary' => true],
                $this->officeB->id => ['is_primary' => false],
            ]);

            $response = $this->getJson("/api/v1/employees/{$this->employee->id}/offices");

            $response->assertSuccessful()
                ->assertJsonCount(2, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'code',
                            'address',
                            'latitude',
                            'longitude',
                            'radius',
                            'is_primary',
                        ],
                    ],
                ]);
        });

        it('returns empty array when employee has no assigned offices', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $response = $this->getJson("/api/v1/employees/{$this->employee->id}/offices");

            $response->assertSuccessful()
                ->assertJsonCount(0, 'data');
        });

        it('returns 404 for employee from different company', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->getJson("/api/v1/employees/{$otherEmployee->id}/offices");

            $response->assertNotFound();
        });

        it('denies regular employee from viewing other employee offices', function () {
            Sanctum::actingAs($this->employeeUser, ['*']);

            // Create another employee
            $anotherEmployee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->getJson("/api/v1/employees/{$anotherEmployee->id}/offices");

            $response->assertForbidden();
        });

        it('allows employee to view their own assigned offices', function () {
            Sanctum::actingAs($this->employeeUser, ['*']);

            $this->employee->officeLocations()->attach($this->officeA->id, ['is_primary' => true]);

            $response = $this->getJson("/api/v1/employees/{$this->employee->id}/offices");

            $response->assertSuccessful()
                ->assertJsonCount(1, 'data');
        });
    });

    describe('POST /api/v1/employees/{employee}/offices', function () {
        it('allows HR to assign offices to employee', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => [$this->officeA->id, $this->officeB->id],
                'primary_office_id' => $this->officeA->id,
            ]);

            $response->assertSuccessful()
                ->assertJsonPath('message', 'Office locations berhasil di-assign.');

            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeA->id,
                'is_primary' => true,
            ]);

            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeB->id,
                'is_primary' => false,
            ]);
        });

        it('replaces existing assignments when re-assigning', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            // Initial assignment
            $this->employee->officeLocations()->attach([
                $this->officeA->id => ['is_primary' => true],
                $this->officeB->id => ['is_primary' => false],
            ]);

            // Re-assign to different offices
            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => [$this->officeC->id],
                'primary_office_id' => $this->officeC->id,
            ]);

            $response->assertSuccessful();

            // Old assignments should be removed
            $this->assertDatabaseMissing('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeA->id,
            ]);

            // New assignment should exist
            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeC->id,
                'is_primary' => true,
            ]);
        });

        it('sets first office as primary if primary_office_id not provided', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => [$this->officeA->id, $this->officeB->id],
            ]);

            $response->assertSuccessful();

            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeA->id,
                'is_primary' => true,
            ]);
        });

        it('validates office_location_ids is required', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['office_location_ids']);
        });

        it('validates office_location_ids must be array', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => 'not-an-array',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['office_location_ids']);
        });

        it('validates office locations exist and belong to same company', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $otherCompany = Company::factory()->create();
            $otherOffice = OfficeLocation::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => [$otherOffice->id],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['office_location_ids.0']);
        });

        it('validates primary_office_id must be in office_location_ids', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => [$this->officeA->id],
                'primary_office_id' => $this->officeB->id,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['primary_office_id']);
        });

        it('denies regular employee from assigning offices', function () {
            Sanctum::actingAs($this->employeeUser, ['*']);

            $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offices", [
                'office_location_ids' => [$this->officeA->id],
            ]);

            $response->assertForbidden();
        });

        it('returns 404 for employee from different company', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->postJson("/api/v1/employees/{$otherEmployee->id}/offices", [
                'office_location_ids' => [$this->officeA->id],
            ]);

            $response->assertNotFound();
        });
    });

    describe('DELETE /api/v1/employees/{employee}/offices', function () {
        it('allows HR to remove all office assignments', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $this->employee->officeLocations()->attach([
                $this->officeA->id => ['is_primary' => true],
                $this->officeB->id => ['is_primary' => false],
            ]);

            $response = $this->deleteJson("/api/v1/employees/{$this->employee->id}/offices");

            $response->assertSuccessful()
                ->assertJsonPath('message', 'Semua office locations berhasil dihapus.');

            $this->assertDatabaseMissing('employee_office_locations', [
                'employee_id' => $this->employee->id,
            ]);
        });

        it('denies regular employee from removing offices', function () {
            Sanctum::actingAs($this->employeeUser, ['*']);

            $response = $this->deleteJson("/api/v1/employees/{$this->employee->id}/offices");

            $response->assertForbidden();
        });
    });

    describe('DELETE /api/v1/employees/{employee}/offices/{office}', function () {
        it('allows HR to remove single office assignment', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $this->employee->officeLocations()->attach([
                $this->officeA->id => ['is_primary' => true],
                $this->officeB->id => ['is_primary' => false],
            ]);

            $response = $this->deleteJson("/api/v1/employees/{$this->employee->id}/offices/{$this->officeB->id}");

            $response->assertSuccessful();

            // Office B should be removed
            $this->assertDatabaseMissing('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeB->id,
            ]);

            // Office A should still exist
            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeA->id,
            ]);
        });

        it('promotes another office to primary when removing primary office', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $this->employee->officeLocations()->attach([
                $this->officeA->id => ['is_primary' => true],
                $this->officeB->id => ['is_primary' => false],
            ]);

            $response = $this->deleteJson("/api/v1/employees/{$this->employee->id}/offices/{$this->officeA->id}");

            $response->assertSuccessful();

            // Office B should now be primary
            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeB->id,
                'is_primary' => true,
            ]);
        });
    });

    describe('PATCH /api/v1/employees/{employee}/offices/{office}/set-primary', function () {
        it('allows HR to set office as primary', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $this->employee->officeLocations()->attach([
                $this->officeA->id => ['is_primary' => true],
                $this->officeB->id => ['is_primary' => false],
            ]);

            $response = $this->patchJson("/api/v1/employees/{$this->employee->id}/offices/{$this->officeB->id}/set-primary");

            $response->assertSuccessful()
                ->assertJsonPath('message', 'Office location berhasil dijadikan primary.');

            // Office B should now be primary
            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeB->id,
                'is_primary' => true,
            ]);

            // Office A should no longer be primary
            $this->assertDatabaseHas('employee_office_locations', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeA->id,
                'is_primary' => false,
            ]);
        });

        it('returns 404 if office not assigned to employee', function () {
            Sanctum::actingAs($this->hrUser, ['*']);

            $this->employee->officeLocations()->attach($this->officeA->id, ['is_primary' => true]);

            $response = $this->patchJson("/api/v1/employees/{$this->employee->id}/offices/{$this->officeB->id}/set-primary");

            $response->assertNotFound();
        });
    });
});

describe('GPS Validation with Multiple Offices', function () {
    it('validates GPS against all assigned offices', function () {
        Sanctum::actingAs($this->employeeUser, ['*']);

        // Assign employee to multiple offices
        $this->employee->officeLocations()->attach([
            $this->officeA->id => ['is_primary' => true],
            $this->officeB->id => ['is_primary' => false],
        ]);

        // Update office B coordinates
        $this->officeB->update([
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius' => 100,
        ]);

        // Validate at office B location
        $response = $this->postJson('/api/v1/office-locations/validate-gps', [
            'latitude' => -6.917464,
            'longitude' => 107.619123,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.office_location_id', $this->officeB->id);
    });

    it('returns invalid when not at any assigned office', function () {
        Sanctum::actingAs($this->employeeUser, ['*']);

        $this->employee->officeLocations()->attach($this->officeA->id, ['is_primary' => true]);

        // Random location far from office
        $response = $this->postJson('/api/v1/office-locations/validate-gps', [
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', 'outside_radius');
    });

    it('returns no_assigned_offices when employee has no offices', function () {
        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->postJson('/api/v1/office-locations/validate-gps', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', 'no_assigned_offices');
    });
});
