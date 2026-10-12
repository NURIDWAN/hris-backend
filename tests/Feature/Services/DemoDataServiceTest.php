<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\WorkSchedule;
use App\Services\DemoDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DemoDataService', function () {

    describe('seedDemoData', function () {

        it('creates office location (headquarters) for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            // Should create at least one office location
            expect(OfficeLocation::where('company_id', $company->id)->count())->toBeGreaterThanOrEqual(1);

            // Should have a headquarters
            $headquarters = OfficeLocation::where('company_id', $company->id)
                ->where('is_headquarters', true)
                ->first();

            expect($headquarters)->not->toBeNull()
                ->and($headquarters->name)->toBe('Kantor Pusat')
                ->and($headquarters->code)->toBe('HQ')
                ->and($headquarters->is_active)->toBeTrue()
                ->and($headquarters->latitude)->not->toBeNull()
                ->and($headquarters->longitude)->not->toBeNull()
                ->and($headquarters->radius)->toBeGreaterThan(0);
        });

        it('creates departments for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            expect(Department::where('company_id', $company->id)->count())->toBe(5);

            // Check specific departments
            $departmentCodes = Department::where('company_id', $company->id)->pluck('code')->toArray();
            expect($departmentCodes)->toContain('HR', 'FIN', 'IT', 'MKT', 'OPS');
        });

        it('creates positions for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            // Should create at least 12 positions
            expect(Position::where('company_id', $company->id)->count())->toBeGreaterThanOrEqual(12);
        });

        it('creates work schedules for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            expect(WorkSchedule::where('company_id', $company->id)->count())->toBe(2);

            // Should have a default schedule
            $defaultSchedule = WorkSchedule::where('company_id', $company->id)
                ->where('is_default', true)
                ->first();

            expect($defaultSchedule)->not->toBeNull();
        });

        it('creates leave types for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            expect(LeaveType::where('company_id', $company->id)->count())->toBe(5);
        });

        it('creates salary components for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            expect(SalaryComponent::where('company_id', $company->id)->count())->toBe(5);
        });

        it('creates demo employees for company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            expect(Employee::where('company_id', $company->id)->count())->toBe(10);
        });

        it('assigns all employees to office location', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            $officeLocation = OfficeLocation::where('company_id', $company->id)
                ->where('is_headquarters', true)
                ->first();

            $employees = Employee::where('company_id', $company->id)->get();

            foreach ($employees as $employee) {
                expect($employee->officeLocations)->not->toBeEmpty();
                expect($employee->officeLocations->contains($officeLocation))->toBeTrue();
            }
        });

        it('sets is_demo_mode to true on company', function () {
            $company = Company::factory()->create(['is_demo_mode' => false]);

            $service = new DemoDataService;
            $service->seedDemoData($company);

            $company->refresh();
            expect($company->is_demo_mode)->toBeTrue()
                ->and($company->demo_started_at)->not->toBeNull();
        });

    });

    describe('resetToProduction', function () {

        it('deletes all demo data but keeps company', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            // Verify data exists
            expect(Employee::where('company_id', $company->id)->count())->toBeGreaterThan(0);
            expect(Department::where('company_id', $company->id)->count())->toBeGreaterThan(0);
            expect(OfficeLocation::where('company_id', $company->id)->count())->toBeGreaterThan(0);

            // Reset to production
            $service->resetToProduction($company);

            // All data should be deleted
            expect(Employee::where('company_id', $company->id)->count())->toBe(0);
            expect(Department::where('company_id', $company->id)->count())->toBe(0);

            // Company should still exist
            expect(Company::find($company->id))->not->toBeNull();

            // Demo mode should be false
            $company->refresh();
            expect($company->is_demo_mode)->toBeFalse()
                ->and($company->demo_started_at)->toBeNull();
        });

        it('deletes office locations when resetting', function () {
            $company = Company::factory()->create();

            $service = new DemoDataService;
            $service->seedDemoData($company);

            expect(OfficeLocation::where('company_id', $company->id)->count())->toBeGreaterThan(0);

            $service->resetToProduction($company);

            expect(OfficeLocation::where('company_id', $company->id)->count())->toBe(0);
        });

    });

});
