<?php

namespace App\Services;

use App\Models\TenantBitrixAccount;
use Illuminate\Support\Facades\Http;
use Exception;

class BitrixTelephonyService
{
    /**
     * Call Bitrix24 REST API.
     */
    public function callMethod(TenantBitrixAccount $account, string $method, array $params): array
    {
        if (!empty($account->webhook_url)) {
            $url = rtrim($account->webhook_url, '/') . '/' . $method;
            $response = Http::post($url, $params);
        } else {
            if (empty($account->access_token)) {
                throw new Exception("No access token or webhook URL configured for Bitrix24.");
            }

            $url = "https://{$account->bitrix_domain}/rest/{$method}";
            
            // Add auth token
            $params['auth'] = $account->access_token;
            $response = Http::post($url, $params);

            // Handle expired token
            if ($response->status() === 401 || (isset($response->json()['error']) && $response->json()['error'] === 'expired_token')) {
                $this->refreshToken($account);
                $params['auth'] = $account->access_token;
                $response = Http::post($url, $params);
            }
        }

        if (!$response->successful()) {
            throw new Exception("Bitrix24 API error [{$method}]: " . $response->body());
        }

        $result = $response->json();
        if (isset($result['error'])) {
            throw new Exception("Bitrix24 business error [{$method}]: {$result['error_description']} ({$result['error']})");
        }

        return $result;
    }

    /**
     * Refresh OAuth token.
     */
    public function refreshToken(TenantBitrixAccount $account): void
    {
        $clientId = $account->client_id;
        $clientSecret = $account->client_secret;

        if (empty($account->refresh_token) || empty($clientId) || empty($clientSecret)) {
            throw new Exception("Cannot refresh Bitrix24 token: Missing refresh token or application client configurations.");
        }

        $response = Http::get('https://oauth.bitrix.info/oauth/token/', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $account->refresh_token,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to refresh Bitrix24 token: " . $response->body());
        }

        $data = $response->json();
        if (isset($data['error'])) {
            throw new Exception("Bitrix24 OAuth refresh error: {$data['error_description']}");
        }

        $account->update([
            'access_token' => $data['access_token'] ?? $account->access_token,
            'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
        ]);
    }

    /**
     * Register a call in Bitrix24.
     */
    public function registerCall(TenantBitrixAccount $account, array $data): array
    {
        return $this->callMethod($account, 'telephony.externalCall.register', [
            'USER_ID' => $data['bitrix_user_id'] ?? null,
            'USER_PHONE_INNER' => $data['employee_phone'] ?? null,
            'PHONE_NUMBER' => $data['customer_phone'],
            'TYPE' => $data['type'] ?? 2, // 2 = inbound, 1 = outbound
            'LINE_NUMBER' => $account->external_line_id ?? $data['line_number'] ?? null,
            'EXTERNAL_CALL_ID' => $data['salestrail_call_id'],
            'SHOW' => $data['show'] ?? 0,
        ]);
    }

    /**
     * Complete a call in Bitrix24.
     */
    public function finishCall(TenantBitrixAccount $account, string $bitrixCallId, array $data): array
    {
        return $this->callMethod($account, 'telephony.externalCall.finish', [
            'CALL_ID' => $bitrixCallId,
            'USER_ID' => $data['bitrix_user_id'] ?? null,
            'USER_PHONE_INNER' => $data['employee_phone'] ?? null,
            'DURATION' => (int) ($data['duration'] ?? 0),
            'STATUS_CODE' => $data['status_code'] ?? '200',
            'ADD_TO_CHAT' => $data['add_to_chat'] ?? 0,
        ]);
    }

    /**
     * Attach call recording to the call.
     */
    public function attachRecord(TenantBitrixAccount $account, string $bitrixCallId, string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("Recording file does not exist: {$filePath}");
        }

        $fileName = basename($filePath);
        $fileContent = base64_encode(file_get_contents($filePath));

        return $this->callMethod($account, 'telephony.externalCall.attachRecord', [
            'CALL_ID' => $bitrixCallId,
            'FILENAME' => $fileName,
            'FILE_CONTENT' => $fileContent,
        ]);
    }
}
