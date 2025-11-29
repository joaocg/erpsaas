<?php

namespace App\Services\Gemini;

use Gemini;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $apiKey = config('services.gemini.key');
        $baseUrl = $this->resolveBaseUrl();

        if (empty($apiKey)) {
            Log::warning('Gemini credentials missing, returning fallback payload.');

            return $this->fallbackResponse($context);
        }

        if (! is_readable($path)) {
            Log::warning('Gemini could not read attachment contents.', [
                'path' => $path,
            ]);

            return $this->fallbackResponse($context);
        }

        try {
            $clientFactory = Gemini::factory()
                ->withApiKey($apiKey);

            if ($baseUrl) {
                $clientFactory->withBaseUrl($baseUrl);
            }

            $result = $clientFactory
                ->make()
                ->generativeModel(model: config('services.gemini.model', 'gemini-1.5-flash'))
                ->generateContent($this->buildParts($path, $context));

            return $result->toArray();
        } catch (\Throwable $exception) {
            Log::error('Gemini request failed', [
                'error' => $exception->getMessage(),
                'base_url' => $baseUrl,
                'model' => config('services.gemini.model'),
                'version' => config('services.gemini.version'),
            ]);

            return $this->fallbackResponse($context);
        }
    }

    protected function resolveBaseUrl(): ?string
    {
        $configuredUrl = trim((string) config('services.gemini.url', ''));

        if ($configuredUrl !== '' && filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
            return rtrim($configuredUrl, '/');
        }

        return $this->buildBaseUrl();
    }

    protected function buildBaseUrl(?string $baseUrl = null): ?string
    {
        $base = $baseUrl ?? (string) config('services.gemini.base_url');
        $version = trim((string) config('services.gemini.version', 'v1beta'), '/');

        if (! filter_var($base, FILTER_VALIDATE_URL) || $version === '') {
            return null;
        }

        return rtrim($base, '/') . '/' . $version;
    }

    protected function buildParts(string $path, array $context): array
    {
        $fileContents = file_get_contents($path);
        $base64File = base64_encode($fileContents);
        $mimeType = $this->resolveMimeType($path);

        $prompt = 'Analise o anexo e devolva um JSON com os campos: summary (string), topics (array de strings), amount (número ou null), currency (string) e detected_type (string).';
        $prompt .= $this->formatContextPrompt($context);

        return [
            $prompt,
            new Blob(
                mimeType: $mimeType,
                data: $base64File,
            ),
        ];
    }

    protected function resolveMimeType(string $path): MimeType
    {
        $detected = mime_content_type($path) ?: '';

        return MimeType::tryFrom($detected) ?? MimeType::IMAGE_JPEG;
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
