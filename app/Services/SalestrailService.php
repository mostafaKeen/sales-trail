<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class SalestrailService
{
    /**
     * Download recording temporarily and return the path.
     */
    public function downloadRecording(string $url, string $apiKey): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->get($url);

        if (!$response->successful()) {
            // Try downloading without the Bearer token in case it's a pre-signed url
            $response = Http::get($url);
        }

        if (!$response->successful()) {
            throw new Exception("Failed to download recording from Salestrail: " . $response->status());
        }

        $tempDir = storage_path('app/temp_recordings');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'mp3';
        if (!in_array(strtolower($extension), ['mp3', 'wav'])) {
            $extension = 'mp3';
        }

        $tempPath = $tempDir . '/' . Str::uuid() . '.' . $extension;
        file_put_contents($tempPath, $response->body());

        return $tempPath;
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhook(string $payload, string $signatureHeader, string $secret): bool
    {
        if (empty($secret)) {
            return true;
        }

        $computedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computedSignature, $signatureHeader);
    }
}
