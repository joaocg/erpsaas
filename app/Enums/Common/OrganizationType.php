<?php

namespace App\Enums\Common;

use Filament\Support\Contracts\HasLabel;

enum OrganizationType: string implements HasLabel
{
    case Company = 'company';
    case Family = 'family';

    public function getLabel(): ?string
    {
        $label = match ($this) {
            self::Company => 'Company',
            self::Family => 'Family',
        };

        return __($label);
    }
}
