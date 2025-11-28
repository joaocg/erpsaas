<?php

namespace App\Services\Waha;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaClient
{
    public function sendTextMessage(string $phone, string $message): void
    {
        $endpoint = rtrim(config('services.waha.url', ''), '/').'/messages/text';
        $token = config('services.waha.token');

        if (empty($endpoint) || empty($token)) {
            Log::info('WAHA credentials missing, skipping send.', [
                'phone' => $phone,
                'message' => $message,
            ]);

            return;
        }

        try {
            Http::withToken($token)->post($endpoint, [
                'to' => $phone,
                'text' => $message,
            ]);
        } catch (\Throwable $exception) {
            Log::error('WAHA send failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
