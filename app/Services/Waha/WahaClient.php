<?php

namespace App\Services\Waha;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaClient
{
    public function registerWebhook(string $callbackUrl): bool
    {
        $endpoint = rtrim(config('services.waha.url', ''), '/');
        $token = config('services.waha.token');
        $session = config('services.waha.session', 'default');

        if (empty($endpoint) || empty($token) || empty($session)) {
            Log::warning('WAHA credentials missing, skipping webhook registration.', [
                'callback_url' => $callbackUrl,
            ]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $token,
            ])->post("{$endpoint}/api/sessions/{$session}/", [
                'webhook' => [
                    'url' => $callbackUrl,
                    'enabled' => true,
                ],
            ]);

            if ($response->failed()) {
                Log::error('WAHA webhook registration failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'callback_url' => $callbackUrl,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('WAHA webhook registration failed', [
                'error' => $exception->getMessage(),
                'callback_url' => $callbackUrl,
            ]);

            return false;
        }
    }

    public function sendTextMessage(string $phone, string $message): void
    {
        $endpoint = rtrim(config('services.waha.url', ''), '/');
        $token = config('services.waha.token');
        $session = config('services.waha.session', 'default');

        if (empty($endpoint) || empty($token) || empty($session)) {
            Log::warning('WAHA credentials missing, skipping send.', [
                'phone' => $phone,
                'message' => $message,
            ]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $token,
            ])->post("{$endpoint}/api/sendText", [
                'session' => $session,
                'chatId' => $phone,
                'text' => $message,
            ]);

            if ($response->failed()) {
                Log::error('WAHA send failed', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('WAHA send failed', [
                'error' => $exception->getMessage(),
                'phone' => $phone,
            ]);
        }
    }
}
