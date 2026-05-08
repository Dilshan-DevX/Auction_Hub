<?php
namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {

        return new Money((int) round((float) $value * 100));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): float
    {
        if ($value instanceof Money) {

            return $value->toDecimal();
        }


        if (is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException('The given value must be a Money instance or numeric.');
    }
}