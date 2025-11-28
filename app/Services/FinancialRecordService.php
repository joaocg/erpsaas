<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FinancialLedger;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class FinancialRecordService
{
    public function createRecord(User $user, array $payload): FinancialRecord
    {
        $category = $this->resolveCategory($user, $payload['category_id'] ?? null, $payload['type'] ?? null);

        $record = FinancialRecord::create([
            'user_id' => $user->id,
            'category_id' => $category?->id,
            'attachment_id' => $payload['attachment_id'] ?? null,
            'type' => $payload['type'] ?? 'expense',
            'amount' => (float) $payload['amount'],
            'currency' => $payload['currency'] ?? 'BRL',
            'occurred_on' => Carbon::parse($payload['occurred_on'] ?? now()),
            'description' => $payload['description'] ?? null,
            'metadata' => Arr::get($payload, 'metadata', []),
        ]);

        $this->syncLedgers($record);

        return $record->load(['ledgers', 'category', 'attachment']);
    }

    public function syncLedgers(FinancialRecord $record): void
    {
        $record->ledgers()->delete();

        $categoryName = optional($record->category)->name ?? 'Categoria';
        $cashAccount = 'Caixa/Banco';

        if ($record->type === 'income') {
            $this->createLedger($record, 'debit', $cashAccount);
            $this->createLedger($record, 'credit', $categoryName);
        } else {
            $this->createLedger($record, 'debit', $categoryName);
            $this->createLedger($record, 'credit', $cashAccount);
        }
    }

    protected function createLedger(FinancialRecord $record, string $direction, string $account): FinancialLedger
    {
        return $record->ledgers()->create([
            'direction' => $direction,
            'account' => $account,
            'amount' => $record->amount,
        ]);
    }

    protected function resolveCategory(User $user, ?int $categoryId, ?string $type): ?Category
    {
        if ($categoryId) {
            return Category::find($categoryId);
        }

        if ($type) {
            return Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => ucfirst($type), 'type' => $type],
                ['color' => '#2563eb']
            );
        }

        return null;
    }
}
