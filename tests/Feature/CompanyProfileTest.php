<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'hr-manager']);
    Role::create(['name' => 'employee']);

    $this->company = Company::factory()->create([
        'name' => 'PT Test Company',
        'email' => 'company@test.com',
        'phone' => '021-12345678',
        'address' => 'Jl. Test No. 123',
        'website' => 'https://test.com',
        'npwp' => '12.345.678.9-012.345',
    ]);

    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');

    $this->hrManager = User::factory()->create(['company_id' => $this->company->id]);
    $this->hrManager->assignRole('hr-manager');
});

describe('Company Profile Index', function () {
    it('displays company profile page for admin', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/company-profile');

        $response->assertStatus(200);
        $response->assertViewIs('settings.company-profile.index');
        $response->assertSee('PT Test Company');
    });

    it('displays company profile page for hr-manager', function () {
        $this->actingAs($this->hrManager);

        $response = $this->get('/settings/company-profile');

        $response->assertStatus(200);
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/settings/company-profile');

        $response->assertRedirect('/login');
    });

    it('shows current company information', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/company-profile');

        $response->assertSee('PT Test Company');
        $response->assertSee('company@test.com');
        $response->assertSee('021-12345678');
    });
});

describe('Company Profile Update', function () {
    it('can update company name', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Updated Company',
            'email' => 'company@test.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'PT Updated Company',
        ]);
    });

    it('can update company contact information', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'newemail@test.com',
            'phone' => '021-87654321',
            'address' => 'Jl. New Address No. 456',
            'website' => 'https://newwebsite.com',
        ]);

        $response->assertRedirect();

        $this->company->refresh();
        expect($this->company->email)->toBe('newemail@test.com');
        expect($this->company->phone)->toBe('021-87654321');
        expect($this->company->address)->toBe('Jl. New Address No. 456');
        expect($this->company->website)->toBe('https://newwebsite.com');
    });

    it('can update npwp', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'npwp' => '98.765.432.1-098.765',
        ]);

        $response->assertRedirect();

        $this->company->refresh();
        expect($this->company->npwp)->toBe('98.765.432.1-098.765');
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => '',
            'email' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    });

    it('validates email format', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates email uniqueness excluding current company', function () {
        $this->actingAs($this->admin);

        // Create another company with different email
        Company::factory()->create(['email' => 'other@company.com']);

        // Try to update current company with that email
        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'other@company.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('allows keeping same email', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Updated Company',
            'email' => 'company@test.com', // Same email
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
    });

    it('validates website url format', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'website' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors(['website']);
    });
});

describe('Company Logo Upload', function () {
    it('can upload company logo', function () {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'logo' => $file,
        ]);

        $response->assertRedirect();

        $this->company->refresh();
        expect($this->company->logo)->not->toBeNull();
        Storage::disk('public')->assertExists($this->company->logo);
    });

    it('validates logo file type', function () {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    });

    it('validates logo file size', function () {
        Storage::fake('public');
        $this->actingAs($this->admin);

        // Create file larger than 2MB
        $file = UploadedFile::fake()->image('logo.png')->size(3000);

        $response = $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    });

    it('deletes old logo when uploading new one', function () {
        Storage::fake('public');
        $this->actingAs($this->admin);

        // Upload first logo
        $oldFile = UploadedFile::fake()->image('old-logo.png');
        $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'logo' => $oldFile,
        ]);

        $this->company->refresh();
        $oldPath = $this->company->logo;

        // Upload new logo
        $newFile = UploadedFile::fake()->image('new-logo.png');
        $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'logo' => $newFile,
        ]);

        $this->company->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($this->company->logo);
    });

    it('can remove company logo', function () {
        Storage::fake('public');
        $this->actingAs($this->admin);

        // First upload a logo
        $file = UploadedFile::fake()->image('logo.png');
        $this->put('/settings/company-profile', [
            'name' => 'PT Test Company',
            'email' => 'company@test.com',
            'logo' => $file,
        ]);

        $this->company->refresh();
        $logoPath = $this->company->logo;

        // Remove logo
        $response = $this->delete('/settings/company-profile/logo');

        $response->assertRedirect();
        $this->company->refresh();
        expect($this->company->logo)->toBeNull();
        Storage::disk('public')->assertMissing($logoPath);
    });
});

describe('Company Settings JSON', function () {
    it('can update company settings', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile/settings', [
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'currency' => 'IDR',
            'working_days_per_month' => 22,
        ]);

        $response->assertRedirect();

        $this->company->refresh();
        expect($this->company->settings['timezone'])->toBe('Asia/Jakarta');
        expect($this->company->settings['date_format'])->toBe('d/m/Y');
        expect($this->company->settings['currency'])->toBe('IDR');
        expect($this->company->settings['working_days_per_month'])->toBe(22);
    });

    it('validates working days per month range', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/settings/company-profile/settings', [
            'working_days_per_month' => 35, // Invalid - more than 31
        ]);

        $response->assertSessionHasErrors(['working_days_per_month']);
    });
});
