<?php

namespace App\Casts;

use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        $currency_code = $attributes['currency_code'] ?? CurrencyAccessor::getDefaultCurrency();

        if ($value !== null) {
            return CurrencyConverter::prepareForMutator($value, $currency_code);
        }

        return '';
    }

    /**
     * @throws UnexpectedValueException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return (int) $value;
    }
}
