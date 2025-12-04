<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case CurrentAsset = 'current_asset';
    case NonCurrentAsset = 'non_current_asset';
    case ContraAsset = 'contra_asset';
    case CurrentLiability = 'current_liability';
    case NonCurrentLiability = 'non_current_liability';
    case ContraLiability = 'contra_liability';
    case Equity = 'equity';
    case ContraEquity = 'contra_equity';
    case OperatingRevenue = 'operating_revenue';
    case NonOperatingRevenue = 'non_operating_revenue';
    case ContraRevenue = 'contra_revenue';
    case UncategorizedRevenue = 'uncategorized_revenue';
    case OperatingExpense = 'operating_expense';
    case NonOperatingExpense = 'non_operating_expense';
    case ContraExpense = 'contra_expense';
    case UncategorizedExpense = 'uncategorized_expense';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CurrentAsset         => __('Current Asset'),
            self::NonCurrentAsset      => __('Non-Current Asset'),
            self::ContraAsset          => __('Contra Asset'),
            self::CurrentLiability     => __('Current Liability'),
            self::NonCurrentLiability  => __('Non-Current Liability'),
            self::ContraLiability      => __('Contra Liability'),
            self::Equity               => __('Equity'),
            self::ContraEquity         => __('Contra Equity'),
            self::OperatingRevenue     => __('Operating Revenue'),
            self::NonOperatingRevenue  => __('Non-Operating Revenue'),
            self::ContraRevenue        => __('Contra Revenue'),
            self::UncategorizedRevenue => __('Uncategorized Revenue'),
            self::OperatingExpense     => __('Operating Expense'),
            self::NonOperatingExpense  => __('Non-Operating Expense'),
            self::ContraExpense        => __('Contra Expense'),
            self::UncategorizedExpense => __('Uncategorized Expense'),
        };
    }

    public function getPluralLabel(): ?string
    {
        return match ($this) {
            self::CurrentAsset         => __('Current Assets'),
            self::NonCurrentAsset      => __('Non-Current Assets'),
            self::ContraAsset          => __('Contra Assets'),
            self::CurrentLiability     => __('Current Liabilities'),
            self::NonCurrentLiability  => __('Non-Current Liabilities'),
            self::ContraLiability      => __('Contra Liabilities'),
            self::Equity               => __('Equity'),
            self::ContraEquity         => __('Contra Equity'),
            self::OperatingRevenue     => __('Operating Revenue'),
            self::NonOperatingRevenue  => __('Non-Operating Revenue'),
            self::ContraRevenue        => __('Contra Revenue'),
            self::UncategorizedRevenue => __('Uncategorized Revenue'),
            self::OperatingExpense     => __('Operating Expenses'),
            self::NonOperatingExpense  => __('Non-Operating Expenses'),
            self::ContraExpense        => __('Contra Expenses'),
            self::UncategorizedExpense => __('Uncategorized Expenses'),
        };
    }

    public function getCategory(): AccountCategory
    {
        return match ($this) {
            self::CurrentAsset,
            self::NonCurrentAsset,
            self::ContraAsset => AccountCategory::Asset,

            self::CurrentLiability,
            self::NonCurrentLiability,
            self::ContraLiability => AccountCategory::Liability,

            self::Equity,
            self::ContraEquity => AccountCategory::Equity,

            self::OperatingRevenue,
            self::NonOperatingRevenue,
            self::ContraRevenue,
            self::UncategorizedRevenue => AccountCategory::Revenue,

            self::OperatingExpense,
            self::NonOperatingExpense,
            self::ContraExpense,
            self::UncategorizedExpense => AccountCategory::Expense,
        };
    }

    public function isUncategorized(): bool
    {
        return match ($this) {
            self::UncategorizedRevenue,
            self::UncategorizedExpense => true,
            default => false,
        };
    }

    public function isNormalDebitBalance(): bool
    {
        return in_array($this, [
            self::CurrentAsset,
            self::NonCurrentAsset,
            self::ContraLiability,
            self::ContraEquity,
            self::ContraRevenue,
            self::OperatingExpense,
            self::NonOperatingExpense,
            self::UncategorizedExpense,
        ], true);
    }

    public function isNormalCreditBalance(): bool
    {
        return ! $this->isNormalDebitBalance();
    }

    public function isNominal(): bool
    {
        return in_array($this->getCategory(), [
            AccountCategory::Revenue,
            AccountCategory::Expense,
        ], true);
    }

    public function isReal(): bool
    {
        return ! $this->isNominal();
    }

    public function isContra(): bool
    {
        return in_array($this, [
            self::ContraAsset,
            self::ContraLiability,
            self::ContraEquity,
            self::ContraRevenue,
            self::ContraExpense,
        ], true);
    }
}
