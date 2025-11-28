<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $endpoint = config('services.gemini.url');
        $apiKey = config('services.gemini.key');

        if (empty($endpoint) || empty($apiKey)) {
            Log::info('Gemini credentials missing, returning fallback payload.');

            return $this->fallbackResponse($context);
        }

        if (! is_readable($path)) {
            Log::warning('Gemini could not read attachment contents.', [
                'path' => $path,
            ]);

            return $this->fallbackResponse($context);
        }

        try {
            $response = Http::withToken($apiKey)->attach(
                'file',
                file_get_contents($path),
                basename($path)
            )->post($endpoint, [
                'context' => $context,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $exception) {
            Log::error('Gemini request failed', [
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackResponse($context);
    }

    protected function fallbackResponse(array $context = []): array
    {
        return [
            'summary' => 'Processamento offline concluído.',
            'topics' => ['valor', 'categoria', 'data'],
            'amount' => $context['amount'] ?? null,
            'currency' => $context['currency'] ?? 'BRL',
            'detected_type' => $context['type'] ?? null,
        ];
    }
}
