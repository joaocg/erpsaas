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
        return $this->name;
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
