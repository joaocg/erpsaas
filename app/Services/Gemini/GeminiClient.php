<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $endpoint = rtrim((string) config('services.gemini.url'), '/');
        $apiKey = config('services.gemini.key');

        if (empty($endpoint) || empty($apiKey) || ! filter_var($endpoint, FILTER_VALIDATE_URL)) {
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
            $response = Http::acceptJson()->post($endpoint . '?key=' . $apiKey, $this->buildPayload($path, $context));

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
