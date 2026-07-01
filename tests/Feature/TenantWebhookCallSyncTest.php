<?php

namespace Tests\Feature;

use App\Domains\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantSalestrailAccount;
use App\Models\TenantBitrixAccount;
use App\Models\TenantUserMapping;
use App\Models\Call;
use App\Models\SyncLog;
use App\Jobs\ProcessSalestrailCallJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TenantWebhookCallSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Tenant A
        $this->tenantA = Tenant::create([
            'company_name' => 'Company A',
            'status' => 'active',
            'timezone' => 'UTC',
        ]);

        TenantSalestrailAccount::create([
            'tenant_id' => $this->tenantA->id,
            'api_key' => 'salestrail-key-a',
            'webhook_secret' => 'secret-a',
        ]);

        TenantBitrixAccount::create([
            'tenant_id' => $this->tenantA->id,
            'bitrix_domain' => 'comp-a.bitrix24.com',
            'webhook_url' => 'https://comp-a.bitrix24.com/rest/1/webhook-token-a/',
            'client_id' => 'client-id-a',
            'client_secret' => 'client-secret-a',
            'external_line_id' => 'line-a',
        ]);

        TenantUserMapping::create([
            'tenant_id' => $this->tenantA->id,
            'salestrail_user_id' => 'st-user-1',
            'salestrail_email' => 'user1@company-a.com',
            'employee_phone' => '101',
            'bitrix_user_id' => 'b24-user-1',
        ]);

        // Create Tenant B
        $this->tenantB = Tenant::create([
            'company_name' => 'Company B',
            'status' => 'active',
            'timezone' => 'UTC',
        ]);

        TenantSalestrailAccount::create([
            'tenant_id' => $this->tenantB->id,
            'api_key' => 'salestrail-key-b',
        ]);
    }

    public function test_webhook_dispatches_processing_job(): void
    {
        Queue::fake();

        $payload = [
            'id' => 'call-uuid-123',
            'phone_number' => '+1234567890',
            'direction' => 'inbound',
            'duration' => 60,
            'answered' => true,
            'user_id' => 'st-user-1',
            'user_email' => 'user1@company-a.com',
            'employee_phone' => '101',
        ];

        // Call Tenant A Webhook with proper signature verification
        $signature = hash_hmac('sha256', json_encode($payload), 'secret-a');

        $response = $this->withHeaders([
            'X-Salestrail-Signature' => $signature,
        ])->postJson("/api/webhooks/{$this->tenantA->uuid}/salestrail", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'queued');

        Queue::assertPushed(ProcessSalestrailCallJob::class, function ($job) {
            return $job->tenantId === $this->tenantA->id;
        });

        // Verify request was logged
        TenantContext::set($this->tenantA);
        $this->assertEquals(1, SyncLog::count());
        $this->assertEquals('queued', SyncLog::first()->status);
        TenantContext::clear();
    }

    public function test_webhook_fails_when_tenant_is_inactive(): void
    {
        $this->tenantA->update(['status' => 'inactive']);
        $payload = ['id' => 'call-uuid-123'];

        $response = $this->postJson("/api/webhooks/{$this->tenantA->uuid}/salestrail", $payload);

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'Tenant is inactive. Please activate it via the admin panel.');

        // Verify it logged to sync_logs
        TenantContext::set($this->tenantA);
        $this->assertEquals(1, SyncLog::count());
        $this->assertEquals('inactive_tenant', SyncLog::first()->status);
        TenantContext::clear();
    }

    public function test_webhook_fails_with_invalid_signature(): void
    {
        $payload = ['id' => 'call-uuid-123'];

        $response = $this->withHeaders([
            'X-Salestrail-Signature' => 'invalid-sig',
        ])->postJson("/api/webhooks/{$this->tenantA->uuid}/salestrail", $payload);

        $response->assertStatus(403);

        // Verify signature failure logged
        TenantContext::set($this->tenantA);
        $this->assertEquals(1, SyncLog::count());
        $this->assertEquals('invalid_signature', SyncLog::first()->status);
        TenantContext::clear();
    }

    public function test_webhook_authenticates_with_valid_basic_auth(): void
    {
        Queue::fake();

        // Configure Basic Auth on the account
        $this->tenantA->salestrailAccount->update([
            'user' => 'valid-user',
            'password' => 'valid-pass',
        ]);

        $payload = [
            'id' => 'call-uuid-123',
            'phone_number' => '+1234567890',
        ];

        // Send request with Basic Auth credentials using Server Variables
        $response = $this->withServerVariables([
            'PHP_AUTH_USER' => 'valid-user',
            'PHP_AUTH_PW' => 'valid-pass',
        ])->postJson("/api/webhooks/{$this->tenantA->uuid}/salestrail", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'queued');
    }

    public function test_webhook_fails_with_invalid_basic_auth(): void
    {
        // Configure Basic Auth on the account
        $this->tenantA->salestrailAccount->update([
            'user' => 'valid-user',
            'password' => 'valid-pass',
        ]);

        $payload = ['id' => 'call-uuid-123'];

        // Send request with incorrect Basic Auth server variables
        $response = $this->withServerVariables([
            'PHP_AUTH_USER' => 'invalid-user',
            'PHP_AUTH_PW' => 'invalid-pass',
        ])->postJson("/api/webhooks/{$this->tenantA->uuid}/salestrail", $payload);

        $response->assertStatus(401);

        // Verify failure logged
        TenantContext::set($this->tenantA);
        $this->assertEquals(1, SyncLog::count());
        $this->assertEquals('invalid_signature', SyncLog::first()->status);
        TenantContext::clear();
    }

    public function test_job_processes_call_sync_to_bitrix(): void
    {
        Http::fake([
            'https://comp-a.bitrix24.com/rest/1/webhook-token-a/telephony.externalCall.register' => Http::response([
                'result' => ['CALL_ID' => 'b24-call-id-999']
            ], 200),
            'https://comp-a.bitrix24.com/rest/1/webhook-token-a/telephony.externalCall.finish' => Http::response([
                'result' => ['ID' => 777]
            ], 200),
            'https://salestrail.com/recording.mp3' => Http::response('mock-audio-content', 200),
            'https://comp-a.bitrix24.com/rest/1/webhook-token-a/telephony.externalCall.attachRecord' => Http::response([
                'result' => ['FILE_ID' => 555]
            ], 200),
            'https://comp-a.bitrix24.com/rest/1/webhook-token-a/crm.contact.list' => Http::response([
                'result' => []
            ], 200),
            'https://comp-a.bitrix24.com/rest/1/webhook-token-a/crm.lead.list' => Http::response([
                'result' => []
            ], 200),
            'https://comp-a.bitrix24.com/rest/1/webhook-token-a/crm.lead.add' => Http::response([
                'result' => 12345
            ], 200),
        ]);

        $payload = [
            'id' => 'st-call-id-unique',
            'phone_number' => '+1234567890',
            'direction' => 'inbound',
            'duration' => 45,
            'answered' => true,
            'user_id' => 'st-user-1',
            'user_email' => 'user1@company-a.com',
            'employee_phone' => '101',
            'recording_url' => 'https://salestrail.com/recording.mp3',
        ];

        // Execute Job directly
        $job = new ProcessSalestrailCallJob($this->tenantA->id, $payload);
        $job->handle(app(\App\Services\BitrixTelephonyService::class), app(\App\Services\SalestrailService::class));

        // Verify call was stored and updated
        TenantContext::set($this->tenantA);
        $call = Call::where('salestrail_call_id', 'st-call-id-unique')->first();
        $this->assertNotNull($call);
        $this->assertEquals('b24-call-id-999', $call->bitrix_call_id);
        $this->assertTrue($call->synced_to_bitrix);
        $this->assertTrue($call->recording_uploaded);

        // Verify sync logs
        $logs = SyncLog::where('call_id', 'st-call-id-unique')->get();
        $this->assertCount(2, $logs); // Register/finish, and Attach record
        TenantContext::clear();
    }

    public function test_tenant_data_isolation(): void
    {
        // Add Call for Tenant A
        TenantContext::set($this->tenantA);
        $callA = Call::create([
            'salestrail_call_id' => 'call-a',
            'employee_phone' => '101',
            'customer_phone' => '201',
            'inbound' => true,
            'answered' => true,
        ]);
        TenantContext::clear();

        // Add Call for Tenant B
        TenantContext::set($this->tenantB);
        $callB = Call::create([
            'salestrail_call_id' => 'call-b',
            'employee_phone' => '102',
            'customer_phone' => '202',
            'inbound' => true,
            'answered' => true,
        ]);
        TenantContext::clear();

        // Set context to Tenant A and verify we only see Tenant A calls
        TenantContext::set($this->tenantA);
        $this->assertEquals(1, Call::count());
        $this->assertEquals('call-a', Call::first()->salestrail_call_id);

        // Set context to Tenant B and verify we only see Tenant B calls
        TenantContext::set($this->tenantB);
        $this->assertEquals(1, Call::count());
        $this->assertEquals('call-b', Call::first()->salestrail_call_id);
        TenantContext::clear();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/tenants');
        $response->assertRedirect('/login');

        $toggleResponse = $this->post("/admin/tenants/{$this->tenantA->id}/toggle-status");
        $toggleResponse->assertRedirect('/login');

        $logsResponse = $this->get("/admin/tenants/{$this->tenantA->id}/logs");
        $logsResponse->assertRedirect('/login');
    }

    public function test_admin_dashboard_can_view_and_toggle_tenants(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // View index as admin
        $response = $this->actingAs($admin)->get('/admin/tenants');
        $response->assertStatus(200);
        $response->assertSee('Company A');
        $response->assertSee('Company B');

        // Toggle Company A status as admin
        $this->assertEquals('active', $this->tenantA->status);
        $toggleResponse = $this->actingAs($admin)->post("/admin/tenants/{$this->tenantA->id}/toggle-status");
        $toggleResponse->assertRedirect();
        
        $this->tenantA->refresh();
        $this->assertEquals('inactive', $this->tenantA->status);

        // Fetch logs as admin
        TenantContext::set($this->tenantA);
        SyncLog::create([
            'tenant_id' => $this->tenantA->id,
            'action' => 'test_action',
            'status' => 'success',
        ]);
        TenantContext::clear();

        $logsResponse = $this->actingAs($admin)->get("/admin/tenants/{$this->tenantA->id}/logs");
        $logsResponse->assertStatus(200);
        $logsResponse->assertJsonFragment(['action' => 'test_action']);
    }
}
