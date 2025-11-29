<?php

namespace App\Services;

use Illuminate\Support\Str;

class IntentDetectionService
{
    public function detect(string $message): array
    {
        $normalized = Str::of($message)->lower()->toString();

        if ($this->containsAny($normalized, ['despesa', 'gasto'])) {
            return [
                'intent' => 'create_financial_record',
                'type' => 'expense',
                'amount' => $this->extractAmount($normalized),
            ];
        }

        if ($this->containsAny($normalized, ['receita', 'entrada', 'recebi'])) {
            return [
                'intent' => 'create_financial_record',
                'type' => 'income',
                'amount' => $this->extractAmount($normalized),
            ];
        }

        if ($this->containsAny($normalized, ['consulta', 'médico', 'medico'])) {
            return [
                'intent' => 'create_appointment',
            ];
        }

        if ($this->containsAny($normalized, ['exame', 'laboratório', 'laboratorio'])) {
            return [
                'intent' => 'create_exam',
            ];
        }

        if ($this->containsAny($normalized, ['últimos', 'ultimos', 'recentes'])) {
            return [
                'intent' => 'list_recent',
            ];
        }

        return [
            'intent' => 'unknown',
        ];
    }

    protected function extractAmount(string $text): float
    {
        $matches = [];

        preg_match('/([0-9]+[,\.]?[0-9]*)/', $text, $matches);

        if (empty($matches[1])) {
            return 0.0;
        }

        return (float) str_replace(',', '.', $matches[1]);
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
