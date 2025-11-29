<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $apiKey = config('services.gemini.key');
        $endpoint = $this->resolveEndpoint();

        if (empty($endpoint) || empty($apiKey)) {
            Log::warning('Gemini credentials missing or endpoint invalid, returning fallback payload.', [
                'endpoint' => $endpoint,
            ]);

            return $this->fallbackResponse($context);
        }

        if (! is_readable($path)) {
            Log::warning('Gemini could not read attachment contents.', [
                'path' => $path,
            ]);

            return $this->fallbackResponse($context);
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                ])
                ->post($endpoint, $this->buildPayload($path, $context));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Gemini request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'endpoint' => $endpoint,
                'model' => config('services.gemini.model'),
                'version' => config('services.gemini.version'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Gemini request failed', [
                'error' => $exception->getMessage(),
                'endpoint' => $endpoint,
            ]);
        }

        return $this->fallbackResponse($context);
    }

    protected function resolveEndpoint(): ?string
    {
        $configuredUrl = trim((string) config('services.gemini.url', ''));

        if ($configuredUrl !== '' && filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
            $path = trim(parse_url($configuredUrl, PHP_URL_PATH) ?? '', '/');

            if (str_contains($path, 'models/')) {
                return rtrim($configuredUrl, '/');
            }

            // If only the host/base was provided, fall back to composed endpoint.
            if ($path === '') {
                return $this->buildEndpointFromBase(rtrim($configuredUrl, '/'));
            }
        }

        return $this->buildEndpointFromBase();
    }

    protected function buildEndpointFromBase(?string $baseUrl = null): ?string
    {
        $base = $baseUrl ?? (string) config('services.gemini.base_url');
        $version = trim((string) config('services.gemini.version', 'v1beta'), '/');
        $model = trim((string) config('services.gemini.model', 'gemini-1.5-flash-latest')); // inlineData é suportado nas variantes 1.5

        if (! filter_var($base, FILTER_VALIDATE_URL) || $version === '' || $model === '') {
            return null;
        }

        $baseEndpoint = rtrim($base, '/') . '/' . $version . '/models/' . $model;

        if (! str_contains($baseEndpoint, ':generateContent')) {
            $baseEndpoint .= ':generateContent';
        }

        return $baseEndpoint;
    }

    protected function buildPayload(string $path, array $context): array
    {
        $fileContents = file_get_contents($path);
        $base64File = base64_encode($fileContents);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        $prompt = 'Analise o anexo e devolva um JSON com os campos: summary (string), topics (array de strings), amount (número ou null), currency (string) e detected_type (string).';
        $prompt .= $this->formatContextPrompt($context);

        return [
            'contents' => [
                [
                    'parts' => array_values(array_filter([
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64File,
                            ],
                        ],
                    ])),
                ],
            ],
        ];
    }

    protected function formatContextPrompt(array $context): string
    {
        $filteredContext = array_filter($context, fn ($value) => $value !== null && $value !== '');

        if (empty($filteredContext)) {
            return '';
        }

        return '\nContexto adicional: ' . json_encode($filteredContext, JSON_UNESCAPED_UNICODE);
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
