<?php

namespace App\Jobs;

use App\Domains\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\Call;
use App\Models\SyncLog;
use App\Models\TenantUserMapping;
use App\Services\BitrixTelephonyService;
use App\Services\SalestrailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSalestrailCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public array $payload
    ) {}

    public function handle(
        BitrixTelephonyService $bitrixService,
        SalestrailService $salestrailService
    ): void {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $salestrailCallId = $this->payload['id'] ?? $this->payload['call_id'] ?? null;
        if (!$salestrailCallId) {
            Log::warning("Salestrail payload missing call ID", ['payload' => $this->payload]);
            return;
        }

        // Find or create call record
        $call = Call::firstOrNew([
            'tenant_id' => $tenant->id,
            'salestrail_call_id' => $salestrailCallId,
        ]);

        // Map payload fields
        $call->employee_phone = $this->payload['employee_phone'] ?? $call->employee_phone ?? '';
        $call->customer_phone = $this->payload['customer_phone'] ?? $this->payload['phone_number'] ?? '';
        $call->inbound = isset($this->payload['inbound']) ? (bool)$this->payload['inbound'] : (strcasecmp($this->payload['direction'] ?? '', 'inbound') === 0);
        $call->answered = (bool)($this->payload['answered'] ?? false);
        $call->duration = (int)($this->payload['duration'] ?? 0);
        $call->recording_url = $this->payload['recording_url'] ?? $call->recording_url;
        $call->started_at = isset($this->payload['started_at']) ? \Illuminate\Support\Carbon::parse($this->payload['started_at']) : now();
        $call->finished_at = isset($this->payload['finished_at']) ? \Illuminate\Support\Carbon::parse($this->payload['finished_at']) : now();
        $call->payload = $this->payload;
        $call->save();

        $bitrixAccount = $tenant->bitrixAccount;
        if (!$bitrixAccount) {
            Log::warning("Tenant {$tenant->id} has no Bitrix24 account configured.");
            return;
        }

        // Resolve user mapping
        $salestrailUserId = $this->payload['user_id'] ?? null;
        $salestrailEmail = $this->payload['user_email'] ?? null;
        
        $mapping = TenantUserMapping::where('tenant_id', $tenant->id)
            ->when($salestrailUserId, fn($q) => $q->where('salestrail_user_id', $salestrailUserId))
            ->when($salestrailEmail, fn($q) => $q->orWhere('salestrail_email', $salestrailEmail))
            ->first();

        $bitrixUserId = $mapping?->bitrix_user_id;

        // Register call on Bitrix if not yet done
        if (!$call->synced_to_bitrix || empty($call->bitrix_call_id)) {
            try {
                // 1. Search for CRM entity (Lead/Contact)
                $crmEntity = $bitrixService->searchCrmEntity($bitrixAccount, $call->customer_phone);

                if (!$crmEntity) {
                    // 2. Create lead if not found
                    $leadId = $bitrixService->createLead($bitrixAccount, [
                        'phone' => $call->customer_phone,
                        'name' => $this->payload['phoneBookName'] ?? 'New Salestrail Contact',
                        'assigned_by_id' => $bitrixUserId,
                    ]);
                    $crmEntity = [
                        'ENTITY_TYPE' => 'LEAD',
                        'ENTITY_ID' => $leadId,
                        'ASSIGNED_BY_ID' => $bitrixUserId
                    ];
                }

                // 3. Register the call
                $registerResponse = $bitrixService->registerCall($bitrixAccount, [
                    'bitrix_user_id' => $bitrixUserId,
                    'employee_phone' => $call->employee_phone,
                    'customer_phone' => $call->customer_phone,
                    'type' => $call->inbound ? 2 : 1,
                    'salestrail_call_id' => $call->salestrail_call_id,
                    'crm_entity_type' => $crmEntity['ENTITY_TYPE'],
                    'crm_entity_id' => $crmEntity['ENTITY_ID'],
                ]);

                $bitrixCallId = $registerResponse['result']['CALL_ID'] ?? null;
                if ($bitrixCallId) {
                    $call->bitrix_call_id = $bitrixCallId;
                    $call->save();

                    // Finish call on Bitrix
                    $statusMapping = $call->answered ? '200' : '304'; // 200 = successful, 304 = missed
                    $finishResponse = $bitrixService->finishCall($bitrixAccount, $bitrixCallId, [
                        'bitrix_user_id' => $bitrixUserId,
                        'employee_phone' => $call->employee_phone,
                        'duration' => $call->duration,
                        'status_code' => $statusMapping,
                    ]);

                    $call->synced_to_bitrix = true;
                    $call->save();

                    $this->logSync($tenant->id, $call->salestrail_call_id, 'register_and_finish', $this->payload, $finishResponse, 'success');
                } else {
                    throw new \Exception("Bitrix call registration failed: No CALL_ID returned.");
                }
            } catch (Throwable $e) {
                $this->logSync($tenant->id, $call->salestrail_call_id, 'register_and_finish', $this->payload, ['error' => $e->getMessage()], 'failed');
                throw $e;
            }
        }

        // Upload recording if available and not yet uploaded
        if ($call->synced_to_bitrix && !empty($call->bitrix_call_id) && !empty($call->recording_url) && !$call->recording_uploaded) {
            $tempFile = null;
            try {
                $salestrailAccount = $tenant->salestrailAccount;
                $apiKey = $salestrailAccount?->api_key ?? '';

                // Download the recording
                $tempFile = $salestrailService->downloadRecording($call->recording_url, $apiKey);

                // Upload/attach to Bitrix
                $attachResponse = $bitrixService->attachRecord($bitrixAccount, $call->bitrix_call_id, $tempFile);

                $call->recording_uploaded = true;
                $call->save();

                $this->logSync($tenant->id, $call->salestrail_call_id, 'attach_record', ['file' => basename($tempFile)], $attachResponse, 'success');
            } catch (Throwable $e) {
                $this->logSync($tenant->id, $call->salestrail_call_id, 'attach_record', ['url' => $call->recording_url], ['error' => $e->getMessage()], 'failed');
                throw $e;
            } finally {
                // Ensure local file is deleted as per the requirements
                if ($tempFile && file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        }

        TenantContext::clear();
    }

    protected function logSync(int $tenantId, string $callId, string $action, array $request, array $response, string $status): void
    {
        SyncLog::create([
            'tenant_id' => $tenantId,
            'call_id' => $callId,
            'action' => $action,
            'request' => $request,
            'response' => $response,
            'status' => $status,
        ]);
    }
}
