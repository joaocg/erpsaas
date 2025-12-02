<?php

namespace App\Services\Referral;

use App\Models\Referral\ReferralCase;
use App\Models\Referral\Referrer;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class WhatsappReferralService
{
    public function __construct(
        protected GeminiClient $geminiClient,
        protected ReferralCaseService $referralCaseService,
    ) {
    }

    public function handleIncomingReferral(string $text, array $attachments = [], ?Referrer $referrer = null): ?ReferralCase
    {
        $prompt = __('Analise a mensagem e retorne JSON com cliente, contato, indicador, descricao e valor.');
        $payload = $this->geminiClient->promptWithJson($prompt, [
            ['role' => 'user', 'parts' => [$text]],
        ]);

        $parsed = $this->normalizeGeminiPayload($payload);

        if (! $parsed) {
            Log::warning('Gemini não retornou payload de indicação válido.', ['raw' => $payload]);

            return null;
        }

        $data = [
            'company_id' => $referrer?->company_id,
            'referrer_id' => $referrer?->id,
            'description' => $parsed['description'] ?? null,
            'case_value' => $parsed['estimated_value'] ?? 0,
            'status' => 'pending',
        ];

        return $this->referralCaseService->createCase($data);
    }

    protected function normalizeGeminiPayload(?array $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        return [
            'client_name' => Arr::get($payload, 'client.name'),
            'client_contact' => Arr::get($payload, 'client.contact'),
            'referrer_code' => Arr::get($payload, 'referrer.code'),
            'description' => Arr::get($payload, 'case.description'),
            'estimated_value' => Arr::get($payload, 'case.estimated_value'),
        ];
    }
}
