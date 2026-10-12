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

describe('HolidayController', function () {
    describe('index', function () {
        it('displays holiday list page', function () {
            $response = $this->get(route('holidays.index'));

            $response->assertOk();
            $response->assertViewIs('holidays.index');
        });

        it('shows holidays for current company only', function () {
            // Create holidays for this company
            $holiday1 = Holiday::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Tahun Baru',
                'date' => '2026-01-01',
            ]);

            // Create holiday for another company
            $otherCompany = Company::factory()->create();
            $holiday2 = Holiday::factory()->create([
                'company_id' => $otherCompany->id,
                'name' => 'Other Company Holiday',
            ]);

            $response = $this->get(route('holidays.index'));

            $response->assertOk();
            $response->assertSee('Tahun Baru');
            $response->assertDontSee('Other Company Holiday');
        });

        it('can filter holidays by year', function () {
            Holiday::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Holiday 2026',
                'date' => '2026-05-01',
            ]);
            Holiday::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Holiday 2025',
                'date' => '2025-05-01',
            ]);

            $response = $this->get(route('holidays.index', ['year' => 2026]));

            $response->assertOk();
            $response->assertSee('Holiday 2026');
            $response->assertDontSee('Holiday 2025');
        });

        it('can filter holidays by type', function () {
            Holiday::factory()->national()->create([
                'company_id' => $this->company->id,
                'name' => 'National Holiday',
            ]);
            Holiday::factory()->company()->create([
                'company_id' => $this->company->id,
                'name' => 'Company Holiday',
            ]);

            $response = $this->get(route('holidays.index', ['type' => 'national']));

            $response->assertOk();
            $response->assertSee('National Holiday');
            $response->assertDontSee('Company Holiday');
        });
    });

    describe('create', function () {
        it('displays create holiday form', function () {
            $response = $this->get(route('holidays.create'));

            $response->assertOk();
            $response->assertViewIs('holidays.create');
        });
    });

    describe('store', function () {
        it('creates a new holiday', function () {
            $response = $this->post(route('holidays.store'), [
                'name' => 'Hari Kemerdekaan',
                'date' => '2026-08-17',
                'type' => 'national',
                'description' => 'Hari Kemerdekaan Republik Indonesia',
                'is_recurring' => true,
            ]);

            $response->assertRedirect(route('holidays.index'));
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('holidays', [
                'company_id' => $this->company->id,
                'name' => 'Hari Kemerdekaan',
                'type' => 'national',
            ]);

            $holiday = Holiday::where('company_id', $this->company->id)->where('name', 'Hari Kemerdekaan')->first();
            expect($holiday->date->format('Y-m-d'))->toBe('2026-08-17');
            expect($holiday->is_recurring)->toBeTrue();
        });

        it('validates required fields', function () {
            $response = $this->post(route('holidays.store'), []);

            $response->assertSessionHasErrors(['name', 'date', 'type']);
        });

        it('validates unique date per company', function () {
            Holiday::factory()->create([
                'company_id' => $this->company->id,
                'date' => '2026-08-17',
            ]);

            $response = $this->post(route('holidays.store'), [
                'name' => 'Another Holiday',
                'date' => '2026-08-17',
                'type' => 'company',
            ]);

            $response->assertSessionHasErrors(['date']);
        });

        it('allows same date for different companies', function () {
            $otherCompany = Company::factory()->create();
            Holiday::factory()->create([
                'company_id' => $otherCompany->id,
                'date' => '2026-08-17',
            ]);

            $response = $this->post(route('holidays.store'), [
                'name' => 'Hari Kemerdekaan',
                'date' => '2026-08-17',
                'type' => 'national',
            ]);

            $response->assertRedirect(route('holidays.index'));

            $holiday = Holiday::where('company_id', $this->company->id)
                ->whereDate('date', '2026-08-17')
                ->first();
            expect($holiday)->not->toBeNull();
        });
    });

    describe('edit', function () {
        it('displays edit holiday form', function () {
            $holiday = Holiday::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('holidays.edit', $holiday));

            $response->assertOk();
            $response->assertViewIs('holidays.edit');
            $response->assertViewHas('holiday');
        });

        it('returns 404 for other company holiday', function () {
            $otherCompany = Company::factory()->create();
            $holiday = Holiday::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->get(route('holidays.edit', $holiday));

            $response->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates an existing holiday', function () {
            $holiday = Holiday::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Old Name',
            ]);

            $response = $this->put(route('holidays.update', $holiday), [
                'name' => 'New Name',
                'date' => '2026-12-25',
                'type' => 'religious',
                'description' => 'Updated description',
                'is_recurring' => true,
            ]);

            $response->assertRedirect(route('holidays.index'));
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('holidays', [
                'id' => $holiday->id,
                'name' => 'New Name',
                'type' => 'religious',
            ]);
        });

        it('returns 404 for other company holiday', function () {
            $otherCompany = Company::factory()->create();
            $holiday = Holiday::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->put(route('holidays.update', $holiday), [
                'name' => 'Hacked',
                'date' => '2026-01-01',
                'type' => 'national',
            ]);

            $response->assertNotFound();
        });
    });

    describe('destroy', function () {
        it('deletes a holiday', function () {
            $holiday = Holiday::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->delete(route('holidays.destroy', $holiday));

            $response->assertRedirect(route('holidays.index'));
            $response->assertSessionHas('success');

            $this->assertSoftDeleted('holidays', ['id' => $holiday->id]);
        });

        it('returns 404 for other company holiday', function () {
            $otherCompany = Company::factory()->create();
            $holiday = Holiday::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->delete(route('holidays.destroy', $holiday));

            $response->assertNotFound();
        });
    });

    describe('calendar view', function () {
        it('displays calendar view', function () {
            $response = $this->get(route('holidays.calendar'));

            $response->assertOk();
            $response->assertViewIs('holidays.calendar');
        });

        it('returns holidays as json for calendar', function () {
            Holiday::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Test Holiday',
                'date' => '2026-05-01',
            ]);

            $response = $this->getJson(route('holidays.events', ['year' => 2026]));

            $response->assertOk();
            $response->assertJsonFragment(['name' => 'Test Holiday']);
        });
    });

    describe('bulk actions', function () {
        it('can generate national holidays for year', function () {
            $response = $this->post(route('holidays.generate'), [
                'year' => 2026,
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success');

            // Should have created Indonesian national holidays
            $tahunBaru = Holiday::where('company_id', $this->company->id)
                ->where('name', 'Tahun Baru')
                ->whereDate('date', '2026-01-01')
                ->where('type', 'national')
                ->first();
            expect($tahunBaru)->not->toBeNull();
        });
    });
});
