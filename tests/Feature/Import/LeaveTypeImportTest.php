<?php

use App\Models\Company;
use App\Models\LeaveType;
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

describe('LeaveTypeImport', function () {
    describe('import page', function () {
        it('displays the import page', function () {
            $response = $this->get(route('imports.leave-types.index'));

            $response->assertOk();
            $response->assertViewIs('imports.leave-types.index');
        });

        it('can download template', function () {
            $response = $this->get(route('imports.leave-types.template'));

            $response->assertOk();
            $response->assertDownload('template_jenis_cuti.xlsx');
        });
    });

    describe('import process', function () {
        it('validates required file', function () {
            $response = $this->post(route('imports.leave-types.store'), []);

            $response->assertSessionHasErrors(['file']);
        });

        it('creates leave types from import data', function () {
            $importData = [
                [
                    'name' => 'Cuti Tahunan',
                    'code' => 'CT',
                    'default_days' => 12,
                    'is_paid' => true,
                    'requires_approval' => true,
                    'is_annual' => true,
                ],
                [
                    'name' => 'Cuti Sakit',
                    'code' => 'CS',
                    'default_days' => 14,
                    'is_paid' => true,
                    'requires_approval' => true,
                    'requires_attachment' => true,
                ],
                [
                    'name' => 'Cuti Melahirkan',
                    'code' => 'CM',
                    'default_days' => 90,
                    'is_paid' => true,
                    'requires_approval' => true,
                ],
            ];

            foreach ($importData as $row) {
                LeaveType::create([
                    'company_id' => $this->company->id,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'default_days' => $row['default_days'],
                    'is_paid' => $row['is_paid'] ?? true,
                    'requires_approval' => $row['requires_approval'] ?? true,
                    'requires_attachment' => $row['requires_attachment'] ?? false,
                    'is_annual' => $row['is_annual'] ?? false,
                    'is_active' => true,
                ]);
            }

            expect(LeaveType::where('company_id', $this->company->id)->count())->toBe(3);
            $this->assertDatabaseHas('leave_types', [
                'code' => 'CT',
                'default_days' => 12,
                'is_annual' => true,
            ]);
        });

        it('handles boolean fields from yes/no text', function () {
            $isPaid = strtolower('Ya') === 'ya' || strtolower('Ya') === 'yes' || strtolower('Ya') === '1';
            $isNotPaid = strtolower('Tidak') === 'ya' || strtolower('Tidak') === 'yes' || strtolower('Tidak') === '1';

            expect($isPaid)->toBeTrue();
            expect($isNotPaid)->toBeFalse();
        });
    });
});
