<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BillStatus: string implements HasColor, HasLabel
{
    case Overdue = 'overdue';
    case Partial = 'partial';
    case Paid = 'paid';
    case Open = 'open';
    case Void = 'void';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Overdue => __('Overdue'),
            self::Partial => __('Partial'),
            self::Paid => __('Paid'),
            self::Open => __('Open'),
            self::Void => __('Void'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Open => 'info',
            self::Overdue => 'danger',
            self::Partial => 'warning',
            self::Paid => 'success',
            self::Void => 'gray',
        };
    }

    public static function canBeOverdue(): array
    {
        return [
            self::Partial,
            self::Open,
        ];
    }

    public static function unpaidStatuses(): array
    {
        return [
            self::Open,
            self::Partial,
            self::Overdue,
        ];
    }

    public static function getUnpaidOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $case) => in_array($case, self::unpaidStatuses()))
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
