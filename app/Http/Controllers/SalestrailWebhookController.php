<?php

namespace App\Http\Controllers;

use App\Services\TenantResolverService;
use App\Services\SalestrailService;
use App\Jobs\ProcessSalestrailCallJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SalestrailWebhookController
{
    public function __construct(
        protected TenantResolverService $tenantResolver,
        protected SalestrailService $salestrailService
    ) {}

    public function handle(Request $request, string $uuid): JsonResponse
    {
        try {
            $tenant = $this->tenantResolver->resolveByUuid($uuid);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        // Set context temporarily for logging
        \App\Domains\Tenant\TenantContext::set($tenant);

        $data = $request->all();
        $callId = $data['callId'] ?? $data['id'] ?? $data['call_id'] ?? null;

        // Check if tenant is active
        if ($tenant->status !== 'active') {
            \App\Models\SyncLog::create([
                'tenant_id' => $tenant->id,
                'call_id' => $callId,
                'action' => 'webhook_receive',
                'request' => $data,
                'response' => ['error' => 'Tenant is inactive.'],
                'status' => 'inactive_tenant',
            ]);
            \App\Domains\Tenant\TenantContext::clear();
            return response()->json(['error' => 'Tenant is inactive. Please activate it via the admin panel.'], 403);
        }

        $salestrailAccount = $tenant->salestrailAccount;
        
        $signature = $request->header('X-Salestrail-Signature') ?? '';
        $payload = $request->getContent();

        Log::info("Incoming Salestrail webhook request", [
            'uuid' => $uuid,
            'headers' => array_map(fn($v) => is_array($v) ? implode(', ', $v) : $v, $request->headers->all()),
            'payload' => $data,
            'raw_content' => $payload,
            'signature_header' => $signature,
        ]);
        
        // Optional webhook signature verification
        if ($salestrailAccount && !empty($salestrailAccount->webhook_secret)) {
            if (!$this->salestrailService->verifyWebhook($payload, $signature, $salestrailAccount->webhook_secret)) {
                $computed = hash_hmac('sha256', $payload, $salestrailAccount->webhook_secret);
                \App\Models\SyncLog::create([
                    'tenant_id' => $tenant->id,
                    'call_id' => $callId,
                    'action' => 'webhook_receive',
                    'request' => $data,
                    'response' => [
                        'error' => 'Unauthorized signature verification failed.',
                        'header_signature' => $signature,
                        'computed_signature' => $computed,
                    ],
                    'status' => 'invalid_signature',
                ]);
                Log::warning("Unauthorized webhook request for tenant UUID: {$uuid}", [
                    'header_signature' => $signature,
                    'computed_signature' => $computed,
                ]);
                \App\Domains\Tenant\TenantContext::clear();
                return response()->json(['error' => 'Unauthorized signature verification failed.'], 403);
            }
        }

        // Always dispatch background processing job first to ensure it's logged
        ProcessSalestrailCallJob::dispatch($tenant->id, $data);

        \App\Models\SyncLog::create([
            'tenant_id' => $tenant->id,
            'call_id' => $callId,
            'action' => 'webhook_receive',
            'request' => $data,
            'response' => ['status' => 'queued', 'message' => 'Call webhook processing dispatched.'],
            'status' => 'queued',
        ]);

        \App\Domains\Tenant\TenantContext::clear();

        return response()->json(['status' => 'queued', 'message' => 'Call webhook processing dispatched.']);
    }
}
