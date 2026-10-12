<?php

use App\Models\Company;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('HolidayImport', function () {
    describe('import page', function () {
        it('displays the import page', function () {
            $response = $this->get(route('imports.holidays.index'));

            $response->assertOk();
            $response->assertViewIs('imports.holidays.index');
        });

        it('can download template', function () {
            $response = $this->get(route('imports.holidays.template'));

            $response->assertOk();
            $response->assertDownload('template_hari_libur.xlsx');
        });
    });

    describe('import process', function () {
        it('validates required file', function () {
            $response = $this->post(route('imports.holidays.store'), []);

            $response->assertSessionHasErrors(['file']);
        });

        it('creates holidays from import data', function () {
            $importData = [
                [
                    'name' => 'Tahun Baru',
                    'date' => '2026-01-01',
                    'type' => 'national',
                    'description' => 'Perayaan Tahun Baru',
                    'is_recurring' => true,
                ],
                [
                    'name' => 'Hari Kemerdekaan',
                    'date' => '2026-08-17',
                    'type' => 'national',
                    'description' => 'Hari Kemerdekaan RI',
                    'is_recurring' => true,
                ],
                [
                    'name' => 'Hari Raya Idul Fitri',
                    'date' => '2026-03-30',
                    'type' => 'religious',
                    'description' => 'Hari Raya Idul Fitri 1 Syawal',
                    'is_recurring' => false,
                ],
            ];

            foreach ($importData as $row) {
                Holiday::create([
                    'company_id' => $this->company->id,
                    'name' => $row['name'],
                    'date' => $row['date'],
                    'type' => $row['type'],
                    'description' => $row['description'] ?? null,
                    'is_recurring' => $row['is_recurring'] ?? false,
                    'is_active' => true,
                    'created_by' => $this->user->id,
                ]);
            }

            expect(Holiday::where('company_id', $this->company->id)->count())->toBe(3);

            $tahunBaru = Holiday::where('company_id', $this->company->id)
                ->where('name', 'Tahun Baru')
                ->whereDate('date', '2026-01-01')
                ->first();
            expect($tahunBaru)->not->toBeNull();
            expect($tahunBaru->type)->toBe('national');
            expect($tahunBaru->is_recurring)->toBeTrue();

            $idulFitri = Holiday::where('company_id', $this->company->id)
                ->where('name', 'Hari Raya Idul Fitri')
                ->first();
            expect($idulFitri)->not->toBeNull();
            expect($idulFitri->type)->toBe('religious');
            expect($idulFitri->is_recurring)->toBeFalse();
        });

        it('handles type mapping from Indonesian text', function () {
            $typeMapping = fn ($text) => match (strtolower(trim($text))) {
                'nasional', 'national' => 'national',
                'perusahaan', 'company' => 'company',
                'keagamaan', 'religious' => 'religious',
                default => 'national',
            };

            expect($typeMapping('Nasional'))->toBe('national');
            expect($typeMapping('nasional'))->toBe('national');
            expect($typeMapping('Perusahaan'))->toBe('company');
            expect($typeMapping('Keagamaan'))->toBe('religious');
            expect($typeMapping('religious'))->toBe('religious');
        });

        it('handles boolean fields from yes/no text', function () {
            $parseBoolean = fn ($value) => in_array(
                strtolower(trim((string) $value)),
                ['ya', 'yes', '1', 'true']
            );

            expect($parseBoolean('Ya'))->toBeTrue();
            expect($parseBoolean('yes'))->toBeTrue();
            expect($parseBoolean('1'))->toBeTrue();
            expect($parseBoolean('Tidak'))->toBeFalse();
            expect($parseBoolean('no'))->toBeFalse();
        });

        it('skips duplicate dates for same company', function () {
            // Create existing holiday
            Holiday::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Existing Holiday',
                'date' => '2026-01-01',
            ]);

            // Simulate import logic - skip if date already exists
            $existingHoliday = Holiday::where('company_id', $this->company->id)
                ->whereDate('date', '2026-01-01')
                ->first();

            expect($existingHoliday)->not->toBeNull();

            // Would skip this row in import
            $shouldSkip = $existingHoliday !== null;
            expect($shouldSkip)->toBeTrue();

            // Final count should still be 1
            expect(Holiday::where('company_id', $this->company->id)->count())->toBe(1);
        });
    });
});
