<?php

use App\Models\Company;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('WorkScheduleImport', function () {
    describe('import page', function () {
        it('displays the import page', function () {
            $response = $this->get(route('imports.work-schedules.index'));

            $response->assertOk();
            $response->assertViewIs('imports.work-schedules.index');
        });

        it('can download template', function () {
            $response = $this->get(route('imports.work-schedules.template'));

            $response->assertOk();
            $response->assertDownload('template_jadwal_kerja.xlsx');
        });
    });

    describe('import process', function () {
        it('validates required file', function () {
            $response = $this->post(route('imports.work-schedules.store'), []);

            $response->assertSessionHasErrors(['file']);
        });

        it('creates work schedules from import data', function () {
            $importData = [
                [
                    'name' => 'Shift Pagi',
                    'code' => 'PAGI',
                    'start_time' => '08:00',
                    'end_time' => '17:00',
                    'break_duration' => 60,
                    'working_days' => '1,2,3,4,5',
                    'late_tolerance' => 15,
                ],
                [
                    'name' => 'Shift Malam',
                    'code' => 'MALAM',
                    'start_time' => '20:00',
                    'end_time' => '05:00',
                    'break_duration' => 60,
                    'working_days' => '1,2,3,4,5',
                    'late_tolerance' => 10,
                ],
            ];

            foreach ($importData as $row) {
                $workingDays = array_map('intval', explode(',', $row['working_days']));
                WorkSchedule::create([
                    'company_id' => $this->company->id,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'break_duration' => $row['break_duration'],
                    'working_days' => $workingDays,
                    'late_tolerance' => $row['late_tolerance'],
                    'is_active' => true,
                ]);
            }

            expect(WorkSchedule::where('company_id', $this->company->id)->count())->toBe(2);
            $this->assertDatabaseHas('work_schedules', [
                'code' => 'PAGI',
                'late_tolerance' => 15,
            ]);
        });

        it('parses working days correctly', function () {
            $schedule = WorkSchedule::create([
                'company_id' => $this->company->id,
                'name' => 'Full Week',
                'code' => 'FW',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'working_days' => [1, 2, 3, 4, 5, 6],
                'is_active' => true,
            ]);

            expect($schedule->working_days)->toBeArray();
            expect($schedule->working_days)->toContain(6);
        });
    });
});
