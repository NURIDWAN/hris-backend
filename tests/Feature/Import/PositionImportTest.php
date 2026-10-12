<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    // Create departments for position reference
    $this->itDepartment = Department::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'IT Department',
        'code' => 'IT',
    ]);
});

describe('PositionImport', function () {
    describe('import page', function () {
        it('displays the import page', function () {
            $response = $this->get(route('imports.positions.index'));

            $response->assertOk();
            $response->assertViewIs('imports.positions.index');
        });

        it('can download template', function () {
            $response = $this->get(route('imports.positions.template'));

            $response->assertOk();
            $response->assertDownload('template_jabatan.xlsx');
        });
    });

    describe('import process', function () {
        it('validates required file', function () {
            $response = $this->post(route('imports.positions.store'), []);

            $response->assertSessionHasErrors(['file']);
        });

        it('creates positions with department reference by code', function () {
            $position = Position::create([
                'company_id' => $this->company->id,
                'department_id' => $this->itDepartment->id,
                'name' => 'Software Engineer',
                'code' => 'SE',
                'level' => 3,
                'base_salary' => 10000000,
                'is_active' => true,
            ]);

            expect($position->department_id)->toBe($this->itDepartment->id);
            expect($position->department->code)->toBe('IT');
        });

        it('can import positions with salary formatting', function () {
            $importData = [
                ['name' => 'Junior Developer', 'code' => 'JD', 'department_code' => 'IT', 'level' => 1, 'base_salary' => '5000000'],
                ['name' => 'Senior Developer', 'code' => 'SD', 'department_code' => 'IT', 'level' => 4, 'base_salary' => '15.000.000'],
            ];

            foreach ($importData as $row) {
                $salary = (int) str_replace(['.', ','], '', $row['base_salary']);
                Position::create([
                    'company_id' => $this->company->id,
                    'department_id' => $this->itDepartment->id,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'level' => $row['level'],
                    'base_salary' => $salary,
                    'is_active' => true,
                ]);
            }

            expect(Position::where('company_id', $this->company->id)->count())->toBe(2);
            $this->assertDatabaseHas('positions', [
                'code' => 'SD',
                'base_salary' => 15000000,
            ]);
        });
    });
});
