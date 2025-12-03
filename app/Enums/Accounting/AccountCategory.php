<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum AccountCategory: string implements HasLabel
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Asset => __('Asset'),
            self::Liability => __('Liability'),
            self::Equity => __('Equity'),
            self::Revenue => __('Revenue'),
            self::Expense => __('Expense'),
        };
    }

    public function getPluralLabel(): ?string
    {
        return match ($this) {
            self::Asset => __('Assets'),
            self::Liability => __('Liabilities'),
            self::Equity => __('Equity'),
            self::Revenue => __('Revenue'),
            self::Expense => __('Expenses'),
        };
    }

    public static function fromPluralLabel(string $label): ?self
    {
        return match ($label) {
            'Assets', __('Assets')               => self::Asset,
            'Liabilities', __('Liabilities')     => self::Liability,
            'Equity', __('Equity')               => self::Equity,
            'Revenue', __('Revenue')             => self::Revenue,
            'Expenses', __('Expenses')           => self::Expense,
            default => null,
        };
    }

    public function isNormalDebitBalance(): bool
    {
        return in_array($this, [self::Asset, self::Expense], true);
    }

    public function isNormalCreditBalance(): bool
    {
        return ! $this->isNormalDebitBalance();
    }

    public function isNominal(): bool
    {
        return in_array($this, [self::Revenue, self::Expense], true);
    }

    public function isReal(): bool
    {
        return ! $this->isNominal();
    }

    public function getRelevantBalanceFields(): array
    {
        $commonFields = ['debit_balance', 'credit_balance', 'net_movement'];

        return $this->isReal()
            ? [...$commonFields, 'starting_balance', 'ending_balance']
            : $commonFields;
    }

    public static function getOrderedCategories(): array
    {
        return [
            self::Asset,
            self::Liability,
            self::Equity,
            self::Revenue,
            self::Expense,
        ];
    }
}
