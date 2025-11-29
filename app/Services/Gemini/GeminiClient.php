<?php

namespace App\Services\Gemini;

use Gemini;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            Log::warning('Gemini credentials missing, returning fallback payload.');

            return $this->fallbackResponse($context);
        }

        $content = $this->loadAttachment($path);

        if ($content === null) {
            return $this->fallbackResponse($context);
        }

        [$fileContents, $mimeType] = $content;
        $prompt = $this->buildPrompt($context);

        try {
            $result = Gemini::client($apiKey)
                ->generativeModel(model: config('services.gemini.model', 'gemini-1.5-flash'))
                ->generateContent([
                    $prompt,
                    new Blob(
                        mimeType: $mimeType,
                        data: base64_encode($fileContents),
                    ),
                ]);

            $text = trim((string) $result->text());
            $decoded = json_decode($text, true);

            if ($this->isValidResponse($decoded)) {
                return $decoded;
            }

            Log::warning('Gemini returned non-JSON response', [
                'raw' => $text,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Gemini SDK error', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return $this->fallbackResponse($context);
        }

        return $this->fallbackResponse($context);
    }

    protected function loadAttachment(string $path): ?array
    {
        if ($this->isHttpUrl($path)) {
            $response = Http::timeout(15)->get($path);

            if (! $response->successful()) {
                Log::warning('Gemini could not fetch remote attachment.', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $contents = $response->body();
            $mimeType = $this->resolveMimeTypeFromHeader($response->header('Content-Type'));
        } else {
            if (! is_readable($path)) {
                Log::warning('Gemini could not read attachment contents.', [
                    'path' => $path,
                ]);

                return null;
            }

            $contents = file_get_contents($path) ?: '';
            $mimeType = $this->resolveMimeType($path);
        }

        if ($contents === '') {
            Log::warning('Gemini attachment contents are empty.', [
                'path' => $path,
            ]);

            return null;
        }

        return [$contents, $mimeType];
    }

    protected function buildPrompt(array $context): string
    {
        $prompt = 'Analise o anexo e devolva APENAS um JSON com os campos: summary (string), topics (array de strings), amount (número ou null), currency (string) e detected_type (string: "expense", "income", "appointment" ou "exam"). Não inclua texto fora do JSON.';

        $filteredContext = array_filter($context, fn ($value) => $value !== null && $value !== '');

        if (! empty($filteredContext)) {
            $prompt .= '\nContexto adicional: ' . json_encode($filteredContext, JSON_UNESCAPED_UNICODE);
        }

        return $prompt;
    }

    protected function resolveMimeType(string $path): MimeType
    {
        $detected = mime_content_type($path) ?: '';

        return MimeType::tryFrom($detected) ?? MimeType::IMAGE_JPEG;
    }

    protected function resolveMimeTypeFromHeader(?string $header): MimeType
    {
        if (empty($header)) {
            return MimeType::IMAGE_JPEG;
        }

        $mimeOnly = strtolower(strtok($header, ';'));

        return MimeType::tryFrom($mimeOnly) ?? MimeType::IMAGE_JPEG;
    }

    protected function isValidResponse(mixed $decoded): bool
    {
        if (! is_array($decoded)) {
            return false;
        }

        $requiredKeys = ['summary', 'topics', 'amount', 'currency', 'detected_type'];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $decoded)) {
                return false;
            }
        }

        return true;
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

    protected function isHttpUrl(string $path): bool
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return true;
        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }
}
