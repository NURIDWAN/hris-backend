<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Position Model', function () {

    it('can create a position', function () {
        $company = Company::factory()->create();

        $position = Position::create([
            'company_id' => $company->id,
            'name' => 'Software Engineer',
            'code' => 'SE',
            'level' => 3,
            'base_salary' => 10000000,
        ]);

        expect($position)->toBeInstanceOf(Position::class)
            ->and($position->name)->toBe('Software Engineer')
            ->and($position->code)->toBe('SE')
            ->and($position->level)->toBe(3)
            ->and($position->base_salary)->toBe(10000000);
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $position = Position::factory()->create(['company_id' => $company->id]);

        expect($position->company)->toBeInstanceOf(Company::class)
            ->and($position->company->id)->toBe($company->id);
    });

    it('can belong to a department', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create([
            'company_id' => $department->company_id,
            'department_id' => $department->id,
        ]);

        expect($position->department)->toBeInstanceOf(Department::class)
            ->and($position->department->id)->toBe($department->id);
    });

    it('has many employees', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        Employee::factory()->count(3)->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($position->employees)->toHaveCount(3);
    });

    it('can be soft deleted', function () {
        $position = Position::factory()->create();

        $position->delete();

        expect($position->trashed())->toBeTrue()
            ->and(Position::count())->toBe(0)
            ->and(Position::withTrashed()->count())->toBe(1);
    });

    it('has level for hierarchy', function () {
        $company = Company::factory()->create();

        $junior = Position::factory()->create([
            'company_id' => $company->id,
            'name' => 'Junior Developer',
            'level' => 1,
        ]);

        $senior = Position::factory()->create([
            'company_id' => $company->id,
            'name' => 'Senior Developer',
            'level' => 3,
        ]);

        $manager = Position::factory()->create([
            'company_id' => $company->id,
            'name' => 'Engineering Manager',
            'level' => 5,
        ]);

        expect($junior->level)->toBeLessThan($senior->level)
            ->and($senior->level)->toBeLessThan($manager->level);
    });

    it('has base salary', function () {
        $position = Position::factory()->create([
            'base_salary' => 15000000,
        ]);

        expect($position->base_salary)->toBe(15000000);
    });

    it('can format salary to rupiah', function () {
        $position = Position::factory()->create([
            'base_salary' => 15000000,
        ]);

        expect($position->formatted_salary)->toBe('Rp 15.000.000');
    });

});
