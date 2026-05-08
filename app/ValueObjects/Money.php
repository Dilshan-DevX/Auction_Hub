<?php

namespace App\ValueObjects;

class Money
{
    public function __construct(
        public readonly int $cents,
        public readonly string $currency = 'USD'
    ) {}


    public function toDecimal(): float
    {
        return $this->cents / 100;
    }
}