<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LarkIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

test('lark settings page displays successfully', function () {
    $response = $this->get(route('settings.lark.index'));

    $response->assertOk();
    $response->assertViewIs('settings.lark.index');
    $response->assertViewHas(['company', 'integration', 'stats']);
});

test('lark settings can be created and updated', function () {
    $response = $this->put(route('settings.lark.update'), [
        'app_id' => 'cli_test_abc123',
        'app_secret' => 'sec_super_secret_456',
        'is_active' => '1',
        'push_clock_in' => '1',
        'push_clock_out' => '1',
        'enable_bot_notifications' => '0',
    ]);

    $response->assertRedirect(route('settings.lark.index'));
    $response->assertSessionHas('success');

    $integration = LarkIntegration::where('company_id', $this->company->id)->first();
    expect($integration)->not->toBeNull();
    expect($integration->app_id)->toBe('cli_test_abc123');
    expect($integration->app_secret)->toBe('sec_super_secret_456');
    expect($integration->is_active)->toBeTrue();
});

test('test-connection route returns success when credentials are valid', function () {
    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-test_token_valid',
        ], 200),
    ]);

    $response = $this->post(route('settings.lark.test-connection'), [
        'app_id' => 'cli_valid',
        'app_secret' => 'sec_valid',
    ]);

    $response->assertRedirect(route('settings.lark.index'));
    $response->assertSessionHas('success');
});

test('lark mapping page displays employees list', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Budi',
        'last_name' => 'Santoso',
    ]);

    $response = $this->get(route('settings.lark.mapping'));

    $response->assertOk();
    $response->assertViewIs('settings.lark.mapping');
    $response->assertSee('Budi Santoso');
});

test('can manually map and unmap an employee to Lark user ID', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $response = $this->post(route('settings.lark.mapping.save', $employee), [
        'lark_user_id' => 'ou_budi_999',
    ]);

    $response->assertRedirect();
    $employee->refresh();
    expect($employee->lark_user_id)->toBe('ou_budi_999');

    $deleteResponse = $this->delete(route('settings.lark.mapping.destroy', $employee));
    $deleteResponse->assertRedirect();
    $employee->refresh();
    expect($employee->lark_user_id)->toBeNull();
});
