<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LarkIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Admin Lark Settings API', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'PT Lark Test Corp',
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        Sanctum::actingAs($this->adminUser);
    });

    it('can get lark settings with stats when no integration exists', function () {
        $response = $this->getJson('/api/v1/admin/settings/lark');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.integration.is_active', false)
            ->assertJsonPath('data.integration.has_app_secret', false)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'integration' => [
                        'id',
                        'company_id',
                        'app_id',
                        'has_app_secret',
                        'is_active',
                        'push_clock_in',
                        'push_clock_out',
                        'enable_bot_notifications',
                        'bot_webhook_url',
                        'last_synced_at',
                    ],
                    'stats' => [
                        'total_employees',
                        'mapped_employees',
                        'unmapped_employees',
                        'today_attendances',
                        'synced_attendances',
                        'failed_attendances',
                    ],
                ],
            ]);
    });

    it('validates required app_secret on initial setup and saves integration', function () {
        // Without app_secret on first setup -> validation error
        $badRes = $this->putJson('/api/v1/admin/settings/lark', [
            'app_id' => 'cli_test_123',
            'is_active' => true,
            'push_clock_in' => true,
            'push_clock_out' => true,
        ]);

        $badRes->assertStatus(422)
            ->assertJsonValidationErrors(['app_secret']);

        // With app_secret
        $saveRes = $this->putJson('/api/v1/admin/settings/lark', [
            'app_id' => 'cli_test_123',
            'app_secret' => 'super_secret_lark_key',
            'is_active' => true,
            'push_clock_in' => true,
            'push_clock_out' => false,
            'enable_bot_notifications' => true,
            'bot_webhook_url' => 'https://open.larksuite.com/open-apis/bot/v2/hook/abc',
        ]);

        $saveRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.app_id', 'cli_test_123')
            ->assertJsonPath('data.has_app_secret', true)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.push_clock_in', true)
            ->assertJsonPath('data.push_clock_out', false)
            ->assertJsonPath('data.enable_bot_notifications', true);

        // Verify stored in DB
        $this->assertDatabaseHas('lark_integrations', [
            'company_id' => $this->company->id,
            'app_id' => 'cli_test_123',
            'is_active' => true,
        ]);
    });

    it('allows updating settings without re-sending app_secret', function () {
        $integration = LarkIntegration::create([
            'company_id' => $this->company->id,
            'app_id' => 'cli_existing_app',
            'app_secret' => 'secret_12345',
            'is_active' => true,
            'push_clock_in' => true,
            'push_clock_out' => true,
        ]);

        $updateRes = $this->putJson('/api/v1/admin/settings/lark', [
            'app_id' => 'cli_updated_app',
            'is_active' => false,
            'push_clock_in' => false,
            'push_clock_out' => false,
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.app_id', 'cli_updated_app')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.has_app_secret', true);

        $integration->refresh();
        expect($integration->app_id)->toBe('cli_updated_app');
        expect($integration->app_secret)->toBe('secret_12345');
    });

    it('can save and return webhook credentials and webhook_url', function () {
        $res = $this->putJson('/api/v1/admin/settings/lark', [
            'app_id' => 'cli_webhook_app',
            'app_secret' => 'webhook_secret',
            'is_active' => true,
            'push_clock_in' => true,
            'push_clock_out' => true,
            'verification_token' => 'v_token_abc_123',
            'encrypt_key' => 'enc_key_xyz_456',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.verification_token', 'v_token_abc_123')
            ->assertJsonPath('data.has_encrypt_key', true);

        $getRes = $this->getJson('/api/v1/admin/settings/lark');
        $getRes->assertStatus(200)
            ->assertJsonPath('data.integration.verification_token', 'v_token_abc_123')
            ->assertJsonPath('data.integration.has_encrypt_key', true)
            ->assertJsonPath('data.integration.webhook_url', url('/api/v1/webhooks/lark'));
    });

    it('can test connection with Lark API using Http fake', function () {
        Http::fake([
            'open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 0,
                'msg' => 'ok',
                'tenant_access_token' => 't-test-token-xyz',
                'expire' => 7140,
            ], 200),
        ]);

        $res = $this->postJson('/api/v1/admin/settings/lark/test-connection', [
            'app_id' => 'cli_test_app',
            'app_secret' => 'cli_test_secret',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true);
    });

    it('returns error when test connection fails', function () {
        Http::fake([
            'open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 10003,
                'msg' => 'app_secret is invalid',
            ], 200),
        ]);

        $res = $this->postJson('/api/v1/admin/settings/lark/test-connection', [
            'app_id' => 'cli_test_app',
            'app_secret' => 'wrong_secret',
        ]);

        $res->assertStatus(400)
            ->assertJsonPath('success', false);
    });

    it('can sync employees with Lark via batch email match', function () {
        LarkIntegration::create([
            'company_id' => $this->company->id,
            'app_id' => 'cli_app',
            'app_secret' => 'secret_xyz',
            'is_active' => true,
            'push_clock_in' => true,
            'push_clock_out' => true,
        ]);

        $emp1 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'email' => 'budi@testcorp.com',
            'is_active' => true,
            'lark_user_id' => null,
        ]);

        $emp2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Siti',
            'last_name' => 'Rahma',
            'email' => 'siti@testcorp.com',
            'is_active' => true,
            'lark_user_id' => null,
        ]);

        Http::fake([
            'open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 0,
                'msg' => 'ok',
                'tenant_access_token' => 't-fake-token',
            ], 200),
            'open.larksuite.com/open-apis/contact/v3/users/batch_get_id*' => Http::response([
                'code' => 0,
                'msg' => 'ok',
                'data' => [
                    'user_list' => [
                        [
                            'email' => 'budi@testcorp.com',
                            'user_id' => 'ou_lark_budi_123',
                            'open_id' => 'ou_open_budi_123',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $syncRes = $this->postJson('/api/v1/admin/settings/lark/sync-employees');

        $syncRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.matched', 1)
            ->assertJsonPath('data.unmatched', 1);

        $emp1->refresh();
        $emp2->refresh();

        expect($emp1->lark_user_id)->toBe('ou_lark_budi_123');
        expect($emp1->lark_open_id)->toBe('ou_open_budi_123');
        expect($emp2->lark_user_id)->toBeNull();
    });

    it('can retrieve paginated employee mapping list with filters', function () {
        $emp1 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Andi',
            'last_name' => 'Pratama',
            'email' => 'andi@testcorp.com',
            'is_active' => true,
            'lark_user_id' => 'ou_andi_lark',
        ]);

        $emp2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Dewi',
            'last_name' => 'Lestari',
            'email' => 'dewi@testcorp.com',
            'is_active' => true,
            'lark_user_id' => null,
        ]);

        // Filter all
        $allRes = $this->getJson('/api/v1/admin/settings/lark/mapping');
        $allRes->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        // Filter mapped
        $mappedRes = $this->getJson('/api/v1/admin/settings/lark/mapping?filter=mapped');
        $mappedRes->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $emp1->id);

        // Filter unmapped
        $unmappedRes = $this->getJson('/api/v1/admin/settings/lark/mapping?filter=unmapped');
        $unmappedRes->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $emp2->id);

        // Search
        $searchRes = $this->getJson('/api/v1/admin/settings/lark/mapping?search=Pratama');
        $searchRes->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Andi Pratama');
    });

    it('can manually map and unmap an employee to lark', function () {
        $emp = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Rina',
            'last_name' => 'Kusuma',
            'is_active' => true,
            'lark_user_id' => null,
        ]);

        // Save mapping
        $mapRes = $this->postJson("/api/v1/admin/settings/lark/mapping/{$emp->id}", [
            'lark_user_id' => 'ou_rina_custom_id',
            'lark_open_id' => 'ou_rina_open_id',
        ]);

        $mapRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $emp->refresh();
        expect($emp->lark_user_id)->toBe('ou_rina_custom_id');
        expect($emp->lark_open_id)->toBe('ou_rina_open_id');

        // Unmap
        $unmapRes = $this->deleteJson("/api/v1/admin/settings/lark/mapping/{$emp->id}");
        $unmapRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $emp->refresh();
        expect($emp->lark_user_id)->toBeNull();
        expect($emp->lark_open_id)->toBeNull();
    });

    it('prevents mapping employee belonging to another company', function () {
        $otherCompany = Company::factory()->create(['name' => 'PT Other Company']);
        $otherEmp = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'is_active' => true,
        ]);

        $res = $this->postJson("/api/v1/admin/settings/lark/mapping/{$otherEmp->id}", [
            'lark_user_id' => 'ou_hacker_id',
        ]);

        $res->assertStatus(404);
    });
});
