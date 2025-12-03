<?php

namespace App\Enums\Common;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum VendorType: string implements HasDescription, HasLabel
{
    use ParsesEnum;

    case Regular = 'regular';
    case Contractor = 'contractor';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Regular => __('Regular'),
            self::Contractor => __('1099-NEC Contractor'),
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Regular => __('Vendors who supply goods or services to your business, such as office supplies, utilities, or equipment.'),
            self::Contractor => __('Independent contractors providing services to your business, typically requiring 1099-NEC reporting for tax purposes.'),
        };
    }
}
