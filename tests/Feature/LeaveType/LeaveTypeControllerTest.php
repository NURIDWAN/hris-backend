<?php

use App\Models\Company;
use App\Models\LeaveType;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('LeaveType Index', function () {
    test('can view leave types list', function () {
        $leaveTypes = LeaveType::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->get(route('leave-types.index'));

        $response->assertStatus(200);
        $response->assertViewIs('leave-types.index');
        $response->assertViewHas('leaveTypes');
    });

    test('only shows leave types from same company', function () {
        $ownLeaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        $otherLeaveType = LeaveType::factory()->create();

        $response = $this->get(route('leave-types.index'));

        $response->assertStatus(200);
        $response->assertViewHas('leaveTypes', function ($leaveTypes) use ($ownLeaveType, $otherLeaveType) {
            return $leaveTypes->contains($ownLeaveType) && !$leaveTypes->contains($otherLeaveType);
        });
    });

    test('can search leave types by name', function () {
        LeaveType::factory()->create(['company_id' => $this->company->id, 'name' => 'Cuti Tahunan']);
        LeaveType::factory()->create(['company_id' => $this->company->id, 'name' => 'Cuti Sakit']);

        $response = $this->get(route('leave-types.index', ['search' => 'Tahunan']));

        $response->assertStatus(200);
        $response->assertViewHas('leaveTypes', function ($leaveTypes) {
            return $leaveTypes->count() === 1 && $leaveTypes->first()->name === 'Cuti Tahunan';
        });
    });

    test('can filter by active status', function () {
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
        LeaveType::factory()->create(['company_id' => $this->company->id, 'is_active' => false]);

        $response = $this->get(route('leave-types.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertViewHas('leaveTypes', function ($leaveTypes) {
            return $leaveTypes->every(fn ($lt) => $lt->is_active === true);
        });
    });
});

describe('LeaveType Create', function () {
    test('can view create form', function () {
        $response = $this->get(route('leave-types.create'));

        $response->assertStatus(200);
        $response->assertViewIs('leave-types.create');
    });

    test('can create leave type', function () {
        $data = [
            'name' => 'Cuti Tahunan',
            'code' => 'CT',
            'description' => 'Cuti tahunan untuk karyawan',
            'default_days' => 12,
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => false,
            'max_consecutive_days' => 5,
            'min_notice_days' => 3,
            'is_carry_forward' => true,
            'max_carry_forward_days' => 6,
            'is_active' => true,
            'color' => 'primary',
        ];

        $response = $this->post(route('leave-types.store'), $data);

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_types', [
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
            'code' => 'CT',
            'default_days' => 12,
        ]);
    });

    test('name is required', function () {
        $data = LeaveType::factory()->make(['name' => ''])->toArray();

        $response = $this->post(route('leave-types.store'), $data);

        $response->assertSessionHasErrors('name');
    });

    test('code must be unique within company', function () {
        LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'CT',
        ]);

        $data = LeaveType::factory()->make(['code' => 'CT'])->toArray();

        $response = $this->post(route('leave-types.store'), $data);

        $response->assertSessionHasErrors('code');
    });

    test('default_days must be positive integer', function () {
        $data = LeaveType::factory()->make(['default_days' => -5])->toArray();

        $response = $this->post(route('leave-types.store'), $data);

        $response->assertSessionHasErrors('default_days');
    });

    test('max_carry_forward_days required when is_carry_forward is true', function () {
        $data = LeaveType::factory()->make([
            'is_carry_forward' => true,
            'max_carry_forward_days' => null,
        ])->toArray();

        $response = $this->post(route('leave-types.store'), $data);

        $response->assertSessionHasErrors('max_carry_forward_days');
    });
});

describe('LeaveType Show', function () {
    test('can view leave type details', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('leave-types.show', $leaveType));

        $response->assertStatus(200);
        $response->assertViewIs('leave-types.show');
        $response->assertViewHas('leaveType', $leaveType);
    });

    test('cannot view leave type from other company', function () {
        $otherLeaveType = LeaveType::factory()->create();

        $response = $this->get(route('leave-types.show', $otherLeaveType));

        $response->assertStatus(404);
    });
});

describe('LeaveType Edit', function () {
    test('can view edit form', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('leave-types.edit', $leaveType));

        $response->assertStatus(200);
        $response->assertViewIs('leave-types.edit');
        $response->assertViewHas('leaveType', $leaveType);
    });

    test('can update leave type', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'name' => 'Cuti Tahunan Updated',
            'code' => 'CTU',
            'default_days' => 14,
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => false,
            'min_notice_days' => 3,
            'is_carry_forward' => false,
            'is_active' => true,
        ];

        $response = $this->put(route('leave-types.update', $leaveType), $data);

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_types', [
            'id' => $leaveType->id,
            'name' => 'Cuti Tahunan Updated',
            'code' => 'CTU',
            'default_days' => 14,
        ]);
    });

    test('cannot update leave type from other company', function () {
        $otherLeaveType = LeaveType::factory()->create();

        $response = $this->put(route('leave-types.update', $otherLeaveType), [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(404);
    });

    test('code unique validation ignores current record', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'CT',
        ]);

        $data = [
            'name' => 'Updated Name',
            'code' => 'CT',
            'default_days' => 12,
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => false,
            'min_notice_days' => 0,
            'is_carry_forward' => false,
            'is_active' => true,
        ];

        $response = $this->put(route('leave-types.update', $leaveType), $data);

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('success');
    });
});

describe('LeaveType Delete', function () {
    test('can delete leave type', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $response = $this->delete(route('leave-types.destroy', $leaveType));

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('leave_types', ['id' => $leaveType->id]);
    });

    test('cannot delete leave type from other company', function () {
        $otherLeaveType = LeaveType::factory()->create();

        $response = $this->delete(route('leave-types.destroy', $otherLeaveType));

        $response->assertStatus(404);
    });
});

describe('LeaveType Toggle Status', function () {
    test('can toggle leave type status', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->patch(route('leave-types.toggle-status', $leaveType));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_types', [
            'id' => $leaveType->id,
            'is_active' => false,
        ]);
    });

    test('cannot toggle status of leave type from other company', function () {
        $otherLeaveType = LeaveType::factory()->create();

        $response = $this->patch(route('leave-types.toggle-status', $otherLeaveType));

        $response->assertStatus(404);
    });
});
