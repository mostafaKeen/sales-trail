<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantBitrixAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Bitrix24Controller extends Controller
{
    /**
     * Handle Bitrix24 installation callback (POST request from Bitrix24)
     */
    public function installationCallback(Request $request)
    {
        Log::info('Bitrix24 installation callback received', $request->all());

        $validated = $request->validate([
            'domain' => 'required|string',
            'member_id' => 'required|string',
            'access_token' => 'required|string',
            'refresh_token' => 'required|string',
            'client_endpoint' => 'nullable|string',
            'server_endpoint' => 'nullable|string',
            'scope' => 'nullable|string',
        ]);

        // Find tenant by domain or member_id (if we have it stored)
        $tenant = Tenant::whereHas('bitrixAccount', function ($q) use ($validated) {
            $q->where('bitrix_domain', $validated['domain'])
                ->orWhere('member_id', $validated['member_id']);
        })->first();

        // If no tenant found, create one (or you might want to handle this differently)
        if (!$tenant) {
            $tenant = Tenant::create([
                'company_name' => 'Bitrix24 Tenant - ' . $validated['domain'],
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 'active',
            ]);
        }

        $tenant->bitrixAccount()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'bitrix_domain' => $validated['domain'],
                'member_id' => $validated['member_id'],
                'access_token' => $validated['access_token'],
                'refresh_token' => $validated['refresh_token'],
                'webhook_url' => $validated['client_endpoint'] ?? null,
            ]
        );

        return response()->json(['status' => 'success', 'message' => 'Tokens saved successfully']);
    }

    /**
     * Start Bitrix24 OAuth 2.0 authorization flow
     */
    public function startOAuth(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $bitrixAccount = $tenant->bitrixAccount;

        if (!$bitrixAccount || !$bitrixAccount->client_id || !$bitrixAccount->bitrix_domain) {
            return redirect()->back()->with('error', 'Bitrix24 credentials or domain not configured');
        }

        $bitrixDomain = $bitrixAccount->bitrix_domain;
        $clientId = $bitrixAccount->client_id;
        $state = (string) \Illuminate\Support\Str::uuid();

        // Store state in session for verification
        session()->put('bitrix_oauth_state_' . $tenantId, $state);

        $authUrl = "https://{$bitrixDomain}/oauth/authorize/";
        $authUrl .= "?client_id=" . urlencode($clientId);
        $authUrl .= "&state=" . urlencode($state);

        return redirect($authUrl);
    }

    /**
     * Handle Bitrix24 OAuth 2.0 callback
     */
    public function handleOAuthCallback(Request $request)
    {
        Log::info('Bitrix24 OAuth callback received', $request->all());

        // Check if this is the direct POST from Bitrix24 interface (with AUTH_ID)
        if ($request->has('AUTH_ID') && $request->has('DOMAIN') && $request->has('member_id')) {
            $domain = $request->input('DOMAIN');
            $memberId = $request->input('member_id');
            $accessToken = $request->input('AUTH_ID');
            $refreshToken = $request->input('REFRESH_ID');
            $serverEndpoint = $request->input('SERVER_ENDPOINT');

            // Find tenant by domain or member_id
            $tenant = Tenant::whereHas('bitrixAccount', function ($q) use ($domain, $memberId) {
                $q->where('bitrix_domain', $domain)
                    ->orWhere('member_id', $memberId);
            })->first();

            if (!$tenant) {
                // Create tenant if not found
                $tenant = Tenant::create([
                    'company_name' => 'Bitrix24 Tenant - ' . $domain,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'status' => 'active',
                ]);
            }

            $bitrixAccount = $tenant->bitrixAccount ?? $tenant->bitrixAccount()->create(['tenant_id' => $tenant->id]);

            $bitrixAccount->update([
                'bitrix_domain' => $domain,
                'member_id' => $memberId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'webhook_url' => $serverEndpoint,
            ]);

            return response('OK');
        }

        // Otherwise, handle the OAuth 2.0 code flow
        $validated = $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
            'domain' => 'required|string',
            'member_id' => 'required|string',
            'scope' => 'nullable|string',
            'server_domain' => 'nullable|string',
        ]);

        // Find tenant by domain or member_id
        $tenant = Tenant::whereHas('bitrixAccount', function ($q) use ($validated) {
            $q->where('bitrix_domain', $validated['domain'])
                ->orWhere('member_id', $validated['member_id']);
        })->first();

        if (!$tenant) {
            // Create tenant if not found
            $tenant = Tenant::create([
                'company_name' => 'Bitrix24 Tenant - ' . $validated['domain'],
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 'active',
            ]);
        }

        $bitrixAccount = $tenant->bitrixAccount ?? $tenant->bitrixAccount()->create(['tenant_id' => $tenant->id]);

        if (!$bitrixAccount->client_id || !$bitrixAccount->client_secret) {
            return redirect('/admin/tenants')->with('error', 'Bitrix24 client ID/secret not configured');
        }

        // Exchange code for access token
        $tokenUrl = "https://oauth.bitrix.info/oauth/token/";
        $response = Http::get($tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $bitrixAccount->client_id,
            'client_secret' => $bitrixAccount->client_secret,
            'code' => $validated['code'],
        ]);

        if (!$response->successful()) {
            Log::error('Failed to exchange code for token', ['response' => $response->body()]);
            return redirect('/admin/tenants')->with('error', 'Failed to obtain Bitrix24 access token');
        }

        $tokenData = $response->json();

        if (isset($tokenData['error'])) {
            Log::error('Bitrix24 OAuth error', $tokenData);
            return redirect('/admin/tenants')->with('error', 'Bitrix24 OAuth error: ' . ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $bitrixAccount->update([
            'bitrix_domain' => $validated['domain'],
            'member_id' => $validated['member_id'],
            'access_token' => $tokenData['access_token'] ?? null,
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'webhook_url' => $tokenData['client_endpoint'] ?? null,
        ]);

        return redirect('/admin/tenants')->with('success', 'Bitrix24 integration configured successfully!');
    }
}
