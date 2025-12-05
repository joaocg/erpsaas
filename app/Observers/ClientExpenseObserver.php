<?php

namespace App\Observers;

use App\Models\ClientExpense;

class ClientExpenseObserver
{
    public function created(ClientExpense $clientExpense): void
    {
        $this->applyBalanceChange($clientExpense, -$clientExpense->amount);
    }

    public function deleted(ClientExpense $clientExpense): void
    {
        $this->applyBalanceChange($clientExpense, $clientExpense->amount);
    }

    protected function applyBalanceChange(ClientExpense $expense, float $delta): void
    {
        $client = $expense->client;

        if (! $client) {
            return;
        }

        $client->updateQuietly([
            'balance' => ($client->balance ?? 0) + $delta,
        ]);
    }
}
