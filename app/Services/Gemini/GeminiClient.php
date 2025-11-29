<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $endpoint = config('services.gemini.url');      // GEMINI_API_URL já com .../models/...:generateContent
        $apiKey   = config('services.gemini.key');      // GEMINI_API_KEY

        if (empty($endpoint) || empty($apiKey)) {
            Log::warning('Gemini credentials missing, returning fallback payload.', [
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

        $fileContents = file_get_contents($path);
        $base64File   = base64_encode($fileContents);
        $mimeType     = mime_content_type($path) ?: 'image/jpeg';

        $prompt = 'Analise o anexo e devolva APENAS um JSON com os campos: '
            . 'summary (string), topics (array de strings), amount (número ou null), '
            . 'currency (string) e detected_type (string: "expense", "income", "appointment" ou "exam"). '
            . 'Não escreva nenhuma explicação fora do JSON.';

        $prompt .= $this->formatContextPrompt($context);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data'     => $base64File,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Extrai o texto principal gerado pelo modelo
                $text = $this->extractTextFromGeminiResponse($data);

                if ($text) {
                    $decoded = json_decode($text, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return array_merge(
                            $this->fallbackResponse($context),
                            $decoded
                        );
                    }

                    Log::warning('Gemini returned non-JSON or invalid JSON text', [
                        'raw_text' => $text,
                    ]);
                }

                // Se não conseguir extrair JSON, loga e volta fallback
                Log::warning('Gemini response did not contain expected text content.', [
                    'response' => $data,
                ]);
            } else {
                Log::error('Gemini request failed', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'endpoint' => $endpoint,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Gemini request failed', [
                'error'    => $exception->getMessage(),
                'endpoint' => $endpoint,
            ]);
        }

        return $this->fallbackResponse($context);
    }

    protected function extractTextFromGeminiResponse(array $data): ?string
    {
        // Estrutura típica do generateContent:
        // {
        //   "candidates": [
        //     {
        //       "content": {
        //         "parts": [ { "text": "..." } ]
        //       }
        //     }
        //   ]
        // }

        $candidates = $data['candidates'] ?? [];

        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];

            foreach ($parts as $part) {
                if (! empty($part['text'])) {
                    return $part['text'];
                }
            }
        }

        return null;
    }

    protected function formatContextPrompt(array $context): string
    {
        $filtered = array_filter($context, fn ($v) => $v !== null && $v !== '');

        if (empty($filtered)) {
            return '';
        }

        return "\nContexto adicional (pode usar se for útil): " . json_encode($filtered, JSON_UNESCAPED_UNICODE);
    }

    protected function fallbackResponse(array $context = []): array
    {
        return [
            'summary'       => 'Processamento offline concluído.',
            'topics'        => ['valor', 'categoria', 'data'],
            'amount'        => $context['amount'] ?? null,
            'currency'      => $context['currency'] ?? 'BRL',
            'detected_type' => $context['type'] ?? null,
        ];
    }
}
