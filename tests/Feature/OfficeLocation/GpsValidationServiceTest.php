<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Services\GpsValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->service = new GpsValidationService;
});

describe('GpsValidationService', function () {
    describe('validateEmployeeLocation', function () {
        it('returns valid when employee is within assigned office radius', function () {
            // Office at Jakarta coordinates (-6.200000, 106.816666) with 100m radius
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

            // Simulate employee at exact office location
            $result = $this->service->validateEmployeeLocation(
                $this->employee,
                -6.200000,
                106.816666
            );

            expect($result['valid'])->toBeTrue();
            expect($result['office_location_id'])->toBe($officeLocation->id);
            expect($result['distance'])->toBeLessThanOrEqual(100);
        });

        it('returns valid when employee is within any assigned office radius', function () {
            // Create multiple offices
            $jakarta = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Jakarta Office',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => true,
            ]);

            $bandung = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Bandung Office',
                'latitude' => -6.917464,
                'longitude' => 107.619123,
                'radius' => 150,
                'is_active' => true,
            ]);

            // Assign both offices to employee
            $this->employee->officeLocations()->attach([
                $jakarta->id => ['is_primary' => true],
                $bandung->id => ['is_primary' => false],
            ]);

            // Employee at Bandung office (not primary)
            $result = $this->service->validateEmployeeLocation(
                $this->employee,
                -6.917464,
                107.619123
            );

            expect($result['valid'])->toBeTrue();
            expect($result['office_location_id'])->toBe($bandung->id);
        });

        it('returns invalid when employee is outside all assigned office radius', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100, // 100 meters
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

            // Employee at Bandung (far from Jakarta office)
            $result = $this->service->validateEmployeeLocation(
                $this->employee,
                -6.917464,
                107.619123
            );

            expect($result['valid'])->toBeFalse();
            expect($result['office_location_id'])->toBeNull();
            expect($result['reason'])->toBe('outside_radius');
        });

        it('returns invalid when employee has no assigned offices', function () {
            $result = $this->service->validateEmployeeLocation(
                $this->employee,
                -6.200000,
                106.816666
            );

            expect($result['valid'])->toBeFalse();
            expect($result['reason'])->toBe('no_assigned_offices');
        });

        it('returns invalid when all assigned offices are inactive', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
                'is_active' => false,
            ]);

            $this->employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

            $result = $this->service->validateEmployeeLocation(
                $this->employee,
                -6.200000,
                106.816666
            );

            expect($result['valid'])->toBeFalse();
            expect($result['reason'])->toBe('no_active_offices');
        });

        it('returns closest office when within multiple office radii', function () {
            // Two offices close to each other with overlapping radii
            $office1 = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Office A',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 500,
                'is_active' => true,
            ]);

            $office2 = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Office B',
                'latitude' => -6.200200, // Slightly different location
                'longitude' => 106.816866,
                'radius' => 500,
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach([
                $office1->id => ['is_primary' => false],
                $office2->id => ['is_primary' => false],
            ]);

            // Employee closer to office2
            $result = $this->service->validateEmployeeLocation(
                $this->employee,
                -6.200200,
                106.816866
            );

            expect($result['valid'])->toBeTrue();
            expect($result['office_location_id'])->toBe($office2->id);
        });
    });

    describe('getAssignedActiveOffices', function () {
        it('returns only active offices assigned to employee', function () {
            $activeOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            $inactiveOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'is_active' => false,
            ]);

            $this->employee->officeLocations()->attach([
                $activeOffice->id => ['is_primary' => true],
                $inactiveOffice->id => ['is_primary' => false],
            ]);

            $offices = $this->service->getAssignedActiveOffices($this->employee);

            expect($offices)->toHaveCount(1);
            expect($offices->first()->id)->toBe($activeOffice->id);
        });
    });

    describe('findNearestOffice', function () {
        it('returns the nearest office from a list', function () {
            $farOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Far Office',
                'latitude' => -6.917464,
                'longitude' => 107.619123, // Bandung
                'is_active' => true,
            ]);

            $nearOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Near Office',
                'latitude' => -6.200100,
                'longitude' => 106.816700, // Near Jakarta
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach([
                $farOffice->id => ['is_primary' => false],
                $nearOffice->id => ['is_primary' => true],
            ]);

            // Employee at Jakarta
            $result = $this->service->findNearestOffice(
                $this->employee,
                -6.200000,
                106.816666
            );

            expect($result['office']->id)->toBe($nearOffice->id);
            expect($result['distance'])->toBeLessThan(1000); // Less than 1km
        });

        it('returns null when no offices assigned', function () {
            $result = $this->service->findNearestOffice(
                $this->employee,
                -6.200000,
                106.816666
            );

            expect($result)->toBeNull();
        });
    });

    describe('calculateDistance', function () {
        it('calculates distance between two coordinates correctly', function () {
            // Jakarta to Bandung is approximately 140km
            $distance = $this->service->calculateDistance(
                -6.200000, 106.816666, // Jakarta
                -6.917464, 107.619123   // Bandung
            );

            // Should be around 120-160 km (allow some tolerance)
            expect($distance)->toBeGreaterThan(100000);
            expect($distance)->toBeLessThan(180000);
        });

        it('returns zero for same coordinates', function () {
            $distance = $this->service->calculateDistance(
                -6.200000, 106.816666,
                -6.200000, 106.816666
            );

            expect($distance)->toBe(0.0);
        });
    });
});
