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
            Log::warning('Gemini credentials missing, returning fallback payload.');

            return $this->fallbackResponse($context);
        }

        $resolvedPath = $this->prepareFile($path);

        if (! $resolvedPath || ! is_readable($resolvedPath)) {
            Log::warning('Gemini could not read attachment contents.', [
                'path' => $path,
            ]);

            return $this->fallbackResponse($context);
        }

        try {
            $response = Http::withToken($apiKey)->attach(
                'file',
                file_get_contents($resolvedPath),
                basename($resolvedPath)
            )->post($endpoint, [
                'context' => $context,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Gemini request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Gemini request failed', [
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackResponse($context);
    }

    protected function prepareFile(string $path): ?string
    {
        if (is_readable($path)) {
            return $path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            try {
                $response = Http::timeout(10)->get($path);

                if ($response->successful()) {
                    $tempPath = tempnam(sys_get_temp_dir(), 'gemini_');
                    file_put_contents($tempPath, $response->body());

                    return $tempPath;
                }

                Log::warning('Gemini download failed', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);
            } catch (\Throwable $exception) {
                Log::error('Gemini download exception', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return null;
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
