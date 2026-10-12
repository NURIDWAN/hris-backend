<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\WorkSchedule;
use Database\Seeders\GemilangCompanySeeder;
use Database\Seeders\GemilangInitialDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GemilangCompanySeeder::class);

    $this->company = Company::where('name', 'PT Gemilang Sari Husada')->first();
    config(['tenant.company_id' => $this->company->id]);
});

describe('GemilangInitialDataSeeder', function () {

    it('creates default departments for the Gemilang company', function () {
        $this->seed(GemilangInitialDataSeeder::class);

        $count = Department::where('company_id', $this->company->id)->count();
        expect($count)->toBeGreaterThanOrEqual(3);
    });

    it('creates default positions linked to departments', function () {
        $this->seed(GemilangInitialDataSeeder::class);

        $positions = Position::where('company_id', $this->company->id)->get();
        expect($positions->count())->toBeGreaterThanOrEqual(3)
            ->and($positions->whereNotNull('department_id')->count())->toBeGreaterThan(0);
    });

    it('creates the six required leave types', function () {
        $this->seed(GemilangInitialDataSeeder::class);

        $names = LeaveType::where('company_id', $this->company->id)->pluck('name')->all();
        $expected = ['Cuti Tahunan', 'Cuti Sakit', 'Cuti Melahirkan', 'Cuti Menikah', 'Cuti Duka', 'Izin'];

        foreach ($expected as $name) {
            expect($names)->toContain($name);
        }
    });

    it('creates morning and night work schedules with one default', function () {
        $this->seed(GemilangInitialDataSeeder::class);

        $schedules = WorkSchedule::where('company_id', $this->company->id)->get();

        expect($schedules->count())->toBeGreaterThanOrEqual(2);
        expect($schedules->where('is_default', true)->count())->toBe(1);

        $morning = $schedules->firstWhere('code', 'PAGI');
        expect($morning)->not->toBeNull()
            ->and($morning->is_overnight)->toBeFalse();
    });

    it('creates 2026 Indonesian national holidays', function () {
        $this->seed(GemilangInitialDataSeeder::class);

        $holidays = Holiday::where('company_id', $this->company->id)
            ->whereYear('date', 2026)
            ->get();

        expect($holidays->count())->toBeGreaterThanOrEqual(10)
            ->and($holidays->contains('name', 'Tahun Baru'))->toBeTrue()
            ->and($holidays->contains('name', 'Hari Kemerdekaan RI'))->toBeTrue();
    });

    it('creates required salary components (earnings + deductions)', function () {
        $this->seed(GemilangInitialDataSeeder::class);

        $components = SalaryComponent::where('company_id', $this->company->id)->get();

        expect($components->where('code', 'BASIC')->count())->toBe(1);
        expect($components->where('type', 'earning')->count())->toBeGreaterThanOrEqual(3);
        expect($components->where('type', 'deduction')->count())->toBeGreaterThanOrEqual(2);
    });

    it('is idempotent - running twice does not duplicate data', function () {
        $this->seed(GemilangInitialDataSeeder::class);
        $countBefore = LeaveType::where('company_id', $this->company->id)->count();

        $this->seed(GemilangInitialDataSeeder::class);
        $countAfter = LeaveType::where('company_id', $this->company->id)->count();

        expect($countAfter)->toBe($countBefore);
    });
});
