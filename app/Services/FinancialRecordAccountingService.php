<?php

namespace App\Services;

use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\FinancialRecord;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Support\Carbon;

class FinancialRecordAccountingService
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function syncTransaction(FinancialRecord $record): void
    {
        $company = $record->company;
        $defaultBankAccount = $company?->default?->bankAccount;
        $cashAccount = $defaultBankAccount?->account;

        if ($company === null || $defaultBankAccount === null || $cashAccount === null) {
            return;
        }

        $transactionType = $record->type === 'income'
            ? TransactionType::Deposit
            : TransactionType::Withdrawal;

        $chartAccount = $this->resolveChartAccount($record, $transactionType);

        if ($chartAccount === null) {
            return;
        }

        $metadata = is_array($record->metadata) ? $record->metadata : [];

        $payload = [
            'company_id' => $company->id,
            'account_id' => $chartAccount->id,
            'bank_account_id' => $defaultBankAccount->id,
            'type' => $transactionType,
            'amount' => CurrencyConverter::convertToCents($record->amount, $record->currency),
            'payment_channel' => 'other',
            'description' => $record->description ?? __(ucfirst($record->type)),
            'pending' => false,
            'reviewed' => false,
            'posted_at' => Carbon::parse($record->occurred_on)->startOfDay(),
            'created_by' => $record->user_id,
            'updated_by' => $record->user_id,
            'meta' => [
                'financial_record_id' => $record->id,
                'source' => $metadata['source'] ?? null,
            ],
        ];

        if ($record->transaction) {
            $record->transaction->update($payload);

            return;
        }

        $transaction = Transaction::create($payload);

        $record->transaction()->associate($transaction);
        $record->saveQuietly();
    }

    public function deleteTransaction(FinancialRecord $record): void
    {
        $transaction = $record->transaction;

        if (! $transaction) {
            return;
        }

        $record->transaction()->dissociate();
        $record->saveQuietly();

        $transaction->delete();
    }

    protected function resolveChartAccount(FinancialRecord $record, TransactionType $type): ?Account
    {
        $company = $record->company;

        if ($company === null) {
            return null;
        }

        if ($record->category) {
            $matchingAccount = $company->accounts()->where('name', $record->category->name)->first();

            if ($matchingAccount) {
                return $matchingAccount;
            }
        }

        return $this->transactionService->getUncategorizedAccount($company, $type);
    }
}
