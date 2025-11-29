<?php

namespace App\Services\Gemini;

use Gemini\Client;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use \Gemini;
use Illuminate\Support\Facades\Log;

use GeminiAPI\Client AS GeminiAPIClient;
use GeminiAPI\Enums\MimeType AS GeminiAPIMimeType;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Resources\Parts\TextPart;


class GeminiClient
{
    public function analyze(string $path, array $context = []): array
    {
        $apiKey = config('services.gemini.key');

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

        // Lê o arquivo e codifica em base64
        $fileContents = file_get_contents($path);
        $base64File   = base64_encode($fileContents);



        // Prompt em português instruindo a Gemini a retornar apenas JSON
        $prompt = 'Analise o anexo e devolva APENAS um JSON com os campos: '
            . 'summary (string), topics (array de strings), amount (número ou null), '
            . 'currency (string) e detected_type (string: "expense", "income", "appointment" ou "exam"). '
            . 'Não escreva nenhuma explicação fora do JSON.';

        // Acrescenta contexto quando necessário
        $prompt .= $this->formatContextPrompt($context);


        try {

// Detecta o MIME via PHP e converte para o enum; se falhar, usa JPEG como fallback
            $mimeTypeString = mime_content_type($path) ?: GeminiAPIMimeType::IMAGE_JPEG->value;
            $mimeTypeEnum   = GeminiAPIMimeType::tryFrom($mimeTypeString) ?? GeminiAPIMimeType::IMAGE_JPEG;

// Chama o modelo (ajuste o ModelName conforme o modelo habilitado na sua conta)
            $client   = new GeminiAPIClient($apiKey);
            $response = $client->generativeModel(ModelName::GEMINI_1_5_FLASH)->generateContent(
                new TextPart($prompt),
                new ImagePart(
                    $mimeTypeEnum,
                    $base64File,
                ),
            );

            // Constrói o cliente SDK e chama o modelo
//            $response = $this->buildClient($apiKey)
//                ->generativeModel($this->model())
//                ->generateContent(
//                    $prompt,
//                    new Blob(
//                        mimeType: $this->resolveMimeType($mimeType),
//                        data: $base64File,
//                    )
//                );


            // Decodifica a resposta em um array
            $decoded = $this->decodeResponse($response);

            if ($decoded !== null) {
                return array_merge(
                    $this->fallbackResponse($context),
                    $decoded,
                );
            }

            // Caso o retorno não seja o JSON esperado
            Log::warning('Gemini response did not contain expected JSON text content.', [
                'response' => $response,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Gemini request failed', [
                'error' => $exception->getMessage(),
                'bytes' => strlen($fileContents),
                'mime' => $mimeTypeString
            ]);
        }

        return $this->fallbackResponse($context);
    }

    protected function decodeResponse($response): ?array
    {
        try {
            $text = $response->text();
        } catch (\Throwable $exception) {
            Log::warning('Gemini response could not provide simple text output.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        Log::warning('Gemini returned non-JSON or invalid JSON text', [
            'raw_text' => $text,
        ]);

        return null;
    }

    protected function buildClient(string $apiKey): Client
    {
        $factory = Gemini::factory()
            ->withApiKey($apiKey);

        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $version = ltrim((string) config('services.gemini.version', ''), '/');

        if ($baseUrl && $version) {
            $factory->withBaseUrl("{$baseUrl}/{$version}");
        }

        return $factory->make();
    }

    protected function model(): string
    {
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (! str_starts_with($model, 'models/')) {
            $model = "models/{$model}";
        }

        return $model;
    }

    protected function resolveMimeType(string $mimeType): MimeType
    {
        return MimeType::tryFrom($mimeType)
            ?? match (true) {
                str_contains($mimeType, 'png')   => MimeType::IMAGE_PNG,
                str_contains($mimeType, 'gif')   => MimeType::IMAGE_JPEG,
                str_contains($mimeType, 'webp')  => MimeType::IMAGE_WEBP,
                str_contains($mimeType, 'pdf')   => MimeType::APPLICATION_PDF,
                default                          => MimeType::IMAGE_JPEG,
            };
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
