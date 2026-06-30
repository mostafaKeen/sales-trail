<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tenant;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TenantController
{
    public function index()
    {
        $tenants = Tenant::with(['salestrailAccount', 'bitrixAccount'])->get();

        $stats = [
            'total' => $tenants->count(),
            'active' => $tenants->where('status', 'active')->count(),
            'inactive' => $tenants->where('status', 'inactive')->count(),
            'logs_count' => SyncLog::count(),
        ];

        return view('admin.tenants.index', compact('tenants', 'stats'));
    }

    public function toggleStatus(int $id)
    {
        $tenant = Tenant::findOrFail($id);
        $newStatus = $tenant->status === 'active' ? 'inactive' : 'active';
        
        $tenant->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Tenant updated successfully. Current Status: " . ucfirst($newStatus));
    }

    public function logs(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $logs = SyncLog::where('tenant_id', $tenant->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    public function updateIntegrations(Request $request, int $id)
    {
        $tenant = Tenant::findOrFail($id);
        
        $request->validate([
            'bitrix_client_id' => 'nullable|string',
            'bitrix_client_secret' => 'nullable|string',
            'bitrix_domain' => 'nullable|string',
            'salestrail_user' => 'nullable|string',
            'salestrail_password' => 'nullable|string',
            'salestrail_api_key' => 'nullable|string',
            'salestrail_api_url' => 'nullable|string',
            'salestrail_webhook_secret' => 'nullable|string',
        ]);

        $tenant->bitrixAccount()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'client_id' => $request->bitrix_client_id,
                'client_secret' => $request->bitrix_client_secret,
                'bitrix_domain' => $request->bitrix_domain ?? $tenant->bitrixAccount->bitrix_domain ?? 'pending.bitrix24.com',
            ]
        );

        $tenant->salestrailAccount()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'user' => $request->salestrail_user,
                'password' => $request->salestrail_password,
                'api_key' => $request->salestrail_api_key ?? $tenant->salestrailAccount->api_key ?? 'pending_key',
                'api_url' => $request->salestrail_api_url ?? $tenant->salestrailAccount->api_url,
                'webhook_secret' => $request->salestrail_webhook_secret ?? $tenant->salestrailAccount->webhook_secret,
            ]
        );

        return redirect()->back()->with('success', 'Integration settings updated successfully.');
    }
}
