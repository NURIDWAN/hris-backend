<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
});

describe('OfficeLocation Model', function () {
    describe('factory', function () {
        it('can create an office location', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
            ]);

            expect($officeLocation)->toBeInstanceOf(OfficeLocation::class)
                ->and($officeLocation->company_id)->toBe($this->company->id)
                ->and($officeLocation->name)->toBeString()
                ->and($officeLocation->code)->toBeString()
                ->and($officeLocation->latitude)->toBeFloat()
                ->and($officeLocation->longitude)->toBeFloat()
                ->and($officeLocation->radius)->toBeInt()
                ->and($officeLocation->is_active)->toBeBool();
        });

        it('can create inactive office location', function () {
            $officeLocation = OfficeLocation::factory()->inactive()->create([
                'company_id' => $this->company->id,
            ]);

            expect($officeLocation->is_active)->toBeFalse();
        });

        it('can create headquarters office location', function () {
            $officeLocation = OfficeLocation::factory()->headquarters()->create([
                'company_id' => $this->company->id,
            ]);

            expect($officeLocation->is_headquarters)->toBeTrue();
        });
    });

    describe('relationships', function () {
        it('belongs to a company', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
            ]);

            expect($officeLocation->company)->toBeInstanceOf(Company::class)
                ->and($officeLocation->company->id)->toBe($this->company->id);
        });

        it('can have many employees', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $employees = Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
            ]);

            $officeLocation->employees()->attach($employees->pluck('id'));

            expect($officeLocation->employees)->toHaveCount(3)
                ->and($officeLocation->employees->first())->toBeInstanceOf(Employee::class);
        });

        it('can track primary office in pivot', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $officeLocation->employees()->attach($employee->id, ['is_primary' => true]);

            expect($officeLocation->employees->first()->pivot->is_primary)->toBeTrue();
        });
    });

    describe('scopes', function () {
        it('can filter active office locations', function () {
            OfficeLocation::factory()->count(2)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);
            OfficeLocation::factory()->inactive()->create([
                'company_id' => $this->company->id,
            ]);

            $activeLocations = OfficeLocation::active()->get();

            expect($activeLocations)->toHaveCount(2);
        });

        it('can filter headquarters', function () {
            OfficeLocation::factory()->headquarters()->create([
                'company_id' => $this->company->id,
            ]);
            OfficeLocation::factory()->count(2)->create([
                'company_id' => $this->company->id,
                'is_headquarters' => false,
            ]);

            $headquarters = OfficeLocation::headquarters()->get();

            expect($headquarters)->toHaveCount(1)
                ->and($headquarters->first()->is_headquarters)->toBeTrue();
        });

        it('can filter by company', function () {
            $otherCompany = Company::factory()->create();

            OfficeLocation::factory()->count(2)->create([
                'company_id' => $this->company->id,
            ]);
            OfficeLocation::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $companyLocations = OfficeLocation::where('company_id', $this->company->id)->get();

            expect($companyLocations)->toHaveCount(2);
        });
    });

    describe('casts', function () {
        it('casts latitude as float', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
            ]);

            expect($officeLocation->latitude)->toBeFloat();
        });

        it('casts longitude as float', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'longitude' => 106.816666,
            ]);

            expect($officeLocation->longitude)->toBeFloat();
        });

        it('casts radius as integer', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'radius' => 100,
            ]);

            expect($officeLocation->radius)->toBeInt();
        });

        it('casts is_active as boolean', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'is_active' => 1,
            ]);

            expect($officeLocation->is_active)->toBeBool();
        });

        it('casts is_headquarters as boolean', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'is_headquarters' => 1,
            ]);

            expect($officeLocation->is_headquarters)->toBeBool();
        });
    });

    describe('methods', function () {
        it('can calculate distance to coordinates', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100,
            ]);

            // Same location should be 0
            $distance = $officeLocation->distanceTo(-6.200000, 106.816666);
            expect($distance)->toBe(0.0);

            // Different location should be > 0
            $distance = $officeLocation->distanceTo(-6.201000, 106.817000);
            expect($distance)->toBeGreaterThan(0);
        });

        it('can check if coordinates are within radius', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius' => 100, // 100 meters
            ]);

            // Same location should be within radius
            expect($officeLocation->isWithinRadius(-6.200000, 106.816666))->toBeTrue();

            // Far location should be outside radius
            expect($officeLocation->isWithinRadius(-6.300000, 106.900000))->toBeFalse();
        });

        it('returns full address', function () {
            $officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'address' => 'Jl. Sudirman No. 1',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
            ]);

            expect($officeLocation->full_address)->toBe('Jl. Sudirman No. 1, Jakarta, DKI Jakarta');
        });
    });

    describe('validation', function () {
        it('requires unique code per company', function () {
            OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'code' => 'HQ',
            ]);

            // Same code, same company should fail
            expect(fn () => OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'code' => 'HQ',
            ]))->toThrow(\Illuminate\Database\QueryException::class);
        });

        it('allows same code in different companies', function () {
            $otherCompany = Company::factory()->create();

            $office1 = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'code' => 'HQ',
            ]);

            $office2 = OfficeLocation::factory()->create([
                'company_id' => $otherCompany->id,
                'code' => 'HQ',
            ]);

            expect($office1->code)->toBe('HQ')
                ->and($office2->code)->toBe('HQ');
        });
    });
});

describe('Company OfficeLocations Relationship', function () {
    it('company has many office locations', function () {
        $company = Company::factory()->create();
        OfficeLocation::factory()->count(3)->create([
            'company_id' => $company->id,
        ]);

        expect($company->officeLocations)->toHaveCount(3);
    });
});

describe('Employee OfficeLocations Relationship', function () {
    it('employee can be assigned to multiple office locations', function () {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
        ]);

        $offices = OfficeLocation::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        $employee->officeLocations()->attach($offices->pluck('id'));

        expect($employee->officeLocations)->toHaveCount(2);
    });

    it('employee can have a primary office location', function () {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
        ]);

        $primaryOffice = OfficeLocation::factory()->create([
            'company_id' => $company->id,
        ]);
        $secondaryOffice = OfficeLocation::factory()->create([
            'company_id' => $company->id,
        ]);

        $employee->officeLocations()->attach([
            $primaryOffice->id => ['is_primary' => true],
            $secondaryOffice->id => ['is_primary' => false],
        ]);

        expect($employee->primaryOfficeLocation->id)->toBe($primaryOffice->id);
    });
});
