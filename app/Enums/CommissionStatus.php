<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CommissionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Accrued = 'accrued';
    case Paid = 'paid';
    case Canceled = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Accrued => __('Accrued'),
            self::Paid => __('Paid'),
            self::Canceled => __('Canceled'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pending => 'info',
            self::Accrued => 'warning',
            self::Paid => 'success',
            self::Canceled => 'gray',
        };
    }
}
