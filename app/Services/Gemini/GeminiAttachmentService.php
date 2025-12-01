<?php

namespace App\Services\Gemini;

use App\Models\Attachment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiAttachmentService
{
    public function analyzeAttachment(Attachment $attachment): array
    {
        $filePath = Storage::path($attachment->path);

        if (! Storage::exists($attachment->path)) {
            Log::warning('Attachment file not found for Gemini analysis.', [
                'attachment_id' => $attachment->id,
                'path' => $attachment->path,
            ]);

            return $this->normalizeResponse([]);
        }

        $mimeType = $attachment->mime ?: Storage::mimeType($attachment->path) ?: 'application/octet-stream';
        $fileContents = file_get_contents($filePath);
        $base64File = base64_encode($fileContents);

        $apiUrl = config('services.gemini.api_url');
        $apiKey = config('services.gemini.api_key');

        if (! $apiUrl || ! $apiKey) {
            Log::warning('Gemini configuration missing.');

            return $this->normalizeResponse([]);
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->buildPrompt()],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64File,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withQueryParameters(['key' => $apiKey])
                ->timeout(40)
                ->post($apiUrl, $payload);

            if ($response->failed()) {
                Log::warning('Gemini API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->normalizeResponse([]);
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($text)) {
                Log::warning('Gemini response did not contain text.', [
                    'response' => $response->json(),
                ]);

                return $this->normalizeResponse([]);
            }

            $decoded = json_decode($this->extractJson($text), true);

            if (! is_array($decoded)) {
                Log::warning('Gemini response JSON could not be decoded.', [
                    'text' => $text,
                ]);

                return $this->normalizeResponse([]);
            }

            return $this->normalizeResponse($decoded);
        } catch (\Throwable $exception) {
            Log::error('Gemini attachment analysis failed.', [
                'attachment_id' => $attachment->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->normalizeResponse([]);
        }
    }

    protected function extractJson(string $text): string
    {
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }

        return trim($text);
    }

    protected function normalizeResponse(array $data): array
    {
        return [
            'intent' => $data['intent'] ?? 'unknown',
            'type' => $data['type'] ?? null,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'occurred_on' => $data['occurred_on'] ?? null,
            'description' => $data['description'] ?? null,
            'exam_type' => $data['exam_type'] ?? null,
            'provider_name' => $data['provider_name'] ?? null,
        ];
    }

    protected function buildPrompt(): string
    {
        return <<<'PROMPT'
Você é um sistema de extração de dados financeiros e médicos.
Sempre responda EXCLUSIVAMENTE com JSON válido, sem texto extra, sem markdown.
Analise o documento/imagen e determine se é:

Lançamento financeiro (conta, boleto, nota, recibo etc)

Consulta médica

Exame médico

E retorne exatamente neste formato:

{
  "intent": "create_financial_record" | "create_appointment" | "create_exam" | "unknown",
  "type": "expense" | "income" | null,
  "amount": 123.45 ou null,
  "occurred_on": "YYYY-MM-DD" ou null,
  "description": "texto descritivo ou null",
  "exam_type": "tipo do exame ou null",
  "provider_name": "nome do médico ou clínica ou null"
}


Sem comentários, sem explicações, apenas o JSON.
PROMPT;
    }
}
