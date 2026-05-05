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
        // Convert the DB decimal (e.g., 150.50) into integer cents (15050)
        return new Money((int) round((float) $value * 100));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): float
    {
        if (! $value instanceof Money) {
            throw new InvalidArgumentException('The given value is not a Money instance.');
        }

        // Convert the integer cents back to decimal for the DB
        return $value->toDecimal();
    }
}